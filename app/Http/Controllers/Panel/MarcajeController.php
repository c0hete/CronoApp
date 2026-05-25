<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Marcaje;
use App\Models\Trabajador;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Vista de marcajes para el dueño (Paso 7, versión lista). Presentación SIEMPRE
 * como "costo de horas no trabajadas", nunca "descuento".
 *
 * La foto-evidencia se sirve por ESTE controlador autorizado (no por URL pública):
 * está fuera de public, sin URLs adivinables.
 */
class MarcajeController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'trabajador_id' => ['nullable', 'integer'],
            'desde'         => ['nullable', 'date'],
            'hasta'         => ['nullable', 'date'],
        ]);

        $marcajes = Marcaje::with('trabajador')
            ->when($request->filled('trabajador_id'),
                fn ($q) => $q->where('trabajador_id', $request->integer('trabajador_id')))
            ->when($request->filled('desde'),
                fn ($q) => $q->whereDate('ts_dispositivo', '>=', $request->date('desde')))
            ->when($request->filled('hasta'),
                fn ($q) => $q->whereDate('ts_dispositivo', '<=', $request->date('hasta')))
            ->latest('ts_dispositivo')
            ->paginate(50)
            ->withQueryString();

        $trabajadores = Trabajador::orderBy('nombre')->get();

        return view('panel.marcajes.index', compact('marcajes', 'trabajadores'));
    }

    /**
     * Sirve la foto-evidencia de un marcaje. Autorizado (la ruta está tras auth+rol);
     * el scope BelongsToEmpresa garantiza que solo se accede a marcajes de la instancia.
     */
    public function foto(Marcaje $marcaje): StreamedResponse|Response
    {
        abort_if(blank($marcaje->foto_evidencia), 404);

        $disk = Storage::disk('fotos');
        abort_unless($disk->exists($marcaje->foto_evidencia), 404);

        return $disk->response($marcaje->foto_evidencia, headers: [
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
