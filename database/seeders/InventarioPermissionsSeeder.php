<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InventarioPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = collect([
            'acceso-inventario',
            'inventario.ver',
            'inventario.bodegas.ver',
            'inventario.bodegas.crear',
            'inventario.bodegas.editar',
            'inventario.productos.ver',
            'inventario.productos.crear',
            'inventario.productos.editar',
            'inventario.existencias.ver',
            'inventario.entradas.ver',
            'inventario.entradas.crear',
            'inventario.entradas.confirmar',
            'inventario.salidas.ver',
            'inventario.salidas.crear',
            'inventario.salidas.confirmar',
            'inventario.ajustes.crear',
            'inventario.kardex.ver',
        ])->map(fn ($permission) => Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        Role::whereIn('name', ['super_admin', 'admin', 'direccion'])
            ->get()
            ->each(fn ($role) => $role->givePermissionTo($permissions));

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
