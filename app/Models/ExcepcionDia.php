<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "No viene hoy": día justificado para un trabajador. Su presencia hace que el
 * EstadoDiaService NO acumule retraso ni cuente ausencia ese día. Queda registrado
 * (quién y cuándo). Reversible borrando la fila.
 */
class ExcepcionDia extends Model
{
    use BelongsToEmpresa;

    protected $table = 'excepciones_dia';

    protected $fillable = [
        'empresa_id',
        'trabajador_id',
        'fecha',
        'tipo',
        'motivo',
        'registrada_por',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function registradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrada_por');
    }
}
