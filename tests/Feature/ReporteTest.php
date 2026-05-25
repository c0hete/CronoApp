<?php

namespace Tests\Feature;

use App\Models\Marcaje;
use App\Models\Trabajador;
use App\Models\User;
use App\Services\ReporteService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Reportes agregados (sección 10). Lo crítico: que los totales SUMEN bien
 * (por trabajador y total) y que solo cuenten las entradas.
 */
class ReporteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function trabajador(string $nombre, string $rut): Trabajador
    {
        return Trabajador::create([
            'empresa_id' => 1, 'nombre' => $nombre, 'tipo_id' => 'rut',
            'numero_id' => $rut, 'activo' => true,
        ]);
    }

    private function marcaje(Trabajador $t, string $tipo, string $fecha, int $min, float $costo): void
    {
        Marcaje::create([
            'uuid' => (string) Str::uuid(), 'empresa_id' => 1, 'trabajador_id' => $t->id,
            'tipo' => $tipo, 'ts_dispositivo' => $fecha, 'ts_servidor' => $fecha,
            'minutos_atraso' => $min, 'costo_atraso' => $costo,
        ]);
    }

    public function test_suma_por_trabajador_y_total_solo_entradas(): void
    {
        $ana = $this->trabajador('Ana', '111111111');
        $leo = $this->trabajador('Leo', '222222222');

        // Ana: 2 entradas (10min/$1000 + 20min/$2000) + 1 salida (no debe contar)
        $this->marcaje($ana, 'entrada', '2026-05-04 09:10', 10, 1000);
        $this->marcaje($ana, 'entrada', '2026-05-05 09:20', 20, 2000);
        $this->marcaje($ana, 'salida',  '2026-05-05 18:00', 0, 0);
        // Leo: 1 entrada (5min/$500)
        $this->marcaje($leo, 'entrada', '2026-05-06 09:05', 5, 500);

        $svc = new ReporteService();
        [$ini, $fin] = $svc->rangoMes(CarbonImmutable::parse('2026-05-15'));
        $r = $svc->agregarPorTrabajador($ini, $fin);

        // total: 35 min, $3500, 3 marcajes de entrada (la salida NO cuenta)
        $this->assertSame(35, $r['total_minutos']);
        $this->assertSame('3500.00', $r['total_costo']);
        $this->assertSame(3, $r['total_marcajes']);

        // por trabajador (ordenado por costo desc → Ana primero)
        $ana_fila = $r['filas']->firstWhere('trabajador', 'Ana');
        $this->assertSame(30, $ana_fila['minutos']);     // 10+20
        $this->assertSame('3000.00', $ana_fila['costo']); // 1000+2000
        $this->assertSame(2, $ana_fila['marcajes']);      // 2 entradas (sin la salida)

        $leo_fila = $r['filas']->firstWhere('trabajador', 'Leo');
        $this->assertSame('500.00', $leo_fila['costo']);
    }

    public function test_corte_semanal_respeta_inicio_lunes(): void
    {
        $svc = new ReporteService();
        // 2026-05-13 es miércoles; la semana (lunes) va del 11 al 17.
        [$ini, $fin] = $svc->rangoSemana(CarbonImmutable::parse('2026-05-13'));
        $this->assertSame('2026-05-11', $ini->format('Y-m-d')); // lunes
        $this->assertSame('2026-05-17', $fin->format('Y-m-d')); // domingo
    }

    public function test_marcaje_fuera_del_rango_no_se_cuenta(): void
    {
        $ana = $this->trabajador('Ana', '111111111');
        $this->marcaje($ana, 'entrada', '2026-04-30 09:10', 10, 1000); // mes anterior

        $svc = new ReporteService();
        [$ini, $fin] = $svc->rangoMes(CarbonImmutable::parse('2026-05-15'));
        $r = $svc->agregarPorTrabajador($ini, $fin);

        $this->assertSame(0, $r['total_marcajes']);
        $this->assertSame('0.00', $r['total_costo']);
    }

    public function test_reportes_requiere_login(): void
    {
        $this->get('/panel/reportes')->assertRedirect('/login');
    }

    public function test_dueno_ve_el_dashboard(): void
    {
        $u = User::create(['name' => 'D', 'email' => 'd@test.cl', 'password' => bcrypt('secret1234')]);
        $u->assignRole('dueno');

        $this->actingAs($u)->get('/panel/reportes')
            ->assertOk()
            ->assertSeeText('Costo de horas no trabajadas')
            ->assertDontSee('descuento', false);
    }
}
