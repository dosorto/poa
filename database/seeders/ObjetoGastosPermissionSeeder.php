<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ObjetoGastosPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'configuracion.objetogastos.ver',
            'configuracion.objetogastos.crear',
            'configuracion.objetogastos.editar',
            'configuracion.objetogastos.eliminar',
        ];

        foreach ($permissions as $permissionName) {
            $permission = DB::table('permissions')
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();

            if (! $permission) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $permission = (object) ['id' => $permissionId];
            }

            foreach (['super_admin', 'admin'] as $roleName) {
                $role = DB::table('roles')->where('name', $roleName)->first();

                if ($role && $permission) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $permission->id,
                        'role_id' => $role->id,
                    ]);
                }
            }
        }
    }
}
