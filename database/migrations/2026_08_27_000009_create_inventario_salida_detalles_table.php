<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_salida_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salida_id')->constrained('inventario_salidas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('inventario_productos')->restrictOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('inventario_lotes')->nullOnDelete();
            $table->decimal('cantidad', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_salida_detalles');
    }
};
