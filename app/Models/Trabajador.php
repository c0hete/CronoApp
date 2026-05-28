<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use App\Support\Rut;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * Identificación lista para mostrar: RUT formateado (25.768.863-1) o el
     * pasaporte tal cual. El valor guardado (numero_id) siempre es canónico.
     */
    protected function identificacionFormateada(): Attribute
    {
        return Attribute::get(fn () => $this->tipo_id === 'rut'
            ? Rut::formatear($this->numero_id)
            : $this->numero_id);
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }

    public function marcajes(): HasMany
    {
        return $this->hasMany(Marcaje::class);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class);
    }

    public function excepciones(): HasMany
    {
        return $this->hasMany(ExcepcionDia::class);
    }

    /**
     * Contrato vigente = el que tiene vigente_hasta NULL.
     */
    public function contratoVigente(): ?Contrato
    {
        return $this->contratos()->whereNull('vigente_hasta')->latest('vigente_desde')->first();
    }

    /**
     * Horario esperado para un día ISO (1=lun…7=dom), o null si ese día no trabaja.
     */
    public function horarioDelDia(int $diaIso): ?Horario
    {
        return $this->horarios->firstWhere('dia_semana', $diaIso);
    }

    /**
     * Enchufe ARCO (fase 2). Hoy normalmente NULL.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
