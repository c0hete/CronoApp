<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de marcaje. NUNCA se borra automáticamente (dato de valor); solo la
 * foto_evidencia se purga por retención — el registro permanece sin imagen.
 * El `uuid` lo genera la tablet para idempotencia en la sync offline.
 */
class Marcaje extends Model
{
    use BelongsToEmpresa;

    protected $table = 'marcajes';

    protected $fillable = [
        'uuid',
        'empresa_id',
        'trabajador_id',
        'tipo',
        'ts_dispositivo',
        'ts_servidor',
        'foto_evidencia',
        'minutos_atraso',
        'costo_atraso',
        'reloj_sospechoso',
    ];

    protected $casts = [
        'ts_dispositivo'   => 'datetime',
        'ts_servidor'      => 'datetime',
        'minutos_atraso'   => 'integer',
        'costo_atraso'     => 'decimal:2',
        'reloj_sospechoso' => 'boolean',
    ];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function esEntrada(): bool
    {
        return $this->tipo === 'entrada';
    }
}
