<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActaBautizo extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'actas_bautizo';

    protected $fillable = [
        'libro_id',
        'bautizado_id',
        'padre_id',
        'madre_id',
        'padrino_1_id',
        'madrina_1_id',
        'numero_pagina',
        'numero_acta',
        'fecha_bautizo',
        'ministro',
        'notas_marginales',
        'imagen_pagina_path',
    ];

    protected $casts = [
        'fecha_bautizo' => 'date',
        'libro_id' => 'integer',
        'bautizado_id' => 'integer',
        'padre_id' => 'integer',
        'madre_id' => 'integer',
        'padrino_1_id' => 'integer',
        'madrina_1_id' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function libro(): BelongsTo
    {
        return $this->belongsTo(Libro::class, 'libro_id');
    }

    public function bautizado(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'bautizado_id');
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'padre_id');
    }

    public function madre(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'madre_id');
    }

    public function padrino(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'padrino_1_id');
    }

    public function madrina(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'madrina_1_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes (Filtros de Búsqueda)
    |--------------------------------------------------------------------------
    */

    /**
     * Aplica filtros generales al query de actas de bautizo.
     */
    public function scopeSearch(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['bautizado'] ?? null, function ($q, $search) {
                $q->filterByBautizado($search);
            })
            ->when($filters['anio'] ?? null, function ($q, $year) {
                $q->filterByAnio($year);
            })
            ->when($filters['familiar'] ?? null, function ($q, $search) {
                $q->filterByFamiliar($search);
            })
            ->when($filters['libro'] ?? null, function ($q, $libroNum) {
                $q->whereHas('libro', function ($subQ) use ($libroNum) {
                    $subQ->where('numero_libro', $libroNum);
                });
            });
    }

    /**
     * Filtra actas por nombres o apellidos del bautizado.
     */
    public function scopeFilterByBautizado(Builder $query, string $search): Builder
    {
        return $query->whereHas('bautizado', function ($q) use ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%");
            });
        });
    }

    /**
     * Filtra actas por el año del bautizo.
     * En bases de datos grandes, realizar 'whereYear' sobre un índice de tipo DATE
     * es óptimo ya que el motor puede escanear rangos.
     */
    public function scopeFilterByAnio(Builder $query, int $year): Builder
    {
        return $query->whereYear('fecha_bautizo', $year);
    }

    /**
     * Filtra actas por coincidencia de nombre/apellido en cualquier familiar registrado (Padre, Madre, Padrino, Madrina).
     */
    public function scopeFilterByFamiliar(Builder $query, string $search): Builder
    {
        // El uso de 'orWhereHas' múltiple es legible. Con índices apropiados en nombres/apellidos de 'personas',
        // el rendimiento se mantiene óptimo en equipos de bajos recursos.
        return $query->where(function ($q) use ($search) {
            $roles = ['padre', 'madre', 'padrino', 'madrina'];
            
            foreach ($roles as $rol) {
                $q->orWhereHas($rol, function ($sub) use ($search) {
                    $sub->where('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%");
                });
            }
        });
    }
}
