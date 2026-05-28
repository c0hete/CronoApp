<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Trabajador;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Estado "en vivo" del día de cada trabajador (vista "Esperados hoy").
 *
 * La clave del producto: el retraso CORRE desde la hora esperada del día, marque o
 * no el trabajador. Así Luis ve que alguien debía entrar a las 21:00 y a las 21:10
 * ya está atrasado, ANTES de que marque. Cuando marca, se cierra con el total real.
 *
 * El estado se calcula en el momento de mirar (contra now()) — sin procesos en
 * background. Es lógica de lectura; no persiste nada.
 *
 * Estados posibles:
 *   - 'justificado' : Luis marcó "no viene hoy" (excepción) → no cuenta retraso/ausencia.
 *   - 'a_tiempo'    : marcó dentro de la tolerancia.
 *   - 'atrasado'    : marcó pasada la tolerancia (minutos cerrados) — o aún no marca
 *                     pero ya pasó su hora+tolerancia (minutos corriendo, en_curso=true).
 *   - 'pendiente'   : tiene horario hoy, aún no es su hora (o dentro de tolerancia sin marcar).
 *   - 'ausente'     : pasó la hora por más que `ausente_tras_min` y nunca marcó.
 */
class EstadoDiaService
{
    /**
     * Estado de un trabajador en una fecha/hora dadas.
     *
     * @return array{
     *   estado:string, minutos_atraso:int, en_curso:bool,
     *   hora_esperada:?string, marco:bool, justificado:bool
     * }
     */
    public function estado(Trabajador $trabajador, CarbonImmutable $ahora): array
    {
        $hoyIso = $ahora->dayOfWeekIso;
        $horario = $trabajador->horarioDelDia($hoyIso);

        $base = [
            'estado' => 'sin_horario', 'minutos_atraso' => 0, 'en_curso' => false,
            'hora_esperada' => null, 'marco' => false, 'justificado' => false,
            'tiene_excepcion' => false,
        ];

        // No se le espera hoy (no tiene horario ese día).
        if ($horario === null) {
            return $base;
        }

        $horaEsperada = substr((string) $horario->hora_entrada, 0, 5); // "HH:MM"
        $base['hora_esperada'] = $horaEsperada;

        // ¿Excepción marcada a mano por Luis? (justificado o ausente)
        $excepcion = $trabajador->excepciones
            ->first(fn ($e) => $e->fecha->isSameDay($ahora));
        if ($excepcion) {
            // El marcaje real GANA: si igual marcó, no respetamos la excepción (sigue abajo).
            $yaMarco = $trabajador->marcajes
                ->where('tipo', 'entrada')
                ->contains(fn ($m) => $m->ts_dispositivo->isSameDay($ahora));
            if (! $yaMarco) {
                return [
                    ...$base,
                    'estado' => $excepcion->tipo, // 'justificado' | 'ausente'
                    'justificado' => $excepcion->tipo === 'justificado',
                    'tiene_excepcion' => true,
                ];
            }
        }

        // ¿Ya marcó entrada hoy? (el marcaje real GANA)
        $entrada = $trabajador->marcajes
            ->where('tipo', 'entrada')
            ->first(fn ($m) => $m->ts_dispositivo->isSameDay($ahora));

        $tolerancia = $horario->tolerancia_min
            ?? (int) optional($trabajador->contratoVigente())->tolerancia_min
            ?? 0;

        [$h, $m] = array_map('intval', explode(':', $horaEsperada));
        $limite = $ahora->setTime($h, $m)->addMinutes($tolerancia);

        if ($entrada) {
            // marcó: estado cerrado contra el momento del marcaje
            $minutos = max(0, $limite->diffInMinutes($entrada->ts_dispositivo, false));

            return [
                ...$base, 'marco' => true,
                'estado' => $minutos > 0 ? 'atrasado' : 'a_tiempo',
                'minutos_atraso' => (int) $minutos, 'en_curso' => false,
            ];
        }

        // NO marcó todavía → estado contra el reloj actual
        if ($ahora->lessThanOrEqualTo($limite)) {
            return [...$base, 'estado' => 'pendiente'];
        }

        $minutosCorriendo = (int) $limite->diffInMinutes($ahora);
        // Ausente automático tras 1 hora de la hora esperada sin marcar (configurable).
        // (Luis también puede marcar la ausencia/excepción a mano antes — ver "No viene hoy".)
        $umbralAusente = (int) Configuracion::valor('ausente_tras_min', '60');

        if ($minutosCorriendo > $umbralAusente) {
            return [...$base, 'estado' => 'ausente', 'minutos_atraso' => $minutosCorriendo, 'en_curso' => true];
        }

        // atrasado, contador corriendo, aún sin marcar
        return [...$base, 'estado' => 'atrasado', 'minutos_atraso' => $minutosCorriendo, 'en_curso' => true];
    }

    /**
     * Los trabajadores que se esperan hoy (tienen horario para el día), con su estado.
     *
     * @return Collection<int, array{trabajador:Trabajador, ...}>
     */
    public function esperadosHoy(CarbonImmutable $ahora): Collection
    {
        $hoyIso = $ahora->dayOfWeekIso;

        return Trabajador::with(['horarios', 'excepciones', 'marcajes', 'contratos'])
            ->where('activo', true)
            ->get()
            ->filter(fn (Trabajador $t) => $t->horarioDelDia($hoyIso) !== null)
            ->map(fn (Trabajador $t) => ['trabajador' => $t] + $this->estado($t, $ahora))
            ->sortBy('hora_esperada')
            ->values();
    }
}
