<?php

namespace Tests\Feature;

use App\Console\Commands\MonitorDisco;
use App\Models\Configuracion;
use App\Models\Marcaje;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Paso 9: comandos programados. Lo INNEGOCIABLE que estos tests blindan:
 *  - fotos:purgar borra SOLO la foto vieja, NUNCA el marcaje.
 *  - respeta la retención (no toca fotos recientes).
 *  - disco:monitor calcula bien y nunca borra (solo informa).
 */
class SchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('fotos');
    }

    private function marcajeConFoto(string $ruta, $tsServidor): Marcaje
    {
        $t = Trabajador::firstOrCreate(
            ['empresa_id' => 1, 'numero_id' => '111111111'],
            ['nombre' => 'Ana', 'tipo_id' => 'rut', 'activo' => true],
        );
        Storage::disk('fotos')->put($ruta, 'jpeg-falso');

        return Marcaje::create([
            'uuid' => (string) Str::uuid(), 'empresa_id' => 1, 'trabajador_id' => $t->id,
            'tipo' => 'entrada', 'ts_dispositivo' => $tsServidor, 'ts_servidor' => $tsServidor,
            'foto_evidencia' => $ruta, 'minutos_atraso' => 0, 'costo_atraso' => 0,
        ]);
    }

    public function test_purga_foto_vieja_pero_conserva_el_marcaje(): void
    {
        Configuracion::poner('retencion_fotos_dias', '60');
        $viejo = $this->marcajeConFoto('1/2026/01/a.jpg', now()->subDays(90));

        $this->artisan('fotos:purgar')->assertExitCode(0);

        // la foto física se borró y la referencia quedó null...
        Storage::disk('fotos')->assertMissing('1/2026/01/a.jpg');
        $this->assertNull($viejo->fresh()->foto_evidencia);
        // ...pero el MARCAJE PERMANECE (regla innegociable)
        $this->assertDatabaseHas('marcajes', ['id' => $viejo->id]);
    }

    public function test_no_purga_fotos_dentro_de_la_retencion(): void
    {
        Configuracion::poner('retencion_fotos_dias', '60');
        $reciente = $this->marcajeConFoto('1/2026/05/b.jpg', now()->subDays(10));

        $this->artisan('fotos:purgar')->assertExitCode(0);

        // foto reciente intacta
        Storage::disk('fotos')->assertExists('1/2026/05/b.jpg');
        $this->assertSame('1/2026/05/b.jpg', $reciente->fresh()->foto_evidencia);
    }

    public function test_dry_run_no_borra_nada(): void
    {
        $m = $this->marcajeConFoto('1/2026/01/c.jpg', now()->subDays(90));

        $this->artisan('fotos:purgar --dry-run')->assertExitCode(0);

        Storage::disk('fotos')->assertExists('1/2026/01/c.jpg');
        $this->assertNotNull($m->fresh()->foto_evidencia);
    }

    public function test_monitor_disco_calcula_porcentaje(): void
    {
        // 100 GB total, 10 GB libres → 90% usado
        $this->assertSame(90.0, MonitorDisco::porcentajeUsado(100e9, 10e9));
        $this->assertSame(0.0, MonitorDisco::porcentajeUsado(0, 0)); // sin división por cero
        $this->assertSame(25.0, MonitorDisco::porcentajeUsado(200, 150));
    }

    public function test_monitor_disco_corre_sin_borrar(): void
    {
        $m = $this->marcajeConFoto('1/2026/05/d.jpg', now());
        $this->artisan('disco:monitor')->assertExitCode(0);
        // no tocó nada
        $this->assertDatabaseHas('marcajes', ['id' => $m->id]);
        Storage::disk('fotos')->assertExists('1/2026/05/d.jpg');
    }
}
