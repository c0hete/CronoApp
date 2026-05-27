<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandingRequest;
use App\Models\Configuracion;
use App\Services\BrandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Branding en autoservicio del dueño (Paso 8). Edita nombre/color/logo.
 * El logo se guarda fuera de public (disco 'fotos') y se sirve por este
 * controlador autorizado — nunca por URL pública adivinable.
 */
class BrandingController extends Controller
{
    public function edit(BrandingService $branding): View
    {
        return view('panel.branding.edit', [
            'nombre' => Configuracion::valor('marca_nombre', ''),
            'color' => $branding->colorPrimario(),
            'logo' => $branding->logo(),
        ]);
    }

    public function update(BrandingRequest $request): RedirectResponse
    {
        Configuracion::poner('marca_nombre', trim($request->input('marca_nombre')));
        Configuracion::poner('marca_color_primario', strtoupper($request->input('marca_color_primario')));

        if ($request->hasFile('logo')) {
            $empresaId = (int) config('crono.empresa_id', 1);
            $ext = $request->file('logo')->getClientOriginalExtension() ?: 'png';

            // borrar logo anterior si existía (no acumular)
            $anterior = Configuracion::valor('marca_logo', '');
            if ($anterior !== '' && Storage::disk('fotos')->exists($anterior)) {
                Storage::disk('fotos')->delete($anterior);
            }

            $ruta = $request->file('logo')->storeAs("branding/{$empresaId}", 'logo.'.strtolower($ext), 'fotos');
            Configuracion::poner('marca_logo', $ruta);
        }

        return back()->with('status', 'Branding actualizado.');
    }

    /**
     * Sirve el logo (fuera de public). Tras auth+rol; el scope garantiza la instancia.
     */
    public function logo(BrandingService $branding): StreamedResponse|Response
    {
        $ruta = $branding->logo();
        abort_if($ruta === null, 404);

        $disk = Storage::disk('fotos');
        abort_unless($disk->exists($ruta), 404);

        return $disk->response($ruta, headers: ['Cache-Control' => 'private, max-age=300']);
    }
}
