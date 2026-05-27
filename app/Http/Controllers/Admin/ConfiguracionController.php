<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Configuración técnica de la instancia (Paso 10). Retención de fotos, umbral de
 * disco, tolerancia de reloj, parámetros de foto. Rol admin (delegable al dueño).
 *
 * NO toca branding (eso es /panel/personalizacion) ni datos de negocio.
 */
class ConfiguracionController extends Controller
{
    /** Claves técnicas editables, con sus reglas de validación y etiqueta. */
    private const CAMPOS = [
        'retencion_fotos_dias' => ['regla' => ['required', 'integer', 'min:1', 'max:3650'], 'label' => 'Retención de fotos (días)'],
        'umbral_disco_alerta' => ['regla' => ['required', 'integer', 'min:50', 'max:99'],  'label' => 'Umbral de alerta de disco (%)'],
        'reloj_tolerancia_min' => ['regla' => ['required', 'integer', 'min:0', 'max:120'],  'label' => 'Tolerancia de reloj (min)'],
        'foto_ancho_px' => ['regla' => ['required', 'integer', 'min:240', 'max:1920'], 'label' => 'Ancho de foto (px)'],
        'foto_calidad' => ['regla' => ['required', 'integer', 'min:30', 'max:95'],   'label' => 'Calidad de foto (1-100)'],
        'foto_rotacion' => ['regla' => ['required', 'integer', 'in:0,90,180,270'],    'label' => 'Rotación de foto (grados)'],
    ];

    public function edit(): View
    {
        $valores = [];
        foreach (self::CAMPOS as $clave => $def) {
            $valores[$clave] = ['valor' => Configuracion::valor($clave, ''), 'label' => $def['label']];
        }

        return view('admin.configuracion.edit', compact('valores'));
    }

    public function update(Request $request): RedirectResponse
    {
        $reglas = [];
        foreach (self::CAMPOS as $clave => $def) {
            $reglas[$clave] = $def['regla'];
        }
        $data = $request->validate($reglas);

        foreach ($data as $clave => $valor) {
            Configuracion::poner($clave, (string) $valor);
        }

        return back()->with('status', 'Configuración técnica actualizada.');
    }
}
