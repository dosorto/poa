<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidad_ejecutora', function (Blueprint $table) {
            $table->foreignId('idAsistenteEstrategico')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('idAdministrador')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('idEncargadoCompra')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('idDirectorDecano')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('unidad_ejecutora', function (Blueprint $table) {
            $table->dropConstrainedForeignId('idAsistenteEstrategico');
            $table->dropConstrainedForeignId('idAdministrador');
            $table->dropConstrainedForeignId('idEncargadoCompra');
            $table->dropConstrainedForeignId('idDirectorDecano');
        });
    }
};

