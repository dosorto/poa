<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_salidas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_salida')->unique();
            $table->foreignId('bodega_id')->constrained('inventario_bodegas')->restrictOnDelete();
            $table->foreignId('acta_entrega_id')->nullable()->constrained('acta_entrega')->nullOnDelete();
            $table->foreignId('requisicion_id')->nullable()->constrained('requisicion')->nullOnDelete();
            $table->string('tipo_salida');
            $table->text('motivo')->nullable();
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $table->foreignId('empleado_recibe_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->foreignId('responsable_entrega_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha_salida');
            $table->text('observacion')->nullable();
            $table->string('estado')->default('borrador');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_salidas');
    }
};
