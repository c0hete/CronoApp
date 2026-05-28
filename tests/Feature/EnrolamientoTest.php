<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\Horario;
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
        // firstOrCreate: idempotente, así llamarlo varias veces en un test no choca
        // con la unicidad del email (p. ej. enrolar() ya lo usa internamente).
        $u = User::firstOrCreate(
            ['email' => 'd@test.cl'],
            ['name' => 'Dueño', 'password' => bcrypt('secret1234')],
        );
        if (! $u->hasRole('dueno')) {
            $u->assignRole('dueno');
        }

        return $u;
    }

    /**
     * POST a enrolamiento como dueño, con token CSRF válido (mismo en sesión y payload).
     */
    private function enrolar(array $overrides = [])
    {
        $payload = array_merge([
            'nombre' => 'Ana Pérez',
            'tipo_id' => 'rut',
            'numero_id' => '11.111.111-1',
            'sueldo_bruto' => 700000,
            'horas_semanales' => 45,
            'hora_entrada_pactada' => '09:00',
            'tolerancia_min' => 5,
            'vigente_desde' => now()->toDateString(),
            '_token' => 'test-token',
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

        // se guarda normalizado (sin puntos/guión), aunque se ingrese con formato
        $this->assertDatabaseHas('trabajadores', ['numero_id' => '111111111', 'empresa_id' => 1]);

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
            'tipo_id' => 'pasaporte',
            'numero_id' => 'AB123456',
            'sueldo_bruto' => null,
            'sueldo_liquido' => null,
        ])->assertSessionHasErrors('sueldo_bruto');

        $this->assertDatabaseCount('trabajadores', 0);
    }

    public function test_enrolar_con_dias_guarda_el_horario(): void
    {
        $this->enrolar([
            'dias' => ['1', '3', '5'], // lun, mié, vie
            'hora' => [1 => '09:00', 3 => '10:00', 5 => '09:00'],
        ])->assertRedirect(route('panel.trabajadores.index'));

        $t = Trabajador::first();
        $this->assertSame(3, Horario::where('trabajador_id', $t->id)->count());
        $this->assertDatabaseHas('horarios', ['trabajador_id' => $t->id, 'dia_semana' => 3, 'hora_entrada' => '10:00']);
    }

    /**
     * Regresión: el endpoint de horarios devolvía 500 (faltaba importar Request en el
     * controlador). Acá guarda y sincroniza (crea los marcados, borra los desmarcados).
     */
    public function test_actualizar_horario_no_revienta_y_sincroniza(): void
    {
        $this->enrolar()->assertRedirect();
        $t = Trabajador::first();
        $dueno = $this->dueno();

        // Primer guardado: lun + mié.
        $this->actingAs($dueno)
            ->withSession(['_token' => 'test-token'])
            ->put("/panel/trabajadores/{$t->id}/horarios", [
                '_token' => 'test-token',
                'dias' => ['1', '3'],
                'hora' => [1 => '09:00', 3 => '09:00'],
            ])
            ->assertRedirect(route('panel.trabajadores.edit', $t))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Horario::where('trabajador_id', $t->id)->count());

        // Segundo guardado: solo viernes → debe borrar lun y mié.
        $this->actingAs($dueno)
            ->withSession(['_token' => 'test-token'])
            ->put("/panel/trabajadores/{$t->id}/horarios", [
                '_token' => 'test-token',
                'dias' => ['5'],
                'hora' => [5 => '08:30'],
            ])->assertRedirect();

        $this->assertSame(1, Horario::where('trabajador_id', $t->id)->count());
        $this->assertDatabaseHas('horarios', ['trabajador_id' => $t->id, 'dia_semana' => 5, 'hora_entrada' => '08:30']);
    }
}
