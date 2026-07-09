<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recurso_detalles_tecnicos') || ! Schema::hasColumn('recurso_detalles_tecnicos', 'nombre')) {
            return;
        }

        DB::statement('ALTER TABLE recurso_detalles_tecnicos MODIFY nombre TEXT NOT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('recurso_detalles_tecnicos') || ! Schema::hasColumn('recurso_detalles_tecnicos', 'nombre')) {
            return;
        }

        DB::statement('ALTER TABLE recurso_detalles_tecnicos MODIFY nombre VARCHAR(255) NOT NULL');
    }
};
