<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcesoCompraSeeder extends Seeder
{
    public function run(): void
    {
        $idUE = DB::table('unidad_ejecutora')->orderBy('id')->value('id');

        if (! $idUE) {
            return;
        }

        DB::table('procesos_compras')->updateOrInsert(
            ['id' => 1],
            [
                'nombre_proceso' => 'Proceso de compra general',
                'monto_total' => 0,
                'idTipoProcesoCompra' => null,
                'idUE' => $idUE,
                'idPoa' => null,
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]
        );
    }
}
