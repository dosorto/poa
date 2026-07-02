<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class RecursoSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('seeders/data/recursos.csv');

        if (! is_file($csvPath)) {
            throw new RuntimeException("No se encontró el archivo de datos: {$csvPath}");
        }

        $this->ensureDefaultProcesoCompraExists();

        $unidadIds = DB::table('unidadmedidas')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $procesoCompraIds = DB::table('procesos_compras')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $cubsCodes = DB::table('cubs')->pluck('IDUNSPSC')->map(fn ($codigo) => (string) $codigo)->all();
        $objetoCodes = DB::table('objetogastos')->pluck('identificador')->map(fn ($codigo) => (string) $codigo)->all();

        $unidadLookup = array_fill_keys($unidadIds, true);
        $procesoLookup = array_fill_keys($procesoCompraIds, true);
        $cubsLookup = array_fill_keys($cubsCodes, true);
        $objetoLookup = array_fill_keys($objetoCodes, true);

        Schema::disableForeignKeyConstraints();

        try {
            DB::table('tareas_historicos')->truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            throw new RuntimeException("No se pudo abrir el archivo CSV: {$csvPath}");
        }

        $header = fgetcsv($handle);

        if ($header !== ['nombre', 'idobjeto', 'idunidad', 'idProcesoCompra', 'idCubs']) {
            fclose($handle);

            throw new RuntimeException('El archivo recursos.csv no tiene el encabezado esperado.');
        }

        $now = now();
        $batch = [];
        $batchSize = 500;
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) < 5) {
                continue;
            }

            [$nombre, $objetoCodigo, $unidadId, $procesoCompraId, $cubCodigo] = $row;

            $nombre = trim((string) $nombre);
            $objetoCodigo = trim((string) $objetoCodigo);
            $cubCodigo = trim((string) $cubCodigo);
            $unidadId = (int) trim((string) $unidadId);
            $procesoCompraId = (int) trim((string) $procesoCompraId);

            if ($nombre === '') {
                continue;
            }

            if (! isset($objetoLookup[$objetoCodigo])) {
                fclose($handle);

                throw new RuntimeException("No existe el objeto de gasto {$objetoCodigo} en la fila {$rowNumber}.");
            }

            if (! isset($unidadLookup[$unidadId])) {
                fclose($handle);

                throw new RuntimeException("No existe la unidad de medida {$unidadId} en la fila {$rowNumber}.");
            }

            if (! isset($procesoLookup[$procesoCompraId])) {
                fclose($handle);

                throw new RuntimeException("No existe el proceso de compra {$procesoCompraId} en la fila {$rowNumber}.");
            }

            if ($cubCodigo !== '' && ! isset($cubsLookup[$cubCodigo])) {
                fclose($handle);

                throw new RuntimeException("No existe el CUBS {$cubCodigo} en la fila {$rowNumber}.");
            }

            $batch[] = [
                'nombre' => $nombre,
                'idobjeto' => $objetoCodigo,
                'idunidad' => $unidadId,
                'idProcesoCompra' => $procesoCompraId,
                'idCubs' => $cubCodigo !== '' ? $cubCodigo : null,
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            if (count($batch) >= $batchSize) {
                DB::table('tareas_historicos')->insert($batch);
                $batch = [];
            }
        }

        fclose($handle);

        if ($batch !== []) {
            DB::table('tareas_historicos')->insert($batch);
        }
    }

    private function ensureDefaultProcesoCompraExists(): void
    {
        $exists = DB::table('procesos_compras')->where('id', 1)->exists();

        if ($exists) {
            return;
        }

        DB::table('procesos_compras')->insert([
            'id' => 1,
            'nombre_proceso' => 'Sin Asignar',
            'monto_total' => 0,
            'idTipoProcesoCompra' => null,
            'idUE' => 1,
            'idPoa' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ]);
    }
}
