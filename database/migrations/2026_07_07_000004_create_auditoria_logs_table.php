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
        Schema::create('auditoria_logs', function (Blueprint $table) {
            $table->id();
            
            // Relación con el usuario que realiza la acción (nullable en caso de que alguna acción sea del sistema)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->string('accion'); // 'crear', 'editar', 'ocultar' (para el soft delete)
            $table->string('tabla_afectada'); // Nombre de la tabla (ej: 'actas_bautizo', 'personas')
            $table->unsignedBigInteger('registro_id'); // ID del registro afectado
            
            $table->json('valores_anteriores')->nullable(); // Valores previos a la acción (null si es creación)
            $table->json('valores_nuevos')->nullable(); // Nuevos valores guardados
            
            $table->timestamp('created_at')->useCurrent(); // Fecha y hora de la acción (solo created_at es necesario)

            // Índices para búsquedas de auditoría por tabla y registro
            $table->index(['tabla_afectada', 'registro_id']);
            $table->index('accion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria_logs');
    }
};
