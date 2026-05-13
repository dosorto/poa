<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecursoDetalleTecnicoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $detallesPorRecurso = [
            'PAPEL BOND T/C' => [
                'Papel Bond tamaño carta base 20',
                'Papel Bond tamaño carta 500 hojas',
            ],
            'PAPEL BOND T/O' => [
                'Papel Bond tamaño oficio base 20',
                'Papel Bond tamaño oficio 500 hojas',
            ],
            'CARTULINA IRIS' => [
                'Cartulina iris color azul',
                'Cartulina iris color amarillo',
                'Cartulina iris color rojo',
                'Cartulina iris color verde',
            ],
            'FOLDER T/C' => [
                'Folder tamaño carta',
                'Folder tamaño carta paquete de 100 unidades',
            ],
            'FOLDER T/O' => [
                'Folder tamaño oficio',
                'Folder tamaño oficio paquete de 100 unidades',
            ],
            'SOBRES MANILA T/C' => [
                'Sobre manila tamaño carta',
                'Sobre manila tamaño carta paquete de 50 unidades',
            ],
            'SOBRES MANILA T/O' => [
                'Sobre manila tamaño oficio',
                'Sobre manila tamaño oficio paquete de 50 unidades',
            ],
            'GUANTES DE LIMPIEZA AMARILLOS UNITALLA' => [
                'Guantes de limpieza amarillos talla M',
                'Guantes de limpieza amarillos talla L',
            ],
            'VASOS TÉRMICOS # 6' => [
                'Vasos térmicos número 6 paquete de 25 unidades',
                'Vasos térmicos número 6 paquete de 50 unidades',
            ],
            'GASOLINA' => [
                'Gasolina regular',
                'Gasolina superior',
            ],
            'DIESEL' => [
                'Diesel automotriz',
                'Diesel para maquinaria',
            ],
        ];

        foreach ($detallesPorRecurso as $recursoNombre => $detalles) {
            $recursoId = DB::table('tareas_historicos')
                ->where('nombre', $recursoNombre)
                ->value('id');

            if (!$recursoId) {
                continue;
            }

            foreach ($detalles as $detalle) {
                DB::table('recurso_detalles_tecnicos')->updateOrInsert(
                    [
                        'id_tareas_historicos' => $recursoId,
                        'nombre' => $detalle,
                    ],
                    [
                        'estado' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
