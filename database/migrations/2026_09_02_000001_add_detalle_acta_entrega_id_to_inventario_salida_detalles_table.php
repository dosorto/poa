<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_salida_detalles', function (Blueprint $table) {
            $table->foreignId('detalle_acta_entrega_id')
                ->nullable()
                ->after('salida_id')
                ->constrained('detalle_acta_entrega')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventario_salida_detalles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('detalle_acta_entrega_id');
        });
    }
};
