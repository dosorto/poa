<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cubs') || ! Schema::hasTable('tareas_historicos')) {
            return;
        }

        if (! $this->indexExists('cubs', 'cubs_idunspsc_index')) {
            DB::statement('ALTER TABLE cubs ADD INDEX cubs_idunspsc_index (IDUNSPSC)');
        }

        DB::statement('ALTER TABLE tareas_historicos MODIFY idCubs VARCHAR(50) NULL');

        if (! $this->indexExists('tareas_historicos', 'tareas_historicos_idcubs_index')) {
            DB::statement('ALTER TABLE tareas_historicos ADD INDEX tareas_historicos_idcubs_index (idCubs)');
        }

        DB::statement("
            UPDATE tareas_historicos recursos
            INNER JOIN cubs ON recursos.idCubs = CAST(cubs.id AS CHAR)
            SET recursos.idCubs = cubs.IDUNSPSC
        ");
    }

    public function down(): void
    {
        if (! Schema::hasTable('cubs') || ! Schema::hasTable('tareas_historicos')) {
            return;
        }

        DB::statement("
            UPDATE tareas_historicos recursos
            INNER JOIN cubs ON recursos.idCubs = cubs.IDUNSPSC
            SET recursos.idCubs = CAST(cubs.id AS CHAR)
        ");

        if ($this->indexExists('tareas_historicos', 'tareas_historicos_idcubs_index')) {
            DB::statement('ALTER TABLE tareas_historicos DROP INDEX tareas_historicos_idcubs_index');
        }

        DB::statement('ALTER TABLE tareas_historicos MODIFY idCubs BIGINT UNSIGNED NULL');
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
