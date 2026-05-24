<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Histórico: para cambiar sueldo/horario nunca se edita el contrato vigente,
 * se cierra (vigente_hasta) y se crea uno nuevo. Así no se corrompen reportes pasados.
 */
class Contrato extends Model
{
    use BelongsToEmpresa;

    protected $table = 'contratos';

    protected $fillable = [
        'empresa_id',
        'trabajador_id',
        'sueldo_bruto',
        'sueldo_liquido',
        'horas_semanales',
        'hora_entrada_pactada',
        'tolerancia_min',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected $casts = [
        'sueldo_bruto'     => 'decimal:2',
        'sueldo_liquido'   => 'decimal:2',
        'horas_semanales'  => 'decimal:2',
        'tolerancia_min'   => 'integer',
        'vigente_desde'    => 'date',
        'vigente_hasta'    => 'date',
    ];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function estaVigente(): bool
    {
        return is_null($this->vigente_hasta);
    }
}
