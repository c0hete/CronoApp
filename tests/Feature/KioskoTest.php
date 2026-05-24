<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\Marcaje;
use App\Models\Trabajador;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Kiosko de marcaje (Paso 5). Garantías: acceso sin login y aislamiento
 * (no expone datos del dueño). El flujo de marcaje en sí lo cubre MarcajeApiTest.
 */
class KioskoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_marcar_es_publico_sin_login(): void
    {
        $this->get('/marcar')
            ->assertOk()
            ->assertSee('Entrada')
            ->assertSee('Salida');
    }

    public function test_kiosko_no_expone_datos_del_dueno(): void
    {
        $html = $this->get('/marcar')->getContent();

        // Aislamiento: la tablet nunca muestra costos, reportes ni el panel.
        foreach (['costo', 'reporte', 'sueldo', 'dashboard', 'logout'] as $prohibido) {
            $this->assertStringNotContainsStringIgnoringCase($prohibido, $html);
        }
    }

    public function test_kiosko_muestra_marca_no_crono(): void
    {
        // Branding agnóstico: con marca_nombre vacío usa el fallback genérico, nunca "Crono".
        $html = $this->get('/marcar')->getContent();
        $this->assertStringNotContainsString('Crono', $html);
    }

    public function test_flujo_end_to_end_marcaje_desde_payload_del_kiosko(): void
    {
        $t = Trabajador::create([
            'empresa_id' => 1, 'nombre' => 'Ana', 'tipo_id' => 'rut',
            'numero_id' => '11.111.111-1', 'activo' => true,
        ]);
        Contrato::create([
            'empresa_id' => 1, 'trabajador_id' => $t->id, 'sueldo_bruto' => 450000,
            'horas_semanales' => 45, 'hora_entrada_pactada' => '09:00:00',
            'tolerancia_min' => 5, 'vigente_desde' => '2026-01-01',
        ]);

        // payload tal como lo arma el JS del kiosko (sin foto para no depender de GD acá)
        $this->postJson('/api/marcar', [
            'uuid'           => (string) Str::uuid(),
            'numero_id'      => '11.111.111-1',
            'tipo'           => 'entrada',
            'ts_dispositivo' => '2026-05-25T09:03:00',
        ])->assertCreated()->assertJsonPath('trabajador', 'Ana');

        $this->assertDatabaseCount('marcajes', 1);
    }
}
