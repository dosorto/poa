<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cubs') || ! Schema::hasColumn('cubs', 'idUE')) {
            return;
        }

        Schema::table('cubs', function (Blueprint $table) {
            $table->dropForeign(['idUE']);
            $table->dropColumn('idUE');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('cubs') || Schema::hasColumn('cubs', 'idUE')) {
            return;
        }

        Schema::table('cubs', function (Blueprint $table) {
            $table->foreignId('idUE')
                ->nullable()
                ->after('descripcion_regional')
                ->constrained('unidad_ejecutora')
                ->nullOnDelete();
        });
    }
};
