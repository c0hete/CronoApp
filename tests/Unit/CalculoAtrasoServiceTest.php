<?php

namespace Tests\Unit;

use App\Models\Configuracion;
use App\Models\Contrato;
use App\Services\CalculoAtrasoService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Batería de casos límite del cálculo de atraso (sección 6). Es el cálculo crítico:
 * un error acá es silencioso y afecta el número por el que el dueño paga. Cada caso
 * usa cifras que dan resultados redondos para poder afirmar el monto EXACTO.
 *
 * Referencia de valor_hora: sueldo 450.000 / 45 h = 10.000 $/h → 166,67 $/min.
 * Con 60 min de atraso → costo = 10.000 exacto.
 */
class CalculoAtrasoServiceTest extends TestCase
{
    use RefreshDatabase;

    private CalculoAtrasoService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // roles + empresa + configuraciones (base_calculo=bruto, etc.)
        $this->svc = new CalculoAtrasoService();
    }

    /**
     * Helper: arma un contrato (sin persistir relaciones complejas) con valores dados.
     */
    private function contrato(array $attrs = []): Contrato
    {
        return new Contrato(array_merge([
            'empresa_id'           => 1,
            'trabajador_id'        => 1,
            'sueldo_bruto'         => 450000,
            'sueldo_liquido'       => null,
            'horas_semanales'      => 45,
            'hora_entrada_pactada' => '09:00:00',
            'tolerancia_min'       => 5,
            'vigente_desde'        => '2026-01-01',
            'vigente_hasta'        => null,
        ], $attrs));
    }

    private function alas(string $hora): Carbon
    {
        // 2026-05-25 es lunes; la hora es lo que importa para el atraso.
        return Carbon::parse("2026-05-25 {$hora}");
    }

    public function test_llega_a_tiempo_atraso_cero(): void
    {
        $r = $this->svc->calcular($this->contrato(), $this->alas('08:55'));
        $this->assertSame(0, $r['minutos_atraso']);
        $this->assertSame('0.00', $r['costo_atraso']);
    }

    public function test_justo_en_el_borde_de_la_tolerancia_no_es_atraso(): void
    {
        // pactada 09:00 + tolerancia 5 = límite 09:05. Marcar 09:05 exacto NO es atraso.
        $r = $this->svc->calcular($this->contrato(), $this->alas('09:05'));
        $this->assertSame(0, $r['minutos_atraso']);
        $this->assertSame('0.00', $r['costo_atraso']);
    }

    public function test_un_minuto_pasado_el_borde_si_es_atraso(): void
    {
        // 09:06 → 1 minuto sobre el límite 09:05.
        $r = $this->svc->calcular($this->contrato(), $this->alas('09:06'));
        $this->assertSame(1, $r['minutos_atraso']);
        // 1/60 * 10.000 = 166,666... → 166.67
        $this->assertSame('166.67', $r['costo_atraso']);
    }

    public function test_una_hora_de_atraso_cuesta_un_valor_hora(): void
    {
        // límite 09:05, marca 10:05 → 60 min. costo = 60/60 * 10.000 = 10.000.
        $r = $this->svc->calcular($this->contrato(), $this->alas('10:05'));
        $this->assertSame(60, $r['minutos_atraso']);
        $this->assertSame('10000.00', $r['costo_atraso']);
        $this->assertSame('bruto', $r['sueldo_usado']);
        $this->assertFalse($r['sin_sueldo']);
    }

    public function test_sin_ningun_sueldo_costo_cero_pero_minutos_cuentan(): void
    {
        $c = $this->contrato(['sueldo_bruto' => null, 'sueldo_liquido' => null]);
        $r = $this->svc->calcular($c, $this->alas('10:05'));
        $this->assertSame(60, $r['minutos_atraso']); // los minutos SÍ se registran
        $this->assertSame('0.00', $r['costo_atraso']);
        $this->assertTrue($r['sin_sueldo']);
        $this->assertNull($r['sueldo_usado']);
    }

    public function test_base_bruto_pero_solo_hay_liquido_usa_liquido_y_lo_reporta(): void
    {
        // base_calculo = bruto (seed), pero el contrato solo tiene líquido.
        $c = $this->contrato(['sueldo_bruto' => null, 'sueldo_liquido' => 450000]);
        $r = $this->svc->calcular($c, $this->alas('10:05'));
        $this->assertSame('10000.00', $r['costo_atraso']);
        $this->assertSame('liquido', $r['sueldo_usado']); // transparencia: avisa cuál usó
    }

    public function test_base_liquido_usa_liquido_cuando_ambos_existen(): void
    {
        Configuracion::poner('base_calculo', 'liquido');
        $c = $this->contrato(['sueldo_bruto' => 900000, 'sueldo_liquido' => 450000]);
        $r = $this->svc->calcular($c, $this->alas('10:05'));
        // usa líquido 450.000 → 10.000, no el bruto.
        $this->assertSame('10000.00', $r['costo_atraso']);
        $this->assertSame('liquido', $r['sueldo_usado']);
    }

    public function test_base_bruto_usa_bruto_cuando_ambos_existen(): void
    {
        // seed deja base=bruto.
        $c = $this->contrato(['sueldo_bruto' => 450000, 'sueldo_liquido' => 900000]);
        $r = $this->svc->calcular($c, $this->alas('10:05'));
        $this->assertSame('10000.00', $r['costo_atraso']); // bruto 450k → 10.000
        $this->assertSame('bruto', $r['sueldo_usado']);
    }

    public function test_horas_semanales_cero_no_divide_por_cero(): void
    {
        $c = $this->contrato(['horas_semanales' => 0]);
        $r = $this->svc->calcular($c, $this->alas('10:05'));
        $this->assertSame(60, $r['minutos_atraso']);
        $this->assertSame('0.00', $r['costo_atraso']); // guarda defensiva, no peta
    }

    public function test_jornada_partida_distinta_hora_pactada(): void
    {
        // contrato con entrada 14:30, tolerancia 0; marca 14:45 → 15 min.
        $c = $this->contrato([
            'hora_entrada_pactada' => '14:30:00',
            'tolerancia_min'       => 0,
            'horas_semanales'      => 30,   // valor_hora = 450000/30 = 15.000
        ]);
        $r = $this->svc->calcular($c, $this->alas('14:45'));
        $this->assertSame(15, $r['minutos_atraso']);
        // 15/60 * 15.000 = 3.750
        $this->assertSame('3750.00', $r['costo_atraso']);
    }
}
