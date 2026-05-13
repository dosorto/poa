<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('recurso_detalles_tecnicos')) {
            Schema::create('recurso_detalles_tecnicos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_tareas_historicos')->constrained('tareas_historicos');
                $table->string('nombre');
                $table->boolean('estado')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurso_detalles_tecnicos');
    }
};
