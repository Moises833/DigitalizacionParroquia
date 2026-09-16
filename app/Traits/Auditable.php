<?php

namespace App\Traits;

use App\Models\AuditoriaLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the trait and register model event listeners.
     */
    protected static function bootAuditable(): void
    {
        // Al crear un registro
        static::created(function ($model) {
            self::logAction('crear', $model, null, $model->getAttributes());
        });

        // Al actualizar un registro
        static::updated(function ($model) {
            // Obtenemos solo los campos que cambiaron para ahorrar almacenamiento en disco (ideal para bajos recursos)
            $changes = $model->getChanges();
            
            $valoresAnteriores = [];
            $valoresNuevos = [];

            foreach ($changes as $key => $value) {
                // Omitimos registrar campos de timestamp de actualización si son los únicos que cambiaron
                if (in_array($key, ['updated_at', 'deleted_at'])) {
                    continue;
                }
                $valoresAnteriores[$key] = $model->getOriginal($key);
                $valoresNuevos[$key] = $value;
            }

            // Solo registramos si hubo cambios reales en datos
            if (!empty($valoresNuevos)) {
                self::logAction('editar', $model, $valoresAnteriores, $valoresNuevos);
            }
        });

        // Al eliminar (o soft-delete) un registro
        static::deleted(function ($model) {
            $esSoftDelete = method_exists($model, 'runSoftDelete') || in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model));
            
            $accion = $esSoftDelete && !$model->isForceDeleting() ? 'ocultar' : 'eliminar';
            
            self::logAction($accion, $model, $model->getOriginal(), null);
        });

        // Al restaurar un registro de soft-delete
        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                self::logAction('restaurar', $model, null, ['deleted_at' => null]);
            });
        }
    }

    /**
     * Guarda la acción realizada en la tabla de auditoría.
     */
    protected static function logAction(string $accion, $model, ?array $antes, ?array $despues): void
    {
        // En entornos locales/bajos recursos, es importante evitar que fallos en auditoría detengan el flujo principal.
        try {
            AuditoriaLog::create([
                'user_id' => Auth::id(), // Obtiene el ID del usuario autenticado actual, o null si es por consola/seeder
                'accion' => $accion,
                'tabla_afectada' => $model->getTable(),
                'registro_id' => $model->id,
                'valores_anteriores' => $antes, // Eloquent se encarga de serializar a JSON si está casteado en el modelo
                'valores_nuevos' => $despues,
            ]);
        } catch (\Exception $e) {
            // Reportar el error en logs tradicionales, pero sin interrumpir la experiencia de usuario
            report($e);
        }
    }
}
