<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

/**
 * Pares clave/valor por empresa. Es lo que diferencia a una instancia de otra:
 * toda la personalización (cálculo, fotos, branding) vive acá, nunca en código.
 */
class Configuracion extends Model
{
    use BelongsToEmpresa;

    protected $table = 'configuraciones';

    protected $fillable = [
        'empresa_id',
        'clave',
        'valor',
    ];

    /**
     * Lee una clave de la empresa activa, con default.
     */
    public static function valor(string $clave, ?string $default = null): ?string
    {
        return static::where('clave', $clave)->value('valor') ?? $default;
    }

    /**
     * Crea o actualiza una clave de la empresa activa.
     */
    public static function poner(string $clave, string $valor): static
    {
        return static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
    }
}
