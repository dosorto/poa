<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_kardex', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bodega_id')->constrained('inventario_bodegas')->restrictOnDelete();
            $table->foreignId('producto_id')->constrained('inventario_productos')->restrictOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('inventario_lotes')->nullOnDelete();
            $table->string('tipo_movimiento');
            $table->decimal('cantidad_entrada', 14, 2)->default(0);
            $table->decimal('cantidad_salida', 14, 2)->default(0);
            $table->decimal('saldo_anterior', 14, 2);
            $table->decimal('saldo_nuevo', 14, 2);
            $table->string('documento_tipo')->nullable();
            $table->unsignedBigInteger('documento_id')->nullable();
            $table->string('referencia')->nullable();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('fecha_movimiento');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['documento_tipo', 'documento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_kardex');
    }
};
