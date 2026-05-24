<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida un RUT chileno (módulo 11). Acepta con o sin puntos/guion;
 * el dígito verificador puede ser número o 'K'.
 *
 * Solo se usa cuando tipo_id = 'rut'. Para 'pasaporte' no aplica
 * (no hay estándar de verificación universal).
 */
class RutChileno implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rut = strtoupper(str_replace(['.', '-', ' '], '', (string) $value));

        if (! preg_match('/^\d{7,8}[0-9K]$/', $rut)) {
            $fail('El RUT no tiene un formato válido.');
            return;
        }

        $cuerpo = substr($rut, 0, -1);
        $dvIngresado = substr($rut, -1);

        if (! $this->dvCorrecto($cuerpo, $dvIngresado)) {
            $fail('El dígito verificador del RUT no es correcto.');
        }
    }

    private function dvCorrecto(string $cuerpo, string $dvIngresado): bool
    {
        $suma = 0;
        $multiplo = 2;

        foreach (array_reverse(str_split($cuerpo)) as $digito) {
            $suma += ((int) $digito) * $multiplo;
            $multiplo = $multiplo === 7 ? 2 : $multiplo + 1;
        }

        $resto = 11 - ($suma % 11);

        $dvCalculado = match ($resto) {
            11 => '0',
            10 => 'K',
            default => (string) $resto,
        };

        return $dvCalculado === $dvIngresado;
    }
}
