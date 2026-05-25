<?php

namespace App\Rules;

use App\Support\Rut;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida un RUT chileno (módulo 11). Acepta con o sin puntos/guion;
 * el dígito verificador puede ser número o 'K'. Delega en App\Support\Rut.
 *
 * Solo se usa cuando tipo_id = 'rut'. Para 'pasaporte' no aplica.
 */
class RutChileno implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Rut::esValido((string) $value)) {
            $fail('El RUT no es válido (revisa el dígito verificador).');
        }
    }
}
