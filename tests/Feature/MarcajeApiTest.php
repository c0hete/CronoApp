<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\Marcaje;
use App\Models\Trabajador;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Integración del endpoint /api/marcar (sub-pasos 4a + 4c + 4d, con 4b en juego).
 */
class MarcajeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function trabajador(): Trabajador
    {
        $t = Trabajador::create([
            'empresa_id' => 1,
            'nombre' => 'Ana Pérez',
            'tipo_id' => 'rut',
            'numero_id' => '111111111', // canónico (como lo deja el form normalizado)
            'activo' => true,
        ]);
        Contrato::create([
            'empresa_id' => 1,
            'trabajador_id' => $t->id,
            'sueldo_bruto' => 450000,
            'horas_semanales' => 45,
            'hora_entrada_pactada' => '09:00:00',
            'tolerancia_min' => 5,
            'vigente_desde' => '2026-01-01',
        ]);

        return $t;
    }

    private function payload(array $o = []): array
    {
        return array_merge([
            'uuid' => (string) Str::uuid(),
            'numero_id' => '11.111.111-1',
            'tipo' => 'entrada',
            'ts_dispositivo' => Carbon::now()->toIso8601String(),
        ], $o);
    }

    // --- 4a: idempotencia ---
    public function test_doble_marcaje_mismo_uuid_no_duplica(): void
    {
        $this->trabajador();
        $uuid = (string) Str::uuid();
        $p = $this->payload(['uuid' => $uuid]);

        $r1 = $this->postJson('/api/marcar', $p);
        $r1->assertCreated()->assertJsonPath('duplicado', false);

        // mismo uuid otra vez (reintento de sync) → 200, no crea otro
        $r2 = $this->postJson('/api/marcar', $p);
        $r2->assertOk()->assertJsonPath('duplicado', true);

        $this->assertSame(1, Marcaje::where('uuid', $uuid)->count());
    }

    public function test_trabajador_inexistente_es_rechazado(): void
    {
        $this->postJson('/api/marcar', $this->payload(['numero_id' => '99.999.999-9']))
            ->assertStatus(422);
        $this->assertDatabaseCount('marcajes', 0);
    }

    public function test_kiosko_matchea_aunque_el_formato_difiera(): void
    {
        // Enrolado canónico (como lo deja el form normalizado): sin puntos/guión.
        $t = Trabajador::create([
            'empresa_id' => 1, 'nombre' => 'Ana', 'tipo_id' => 'rut',
            'numero_id' => '111111111', 'activo' => true,
        ]);
        Contrato::create([
            'empresa_id' => 1, 'trabajador_id' => $t->id, 'sueldo_bruto' => 450000,
            'horas_semanales' => 45, 'hora_entrada_pactada' => '09:00:00',
            'tolerancia_min' => 5, 'vigente_desde' => '2026-01-01',
        ]);

        // El kiosko teclea sin guión (no tiene la tecla) → debe matchear igual.
        $this->postJson('/api/marcar', $this->payload([
            'numero_id' => '111111111',
        ]))->assertCreated()->assertJsonPath('trabajador', 'Ana');

        // Y también si por algún canal llega con puntos/guión → normaliza y matchea.
        $this->postJson('/api/marcar', $this->payload([
            'uuid' => (string) Str::uuid(),
            'numero_id' => '11.111.111-1',
        ]))->assertCreated()->assertJsonPath('trabajador', 'Ana');
    }

    // --- 4d: doble timestamp + reloj_sospechoso ---
    public function test_ts_dentro_de_tolerancia_no_marca_reloj_sospechoso(): void
    {
        $this->trabajador();
        $this->postJson('/api/marcar', $this->payload([
            'ts_dispositivo' => Carbon::now()->toIso8601String(),
        ]))->assertJsonPath('reloj_sospechoso', false);
    }

    public function test_ts_desfasado_marca_reloj_sospechoso(): void
    {
        $this->trabajador();
        // tolerancia seed = 5 min; desfase de 30 min debe levantar el flag.
        $this->postJson('/api/marcar', $this->payload([
            'ts_dispositivo' => Carbon::now()->subMinutes(30)->toIso8601String(),
        ]))->assertJsonPath('reloj_sospechoso', true);
    }

    // --- 4b en juego: entrada calcula, salida no ---
    public function test_entrada_calcula_atraso(): void
    {
        $this->trabajador();
        // marca 10:05 contra pactada 09:00 + tol 5 = 60 min de atraso.
        $r = $this->postJson('/api/marcar', $this->payload([
            'tipo' => 'entrada',
            'ts_dispositivo' => '2026-05-25T10:05:00',
        ]));
        $r->assertCreated()->assertJsonPath('minutos_atraso', 60);
        $this->assertSame('10000.00', Marcaje::first()->costo_atraso);
    }

    public function test_salida_no_calcula_atraso(): void
    {
        $this->trabajador();
        $r = $this->postJson('/api/marcar', $this->payload([
            'tipo' => 'salida',
            'ts_dispositivo' => '2026-05-25T18:30:00',
        ]));
        $r->assertCreated()->assertJsonPath('minutos_atraso', 0);
        $this->assertSame(0, Marcaje::first()->minutos_atraso);
    }

    // --- 4c: foto fuera de public + no accesible por URL directa ---
    public function test_foto_se_guarda_fuera_de_public(): void
    {
        Storage::fake('fotos');
        $this->trabajador();

        // PNG 10x10 válido (data-uri); decodificable por GD.
        $png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAIAAAACUFjqAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAFElEQVQYlWM8kWLEgBsw4ZEbwdIAsw8Bcqq0vakAAAAASUVORK5CYII=';

        $r = $this->postJson('/api/marcar', $this->payload(['foto' => $png]));
        $r->assertCreated();

        $marcaje = Marcaje::first();
        $this->assertNotNull($marcaje->foto_evidencia);
        // se guardó en el disco 'fotos' (fuera de public)
        Storage::disk('fotos')->assertExists($marcaje->foto_evidencia);
        // la ruta NO está bajo public/
        $this->assertStringNotContainsString('public', $marcaje->foto_evidencia);
    }
}
