<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persona extends Model
{
    use Auditable; // Auditoría automática de cambios

    protected $table = 'personas';

    protected $fillable = [
        'nombres',
        'apellidos',
        'cedula',
        'fecha_nacimiento',
        'genero',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    /**
     * Obtener el nombre completo de la persona.
     * Ideal para formatear rápidamente los datos en la UI.
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    /**
     * Actas de bautizo donde esta persona es la bautizada.
     */
    public function bautizos(): HasMany
    {
        return $this->hasMany(ActaBautizo::class, 'bautizado_id');
    }

    /**
     * Actas de bautizo donde esta persona figura como padre.
     */
    public function hijosComoPadre(): HasMany
    {
        return $this->hasMany(ActaBautizo::class, 'padre_id');
    }

    /**
     * Actas de bautizo donde esta persona figura como madre.
     */
    public function hijosComoMadre(): HasMany
    {
        return $this->hasMany(ActaBautizo::class, 'madre_id');
    }

    /**
     * Actas de bautizo donde esta persona figura como padrino.
     */
    public function ahijadosComoPadrino(): HasMany
    {
        return $this->hasMany(ActaBautizo::class, 'padrino_1_id');
    }

    /**
     * Actas de bautizo donde esta persona figura como madrina.
     */
    public function ahijadosComoMadrina(): HasMany
    {
        return $this->hasMany(ActaBautizo::class, 'madrina_1_id');
    }
}
