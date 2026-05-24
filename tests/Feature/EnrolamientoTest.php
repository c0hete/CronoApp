<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrolamientoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seeds base: roles + empresa + configuraciones.
        $this->seed();
    }

    private function dueno(): User
    {
        $u = User::create([
            'name'     => 'Dueño',
            'email'    => 'd@test.cl',
            'password' => bcrypt('secret1234'),
        ]);
        $u->assignRole('dueno');

        return $u;
    }

    /**
     * POST a enrolamiento como dueño, con token CSRF válido (mismo en sesión y payload).
     */
    private function enrolar(array $overrides = [])
    {
        $payload = array_merge([
            'nombre'               => 'Ana Pérez',
            'tipo_id'              => 'rut',
            'numero_id'            => '11.111.111-1',
            'sueldo_bruto'         => 700000,
            'horas_semanales'      => 45,
            'hora_entrada_pactada' => '09:00',
            'tolerancia_min'       => 5,
            'vigente_desde'        => now()->toDateString(),
            '_token'               => 'test-token',
        ], $overrides);

        return $this->actingAs($this->dueno())
            ->withSession(['_token' => 'test-token'])
            ->post('/panel/trabajadores', $payload);
    }

    public function test_panel_redirige_a_login_sin_auth(): void
    {
        $this->get('/panel/trabajadores')->assertRedirect('/login');
    }

    public function test_dueno_puede_enrolar_trabajador_con_contrato(): void
    {
        $this->enrolar()->assertRedirect(route('panel.trabajadores.index'));

        $this->assertDatabaseHas('trabajadores', ['numero_id' => '11.111.111-1', 'empresa_id' => 1]);

        $trabajador = Trabajador::first();
        $this->assertSame(1, Contrato::where('trabajador_id', $trabajador->id)->count());
        $this->assertNull($trabajador->contratoVigente()->vigente_hasta);
    }

    public function test_rechaza_rut_con_dv_invalido(): void
    {
        $this->enrolar([
            'numero_id' => '12.345.678-9', // dv malo
        ])->assertSessionHasErrors('numero_id');

        $this->assertDatabaseCount('trabajadores', 0);
    }

    public function test_exige_al_menos_un_sueldo(): void
    {
        $this->enrolar([
            'tipo_id'        => 'pasaporte',
            'numero_id'      => 'AB123456',
            'sueldo_bruto'   => null,
            'sueldo_liquido' => null,
        ])->assertSessionHasErrors('sueldo_bruto');

        $this->assertDatabaseCount('trabajadores', 0);
    }
}
