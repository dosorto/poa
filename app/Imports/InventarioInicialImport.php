<?php

namespace App\Imports;

use App\Models\Inventario\InventarioImportacion;
use App\Models\Inventario\InventarioProducto;
use App\Services\Inventario\InventarioService;
use Illuminate\Support\Facades\Validator;

class InventarioInicialImport
{
    public array $errores = [];
    public int $importados = 0;
    public int $total = 0;
    private int $filaActual = 1;

    public function __construct(
        private readonly int $bodegaId,
        private readonly int $usuarioId,
        private readonly int $importacionId,
    ) {
    }

    public function model(array $row): mixed
    {
        $service = app(InventarioService::class);
        $this->total++;
        $this->filaActual++;
        $fila = $this->filaActual;
        $data = [
            'codigo_interno' => trim((string) ($row['codigo_interno'] ?? '')),
            'codigo_barra' => trim((string) ($row['codigo_barra'] ?? '')),
            'nombre' => trim((string) ($row['nombre'] ?? '')),
            'descripcion' => trim((string) ($row['descripcion'] ?? '')),
            'unidad_medida_id' => $row['unidad_medida_id'] ?? null,
            'idobjeto' => trim((string) ($row['idobjeto'] ?? '')),
            'idCubs' => trim((string) ($row['id_cubs'] ?? $row['idcubs'] ?? $row['idCubs'] ?? '')),
            'codigo_lote' => trim((string) ($row['codigo_lote'] ?? 'SIN-LOTE')),
            'cantidad' => $row['cantidad'] ?? null,
            'fecha_vencimiento' => $row['fecha_vencimiento'] ?? null,
            'ubicacion' => trim((string) ($row['ubicacion'] ?? '')),
            'stock_minimo' => $row['stock_minimo'] ?? null,
        ];

        $validator = Validator::make($data, [
            'codigo_interno' => 'required|string|max:255',
            'codigo_barra' => 'nullable|string|max:255',
            'nombre' => 'required|string|max:255',
            'unidad_medida_id' => 'required|exists:unidadmedidas,id',
            'idobjeto' => 'nullable|exists:objetogastos,identificador',
            'idCubs' => 'nullable|exists:cubs,IDUNSPSC',
            'cantidad' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            $this->errores[] = 'Fila ' . $fila . ': ' . $validator->errors()->first();
            $this->actualizarImportacion();
            return null;
        }

        $producto = InventarioProducto::updateOrCreate(
            ['codigo_interno' => $data['codigo_interno']],
            [
                'codigo_barra' => $data['codigo_barra'] ?: null,
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?: null,
                'unidad_medida_id' => (int) $data['unidad_medida_id'],
                'idobjeto' => $data['idobjeto'] ?: null,
                'idCubs' => $data['idCubs'] ?: null,
                'stock_minimo' => $data['stock_minimo'] ?: null,
                'maneja_lote' => $data['codigo_lote'] !== 'SIN-LOTE',
                'maneja_vencimiento' => ! empty($data['fecha_vencimiento']),
                'activo' => true,
                'created_by' => $this->usuarioId,
            ]
        );

        $service->registrarSaldoInicial([
            'bodega_id' => $this->bodegaId,
            'producto_id' => $producto->id,
            'codigo_lote' => $data['codigo_lote'],
            'cantidad' => (float) $data['cantidad'],
            'fecha_ingreso' => now()->toDateString(),
            'fecha_vencimiento' => $data['fecha_vencimiento'] ?: null,
            'ubicacion' => $data['ubicacion'] ?: null,
            'usuario_id' => $this->usuarioId,
            'documento_id' => $this->importacionId,
            'referencia' => 'Importacion inicial',
        ]);

        $this->importados++;
        $this->actualizarImportacion();

        return null;
    }

    private function actualizarImportacion(): void
    {
        InventarioImportacion::whereKey($this->importacionId)->update([
            'total_filas' => $this->total,
            'filas_importadas' => $this->importados,
            'errores' => $this->errores,
            'estado' => empty($this->errores) ? 'confirmado' : 'confirmado_con_errores',
        ]);
    }
}
