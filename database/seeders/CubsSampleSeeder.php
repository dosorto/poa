<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CubsSampleSeeder extends Seeder
{
    public function run(): void
    {
        $cubs = [
            [
                'IDUNSPSC' => '26111532',
                'descripcion_esp' => 'Soportes o conjuntos del eje',
                'descripcion_regional' => 'Soportes o conjuntos del eje',
            ],
            [
                'IDUNSPSC' => '26111601',
                'descripcion_esp' => 'Generadores diesel',
                'descripcion_regional' => 'Generadores diesel',
            ],
            [
                'IDUNSPSC' => '60101205',
                'descripcion_esp' => 'Tarjetas perforadas de incentivo',
                'descripcion_regional' => 'Tarjetas perforadas de incentivo para la educacion',
            ],
        ];

        foreach ($cubs as $cub) {
            DB::table('cubs')->updateOrInsert(
                ['IDUNSPSC' => $cub['IDUNSPSC']],
                array_merge($cub, [
                    'created_by' => null,
                    'updated_by' => null,
                    'deleted_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ])
            );
        }
    }
}
