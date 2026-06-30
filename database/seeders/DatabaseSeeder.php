<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\PermisoSeeder;
use Database\Seeders\UsuarioTablaSeeder;
use Database\Seeders\InstitucionSeeder;
use Database\Seeders\UnidadEjecutoraSeeder;
use Database\Seeders\FuenteSeeder;
use Database\Seeders\GrupoGastoSeeder;
use Database\Seeders\ObjetogastosSeeder;
use Database\Seeders\CubSeeder;
use App\Models\Area;
use Database\Seeders\TechoDeptoSeeder;
use Database\Seeders\TechoUesSeeder;
use Database\Seeders\DepartamentoSeeder;
use Database\Seeders\PoasSeeder;
use Database\Seeders\PoaDeptoSeeder;
use Database\Seeders\EmpleadosSeeder;
use Database\Seeders\EmpleadoDeptoSeeder;
use Database\Seeders\ActividadesSeeder;
use Database\Seeders\IndicadoresSeeder;
use Database\Seeders\PlanificacionsSeeder;
use Database\Seeders\EmpleadoActividadsSeeder;
use Database\Seeders\ProcesoCompraSeeder;
use Database\Seeders\RecursoSeeder;
use Database\Seeders\TareasSeeder;
use Database\Seeders\PresupuestosSeeder;
use Database\Seeders\RevisionesSeeder;
use Database\Seeders\EstadoRequisicionLogsSeeder;
use Database\Seeders\RequisicionSeeder;
use Database\Seeders\DetalleRequisicionSeeder;
use Database\Seeders\EjecucionPresupuestariaSeeder;
use Database\Seeders\EjecucionPresupuestariaLogsSeeder;
use Database\Seeders\DetalleEjecucionPresupuestariaSeeder;
use Database\Seeders\OrdenCombustibleSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(RolesSeeder::class);
        $this->call(PermisoSeeder::class);
        $this->call(UsuarioTablaSeeder::class);
        $this->call(UsuariosSeeder::class);
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
