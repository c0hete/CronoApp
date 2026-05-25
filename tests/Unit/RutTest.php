<?php

namespace Tests\Unit;

use App\Support\Rut;
use PHPUnit\Framework\TestCase;

class RutTest extends TestCase
{
    public function test_normaliza_quita_puntos_guion_y_sube_la_k(): void
    {
        $this->assertSame('257688631', Rut::normalizar('25.768.863-1'));
        $this->assertSame('257688631', Rut::normalizar('25768863-1'));
        $this->assertSame('257688631', Rut::normalizar('257688631'));
        $this->assertSame('12345678K', Rut::normalizar('12.345.678-k'));
        $this->assertSame('12345678K', Rut::normalizar(' 12345678-K '));
    }

    public function test_formatea_para_mostrar(): void
    {
        $this->assertSame('25.768.863-1', Rut::formatear('257688631'));
        $this->assertSame('25.768.863-1', Rut::formatear('25768863-1'));
        $this->assertSame('12.345.678-K', Rut::formatear('12345678K'));
    }

    public function test_valida_modulo_11(): void
    {
        $this->assertTrue(Rut::esValido('25.768.863-1'));
        $this->assertTrue(Rut::esValido('11111111-1'));
        $this->assertFalse(Rut::esValido('12.345.678-9')); // dv malo
        $this->assertFalse(Rut::esValido('no-es-rut'));
    }

    public function test_k_y_cero_son_dv_distintos(): void
    {
        // Garantiza que NO confundimos K con 0 (eran personas distintas)
        $cuerpo = '12345678';
        $dv = Rut::dvDe($cuerpo);
        // el dv de 12345678 es 5; lo que importa: K se preserva como K, no como 0
        $this->assertSame('K', Rut::normalizar('22222222-K')[8]);
        $this->assertNotSame(Rut::normalizar('22222222-K'), Rut::normalizar('22222222-0'));
    }
}
