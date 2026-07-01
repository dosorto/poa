<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuariosFaltantesSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Usuarios del dump que no estaban en el UsuariosSeeder original.
        // Todos se crean con la clave 123456789.
        $usuarios = [
            [
                'id' => 120,
                'email' => 'carmen.rivas@unah.edu.hn',
                'name' => 'carmen.rivas',
                'idEmpleado' => 146,
                'idRol' => 3,
                'created_at' => '2025-06-06 08:43:14',
                'updated_at' => '2025-09-23 12:24:35',
            ],
            [
                'id' => 121,
                'email' => 'krisnareyes@unah.edu.hn',
                'name' => 'krisnareyes',
                'idEmpleado' => 147,
                'idRol' => 3,
                'created_at' => '2025-06-13 08:00:18',
                'updated_at' => '2025-12-18 10:22:30',
            ],
            [
                'id' => 122,
                'email' => 'laura.banegas@unah.edu.hn',
                'name' => 'laura.banegas',
                'idEmpleado' => 13,
                'idRol' => 3,
                'created_at' => '2025-06-20 14:49:32',
                'updated_at' => '2025-06-20 14:58:19',
            ],
            [
                'id' => 123,
                'email' => 'dsorianoa@unah.edu.hn',
                'name' => 'dsorianoa',
                'idEmpleado' => 150,
                'idRol' => 3,
                'created_at' => '2025-06-24 11:00:48',
                'updated_at' => '2025-06-24 11:19:35',
            ],
            [
                'id' => 124,
                'email' => 'erick.ardon@unah.edu.hn',
                'name' => 'erick.ardon',
                'idEmpleado' => 129,
                'idRol' => 3,
                'created_at' => '2025-12-05 14:09:06',
                'updated_at' => '2026-02-23 11:55:03',
            ],
            [
                'id' => 125,
                'email' => 'eiden.baca@unah.edu.hn',
                'name' => 'eiden.baca',
                'idEmpleado' => 149,
                'idRol' => 3,
                'created_at' => '2025-12-18 10:20:08',
                'updated_at' => '2025-12-18 10:20:08',
            ],
            [
                'id' => 126,
                'email' => 'josefa.rodriguez@unah.edu.hn',
                'name' => 'josefa.rodriguez',
                'idEmpleado' => 148,
                'idRol' => 3,
                'created_at' => '2025-12-18 10:22:17',
                'updated_at' => '2025-12-18 10:55:31',
            ],
            [
                'id' => 127,
                'email' => 'oscar.bustillo@unah.edu.hn',
                'name' => 'oscar.bustillo',
                'idEmpleado' => 121,
                'idRol' => 3,
                'created_at' => '2026-02-05 11:44:12',
                'updated_at' => '2026-02-05 11:44:12',
            ],
        ];

        $password = Hash::make('123456789');

        foreach ($usuarios as $usuario) {
            DB::table('users')->updateOrInsert(
                ['id' => $usuario['id']],
                [
                    'name' => $usuario['name'],
                    'email' => $usuario['email'],
                    'email_verified_at' => null,
                    'password' => $password,
                    'remember_token' => null,
                    'idEmpleado' => $usuario['idEmpleado'],
                    'current_team_id' => null,
                    'profile_photo_path' => null,
                    'created_at' => $usuario['created_at'],
                    'updated_at' => $usuario['updated_at'],
                ]
            );
        }

        DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->whereIn('model_id', array_column($usuarios, 'id'))
            ->delete();

        foreach ($usuarios as $usuario) {
            DB::table('model_has_roles')->insert([
                'role_id' => $usuario['idRol'],
                'model_type' => 'App\\Models\\User',
                'model_id' => $usuario['id'],
            ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
