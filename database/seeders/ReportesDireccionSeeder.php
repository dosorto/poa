<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ReportesDireccionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = collect([
            'acceso-reportes',
            'reportes.direccion.ver',
            'reportes.direccion.exportar',
        ])->map(fn ($permission) => Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]));

        Role::whereIn('name', ['Dirección', 'direccion', 'super_admin'])
            ->get()
            ->each(fn ($role) => $role->givePermissionTo($permissions));

        $director = User::where('email', 'celeo.arias@unah.edu.hn')->first();

        if ($director) {
            $director->givePermissionTo($permissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
