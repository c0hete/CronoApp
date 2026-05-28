<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\ExcepcionDia;
use App\Models\Trabajador;
use App\Services\EstadoDiaService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Esperados hoy": a quién se espera hoy y su estado en vivo (pendiente / a tiempo /
 * atrasado-corriendo / ausente / justificado). El retraso corre desde la hora
 * esperada aunque el trabajador no haya marcado.
 *
 * Luis puede marcar a mano (dropdown):
 *   - "No viene hoy" (justificado) → no cuenta como falta.
 *   - "Marcar ausente" → falta injustificada.
 * Ambas reversibles. El marcaje real gana sobre la excepción.
 */
class EsperadosController extends Controller
{
    public function index(EstadoDiaService $estados): View
    {
        $ahora = CarbonImmutable::now(config('app.timezone'));

        return view('panel.esperados.index', [
            'esperados' => $estados->esperadosHoy($ahora),
            'ahora'     => $ahora,
        ]);
    }

    /** Crea/actualiza la excepción del día (justificado o ausente). */
    public function marcar(Request $request, Trabajador $trabajador): RedirectResponse
    {
        $data = $request->validate([
            'tipo'   => ['required', 'in:justificado,ausente'],
            'motivo' => ['nullable', 'string', 'max:120'],
        ]);

        ExcepcionDia::updateOrCreate(
            ['trabajador_id' => $trabajador->id, 'fecha' => now()->toDateString()],
            [
                'tipo'           => $data['tipo'],
                'motivo'         => $data['motivo'] ?? null,
                'registrada_por' => $request->user()->id,
            ],
        );

        return back()->with('status', 'Estado del día actualizado.');
    }

    /** Deshace la excepción del día (reversa). */
    public function deshacer(Trabajador $trabajador): RedirectResponse
    {
        ExcepcionDia::where('trabajador_id', $trabajador->id)
            ->whereDate('fecha', now()->toDateString())
            ->delete();

        return back()->with('status', 'Se quitó la marca del día.');
    }
}
