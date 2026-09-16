<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Libro extends Model
{
    use Auditable; // Auditoría automática de cambios

    protected $table = 'libros';

    protected $fillable = [
        'numero_libro',
        'anio_inicio',
        'anio_fin',
        'observaciones',
    ];

    protected $casts = [
        'anio_inicio' => 'integer',
        'anio_fin' => 'integer',
    ];

    /**
     * Actas de bautizo contenidas en este libro físico.
     */
    public function actas(): HasMany
    {
        return $this->hasMany(ActaBautizo::class, 'libro_id');
    }
}
