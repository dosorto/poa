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
        $this->prepararRespaldoRequisicion($producto);

        $salida = InventarioSalida::create([
            'numero_salida' => 'SAL-1',
            'bodega_id' => $bodega->id,
            'tipo_salida' => 'entrega',
            'requisicion_id' => 1,
            'acta_entrega_id' => 1,
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
            'detalle_acta_entrega_id' => 1,
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
        $this->prepararRespaldoRequisicion($producto);

        $salida = InventarioSalida::create([
            'numero_salida' => 'SAL-2',
            'bodega_id' => $bodega->id,
            'tipo_salida' => 'entrega',
            'requisicion_id' => 1,
            'acta_entrega_id' => 1,
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
            'detalle_acta_entrega_id' => 1,
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

    public function test_no_permite_confirmar_dos_veces_la_misma_salida(): void
    {
        $this->test_salida_confirmada_descuenta_existencia();
        $salida = InventarioSalida::firstOrFail();
        try {
            app(InventarioService::class)->confirmarSalida($salida);
            $this->fail('Se permitió confirmar dos veces.');
        } catch (ValidationException $e) {
            $this->assertDatabaseHas('inventario_existencias', ['cantidad_disponible' => 6]);
            $this->assertSame(1, DB::table('inventario_kardex')->where('tipo_movimiento', 'salida')->count());
        }
    }

    public function test_formulario_rechaza_guardar_una_salida_confirmada(): void
    {
        $this->test_salida_confirmada_descuenta_existencia();
        $form = new \App\Livewire\Inventario\SalidaForm;
        $form->salidaId = InventarioSalida::firstOrFail()->id;
        $form->save(app(InventarioService::class));

        $this->assertTrue($form->showAdvertenciaModal);
        $this->assertDatabaseHas('inventario_existencias', ['cantidad_disponible' => 6]);
        $this->assertDatabaseHas('inventario_salidas', ['id' => $form->salidaId, 'estado' => 'confirmado']);
    }

    public function test_asigna_varios_lotes_por_vencimiento_sin_reutilizar_existencias(): void
    {
        [$user, $bodega, $producto] = $this->baseInventario(['maneja_vencimiento' => true]);
        $service = app(InventarioService::class);
        foreach (['TARDE' => 20, 'PRONTO' => 5] as $codigo => $dias) {
            $service->registrarSaldoInicial([
                'bodega_id' => $bodega->id, 'producto_id' => $producto->id,
                'codigo_lote' => $codigo, 'cantidad' => 5,
                'fecha_vencimiento' => now()->addDays($dias)->toDateString(), 'usuario_id' => $user->id,
            ]);
        }
        $form = new \App\Livewire\Inventario\SalidaForm;
        $form->bodega_id = $bodega->id;
        $form->detalles = [
            ['producto_id' => $producto->id, 'lote_id' => null, 'cantidad' => 7],
            ['producto_id' => $producto->id, 'lote_id' => null, 'cantidad' => 4],
        ];
        (new \ReflectionMethod($form, 'asignarLotesDisponibles'))->invoke($form);

        $this->assertCount(4, $form->detalles);
        $this->assertEquals(5, $form->detalles[0]['cantidad']);
        $this->assertSame($service->sugerirLotes($bodega->id, $producto->id)[0]['lote_id'], $form->detalles[0]['lote_id']);
        $this->assertNull($form->detalles[3]['lote_id']);
        $this->assertEquals(1, $form->detalles[3]['cantidad']);
    }

    public function test_limita_entregas_acumuladas_entre_actas_y_revierte_si_excede(): void
    {
        [$user, $bodega, $producto] = $this->baseInventario();
        $this->registrarSaldo($bodega->id, $producto->id, $user->id, 20);
        $this->crearEsquemaActas();
        DB::table('requisicion')->insert(['id' => 1]);
        DB::table('detalle_requisicion')->insert(['id' => 1, 'idRequisicion' => 1, 'idRecurso' => 1, 'cantidad' => 10]);
        DB::table('detalle_ejecucion_presupuestaria')->insert(['id' => 1, 'idDetalleRequisicion' => 1, 'cant_ejecutada' => 10]);
        DB::table('tipo_acta_entrega')->insert(['id' => 1, 'tipo' => 'Intermedia']);
        DB::table('tareas_historicos')->insert(['id' => 1]);
        DB::table('inventario_producto_recurso')->insert(['producto_id' => $producto->id, 'recurso_id' => 1]);
        $service = app(InventarioService::class);
        foreach ([1 => 6, 2 => 5] as $id => $cantidad) {
            DB::table('acta_entrega')->insert(['id' => $id, 'idTipoActaEntrega' => 1, 'idRequisicion' => 1]);
            DB::table('detalle_acta_entrega')->insert([
                'id' => $id, 'idActaEntrega' => $id, 'idDetalleRequisicion' => 1,
                'idDetalleEjecucionPresupuestaria' => 1, 'log_cant_ejecutada' => 10,
            ]);
            $salida = InventarioSalida::create([
                'numero_salida' => 'ACT-' . $id, 'bodega_id' => $bodega->id, 'tipo_salida' => 'entrega',
                'usuario_id' => $user->id, 'fecha_salida' => now(), 'estado' => 'borrador',
                'acta_entrega_id' => $id, 'requisicion_id' => 1,
            ]);
            InventarioSalidaDetalle::create([
                'salida_id' => $salida->id, 'detalle_acta_entrega_id' => $id,
                'producto_id' => $producto->id, 'lote_id' => InventarioExistencia::first()->lote_id, 'cantidad' => $cantidad,
            ]);
            if ($id === 1) {
                $service->confirmarSalida($salida);
            } else {
                try {
                    $service->confirmarSalida($salida);
                    $this->fail('Se permitió exceder el acumulado.');
                } catch (ValidationException $e) {
                    $this->assertSame('borrador', $salida->refresh()->estado);
                    $this->assertDatabaseHas('inventario_existencias', ['cantidad_disponible' => 14]);
                }
            }
        }
        try {
            $service->validarDespachoCompleto(1);
            $this->fail('Se permitió cerrar con pendientes.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('entrega', $e->errors());
        }
        $salida->detalles()->update(['cantidad' => 4]);
        $service->confirmarSalida($salida);
        $service->validarDespachoCompleto(1);
        $this->assertDatabaseHas('inventario_existencias', ['cantidad_disponible' => 10]);
    }

    public function test_no_omite_productos_sin_lote_para_permitir_el_cierre(): void
    {
        $form = new \App\Livewire\Inventario\SalidaForm;
        $form->detalles = [['producto_id' => 1, 'lote_id' => null, 'cantidad' => 5, 'recurso' => 'Papel']];
        $original = $form->detalles;

        $resultado = (new \ReflectionMethod($form, 'prepararDetallesParaValidacion'))->invoke($form);

        $this->assertFalse($resultado);
        $this->assertSame($original, $form->detalles);
        $this->assertTrue($form->showAdvertenciaModal);
    }

    public function test_rechaza_salidas_sin_requisicion_sin_afectar_existencias(): void
    {
        [$user, $bodega, $producto] = $this->baseInventario();
        $this->registrarSaldo($bodega->id, $producto->id, $user->id, 10);
        $salida = InventarioSalida::create([
            'numero_salida' => 'MANUAL', 'bodega_id' => $bodega->id, 'usuario_id' => $user->id,
            'tipo_salida' => 'manual', 'estado' => 'borrador', 'fecha_salida' => now(),
        ]);
        InventarioSalidaDetalle::create([
            'salida_id' => $salida->id, 'producto_id' => $producto->id,
            'lote_id' => InventarioExistencia::first()->lote_id, 'cantidad' => 3,
        ]);
        try {
            app(InventarioService::class)->confirmarSalida($salida);
            $this->fail('Se permitió una salida sin requisición.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('salida', $e->errors());
            $this->assertDatabaseHas('inventario_existencias', ['cantidad_disponible' => 10]);
            $this->assertSame('borrador', $salida->refresh()->estado);
        }
    }

    public function test_no_existen_rutas_para_crear_o_editar_salidas_independientes(): void
    {
        $rutas = app('router')->getRoutes();
        $this->assertNull($rutas->getByName('inventario.salidas.create'));
        $this->assertNull($rutas->getByName('inventario.salidas.edit'));
        $this->assertNotNull($rutas->getByName('inventario.salidas.create.acta'));
        $this->assertNotNull($rutas->getByName('inventario.salidas.create.requisicion.intermedia'));
    }

    public function test_historial_no_expone_acciones_que_modifiquen_inventario(): void
    {
        $historial = new \App\Livewire\Inventario\Salidas;
        foreach (['confirmar', 'anular', 'abrirConfirmacion'] as $accion) {
            $this->assertFalse(method_exists($historial, $accion));
        }
    }

    public function test_historial_filtra_fechas_inclusivas_y_rechaza_rangos_invertidos(): void
    {
        [$user, $bodega] = $this->baseInventarioSinProducto();
        $this->crearEsquemaActas();
        foreach (['2026-10-01', '2026-10-15', '2026-10-20', '2026-10-21'] as $indice => $fecha) {
            InventarioSalida::create([
                'numero_salida' => 'FECHA-' . $indice, 'bodega_id' => $bodega->id,
                'usuario_id' => $user->id, 'tipo_salida' => 'entrega',
                'fecha_salida' => $fecha, 'estado' => 'confirmado',
            ]);
        }
        $historial = new \App\Livewire\Inventario\Salidas;
        $historial->fechaDesde = '2026-10-15';
        $historial->fechaHasta = '2026-10-20';
        $datos = $historial->render()->getData();
        $this->assertSame(2, $datos['salidas']->total());
        $this->assertSame('', $datos['errorFechas']);

        $historial->fechaDesde = '';
        $this->assertSame(3, $historial->render()->getData()['salidas']->total());

        $historial->fechaDesde = '2026-10-21';
        $datos = $historial->render()->getData();
        $this->assertSame(0, $datos['salidas']->total());
        $this->assertSame('La fecha «Hasta» debe ser igual o posterior a «Desde».', $datos['errorFechas']);

        $historial->limpiarFechas();
        $this->assertSame(4, $historial->render()->getData()['salidas']->total());
    }

    public function test_selector_muestra_opciones_de_bodega_y_conserva_binding(): void
    {
        $html = \Illuminate\Support\Facades\Blade::render(
            '<x-select wire:model.live="bodega_id"><option value="7">Bodega destino</option></x-select>',
        );
        $this->assertStringContainsString('<option value="7">Bodega destino</option>', $html);
        $this->assertStringContainsString('wire:model.live="bodega_id"', $html);
    }

    public function test_formulario_entrada_no_duplica_existencias_confirmadas(): void
    {
        [$user, $bodega, $producto] = $this->baseInventario();
        $entrada = $this->registrarSaldo($bodega->id, $producto->id, $user->id, 7);
        \Illuminate\Support\Facades\Gate::partialMock()->shouldReceive('authorize')
            ->with('inventario.entradas.crear', [])->andReturn(\Illuminate\Auth\Access\Response::allow());
        $form = new \App\Livewire\Inventario\EntradaForm;
        $form->mount($entrada);
        $this->assertTrue($form->soloLectura);
        $form->soloLectura = false;
        $form->save(app(InventarioService::class));

        $this->assertTrue($form->showAdvertenciaModal);
        $this->assertSame('confirmado', $entrada->refresh()->estado);
        $this->assertDatabaseHas('inventario_existencias', ['cantidad_disponible' => 7]);
        $this->assertSame(1, DB::table('inventario_kardex')->count());
    }

    public function test_entrada_con_costo_cero_conserva_el_valor_y_no_se_duplica_al_reintentar(): void
    {
        [$user, $bodega, $producto] = $this->baseInventario();
        \Illuminate\Support\Facades\Gate::partialMock()->shouldReceive('authorize')
            ->with('inventario.entradas.crear', [])->andReturn(\Illuminate\Auth\Access\Response::allow());
        $form = new \App\Livewire\Inventario\EntradaForm;
        $form->mount();
        $form->numero_factura = 'FAC-CERO';
        $form->proveedor = 'Proveedor prueba';
        $form->fecha_factura = now()->toDateString();
        $form->detalles = [[
            'producto_id' => $producto->id, 'codigo_lote' => 'CERO', 'cantidad' => 3,
            'costo_unitario' => 0, 'fecha_vencimiento' => null,
        ]];
        $form->save(app(InventarioService::class));
        $this->assertFalse($form->showAdvertenciaModal, $form->advertenciaMensaje);
        $this->assertDatabaseHas('inventario_entrada_detalles', ['costo_unitario' => 0, 'total' => 0]);
        $this->assertTrue($form->soloLectura);
        $form->save(app(InventarioService::class));
        $this->assertSame(1, InventarioEntrada::count());
        $this->assertDatabaseHas('inventario_existencias', ['cantidad_disponible' => 3]);
    }

    public function test_modal_entrada_muestra_validaciones_en_espanol(): void
    {
        $form = new \App\Livewire\Inventario\EntradaForm;
        $form->openProductoModal();
        $form->nuevoDetalle['cantidad'] = 0;
        try {
            $form->agregarProducto();
            $this->fail('Se aceptó un producto incompleto.');
        } catch (ValidationException $e) {
            $this->assertSame('Seleccione un producto.', $e->errors()['nuevoDetalle.producto_id'][0]);
            $this->assertSame('El campo cantidad debe ser mayor o igual a 0.01.', $e->errors()['nuevoDetalle.cantidad'][0]);
        }
    }

    public function test_anular_entrada_consumida_muestra_error_sin_cambiar_estado(): void
    {
        [$user, $bodega, $producto] = $this->baseInventario();
        $entrada = $this->registrarSaldo($bodega->id, $producto->id, $user->id, 7);
        InventarioExistencia::query()->update(['cantidad_disponible' => 2]);
        \Illuminate\Support\Facades\Gate::partialMock()->shouldReceive('authorize')
            ->with('inventario.ajustes.crear', [])->andReturn(\Illuminate\Auth\Access\Response::allow());
        $lista = new \App\Livewire\Inventario\Entradas;
        $lista->anular($entrada->id, app(InventarioService::class));

        $this->assertStringContainsString('ya fue consumida', session('error'));
        $this->assertSame('confirmado', $entrada->refresh()->estado);
        $this->assertDatabaseHas('inventario_existencias', ['cantidad_disponible' => 2]);
        $this->assertSame(1, DB::table('inventario_kardex')->count());
    }

    public function test_no_permite_anular_entradas_sin_permiso(): void
    {
        [$user, $bodega, $producto] = $this->baseInventario();
        $entrada = $this->registrarSaldo($bodega->id, $producto->id, $user->id, 7);
        \Illuminate\Support\Facades\Gate::partialMock()->shouldReceive('authorize')
            ->with('inventario.ajustes.crear', [])->andThrow(new \Illuminate\Auth\Access\AuthorizationException);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        (new \App\Livewire\Inventario\Entradas)->anular($entrada->id, app(InventarioService::class));
    }

    public function test_entrada_rechaza_bodega_destino_inactiva(): void
    {
        [$user, $bodega] = $this->baseInventarioSinProducto();
        $bodega->update(['activo' => false]);
        $form = new \App\Livewire\Inventario\EntradaForm;
        $form->mount();
        $form->bodega_id = $bodega->id;
        $form->numero_factura = 'FAC-1';
        $form->proveedor = 'Proveedor';
        $form->fecha_factura = now()->toDateString();
        $form->siguientePaso();

        $this->assertSame(1, $form->paso);
        $this->assertSame('Seleccione una bodega destino activa.', $form->advertenciaMensaje);
    }

    public function test_no_permite_confirmar_entradas_sin_permiso(): void
    {
        \Illuminate\Support\Facades\Gate::partialMock()->shouldReceive('authorize')
            ->with('inventario.entradas.confirmar', [])->andThrow(new \Illuminate\Auth\Access\AuthorizationException);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        (new \App\Livewire\Inventario\Entradas)->confirmar(app(InventarioService::class));
    }

    public function test_acta_recepcion_adapta_vista_oscura_y_conserva_impresion_clara(): void
    {
        $entrada = new InventarioEntrada([
            'numero_entrada' => 'ENT-PREVIEW', 'fecha_entrada' => now(), 'proveedor' => 'Proveedor',
        ]);
        $entrada->setRelation('requisicion', null);
        $entrada->setRelation('usuario', null);
        $entrada->setRelation('bodega', new InventarioBodega(['nombre' => 'Bodega principal']));
        $datos = ['entrada' => $entrada, 'detalles' => collect(), 'total' => 0, 'marcaAgua' => ''];
        $oscuro = view('pdf.inventario-acta-recepcion', [...$datos, 'pdfTheme' => 'dark'])->render();
        $claro = view('pdf.inventario-acta-recepcion', $datos)->render();

        $this->assertStringContainsString('background-color: #050505', $oscuro);
        $this->assertStringContainsString('color: #f5f5f5', $oscuro);
        $this->assertStringContainsString('background-color: #ffffff', $claro);
        $this->assertStringContainsString('<div class="page-background"></div>', $oscuro);
        $this->assertStringContainsString('@page { margin: 0;', $oscuro);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($oscuro)->setPaper('letter')->output();
        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function test_empleado_recibe_incluye_todos_sin_restringir_por_departamento(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('num_empleado')->nullable();
            $table->softDeletes();
        });
        DB::table('empleados')->insert([
            ['id' => 1, 'nombre' => 'Ana', 'apellido' => 'Zelaya', 'num_empleado' => '100', 'deleted_at' => null],
            ['id' => 2, 'nombre' => 'Carlos', 'apellido' => 'Pérez', 'num_empleado' => '200', 'deleted_at' => null],
            ['id' => 3, 'nombre' => 'Eliminado', 'apellido' => 'Prueba', 'num_empleado' => '300', 'deleted_at' => now()],
        ]);
        $form = new \App\Livewire\Inventario\SalidaForm;
        $form->departamento_id = 123;
        $form->empleado_recibe_id = 2;
        $empleados = (new \ReflectionMethod($form, 'empleadosDisponibles'))->invoke($form);

        $this->assertSame([1, 2], array_column($empleados, 'id'));
        $this->assertSame('Carlos Pérez - 200', $empleados[1]['text']);
    }

    public function test_entrega_asigna_lote_antes_de_generar_y_recalcula_al_cambiar_bodega(): void
    {
        [$user, $bodega, $producto] = $this->baseInventario();
        $entrada = $this->registrarSaldo($bodega->id, $producto->id, $user->id, 8);
        $otraBodega = InventarioBodega::create(['nombre' => 'Otra bodega', 'activo' => true]);
        $form = new \App\Livewire\Inventario\SalidaForm;
        $form->soloLectura = false;
        $form->tipoActaPendiente = 'intermedia';
        $form->bodega_id = $bodega->id;
        $form->detalles = [['producto_id' => $producto->id, 'lote_id' => null, 'cantidad' => 4]];
        $form->siguientePaso();
        $this->assertSame($entrada->detalles->first()->lote_id, $form->detalles[0]['lote_id']);
        $this->assertDatabaseHas('inventario_existencias', ['cantidad_disponible' => 8]);

        $form->bodega_id = $otraBodega->id;
        $form->updatedBodegaId();
        $this->assertNull($form->detalles[0]['lote_id']);
        $form->bodega_id = $bodega->id;
        $form->updatedBodegaId();
        $this->assertSame($entrada->detalles->first()->lote_id, $form->detalles[0]['lote_id']);
    }

    public function test_generar_entrega_pide_confirmacion_sin_descontar_inventario(): void
    {
        [$user, $bodega, $producto] = $this->baseInventario();
        $entrada = $this->registrarSaldo($bodega->id, $producto->id, $user->id, 8);
        \Illuminate\Support\Facades\Gate::partialMock()->shouldReceive('authorize')
            ->with('inventario.salidas.crear', [])->andReturn(\Illuminate\Auth\Access\Response::allow());
        $form = new \App\Livewire\Inventario\SalidaForm;
        $form->soloLectura = false;
        $form->bodega_id = $bodega->id;
        $form->detalles = [[
            'producto_id' => $producto->id, 'lote_id' => $entrada->detalles->first()->lote_id, 'cantidad' => 4,
        ]];

        $this->assertNull($form->save(app(InventarioService::class)));
        $this->assertTrue($form->showGuardarModal);
        $this->assertSame(0, InventarioSalida::count());
        $this->assertDatabaseHas('inventario_existencias', ['cantidad_disponible' => 8]);
        $this->assertSame(1, DB::table('inventario_kardex')->count());

        $form->cerrarConfirmacionGuardar();
        $this->assertFalse($form->showGuardarModal);
        $this->assertSame(0, InventarioSalida::count());
    }

    private function prepararRespaldoRequisicion(InventarioProducto $producto): void
    {
        $this->crearEsquemaActas();
        DB::table('requisicion')->insert(['id' => 1]);
        DB::table('detalle_requisicion')->insert(['id' => 1, 'idRequisicion' => 1, 'idRecurso' => 1, 'cantidad' => 10]);
        DB::table('detalle_ejecucion_presupuestaria')->insert(['id' => 1, 'idDetalleRequisicion' => 1, 'cant_ejecutada' => 10]);
        DB::table('tipo_acta_entrega')->insert(['id' => 1, 'tipo' => 'Intermedia']);
        DB::table('tareas_historicos')->insert(['id' => 1]);
        DB::table('inventario_producto_recurso')->insert(['producto_id' => $producto->id, 'recurso_id' => 1]);
        DB::table('acta_entrega')->insert(['id' => 1, 'idTipoActaEntrega' => 1, 'idRequisicion' => 1]);
        DB::table('detalle_acta_entrega')->insert([
            'id' => 1, 'idActaEntrega' => 1, 'idDetalleRequisicion' => 1,
            'idDetalleEjecucionPresupuestaria' => 1, 'log_cant_ejecutada' => 10,
        ]);
    }

    private function crearEsquemaActas(): void
    {
        foreach (['requisicion', 'detalle_requisicion', 'detalle_ejecucion_presupuestaria', 'acta_entrega', 'detalle_acta_entrega', 'tareas_historicos', 'tipo_acta_entrega'] as $nombre) {
            Schema::create($nombre, function (Blueprint $table) use ($nombre) {
                $table->id();
                $table->timestamps();
                $table->softDeletes();
                if ($nombre === 'detalle_requisicion') {
                    $table->unsignedBigInteger('idRequisicion');
                    $table->unsignedBigInteger('idRecurso');
                    $table->decimal('cantidad', 14, 2);
                } elseif ($nombre === 'detalle_ejecucion_presupuestaria') {
                    $table->unsignedBigInteger('idDetalleRequisicion');
                    $table->decimal('cant_ejecutada', 14, 2);
                } elseif ($nombre === 'acta_entrega') {
                    $table->unsignedBigInteger('idTipoActaEntrega');
                    $table->unsignedBigInteger('idRequisicion');
                } elseif ($nombre === 'detalle_acta_entrega') {
                    $table->unsignedBigInteger('idActaEntrega');
                    $table->unsignedBigInteger('idDetalleRequisicion');
                    $table->unsignedBigInteger('idDetalleEjecucionPresupuestaria');
                    $table->decimal('log_cant_ejecutada', 14, 2);
                } elseif ($nombre === 'tipo_acta_entrega') {
                    $table->string('tipo');
                }
            });
        }
        Schema::create('inventario_producto_recurso', function (Blueprint $table) {
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('recurso_id');
            $table->timestamps();
        });
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
            'empleados',
            'detalle_acta_entrega',
            'acta_entrega',
            'tipo_acta_entrega',
            'detalle_ejecucion_presupuestaria',
            'detalle_requisicion',
            'requisicion',
            'tareas_historicos',
            'inventario_producto_recurso',
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
            $table->unsignedBigInteger('detalle_acta_entrega_id')->nullable();
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
