<?php

namespace App\Support;

/**
 * Manejo de RUT chileno. Regla del producto:
 *  - GUARDAR siempre normalizado: cuerpo + DV pegados, sin puntos ni guion, K mayúscula
 *    (ej. "123456785", "12345678K"). Es el valor canónico para comparar/buscar.
 *  - MOSTRAR formateado en la UI (ej. "12.345.678-5").
 *  - El kiosko teclea solo dígitos + tecla K (sin guión); normalizar() lo deja canónico.
 *
 * Nota: K y 0 son dígitos verificadores DISTINTOS y ambos válidos — nunca convertir K→0.
 */
class Rut
{
    /**
     * Deja el RUT canónico: mayúsculas, sin puntos/guion/espacios.
     * "12.345.678-5" → "123456785" ; "12345678-k" → "12345678K".
     */
    public static function normalizar(string $rut): string
    {
        return strtoupper(str_replace(['.', '-', ' '], '', trim($rut)));
    }

    /**
     * Formatea para mostrar: "123456785" → "12.345.678-5".
     * Si no parece un RUT (longitud/forma), devuelve la entrada tal cual.
     */
    public static function formatear(string $rut): string
    {
        $n = self::normalizar($rut);

        if (! preg_match('/^(\d{7,8})([0-9K])$/', $n, $m)) {
            return $rut;
        }

        $cuerpo = number_format((int) $m[1], 0, '', '.');

        return "{$cuerpo}-{$m[2]}";
    }

    /**
     * ¿El RUT (en cualquier formato) es válido por módulo 11?
     */
    public static function esValido(string $rut): bool
    {
        $n = self::normalizar($rut);

        if (! preg_match('/^(\d{7,8})([0-9K])$/', $n, $m)) {
            return false;
        }

        return self::dvDe($m[1]) === $m[2];
    }

    /**
     * Dígito verificador (módulo 11) de un cuerpo numérico. Devuelve '0'..'9' o 'K'.
     */
    public static function dvDe(string $cuerpo): string
    {
        $suma = 0;
        $multiplo = 2;

        foreach (array_reverse(str_split($cuerpo)) as $digito) {
            $suma += ((int) $digito) * $multiplo;
            $multiplo = $multiplo === 7 ? 2 : $multiplo + 1;
        }

        $resto = 11 - ($suma % 11);

        return match ($resto) {
            11 => '0',
            10 => 'K',
            default => (string) $resto,
        };
    }
}
