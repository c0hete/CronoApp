<?php

namespace App\Services;

use App\Models\Configuracion;

/**
 * Branding white-label. Crono es agnóstico de marca: la UI muestra el nombre del
 * negocio del cliente (marca_nombre), NUNCA "Crono".
 *
 * El dueño edita nombre/color/logo en autoservicio (Paso 8), con límites de seguridad:
 *  - elige UN color primario; la paleta (hover/claro/oscuro/texto) se DERIVA acá → no
 *    puede romper contraste/legibilidad.
 *  - el logo se guarda fuera de public y se sirve autorizado (como las fotos).
 */
class BrandingService
{
    public const COLOR_DEFAULT = '#2E75B6';

    /**
     * Nombre del negocio para mostrar. Fallback genérico, jamás "Crono".
     */
    public function nombre(): string
    {
        $nombre = trim((string) Configuracion::valor('marca_nombre', ''));

        return $nombre !== '' ? $nombre : 'Asistencia';
    }

    /**
     * Color primario válido (#RRGGBB). Si lo guardado no es HEX válido, cae al default.
     */
    public function colorPrimario(): string
    {
        $color = (string) Configuracion::valor('marca_color_primario', self::COLOR_DEFAULT);

        return self::esHexValido($color) ? strtoupper($color) : self::COLOR_DEFAULT;
    }

    /**
     * Ruta del logo (en disco 'fotos', fuera de public) o null si no hay.
     */
    public function logo(): ?string
    {
        $logo = (string) Configuracion::valor('marca_logo', '');

        return $logo !== '' ? $logo : null;
    }

    /**
     * Paleta derivada del color primario para inyectar como variables CSS.
     * El dueño NO elige estas: se calculan para no romper contraste.
     *
     * @return array{primary:string, primary_hover:string, primary_dark:string, on_primary:string}
     */
    public function paleta(): array
    {
        $primary = $this->colorPrimario();

        return [
            'primary' => $primary,
            'primary_hover' => self::ajustarLuminosidad($primary, -12),
            'primary_dark' => self::ajustarLuminosidad($primary, -28),
            'on_primary' => self::colorTextoSobre($primary), // blanco o negro según contraste
        ];
    }

    // --- Validación de HEX ---

    public static function esHexValido(string $color): bool
    {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', $color);
    }

    // --- Helpers de color (puros) ---

    /**
     * Aclara (+) u oscurece (-) un HEX por un porcentaje. Devuelve #RRGGBB.
     */
    public static function ajustarLuminosidad(string $hex, int $porcentaje): string
    {
        [$r, $g, $b] = self::aRgb($hex);
        $factor = $porcentaje / 100;

        $aj = fn (int $c) => max(0, min(255, (int) round($c + ($porcentaje >= 0 ? (255 - $c) : $c) * $factor)));

        return sprintf('#%02X%02X%02X', $aj($r), $aj($g), $aj($b));
    }

    /**
     * Devuelve '#FFFFFF' o '#111111' según cuál contraste mejor sobre el color dado
     * (luminancia relativa — garantiza texto legible sobre el primario).
     */
    public static function colorTextoSobre(string $hex): string
    {
        [$r, $g, $b] = self::aRgb($hex);
        // luminancia perceptual aproximada
        $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $lum > 0.6 ? '#111111' : '#FFFFFF';
    }

    /** @return array{0:int,1:int,2:int} */
    private static function aRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
