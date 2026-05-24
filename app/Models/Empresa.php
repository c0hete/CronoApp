<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Raíz del tenant. NO usa BelongsToEmpresa (es la empresa misma).
 * En el modelo instancia-por-cliente hay una empresa por instancia (id=1).
 */
class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nombre',
        'rut_empresa',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function trabajadores(): HasMany
    {
        return $this->hasMany(Trabajador::class);
    }

    public function configuraciones(): HasMany
    {
        return $this->hasMany(Configuracion::class);
    }
}
