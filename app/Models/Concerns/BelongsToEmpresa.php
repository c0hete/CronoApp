<?php

namespace App\Models\Concerns;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scope de tenant. Mantiene `empresa_id` en cada modelo aunque cada instancia
 * tenga un solo cliente: da coherencia de esquema entre instancias y permite,
 * si el negocio algún día lo pide, consolidar varios clientes en una base.
 *
 * En el modelo SaaS instancia-por-cliente, la empresa activa de ESTA instancia
 * sale de la config `CRONO_EMPRESA_ID` (default 1).
 *
 * - Auto-asigna `empresa_id` al crear si no viene seteado.
 * - Filtra todas las consultas por la empresa activa (global scope).
 */
trait BelongsToEmpresa
{
    public static function bootBelongsToEmpresa(): void
    {
        // Filtrar siempre por la empresa de la instancia.
        static::addGlobalScope('empresa', function (Builder $builder) {
            $builder->where($builder->getModel()->getTable() . '.empresa_id', static::empresaActivaId());
        });

        // Asignar empresa_id en creación si no se especificó.
        static::creating(function (Model $model) {
            if (empty($model->empresa_id)) {
                $model->empresa_id = static::empresaActivaId();
            }
        });
    }

    /**
     * Id de la empresa activa de esta instancia (config CRONO_EMPRESA_ID).
     */
    public static function empresaActivaId(): int
    {
        return (int) config('crono.empresa_id', 1);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
