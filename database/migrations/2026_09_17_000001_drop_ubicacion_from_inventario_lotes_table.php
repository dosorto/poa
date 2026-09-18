<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventario_lotes') && Schema::hasColumn('inventario_lotes', 'ubicacion')) {
            Schema::table('inventario_lotes', function (Blueprint $table) {
                $table->dropColumn('ubicacion');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inventario_lotes') && ! Schema::hasColumn('inventario_lotes', 'ubicacion')) {
            Schema::table('inventario_lotes', function (Blueprint $table) {
                $table->string('ubicacion')->nullable()->after('fecha_vencimiento');
            });
        }
    }
};
