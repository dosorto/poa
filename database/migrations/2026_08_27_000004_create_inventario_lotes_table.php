<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('inventario_productos')->cascadeOnDelete();
            $table->string('codigo_lote');
            $table->date('fecha_ingreso');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('ubicacion')->nullable();
            $table->string('estado')->default('disponible');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['producto_id', 'codigo_lote'], 'inventario_lotes_producto_codigo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_lotes');
    }
};
