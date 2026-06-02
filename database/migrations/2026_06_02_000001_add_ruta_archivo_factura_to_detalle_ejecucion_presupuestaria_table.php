<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_ejecucion_presupuestaria', function (Blueprint $table) {
            if (!Schema::hasColumn('detalle_ejecucion_presupuestaria', 'ruta_archivo_factura')) {
                $table->string('ruta_archivo_factura')->nullable()->after('referenciaActaEntrega');
            }
        });
    }

    public function down(): void
    {
        Schema::table('detalle_ejecucion_presupuestaria', function (Blueprint $table) {
            if (Schema::hasColumn('detalle_ejecucion_presupuestaria', 'ruta_archivo_factura')) {
                $table->dropColumn('ruta_archivo_factura');
            }
        });
    }
};
