<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Horario esperado de un trabajador para un día de la semana (ISO: 1=lunes…7=domingo).
 * Con esto el sistema sabe a quién esperar cada día y a qué hora — base del retraso
 * "en vivo" (corre desde la hora esperada, marque o no el trabajador).
 */
class Horario extends Model
{
    use BelongsToEmpresa;

    protected $table = 'horarios';

    protected $fillable = [
        'empresa_id',
        'trabajador_id',
        'dia_semana',
        'hora_entrada',
        'tolerancia_min',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'tolerancia_min' => 'integer',
    ];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    /** Nombre del día en español (para la UI). */
    public function nombreDia(): string
    {
        return self::nombresDias()[$this->dia_semana] ?? '—';
    }

    /** @return array<int,string> mapa dia_semana → nombre */
    public static function nombresDias(): array
    {
        return [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
    }
}
