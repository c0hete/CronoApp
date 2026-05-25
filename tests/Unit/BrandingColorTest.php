<?php

namespace Tests\Unit;

use App\Services\BrandingService;
use PHPUnit\Framework\TestCase;

/**
 * Helpers de color del branding (puros). Lo clave: el dueño elige UN color y la
 * paleta derivada NO debe romper la legibilidad (texto contrastante).
 */
class BrandingColorTest extends TestCase
{
    public function test_valida_hex(): void
    {
        $this->assertTrue(BrandingService::esHexValido('#2E75B6'));
        $this->assertTrue(BrandingService::esHexValido('#abcdef'));
        $this->assertFalse(BrandingService::esHexValido('2E75B6'));    // sin #
        $this->assertFalse(BrandingService::esHexValido('#FFF'));      // corto
        $this->assertFalse(BrandingService::esHexValido('#GGGGGG'));   // no hex
        $this->assertFalse(BrandingService::esHexValido('rojo'));
    }

    public function test_texto_contrasta_con_el_fondo(): void
    {
        // sobre color oscuro → texto blanco; sobre claro → texto negro
        $this->assertSame('#FFFFFF', BrandingService::colorTextoSobre('#1A1A1A'));
        $this->assertSame('#FFFFFF', BrandingService::colorTextoSobre('#2E75B6')); // azul medio-oscuro
        $this->assertSame('#111111', BrandingService::colorTextoSobre('#FFFF00')); // amarillo claro
        $this->assertSame('#111111', BrandingService::colorTextoSobre('#F0F0F0'));
    }

    public function test_ajustar_luminosidad_devuelve_hex_valido(): void
    {
        $hover = BrandingService::ajustarLuminosidad('#2E75B6', -12);
        $this->assertTrue(BrandingService::esHexValido($hover));
        // oscurecer da un color distinto y más oscuro
        $this->assertNotSame('#2E75B6', $hover);
    }
}
