<?php

namespace Database\Seeders;

use App\Models\Inventario\InventarioProducto;
use App\Models\Requisicion\UnidadMedida;
use App\Models\Tareas\TareaHistorico;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RecursosAProductosInventarioSeeder extends Seeder
{
    private array $excluirPorNombre = [
        'ALQUILER',
        'AUTENTICA',
        'CAPACITACION',
        'LICENCIA',
        'MANTENIMIENTO',
        'MANO DE OBRA',
        'REPARACION',
        'SERVICIO',
        'SERVICIOS',
        'VIATICO',
        'VIATICOS',
    ];

    public function run(): void
    {
        $unidadesValidas = UnidadMedida::query()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $creados = 0;
        $actualizados = 0;
        $omitidos = 0;

        TareaHistorico::query()
            ->orderBy('id')
            ->chunkById(200, function ($recursos) use ($unidadesValidas, &$creados, &$actualizados, &$omitidos) {
                foreach ($recursos as $recurso) {
                    if ($this->debeOmitirse($recurso) || ! $unidadesValidas->has((int) $recurso->idunidad)) {
                        $omitidos++;
                        continue;
                    }

                    $producto = InventarioProducto::query()
                        ->where('recurso_id', $recurso->id)
                        ->first();

                    if (! $producto) {
                        $producto = InventarioProducto::query()
                            ->where('codigo_interno', $this->codigoInterno($recurso))
                            ->first();
                    }

                    $producto ??= new InventarioProducto();
                    $esNuevo = ! $producto->exists;

                    $producto->forceFill([
                        'recurso_id' => $producto->recurso_id ?: $recurso->id,
                        'idCubs' => $producto->idCubs ?: $recurso->idCubs,
                        'idobjeto' => $producto->idobjeto ?: $recurso->idobjeto,
                        'unidad_medida_id' => $producto->unidad_medida_id ?: (int) $recurso->idunidad,
                        'codigo_interno' => $producto->codigo_interno ?: $this->codigoInterno($recurso),
                        'codigo_barra' => $producto->codigo_barra ?: null,
                        'nombre' => $producto->nombre ?: $recurso->nombre,
                        'descripcion' => $producto->descripcion ?: 'Producto generado desde recurso POA.',
                        'stock_minimo' => $producto->stock_minimo,
                        'maneja_lote' => $producto->maneja_lote ?? false,
                        'maneja_vencimiento' => $producto->maneja_vencimiento ?? false,
                        'activo' => $producto->exists ? $producto->activo : true,
                    ]);

                    $producto->save();
                    $producto->recursos()->syncWithoutDetaching([$recurso->id]);

                    $esNuevo ? $creados++ : $actualizados++;
                }
            });

        $this->command?->info("Productos de inventario creados: {$creados}");
        $this->command?->info("Productos de inventario actualizados/vinculados: {$actualizados}");
        $this->command?->info("Recursos omitidos: {$omitidos}");
    }

    private function debeOmitirse(TareaHistorico $recurso): bool
    {
        $nombre = Str::upper(Str::ascii((string) $recurso->nombre));

        foreach ($this->excluirPorNombre as $patron) {
            if (str_contains($nombre, $patron)) {
                return true;
            }
        }

        return trim((string) $recurso->nombre) === '';
    }

    private function codigoInterno(TareaHistorico $recurso): string
    {
        $nombre = Str::upper(Str::ascii((string) $recurso->nombre));
        $slug = Str::of($nombre)
            ->replaceMatches('/[^A-Z0-9]+/', '-')
            ->trim('-')
            ->limit(24, '')
            ->toString();

        return 'REC-' . str_pad((string) $recurso->id, 6, '0', STR_PAD_LEFT) . '-' . ($slug ?: 'PRODUCTO');
    }
}
