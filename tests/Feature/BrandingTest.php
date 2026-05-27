<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\User;
use App\Services\BrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function dueno(): User
    {
        $u = User::create(['name' => 'D', 'email' => 'd@test.cl', 'password' => bcrypt('secret1234')]);
        $u->assignRole('dueno');

        return $u;
    }

    /** POST a branding con token CSRF válido (sesión + payload coinciden). */
    private function guardar(array $data)
    {
        return $this->actingAs($this->dueno())
            ->withSession(['_token' => 'tok'])
            ->post('/panel/personalizacion', array_merge(['_token' => 'tok'], $data));
    }

    public function test_branding_requiere_login(): void
    {
        $this->get('/panel/personalizacion')->assertRedirect('/login');
    }

    public function test_dueno_guarda_nombre_y_color(): void
    {
        $this->guardar([
            'marca_nombre' => 'Fugo Sushi',
            'marca_color_primario' => '#E2231A',
        ])->assertRedirect();

        $this->assertSame('Fugo Sushi', Configuracion::valor('marca_nombre'));
        $this->assertSame('#E2231A', Configuracion::valor('marca_color_primario'));
    }

    public function test_color_invalido_es_rechazado(): void
    {
        $this->guardar([
            'marca_nombre' => 'X',
            'marca_color_primario' => 'rojo',
        ])->assertSessionHasErrors('marca_color_primario');
    }

    public function test_branding_service_cae_a_default_si_el_color_guardado_es_invalido(): void
    {
        // Si por lo que sea quedó un color corrupto en config, la lectura lo sanea.
        Configuracion::poner('marca_color_primario', 'no-es-hex');
        $this->assertSame(BrandingService::COLOR_DEFAULT, app(BrandingService::class)->colorPrimario());
    }

    public function test_logo_valido_se_guarda_fuera_de_public(): void
    {
        Storage::fake('fotos');

        $this->guardar([
            'marca_nombre' => 'Fugo Sushi',
            'marca_color_primario' => '#E2231A',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 80),
        ])->assertRedirect();

        $ruta = Configuracion::valor('marca_logo');
        $this->assertNotEmpty($ruta);
        Storage::disk('fotos')->assertExists($ruta);
        $this->assertStringNotContainsString('public', $ruta);
    }

    public function test_logo_rechaza_tipo_no_permitido(): void
    {
        Storage::fake('fotos');

        $this->guardar([
            'marca_nombre' => 'X',
            'marca_color_primario' => '#E2231A',
            'logo' => UploadedFile::fake()->create('virus.pdf', 50, 'application/pdf'),
        ])->assertSessionHasErrors('logo');
    }

    public function test_logo_se_sirve_autorizado(): void
    {
        Storage::fake('fotos');
        Storage::disk('fotos')->put('branding/1/logo.png', 'png-falso');
        Configuracion::poner('marca_logo', 'branding/1/logo.png');

        $this->actingAs($this->dueno())->get('/panel/personalizacion/logo')->assertOk();
        // sin login, la ruta del panel redirige
        auth()->logout();
        $this->get('/panel/personalizacion/logo')->assertRedirect('/login');
    }
}
