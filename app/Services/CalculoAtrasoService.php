<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Contrato;
use Carbon\CarbonInterface;

/**
 * Cálculo de atraso y su costo (sección 6 de implementacion.md). Servicio PURO:
 * recibe datos, devuelve resultado, no toca DB ni HTTP. Así es fácil de testear
 * con casos límite — y este es EL cálculo crítico del producto: un error acá es
 * silencioso (no rompe nada visible, solo da cifras mal), y es el número por el
 * que el dueño paga y decide. Por eso lleva la batería de tests más grande.
 *
 * Presentación SIEMPRE como "costo de horas no trabajadas", nunca "descuento".
 */
class CalculoAtrasoService
{
    /**
     * Calcula el atraso de un marcaje de ENTRADA contra el contrato vigente.
     *
     * @return array{minutos_atraso:int, costo_atraso:string, sin_sueldo:bool, sueldo_usado:?string}
     *                                                                                               - minutos_atraso: minutos por encima de (hora_pactada + tolerancia); 0 si llegó a tiempo.
     *                                                                                               - costo_atraso: decimal string (2 dec) listo para persistir.
     *                                                                                               - sin_sueldo: true si el contrato no tiene ningún sueldo → costo 0 pero minutos sí cuentan.
     *                                                                                               - sueldo_usado: 'bruto' | 'liquido' | null (cuál se usó, para transparencia).
     */
    public function calcular(Contrato $contrato, CarbonInterface $tsDispositivo): array
    {
        $minutosAtraso = $this->minutosAtraso($contrato, $tsDispositivo);

        [$sueldo, $sueldoUsado] = $this->resolverSueldo($contrato);

        // Sin sueldo: los minutos de atraso igual se registran, pero el costo es 0.
        if ($sueldo === null) {
            return [
                'minutos_atraso' => $minutosAtraso,
                'costo_atraso' => '0.00',
                'sin_sueldo' => true,
                'sueldo_usado' => null,
            ];
        }

        $horasSemanales = (float) $contrato->horas_semanales;

        // Guarda defensiva: horas_semanales nunca debería ser 0 (validado al enrolar),
        // pero si lo fuera, no dividir por cero — costo 0 y marcar sin_sueldo a efectos prácticos.
        if ($horasSemanales <= 0) {
            return [
                'minutos_atraso' => $minutosAtraso,
                'costo_atraso' => '0.00',
                'sin_sueldo' => true,
                'sueldo_usado' => null,
            ];
        }

        // valor_hora (base SEMANAL) = sueldo / horas_semanales
        // costo = (minutos_atraso / 60) * valor_hora
        $valorHora = $sueldo / $horasSemanales;
        $costo = ($minutosAtraso / 60.0) * $valorHora;

        return [
            'minutos_atraso' => $minutosAtraso,
            'costo_atraso' => number_format($costo, 2, '.', ''),
            'sin_sueldo' => false,
            'sueldo_usado' => $sueldoUsado,
        ];
    }

    /**
     * Minutos de atraso = max(0, hora_marcaje - (hora_pactada + tolerancia)).
     * Solo cuenta la hora del día (no la fecha): comparar minutos-del-día.
     */
    public function minutosAtraso(Contrato $contrato, CarbonInterface $tsDispositivo): int
    {
        // hora_entrada_pactada viene como 'HH:MM:SS' (cast time).
        [$h, $m] = array_map('intval', explode(':', substr((string) $contrato->hora_entrada_pactada, 0, 5).':00'));
        $minutosPactados = $h * 60 + $m;

        $minutosLimite = $minutosPactados + (int) $contrato->tolerancia_min;

        $minutosMarcaje = $tsDispositivo->hour * 60 + $tsDispositivo->minute;

        return max(0, $minutosMarcaje - $minutosLimite);
    }

    /**
     * Elige el sueldo según base_calculo (config de la empresa):
     *   - base=bruto y bruto existe → bruto.
     *   - base=liquido y liquido existe → líquido.
     *   - el elegido es NULL pero el otro existe → usar el disponible (y reportar cuál).
     *   - ninguno → null (sin sueldo).
     *
     * @return array{0: ?float, 1: ?string} [monto, 'bruto'|'liquido'|null]
     */
    private function resolverSueldo(Contrato $contrato): array
    {
        $base = Configuracion::valor('base_calculo', config('crono.base_calculo', 'bruto'));

        $bruto = $contrato->sueldo_bruto !== null ? (float) $contrato->sueldo_bruto : null;
        $liquido = $contrato->sueldo_liquido !== null ? (float) $contrato->sueldo_liquido : null;

        $preferido = $base === 'liquido' ? $liquido : $bruto;
        $preferidoNombre = $base === 'liquido' ? 'liquido' : 'bruto';

        if ($preferido !== null) {
            return [$preferido, $preferidoNombre];
        }

        // El preferido no está; usar el otro si existe.
        $alternativo = $base === 'liquido' ? $bruto : $liquido;
        $alternativoNombre = $base === 'liquido' ? 'bruto' : 'liquido';

        if ($alternativo !== null) {
            return [$alternativo, $alternativoNombre];
        }

        return [null, null];
    }
}
