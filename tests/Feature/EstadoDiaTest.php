<?php

namespace Tests\Feature;

use App\Models\ExcepcionDia;
use App\Models\Horario;
use App\Models\Marcaje;
use App\Models\Trabajador;
use App\Services\EstadoDiaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Estado "en vivo" del día (vista "Esperados hoy"). Lo clave: el retraso CORRE
 * desde la hora esperada aunque el trabajador no haya marcado, y Luis puede
 * justificar/marcar ausente a mano (el marcaje real gana sobre la excepción).
 *
 * Día de referencia: 2026-05-25 es LUNES (dayOfWeekIso = 1).
 */
class EstadoDiaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private EstadoDiaService $svc;

    private function trabajadorConHorario(string $hora = '09:00', ?int $tol = 5): Trabajador
    {
        $t = Trabajador::create([
            'empresa_id' => 1, 'nombre' => 'Ana', 'tipo_id' => 'rut',
            'numero_id' => '111111111', 'activo' => true,
        ]);
        Horario::create([
            'empresa_id' => 1, 'trabajador_id' => $t->id, 'dia_semana' => 1, // lunes
            'hora_entrada' => $hora, 'tolerancia_min' => $tol,
        ]);

        return $t->fresh(['horarios', 'excepciones', 'marcajes', 'contratos']);
    }

    private function ahora(string $hhmm): CarbonImmutable
    {
        return CarbonImmutable::parse("2026-05-25 {$hhmm}"); // lunes
    }

    protected function estado(Trabajador $t, string $hhmm): array
    {
        return (new EstadoDiaService)->estado($t->fresh(['horarios', 'excepciones', 'marcajes', 'contratos']), $this->ahora($hhmm));
    }

    public function test_sin_horario_ese_dia_no_se_espera(): void
    {
        $t = Trabajador::create(['empresa_id' => 1, 'nombre' => 'X', 'tipo_id' => 'rut', 'numero_id' => '9', 'activo' => true]);
        $this->assertSame('sin_horario', $this->estado($t, '12:00')['estado']);
    }

    public function test_pendiente_antes_de_su_hora(): void
    {
        $t = $this->trabajadorConHorario('09:00', 5);
        $this->assertSame('pendiente', $this->estado($t, '08:30')['estado']);
        // dentro de tolerancia tampoco es atraso aún
        $this->assertSame('pendiente', $this->estado($t, '09:04')['estado']);
    }

    public function test_atrasado_corriendo_sin_marcar(): void
    {
        $t = $this->trabajadorConHorario('09:00', 5); // límite 09:05
        $r = $this->estado($t, '09:20'); // 15 min pasados del límite, sin marcar
        $this->assertSame('atrasado', $r['estado']);
        $this->assertSame(15, $r['minutos_atraso']);
        $this->assertTrue($r['en_curso']);   // el contador corre
        $this->assertFalse($r['marco']);
    }

    public function test_ausente_tras_una_hora_sin_marcar(): void
    {
        $t = $this->trabajadorConHorario('09:00', 5); // límite 09:05
        // 60 min de umbral por defecto: a las 10:10 ya pasó >60 del límite
        $this->assertSame('ausente', $this->estado($t, '10:10')['estado']);
    }

    public function test_marco_a_tiempo(): void
    {
        $t = $this->trabajadorConHorario('09:00', 5);
        Marcaje::create([
            'uuid' => (string) Str::uuid(), 'empresa_id' => 1, 'trabajador_id' => $t->id, 'tipo' => 'entrada',
            'ts_dispositivo' => $this->ahora('09:03'), 'ts_servidor' => $this->ahora('09:03'),
        ]);
        $r = $this->estado($t, '09:30');
        $this->assertSame('a_tiempo', $r['estado']);
        $this->assertTrue($r['marco']);
    }

    public function test_marco_atrasado_cierra_el_total(): void
    {
        $t = $this->trabajadorConHorario('09:00', 5);
        Marcaje::create([
            'uuid' => (string) Str::uuid(), 'empresa_id' => 1, 'trabajador_id' => $t->id, 'tipo' => 'entrada',
            'ts_dispositivo' => $this->ahora('09:25'), 'ts_servidor' => $this->ahora('09:25'),
        ]);
        $r = $this->estado($t, '11:00'); // ya pasó rato, pero marcó a las 09:25
        $this->assertSame('atrasado', $r['estado']);
        $this->assertSame(20, $r['minutos_atraso']); // 09:25 - 09:05 límite
        $this->assertFalse($r['en_curso']);          // cerrado, ya no corre
    }

    public function test_justificado_no_corre_retraso(): void
    {
        $t = $this->trabajadorConHorario('09:00', 5);
        ExcepcionDia::create([
            'empresa_id' => 1, 'trabajador_id' => $t->id, 'fecha' => '2026-05-25',
            'tipo' => 'justificado', 'registrada_por' => null,
        ]);
        $r = $this->estado($t, '10:30'); // muy pasada la hora, pero justificado
        $this->assertSame('justificado', $r['estado']);
        $this->assertSame(0, $r['minutos_atraso']);
        $this->assertTrue($r['tiene_excepcion']);
    }

    public function test_marcar_ausente_a_mano(): void
    {
        $t = $this->trabajadorConHorario('09:00', 5);
        ExcepcionDia::create([
            'empresa_id' => 1, 'trabajador_id' => $t->id, 'fecha' => '2026-05-25',
            'tipo' => 'ausente', 'registrada_por' => null,
        ]);
        $this->assertSame('ausente', $this->estado($t, '09:30')['estado']);
    }

    public function test_el_marcaje_gana_sobre_la_excepcion(): void
    {
        $t = $this->trabajadorConHorario('09:00', 5);
        ExcepcionDia::create([
            'empresa_id' => 1, 'trabajador_id' => $t->id, 'fecha' => '2026-05-25',
            'tipo' => 'justificado', 'registrada_por' => null,
        ]);
        // pero igual marcó → el marcaje manda
        Marcaje::create([
            'uuid' => (string) Str::uuid(), 'empresa_id' => 1, 'trabajador_id' => $t->id, 'tipo' => 'entrada',
            'ts_dispositivo' => $this->ahora('09:02'), 'ts_servidor' => $this->ahora('09:02'),
        ]);
        $r = $this->estado($t, '09:30');
        $this->assertSame('a_tiempo', $r['estado']);
        $this->assertTrue($r['marco']);
    }
}
