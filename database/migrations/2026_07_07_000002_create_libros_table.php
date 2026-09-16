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
        Schema::create('libros', function (Blueprint $table) {
            $table->id();
            $table->string('numero_libro')->index(); // Número de tomo o libro físico de la parroquia
            $table->unsignedSmallInteger('anio_inicio')->index(); // Año de inicio del libro
            $table->unsignedSmallInteger('anio_fin')->nullable()->index(); // Año de finalización (nullable si está en curso)
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('libros');
    }
};
