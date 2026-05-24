<?php

namespace App\Http\Controllers\Kiosko;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Kiosko de marcaje (tablet). SIN login. Solo ID + cámara.
 *
 * Regla de aislamiento: esta vista expone CERO datos del dueño (ni reportes, ni
 * costos, ni listado de trabajadores). Solo el branding del negocio y el formulario.
 * El branding ya está disponible como $branding en todas las vistas (AppServiceProvider).
 */
class MarcarController extends Controller
{
    public function index(): View
    {
        return view('kiosko.marcar');
    }
}
