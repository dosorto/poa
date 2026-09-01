<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_entrada_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrada_id')->constrained('inventario_entradas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('inventario_productos')->restrictOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('inventario_lotes')->nullOnDelete();
            $table->string('codigo_lote')->nullable();
            $table->decimal('cantidad', 14, 2);
            $table->decimal('costo_unitario', 14, 2)->nullable();
            $table->decimal('total', 14, 2)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_entrada_detalles');
    }
};
