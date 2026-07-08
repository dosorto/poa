<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class CubSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('seeders/data/cubs.csv');

        if (! is_file($csvPath)) {
            throw new RuntimeException("No se encontró el archivo de datos: {$csvPath}");
        }

        Schema::disableForeignKeyConstraints();

        try {
            DB::table('cubs')->truncate();
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            throw new RuntimeException("No se pudo abrir el archivo CSV: {$csvPath}");
        }

        $header = fgetcsv($handle);

        if ($header !== ['IDUNSPSC', 'descripcion_esp', 'descripcion_regional']) {
            fclose($handle);

            throw new RuntimeException('El archivo cubs.csv no tiene el encabezado esperado.');
        }

        $now = now();
        $batch = [];
        $batchSize = 1000;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) {
                continue;
            }

            [$idUnspsc, $descripcionEsp, $descripcionRegional] = $row;

            $idUnspsc = trim((string) $idUnspsc);

            if ($idUnspsc === '') {
                continue;
            }

            $batch[] = [
                'IDUNSPSC' => $idUnspsc,
                'descripcion_esp' => trim((string) $descripcionEsp),
                'descripcion_regional' => trim((string) $descripcionRegional),
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            if (count($batch) >= $batchSize) {
                DB::table('cubs')->insert($batch);
                $batch = [];
            }
        }

        fclose($handle);

        if ($batch !== []) {
            DB::table('cubs')->insert($batch);
        }
    }
}
