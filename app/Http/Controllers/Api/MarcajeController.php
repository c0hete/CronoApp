<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarcarRequest;
use App\Models\Configuracion;
use App\Models\Marcaje;
use App\Models\Trabajador;
use App\Services\CalculoAtrasoService;
use App\Services\FotoService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint de marcaje del kiosko (tablet). Sin login.
 *
 * Flujo (sección 7):
 *  - 4a idempotencia: si el uuid ya existe → 200 sin duplicar (reintentos de sync offline).
 *  - 4a resolución: trabajador por numero_id dentro de la empresa de la instancia.
 *  - 4d doble timestamp: ts_servidor = now(); si difiere de ts_dispositivo más que la
 *        tolerancia → flag reloj_sospechoso. El servidor es la fuente de verdad.
 *  - 4c foto: degradar y guardar fuera de public (si vino).
 *  - 4b cálculo: solo en 'entrada'; 'salida' no calcula atraso.
 */
class MarcajeController extends Controller
{
    public function __construct(
        private readonly CalculoAtrasoService $calculo,
        private readonly FotoService $fotos,
    ) {}

    public function store(MarcarRequest $request): JsonResponse
    {
        $data = $request->validated();
        $empresaId = (int) config('crono.empresa_id', 1);

        // --- 4a: idempotencia por uuid ---
        $existente = Marcaje::where('uuid', $data['uuid'])->first();
        if ($existente) {
            return response()->json([
                'ok'         => true,
                'duplicado'  => true,
                'marcaje_id' => $existente->id,
                'mensaje'    => 'Marcaje ya registrado.',
            ], 200);
        }

        // --- 4a: resolver trabajador por numero_id (en la empresa activa) ---
        $trabajador = Trabajador::where('numero_id', $data['numero_id'])
            ->where('activo', true)
            ->first();

        if (! $trabajador) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Trabajador no encontrado o inactivo.',
            ], 422);
        }

        // --- 4d: doble timestamp + flag reloj_sospechoso ---
        $tsDispositivo = Carbon::parse($data['ts_dispositivo']);
        $tsServidor = Carbon::now();
        $tolerancia = (int) Configuracion::valor('reloj_tolerancia_min', '5');
        $relojSospechoso = abs($tsServidor->diffInMinutes($tsDispositivo)) > $tolerancia;

        // --- 4c: foto-evidencia (si vino) ---
        $rutaFoto = null;
        if (! empty($data['foto'])) {
            $rutaFoto = $this->fotos->guardar($data['foto'], $empresaId, $data['uuid']);
        }

        // --- 4b: cálculo de atraso (solo entrada) ---
        $minutosAtraso = 0;
        $costoAtraso = '0.00';
        $infoCalculo = null;

        if ($data['tipo'] === 'entrada') {
            $contrato = $trabajador->contratoVigente();
            if ($contrato) {
                $r = $this->calculo->calcular($contrato, $tsDispositivo);
                $minutosAtraso = $r['minutos_atraso'];
                $costoAtraso   = $r['costo_atraso'];
                $infoCalculo   = $r;
            }
        }

        $marcaje = Marcaje::create([
            'uuid'             => $data['uuid'],
            'empresa_id'       => $empresaId,
            'trabajador_id'    => $trabajador->id,
            'tipo'             => $data['tipo'],
            'ts_dispositivo'   => $tsDispositivo,
            'ts_servidor'      => $tsServidor,
            'foto_evidencia'   => $rutaFoto,
            'minutos_atraso'   => $minutosAtraso,
            'costo_atraso'     => $costoAtraso,
            'reloj_sospechoso' => $relojSospechoso,
        ]);

        return response()->json([
            'ok'               => true,
            'duplicado'        => false,
            'marcaje_id'       => $marcaje->id,
            'trabajador'       => $trabajador->nombre,
            'tipo'             => $marcaje->tipo,
            'minutos_atraso'   => $minutosAtraso,
            'reloj_sospechoso' => $relojSospechoso,
            'calculo'          => $infoCalculo,
            'mensaje'          => 'Marcaje registrado.',
        ], 201);
    }
}
