<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz redirige al panel (el kiosko /marcar llega en el Paso 5).
     */
    public function test_la_raiz_redirige_al_panel(): void
    {
        $this->get('/')->assertRedirect('/panel');
    }
}
