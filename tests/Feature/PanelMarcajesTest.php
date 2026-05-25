<?php

namespace Tests\Feature;

use App\Models\Marcaje;
use App\Models\Trabajador;
use App\Models\User;
use App\Services\FotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Paso 7: el dueño ve las marcaciones y la foto-evidencia (servida autorizada,
 * nunca por URL pública). Incluye el fix de la ruta de foto (mes sin .jpg).
 */
class PanelMarcajesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function dueno(): User
    {
        $u = User::create(['name' => 'Dueño', 'email' => 'd@test.cl', 'password' => bcrypt('secret1234')]);
        $u->assignRole('dueno');
        return $u;
    }

    private function trabajador(): Trabajador
    {
        return Trabajador::create([
            'empresa_id' => 1, 'nombre' => 'Ana', 'tipo_id' => 'rut',
            'numero_id' => '111111111', 'activo' => true,
        ]);
    }

    private function marcaje(Trabajador $t, ?string $foto = null): Marcaje
    {
        return Marcaje::create([
            'uuid' => (string) Str::uuid(), 'empresa_id' => 1, 'trabajador_id' => $t->id,
            'tipo' => 'entrada', 'ts_dispositivo' => now(), 'ts_servidor' => now(),
            'foto_evidencia' => $foto, 'minutos_atraso' => 0, 'costo_atraso' => 0,
        ]);
    }

    public function test_ver_marcajes_requiere_login(): void
    {
        $this->get('/panel/marcajes')->assertRedirect('/login');
    }

    public function test_dueno_ve_la_lista_de_marcajes(): void
    {
        $t = $this->trabajador();
        $this->marcaje($t);

        $this->actingAs($this->dueno())
            ->get('/panel/marcajes')
            ->assertOk()
            ->assertSee('Ana')
            ->assertSee('Marcaciones')
            ->assertSeeText('Costo de horas no trabajadas') // lenguaje correcto, nunca "descuento"
            ->assertDontSee('descuento', false);
    }

    public function test_foto_requiere_login(): void
    {
        $t = $this->trabajador();
        $m = $this->marcaje($t, 'fake/ruta.jpg');
        $this->get("/panel/marcajes/{$m->id}/foto")->assertRedirect('/login');
    }

    public function test_foto_se_sirve_autorizada(): void
    {
        Storage::fake('fotos');
        $t = $this->trabajador();
        // crear una foto real en el disco fake
        Storage::disk('fotos')->put('1/2026/05/x.jpg', 'binario-jpeg-falso');
        $m = $this->marcaje($t, '1/2026/05/x.jpg');

        $this->actingAs($this->dueno())
            ->get("/panel/marcajes/{$m->id}/foto")
            ->assertOk();
    }

    public function test_foto_inexistente_da_404(): void
    {
        $t = $this->trabajador();
        $m = $this->marcaje($t, null); // sin foto
        $this->actingAs($this->dueno())
            ->get("/panel/marcajes/{$m->id}/foto")
            ->assertNotFound();
    }

    public function test_ruta_de_foto_no_mete_jpg_en_la_carpeta_del_mes(): void
    {
        // Regresión del bug: la carpeta del mes no debe llevar .jpg
        Storage::fake('fotos');
        $svc = new FotoService();
        // PNG 10x10 válido
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAIAAAACUFjqAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAAFElEQVQYlWM8kWLEgBsw4ZEbwdIAsw8Bcqq0vakAAAAASUVORK5CYII=');
        $ruta = $svc->guardar($png, 1, 'abc-123');

        // debe ser empresa/año/mes/uuid.jpg, con el mes SIN .jpg
        $this->assertMatchesRegularExpression('#^1/\d{4}/\d{2}/abc-123\.jpg$#', $ruta);
        $this->assertStringNotContainsString('.jpg/', $ruta); // el bug viejo metía 05.jpg/
    }
}
