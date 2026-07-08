<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class ConsolidadoPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'planificacion.consolidado.ver',
            'planificacion.consolidado.generar',
            'consola.consolidado.ver',
            'consola.consolidado.generar',
        ];

        foreach ($permissions as $permissionName) {
            $permission = DB::table('permissions')
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();

            if ($permission) {
                $this->command?->info("Permiso \"{$permissionName}\" ya existe");
                continue;
            }

            DB::table('permissions')->insert([
                'name' => $permissionName,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command?->info("Permiso \"{$permissionName}\" creado");
        }

        foreach (['super_admin', 'admin_general'] as $roleName) {
            $role = DB::table('roles')->where('name', $roleName)->first();

            if (! $role) {
                continue;
            }

            $permissionIds = DB::table('permissions')
                ->whereIn('name', ['consola.consolidado.ver', 'consola.consolidado.generar'])
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('permission_id', $permissionId)
                    ->where('role_id', $role->id)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $role->id,
                    ]);
                }
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
