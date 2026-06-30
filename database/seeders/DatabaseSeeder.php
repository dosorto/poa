<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesSeeder::class);
        $this->call(PermisoSeeder::class);
        $this->call(UsuarioTablaSeeder::class);
        $this->call(InstitucionSeeder::class);
        $this->call(UnidadEjecutoraSeeder::class);
        $this->call(DepartamentoSeeder::class);
        $this->call(EmpleadosSeeder::class);
        $this->call(CatalogoDumpMergeSeeder::class);
        $this->call(UsuariosSeeder::class);
        $this->call(EmpleadoDeptoSeeder::class);
        $this->call(FuenteSeeder::class);
        $this->call(GrupoGastoSeeder::class);
        $this->call(ObjetogastosSeeder::class);
        $this->call(PeiDataSeeder::class);
        $this->call(PeiSeeder::class);
        $this->call(TrimestresSeeder::class);
        $this->call(MesesSeeder::class);
        $this->call(TipoActividadSeeder::class);
        $this->call(UnidadMedidaSeeder::class);
        $this->call(CategoriasSeeder::class);
        $this->call(RoleHasPermissionsSeeder::class);
        $this->call(PlazosPermissionSeeder::class);
    }
}
