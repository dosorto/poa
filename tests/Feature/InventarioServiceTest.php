<?php

namespace Tests\Feature;

use App\Models\Inventario\InventarioBodega;
use App\Models\Inventario\InventarioEntrada;
use App\Models\Inventario\InventarioEntradaDetalle;
use App\Models\Inventario\InventarioExistencia;
use App\Models\Inventario\InventarioProducto;
use App\Models\Inventario\InventarioSalida;
use App\Models\Inventario\InventarioSalidaDetalle;
use App\Models\User;
use App\Services\Inventario\InventarioService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventarioServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEsquemaMinimo();
    }

    public function test_entrada_confirmada_aumenta_existencia_y_crea_kardex(): void
    {
        [$user, $bodega, $producto] = $this->baseInventario();

        $entrada = InventarioEntrada::create([
            'numero_entrada' => 'ENT-1',
            'bodega_id' => $bodega->id,
            'fecha_entrada' => now()->toDateString(),
            'usuario_id' => $user->id,
            'estado' => 'borrador',
        ]);

        InventarioEntradaDetalle::create([
            'entrada_id' => $entrada->id,
            'producto_id' => $producto->id,
            'codigo_lote' => 'L-1',
            'cantidad' => 10,
        ]);

        app(InventarioService::class)->confirmarEntrada($entrada);

        $this->assertDatabaseHas('inventario_existencias', [
            'bodega_id' => $bodega->id,
            'producto_id' => $producto->id,
            'cantidad_disponible' => 10,
        ]);
        $this->assertDatabaseHas('inventario_kardex', [
            'tipo_movimiento' => 'entrada',
            'cantidad_entrada' => 10,
            'saldo_nuevo' => 10,
        ]);
    }

    public function test_salida_confirmada_descuenta_existencia(): void
    {
        [$user, $bodega, $producto] = $this->baseInventario();
        $this->registrarSaldo($bodega->id, $producto->id, $user->id, 10);

        $salida = InventarioSalida::create([
            'numero_salida' => 'SAL-1',
            'bodega_id' => $bodega->id,
            'tipo_salida' => 'manual',
            'motivo' => 'Entrega interna',
            'departamento_id' => 1,
            'responsable_entrega_id' => $user->id,
            'usuario_id' => $user->id,
            'fecha_salida' => now()->toDateString(),
            'observacion' => 'Salida de prueba',
            'estado' => 'borrador',
        ]);

        InventarioSalidaDetalle::create([
            'salida_id' => $salida->id,
            'producto_id' => $producto->id,
            'lote_id' => InventarioExistencia::first()->lote_id,
            'cantidad' => 4,
        ]);

        app(InventarioService::class)->confirmarSalida($salida);

        $this->assertDatabaseHas('inventario_existencias', [
            'producto_id' => $producto->id,
            'cantidad_disponible' => 6,
        ]);
        $this->assertDatabaseHas('inventario_kardex', [
            'tipo_movimiento' => 'salida',
            'cantidad_salida' => 4,
            'saldo_nuevo' => 6,
        ]);
    }

    public function test_impide_stock_negativo(): void
    {
        [$user, $bodega, $producto] = $this->baseInventario();
        $this->registrarSaldo($bodega->id, $producto->id, $user->id, 2);

        $salida = InventarioSalida::create([
            'numero_salida' => 'SAL-2',
            'bodega_id' => $bodega->id,
            'tipo_salida' => 'manual',
            'motivo' => 'Entrega interna',
            'departamento_id' => 1,
            'responsable_entrega_id' => $user->id,
            'usuario_id' => $user->id,
            'fecha_salida' => now()->toDateString(),
            'observacion' => 'Salida de prueba',
            'estado' => 'borrador',
        ]);

        InventarioSalidaDetalle::create([
            'salida_id' => $salida->id,
            'producto_id' => $producto->id,
            'lote_id' => InventarioExistencia::first()->lote_id,
            'cantidad' => 5,
        ]);

        $this->expectException(ValidationException::class);
        app(InventarioService::class)->confirmarSalida($salida);
    }

    public function test_sugerencia_fefo_ordena_por_vencimiento(): void
    {
        [$user, $bodega, $producto] = $this->baseInventario(['maneja_vencimiento' => true, 'maneja_lote' => true]);
        $service = app(InventarioService::class);

        $service->registrarSaldoInicial([
            'bodega_id' => $bodega->id,
            'producto_id' => $producto->id,
            'codigo_lote' => 'L-TARDE',
            'cantidad' => 5,
            'fecha_vencimiento' => now()->addMonths(2)->toDateString(),
            'usuario_id' => $user->id,
        ]);
        $service->registrarSaldoInicial([
            'bodega_id' => $bodega->id,
            'producto_id' => $producto->id,
            'codigo_lote' => 'L-PRONTO',
            'cantidad' => 5,
            'fecha_vencimiento' => now()->addDays(5)->toDateString(),
            'usuario_id' => $user->id,
        ]);

        $sugeridos = $service->sugerirLotes($bodega->id, $producto->id);

        $this->assertSame('L-PRONTO', $sugeridos[0]['codigo_lote']);
    }

    public function test_anulacion_genera_movimiento_reverso(): void
    {
        [$user, $bodega, $producto] = $this->baseInventario();
        $entrada = $this->registrarSaldo($bodega->id, $producto->id, $user->id, 7);

        app(InventarioService::class)->anularEntrada($entrada);

        $this->assertDatabaseHas('inventario_kardex', [
            'tipo_movimiento' => 'ajuste_negativo',
            'cantidad_salida' => 7,
            'saldo_nuevo' => 0,
        ]);
    }

    private function baseInventario(array $productoData = []): array
    {
        [$user, $bodega] = $this->baseInventarioSinProducto();

        $producto = InventarioProducto::create(array_merge([
            'unidad_medida_id' => 1,
            'codigo_interno' => 'PROD-' . uniqid(),
            'nombre' => 'Producto prueba',
            'maneja_lote' => true,
            'maneja_vencimiento' => false,
            'activo' => true,
        ], $productoData));

        return [$user, $bodega, $producto];
    }

    private function baseInventarioSinProducto(): array
    {
        $user = User::create([
            'name' => 'Usuario prueba',
            'email' => uniqid() . '@example.com',
            'password' => 'password',
        ]);
        $this->actingAs($user);

        DB::table('unidadmedidas')->insert([
            'id' => 1,
            'nombre' => 'Unidad',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bodega = InventarioBodega::create([
            'nombre' => 'Bodega principal',
            'activo' => true,
        ]);

        return [$user, $bodega];
    }

    private function registrarSaldo(int $bodegaId, int $productoId, int $userId, float $cantidad): InventarioEntrada
    {
        $entrada = InventarioEntrada::create([
            'numero_entrada' => 'ENT-' . uniqid(),
            'bodega_id' => $bodegaId,
            'fecha_entrada' => now()->toDateString(),
            'usuario_id' => $userId,
            'estado' => 'borrador',
        ]);

        InventarioEntradaDetalle::create([
            'entrada_id' => $entrada->id,
            'producto_id' => $productoId,
            'codigo_lote' => 'L-' . uniqid(),
            'cantidad' => $cantidad,
        ]);

        return app(InventarioService::class)->confirmarEntrada($entrada);
    }

    private function crearEsquemaMinimo(): void
    {
        foreach ([
            'inventario_kardex',
            'inventario_salida_detalles',
            'inventario_salidas',
            'inventario_entrada_detalles',
            'inventario_entradas',
            'inventario_existencias',
            'inventario_lotes',
            'inventario_productos',
            'inventario_bodegas',
            'unidadmedidas',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('unidadmedidas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        Schema::create('inventario_bodegas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventario_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurso_id')->nullable();
            $table->string('idCubs', 50)->nullable();
            $table->string('idobjeto', 50)->nullable();
            $table->foreignId('unidad_medida_id');
            $table->string('codigo_interno')->unique();
            $table->string('codigo_barra')->nullable()->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('marca')->nullable();
            $table->string('presentacion')->nullable();
            $table->decimal('stock_minimo', 12, 2)->nullable();
            $table->boolean('maneja_lote')->default(false);
            $table->boolean('maneja_vencimiento')->default(false);
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventario_lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id');
            $table->string('codigo_lote');
            $table->date('fecha_ingreso');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('ubicacion')->nullable();
            $table->string('estado')->default('disponible');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('inventario_existencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bodega_id');
            $table->foreignId('producto_id');
            $table->foreignId('lote_id')->nullable();
            $table->decimal('cantidad_disponible', 14, 2)->default(0);
            $table->decimal('cantidad_reservada', 14, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('inventario_entradas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_entrada')->unique();
            $table->foreignId('bodega_id');
            $table->date('fecha_entrada');
            $table->foreignId('usuario_id');
            $table->text('observacion')->nullable();
            $table->string('estado')->default('borrador');
            $table->string('numero_factura')->nullable();
            $table->string('proveedor')->nullable();
            $table->date('fecha_factura')->nullable();
            $table->string('orden_compra_referencia')->nullable();
            $table->unsignedBigInteger('requisicion_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventario_entrada_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrada_id');
            $table->foreignId('producto_id');
            $table->foreignId('lote_id')->nullable();
            $table->string('codigo_lote')->nullable();
            $table->decimal('cantidad', 14, 2);
            $table->decimal('costo_unitario', 14, 2)->nullable();
            $table->decimal('total', 14, 2)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->timestamps();
        });

        Schema::create('inventario_salidas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_salida')->unique();
            $table->foreignId('bodega_id');
            $table->string('tipo_salida');
            $table->text('motivo')->nullable();
            $table->unsignedBigInteger('departamento_id')->nullable();
            $table->unsignedBigInteger('empleado_recibe_id')->nullable();
            $table->foreignId('responsable_entrega_id')->nullable();
            $table->foreignId('usuario_id');
            $table->date('fecha_salida');
            $table->text('observacion')->nullable();
            $table->string('estado')->default('borrador');
            $table->unsignedBigInteger('acta_entrega_id')->nullable();
            $table->unsignedBigInteger('requisicion_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventario_salida_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salida_id');
            $table->foreignId('producto_id');
            $table->foreignId('lote_id')->nullable();
            $table->decimal('cantidad', 14, 2);
            $table->timestamps();
        });

        Schema::create('inventario_kardex', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bodega_id');
            $table->foreignId('producto_id');
            $table->foreignId('lote_id')->nullable();
            $table->string('tipo_movimiento');
            $table->decimal('cantidad_entrada', 14, 2)->default(0);
            $table->decimal('cantidad_salida', 14, 2)->default(0);
            $table->decimal('saldo_anterior', 14, 2);
            $table->decimal('saldo_nuevo', 14, 2);
            $table->string('documento_tipo')->nullable();
            $table->unsignedBigInteger('documento_id')->nullable();
            $table->string('referencia')->nullable();
            $table->foreignId('usuario_id');
            $table->dateTime('fecha_movimiento');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        Schema::create('inventario_importaciones', function (Blueprint $table) {
            $table->id();
            $table->string('archivo');
            $table->foreignId('usuario_id');
            $table->dateTime('fecha');
            $table->string('estado')->default('borrador');
            $table->unsignedInteger('total_filas')->default(0);
            $table->unsignedInteger('filas_importadas')->default(0);
            $table->json('errores')->nullable();
            $table->timestamps();
        });
    }
}
