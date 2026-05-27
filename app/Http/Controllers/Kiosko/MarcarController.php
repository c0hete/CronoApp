<?php

namespace App\Http\Controllers\Kiosko;

use App\Http\Controllers\Controller;
use App\Services\BrandingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Logo del negocio para el kiosko. Público (la tablet no tiene login), pero NO
     * es dato sensible: es la marca pública del cliente (la misma que ve cualquiera
     * que entre al local). Sigue fuera de public/, servido por este controlador.
     */
    public function logo(BrandingService $branding): StreamedResponse|Response
    {
        $ruta = $branding->logo();
        abort_if($ruta === null, 404);

        $disk = Storage::disk('fotos');
        abort_unless($disk->exists($ruta), 404);

        return $disk->response($ruta, headers: ['Cache-Control' => 'public, max-age=300']);
    }

    /**
     * Manifest PWA dinámico: refleja el branding del cliente (nombre, color) para que,
     * instalada en la tablet, se vea como la app del negocio, no como "Crono".
     */
    public function manifest(BrandingService $branding): JsonResponse
    {
        $manifest = [
            'name' => $branding->nombre(),
            'short_name' => $branding->nombre(),
            'start_url' => '/marcar',
            'scope' => '/marcar',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#11151c',
            'theme_color' => $branding->colorPrimario(),
            'icons' => [
                ['src' => asset('icons/crono-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => asset('icons/crono-512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json');
    }
}
