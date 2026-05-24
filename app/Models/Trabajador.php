<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * El trabajador NO es usuario autenticado: es entidad de datos propia.
 * `user_id` es nullable, como enchufe para login futuro (derechos ARCO, Ley 21.719).
 */
class Trabajador extends Model
{
    use BelongsToEmpresa;

    protected $table = 'trabajadores';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'nombre',
        'tipo_id',
        'numero_id',
        'foto_enrolamiento',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }

    public function marcajes(): HasMany
    {
        return $this->hasMany(Marcaje::class);
    }

    /**
     * Contrato vigente = el que tiene vigente_hasta NULL.
     */
    public function contratoVigente(): ?Contrato
    {
        return $this->contratos()->whereNull('vigente_hasta')->latest('vigente_desde')->first();
    }

    /**
     * Enchufe ARCO (fase 2). Hoy normalmente NULL.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
