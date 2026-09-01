<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_importaciones', function (Blueprint $table) {
            $table->id();
            $table->string('archivo');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('fecha');
            $table->string('estado')->default('borrador');
            $table->unsignedInteger('total_filas')->default(0);
            $table->unsignedInteger('filas_importadas')->default(0);
            $table->json('errores')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_importaciones');
    }
};
