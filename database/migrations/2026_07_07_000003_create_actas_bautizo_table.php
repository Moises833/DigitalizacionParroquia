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
        Schema::create('actas_bautizo', function (Blueprint $table) {
            $table->id();
            
            // Relación con el libro físico
            $table->foreignId('libro_id')
                  ->constrained('libros')
                  ->onDelete('restrict'); // Evitamos borrar libros con actas asignadas

            // Relaciones con personas (bautizado, padres y padrinos)
            $table->foreignId('bautizado_id')
                  ->constrained('personas')
                  ->onDelete('restrict'); // Restringido para mantener la integridad de los datos
                  
            $table->foreignId('padre_id')
                  ->nullable()
                  ->constrained('personas')
                  ->onDelete('set null'); // Si se elimina el registro de la persona, queda null en el acta
                  
            $table->foreignId('madre_id')
                  ->nullable()
                  ->constrained('personas')
                  ->onDelete('set null');

            $table->foreignId('padrino_1_id')
                  ->nullable()
                  ->constrained('personas')
                  ->onDelete('set null');

            $table->foreignId('madrina_1_id')
                  ->nullable()
                  ->constrained('personas')
                  ->onDelete('set null');

            // Campos propios del acta física
            $table->string('numero_pagina', 20); // Número de página del libro (generalmente texto o número)
            $table->string('numero_acta', 20); // Número correlativo de acta
            $table->date('fecha_bautizo'); // Fecha del bautizo
            $table->string('ministro')->index(); // Nombre del sacerdote o ministro que bautizó
            $table->text('notas_marginales')->nullable(); // Notas agregadas a los bordes del libro físico (matrimonio, defunción, etc.)
            
            // Campo opcional para almacenar la ruta local de la imagen de la página física
            $table->string('imagen_pagina_path')->nullable();

            $table->softDeletes(); // Borrado lógico para seguridad y trazabilidad
            $table->timestamps();

            // Índices específicos
            $table->index('fecha_bautizo');
            $table->index(['libro_id', 'numero_pagina', 'numero_acta']); // Índice compuesto para búsquedas directas dentro del libro físico
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actas_bautizo');
    }
};
