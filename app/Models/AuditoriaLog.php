<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaLog extends Model
{
    // Desactivamos los timestamps tradicionales de Laravel (created_at y updated_at)
    // ya que en la migración solo creamos 'created_at' y se llena automáticamente vía base de datos (useCurrent)
    public $timestamps = false;

    protected $table = 'auditoria_logs';

    protected $fillable = [
        'user_id',
        'accion',
        'tabla_afectada',
        'registro_id',
        'valores_anteriores',
        'valores_nuevos',
    ];

    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos' => 'array',
    ];

    /**
     * Relación con el usuario que ejecutó la acción.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
