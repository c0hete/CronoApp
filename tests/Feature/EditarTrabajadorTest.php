<?php

namespace Tests\Feature;

use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditarTrabajadorTest extends TestCase
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

    private function trabajador(): Trabajador
    {
        return Trabajador::create([
            'empresa_id' => 1, 'nombre' => 'Ana', 'tipo_id' => 'rut',
            'numero_id' => '111111111', 'activo' => true,
        ]);
    }

    public function test_editar_requiere_login(): void
    {
        $t = $this->trabajador();
        $this->get("/panel/trabajadores/{$t->id}/editar")->assertRedirect('/login');
    }

    public function test_dueno_actualiza_datos_del_trabajador(): void
    {
        $t = $this->trabajador();

        $this->actingAs($this->dueno())
            ->withSession(['_token' => 'tok'])
            ->put("/panel/trabajadores/{$t->id}", [
                '_token' => 'tok',
                'nombre' => 'Ana María',
                'tipo_id' => 'rut',
                'numero_id' => '11.111.111-1', // con formato → se normaliza
                'activo' => '1',
            ])->assertRedirect(route('panel.trabajadores.index'));

        $t->refresh();
        $this->assertSame('Ana María', $t->nombre);
        $this->assertSame('111111111', $t->numero_id); // guardado normalizado
    }

    public function test_no_puede_dejar_rut_invalido(): void
    {
        $t = $this->trabajador();

        $this->actingAs($this->dueno())
            ->withSession(['_token' => 'tok'])
            ->put("/panel/trabajadores/{$t->id}", [
                '_token' => 'tok',
                'nombre' => 'Ana',
                'tipo_id' => 'rut',
                'numero_id' => '12.345.678-9', // dv malo
                'activo' => '1',
            ])->assertSessionHasErrors('numero_id');
    }
}
