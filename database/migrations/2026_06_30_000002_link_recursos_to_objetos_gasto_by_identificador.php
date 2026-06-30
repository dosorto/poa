<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('objetogastos')) {
            return;
        }

        if (! $this->indexExists('objetogastos', 'objetogastos_identificador_index')) {
            DB::statement('ALTER TABLE objetogastos ADD INDEX objetogastos_identificador_index (identificador)');
        }

        if (Schema::hasTable('tareas_historicos')) {
            DB::statement('ALTER TABLE tareas_historicos MODIFY idobjeto VARCHAR(50) NOT NULL');

            if (! $this->indexExists('tareas_historicos', 'tareas_historicos_idobjeto_index')) {
                DB::statement('ALTER TABLE tareas_historicos ADD INDEX tareas_historicos_idobjeto_index (idobjeto)');
            }

            DB::statement("
                UPDATE tareas_historicos recursos
                INNER JOIN objetogastos objetos ON recursos.idobjeto = CAST(objetos.id AS CHAR)
                SET recursos.idobjeto = objetos.identificador
            ");
        }

        if (Schema::hasTable('presupuestos')) {
            DB::statement('ALTER TABLE presupuestos MODIFY idobjeto VARCHAR(50) NOT NULL');

            if (! $this->indexExists('presupuestos', 'presupuestos_idobjeto_index')) {
                DB::statement('ALTER TABLE presupuestos ADD INDEX presupuestos_idobjeto_index (idobjeto)');
            }

            DB::statement("
                UPDATE presupuestos
                INNER JOIN objetogastos objetos ON presupuestos.idobjeto = CAST(objetos.id AS CHAR)
                SET presupuestos.idobjeto = objetos.identificador
            ");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('objetogastos')) {
            return;
        }

        if (Schema::hasTable('presupuestos')) {
            DB::statement("
                UPDATE presupuestos
                INNER JOIN objetogastos objetos ON presupuestos.idobjeto = objetos.identificador
                SET presupuestos.idobjeto = CAST(objetos.id AS CHAR)
            ");

            if ($this->indexExists('presupuestos', 'presupuestos_idobjeto_index')) {
                DB::statement('ALTER TABLE presupuestos DROP INDEX presupuestos_idobjeto_index');
            }

            DB::statement('ALTER TABLE presupuestos MODIFY idobjeto INT NOT NULL');
        }

        if (Schema::hasTable('tareas_historicos')) {
            DB::statement("
                UPDATE tareas_historicos recursos
                INNER JOIN objetogastos objetos ON recursos.idobjeto = objetos.identificador
                SET recursos.idobjeto = CAST(objetos.id AS CHAR)
            ");

            if ($this->indexExists('tareas_historicos', 'tareas_historicos_idobjeto_index')) {
                DB::statement('ALTER TABLE tareas_historicos DROP INDEX tareas_historicos_idobjeto_index');
            }

            DB::statement('ALTER TABLE tareas_historicos MODIFY idobjeto BIGINT UNSIGNED NOT NULL');
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
