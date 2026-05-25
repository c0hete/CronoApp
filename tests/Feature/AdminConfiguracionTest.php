<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Paso 10: configuración técnica de la instancia.
 */
class AdminConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function usuario(string $rol): User
    {
        $u = User::create(['name' => 'U', 'email' => "$rol@test.cl", 'password' => bcrypt('secret1234')]);
        $u->assignRole($rol);
        return $u;
    }

    public function test_requiere_login(): void
    {
        $this->get('/admin/configuracion')->assertRedirect('/login');
    }

    public function test_admin_ve_la_config(): void
    {
        $this->actingAs($this->usuario('admin'))
            ->get('/admin/configuracion')
            ->assertOk()
            ->assertSeeText('Configuración técnica')
            ->assertSeeText('Retención de fotos (días)');
    }

    public function test_dueno_tambien_puede_entrar_delegable(): void
    {
        // la config técnica es delegable al dueño
        $this->actingAs($this->usuario('dueno'))->get('/admin/configuracion')->assertOk();
    }

    public function test_guarda_configuracion_valida(): void
    {
        $this->actingAs($this->usuario('admin'))
            ->withSession(['_token' => 'tok'])
            ->put('/admin/configuracion', [
                '_token' => 'tok',
                'retencion_fotos_dias' => '30',
                'umbral_disco_alerta'  => '85',
                'reloj_tolerancia_min' => '10',
                'foto_ancho_px'        => '800',
                'foto_calidad'         => '75',
                'foto_rotacion'        => '0',
            ])->assertRedirect();

        $this->assertSame('30', Configuracion::valor('retencion_fotos_dias'));
        $this->assertSame('85', Configuracion::valor('umbral_disco_alerta'));
        $this->assertSame('10', Configuracion::valor('reloj_tolerancia_min'));
    }

    public function test_rechaza_valores_fuera_de_rango(): void
    {
        $this->actingAs($this->usuario('admin'))
            ->withSession(['_token' => 'tok'])
            ->put('/admin/configuracion', [
                '_token' => 'tok',
                'retencion_fotos_dias' => '0',     // min 1
                'umbral_disco_alerta'  => '200',   // max 99
                'reloj_tolerancia_min' => '10',
                'foto_ancho_px'        => '800',
                'foto_calidad'         => '75',
                'foto_rotacion'        => '45',    // no permitido
            ])->assertSessionHasErrors(['retencion_fotos_dias', 'umbral_disco_alerta', 'foto_rotacion']);
    }
}
