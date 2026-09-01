<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_existencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bodega_id')->constrained('inventario_bodegas')->restrictOnDelete();
            $table->foreignId('producto_id')->constrained('inventario_productos')->restrictOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('inventario_lotes')->restrictOnDelete();
            $table->decimal('cantidad_disponible', 14, 2)->default(0);
            $table->decimal('cantidad_reservada', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['bodega_id', 'producto_id', 'lote_id'], 'inventario_existencias_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_existencias');
    }
};
