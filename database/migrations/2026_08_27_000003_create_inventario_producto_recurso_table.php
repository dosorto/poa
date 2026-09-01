<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_producto_recurso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('inventario_productos')->cascadeOnDelete();
            $table->foreignId('recurso_id')->constrained('tareas_historicos')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['producto_id', 'recurso_id'], 'inventario_producto_recurso_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_producto_recurso');
    }
};
