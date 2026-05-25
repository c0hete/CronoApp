<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Paso 11: PWA. El manifest es público (la tablet no tiene login) y refleja el
 * branding del cliente — nunca "Crono".
 */
class PwaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_manifest_es_publico_y_json(): void
    {
        $this->get('/marcar/manifest.webmanifest')
            ->assertOk()
            ->assertJsonStructure(['name', 'short_name', 'start_url', 'display', 'theme_color', 'icons']);
    }

    public function test_manifest_refleja_el_branding_no_crono(): void
    {
        Configuracion::poner('marca_nombre', 'Fugo Sushi');
        Configuracion::poner('marca_color_primario', '#E2231A');

        $r = $this->get('/marcar/manifest.webmanifest');
        $r->assertJsonPath('name', 'Fugo Sushi');
        $r->assertJsonPath('theme_color', '#E2231A');
        $this->assertStringNotContainsString('Crono', $r->getContent());
    }

    public function test_start_url_es_el_kiosko(): void
    {
        $this->get('/marcar/manifest.webmanifest')->assertJsonPath('start_url', '/marcar');
    }
}
