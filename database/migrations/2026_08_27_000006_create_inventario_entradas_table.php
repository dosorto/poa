<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_entradas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_entrada')->unique();
            $table->string('numero_factura')->nullable();
            $table->string('proveedor')->nullable();
            $table->date('fecha_factura')->nullable();
            $table->string('orden_compra_referencia')->nullable();
            $table->foreignId('requisicion_id')->nullable()->constrained('requisicion')->nullOnDelete();
            $table->foreignId('bodega_id')->constrained('inventario_bodegas')->restrictOnDelete();
            $table->date('fecha_entrada');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
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
        Schema::dropIfExists('inventario_entradas');
    }
};
