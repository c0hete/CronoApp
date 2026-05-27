<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Marcaje;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Reportes agregados (sección 10): minutos de atraso + costo por trabajador y total,
 * en un período. Solo cuentan los marcajes de ENTRADA (las salidas no tienen atraso).
 *
 * Presentación SIEMPRE como "costo de horas no trabajadas", nunca "descuento".
 */
class ReporteService
{
    /**
     * Color estable por trabajador (mismo id → mismo color siempre). Paleta fija,
     * legible, sin depender del color de marca (que tiñe el resto de la UI).
     */
    public static function colorTrabajador(int $id): string
    {
        $paleta = ['#2E75B6', '#E2231A', '#2E8B57', '#E0820C', '#7A3FB8', '#0E8C8C', '#C2185B', '#5D6D7E'];

        return $paleta[$id % count($paleta)];
    }

    /** Día de inicio de semana configurado (default lunes). */
    public function inicioSemana(): int
    {
        $dia = strtolower((string) Configuracion::valor('inicio_semana', config('crono.corte_semana', 'lunes')));

        return match ($dia) {
            'domingo' => CarbonInterface::SUNDAY,
            'lunes' => CarbonInterface::MONDAY,
            default => CarbonInterface::MONDAY,
        };
    }

    /** Rango [inicio, fin] de la semana que contiene a $fecha, según inicio_semana. */
    public function rangoSemana(CarbonInterface $fecha): array
    {
        $f = CarbonImmutable::parse($fecha);
        $inicio = $f->startOfWeek($this->inicioSemana());

        return [$inicio, $inicio->addDays(6)->endOfDay()];
    }

    /** Rango [inicio, fin] del mes que contiene a $fecha. */
    public function rangoMes(CarbonInterface $fecha): array
    {
        $f = CarbonImmutable::parse($fecha);

        return [$f->startOfMonth(), $f->endOfMonth()];
    }

    /**
     * Agrega los marcajes de entrada del rango por trabajador.
     *
     * @return array{
     *   filas: Collection<int, array{trabajador:string, marcajes:int, minutos:int, costo:string}>,
     *   total_minutos:int, total_costo:string, total_marcajes:int
     * }
     */
    public function agregarPorTrabajador(CarbonInterface $inicio, CarbonInterface $fin): array
    {
        $marcajes = Marcaje::with('trabajador')
            ->where('tipo', 'entrada')
            ->whereBetween('ts_dispositivo', [$inicio, $fin])
            ->get();

        $filas = $marcajes
            ->groupBy('trabajador_id')
            ->map(function (Collection $grupo, $trabajadorId) {
                return [
                    'trabajador_id' => (int) $trabajadorId,
                    'trabajador' => $grupo->first()->trabajador?->nombre ?? '—',
                    'color' => self::colorTrabajador((int) $trabajadorId),
                    'marcajes' => $grupo->count(),
                    'minutos' => (int) $grupo->sum('minutos_atraso'),
                    'costo' => number_format($grupo->sum(fn ($m) => (float) $m->costo_atraso), 2, '.', ''),
                ];
            })
            ->sortByDesc('costo')
            ->values();

        $totalCosto = $marcajes->sum(fn ($m) => (float) $m->costo_atraso);

        return [
            'filas' => $filas,
            'total_minutos' => (int) $marcajes->sum('minutos_atraso'),
            'total_costo' => number_format($totalCosto, 2, '.', ''),
            'total_marcajes' => $marcajes->count(),
            // Segmentos para la torta: solo trabajadores con costo > 0, con su % del total.
            'torta' => $totalCosto > 0
                ? $filas->filter(fn ($f) => (float) $f['costo'] > 0)
                    ->map(fn ($f) => [
                        'trabajador' => $f['trabajador'],
                        'color' => $f['color'],
                        'pct' => round((float) $f['costo'] / $totalCosto * 100, 1),
                    ])->values()
                : collect(),
        ];
    }
}
