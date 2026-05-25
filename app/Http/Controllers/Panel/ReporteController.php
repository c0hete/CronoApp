<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Services\ReporteService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard de reportes para el dueño (Paso 7 / sección 10): costo de horas no
 * trabajadas por trabajador y total, agrupado por semana (según inicio_semana) o mes.
 */
class ReporteController extends Controller
{
    public function index(Request $request, ReporteService $reportes): View
    {
        $request->validate([
            'periodo' => ['nullable', 'in:semanal,mensual'],
            'ref'     => ['nullable', 'date'],
        ]);

        $periodo = $request->input('periodo', 'mensual');
        $ref = CarbonImmutable::parse($request->input('ref', now()));

        [$inicio, $fin] = $periodo === 'semanal'
            ? $reportes->rangoSemana($ref)
            : $reportes->rangoMes($ref);

        $datos = $reportes->agregarPorTrabajador($inicio, $fin);

        // Navegación: período anterior / siguiente (para los botones de la vista)
        $anterior = $periodo === 'semanal' ? $ref->subWeek() : $ref->subMonth();
        $siguiente = $periodo === 'semanal' ? $ref->addWeek() : $ref->addMonth();

        $etiqueta = $periodo === 'semanal'
            ? 'Semana del ' . $inicio->format('d-m-Y') . ' al ' . $fin->format('d-m-Y')
            : ucfirst($inicio->locale('es')->isoFormat('MMMM YYYY'));

        return view('panel.reportes.index', [
            'periodo'   => $periodo,
            'etiqueta'  => $etiqueta,
            'datos'     => $datos,
            'ref'       => $ref->toDateString(),
            'anterior'  => $anterior->toDateString(),
            'siguiente' => $siguiente->toDateString(),
            'hoy'       => now()->toDateString(),
        ]);
    }
}
