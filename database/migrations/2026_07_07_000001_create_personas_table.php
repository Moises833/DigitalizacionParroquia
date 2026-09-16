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
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('cedula')->nullable()->unique(); // Cédula/Documento de identidad (opcional y único si existe)
            $table->date('fecha_nacimiento')->nullable();
            $table->char('genero', 1); // 'M' o 'F'
            $table->timestamps();

            // Índices para optimizar las búsquedas rápidas (muy común buscar por apellidos y nombres separados o juntos)
            $table->index('nombres');
            $table->index('apellidos');
            // Índice compuesto para cuando se busque por nombre completo
            $table->index(['apellidos', 'nombres']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
