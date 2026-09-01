<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurso_id')->nullable()->constrained('tareas_historicos')->nullOnDelete();
            $table->string('idCubs', 50)->nullable()->index();
            $table->string('idobjeto', 50)->nullable()->index();
            $table->foreignId('unidad_medida_id')->constrained('unidadmedidas')->restrictOnDelete();
            $table->string('codigo_interno')->unique();
            $table->string('codigo_barra')->nullable()->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('marca')->nullable();
            $table->string('presentacion')->nullable();
            $table->decimal('stock_minimo', 12, 2)->nullable();
            $table->boolean('maneja_lote')->default(false);
            $table->boolean('maneja_vencimiento')->default(false);
            $table->boolean('activo')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_productos');
    }
};
