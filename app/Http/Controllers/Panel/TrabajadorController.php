<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\EditarTrabajadorRequest;
use App\Http\Requests\EnrolarTrabajadorRequest;
use App\Models\Contrato;
use App\Models\Horario;
use App\Models\Trabajador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Enrolamiento de trabajadores (panel del dueño). El trabajador es entidad de
 * datos, no usuario. Crear un trabajador crea también su primer contrato vigente.
 * El scope BelongsToEmpresa garantiza que todo queda bajo la empresa de la instancia.
 */
class TrabajadorController extends Controller
{
    public function index(): View
    {
        $trabajadores = Trabajador::with([
            'contratos' => fn ($q) => $q->whereNull('vigente_hasta'),
            'horarios',
        ])
            ->orderBy('nombre')
            ->get();

        return view('panel.trabajadores.index', compact('trabajadores'));
    }

    public function create(): View
    {
        return view('panel.trabajadores.create');
    }

    public function store(EnrolarTrabajadorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            // empresa_id lo inyecta el trait BelongsToEmpresa al crear.
            $trabajador = Trabajador::create([
                'nombre' => $data['nombre'],
                'tipo_id' => $data['tipo_id'],
                'numero_id' => $data['numero_id'],
                'activo' => true,
            ]);

            Contrato::create([
                'trabajador_id' => $trabajador->id,
                'sueldo_bruto' => $data['sueldo_bruto'] ?? null,
                'sueldo_liquido' => $data['sueldo_liquido'] ?? null,
                'horas_semanales' => $data['horas_semanales'],
                'hora_entrada_pactada' => $data['hora_entrada_pactada'],
                'tolerancia_min' => $data['tolerancia_min'],
                'vigente_desde' => $data['vigente_desde'],
                'vigente_hasta' => null, // vigente
            ]);

            // Días de la semana que trabaja (se eligen ya al enrolar).
            $this->sincronizarHorario($trabajador, $data['dias'] ?? [], $data['hora'] ?? []);
        });

        return redirect()
            ->route('panel.trabajadores.index')
            ->with('status', 'Trabajador enrolado correctamente.');
    }

    public function edit(Trabajador $trabajador): View
    {
        return view('panel.trabajadores.edit', compact('trabajador'));
    }

    public function update(EditarTrabajadorRequest $request, Trabajador $trabajador): RedirectResponse
    {
        $trabajador->update($request->validated());

        return redirect()
            ->route('panel.trabajadores.index')
            ->with('status', 'Datos del trabajador actualizados.');
    }

    /**
     * Guarda el horario semanal esperado: por cada día (1=lun…7=dom), si está
     * activo, su hora de entrada. Sincroniza la tabla horarios (crea/actualiza/borra).
     */
    public function horarios(Request $request, Trabajador $trabajador): RedirectResponse
    {
        $data = $request->validate([
            'dias' => ['array'],
            'dias.*' => ['in:1,2,3,4,5,6,7'],
            'hora' => ['array'],
            'hora.*' => ['nullable', 'date_format:H:i'],
        ]);

        $this->sincronizarHorario($trabajador, $data['dias'] ?? [], $data['hora'] ?? []);

        return redirect()
            ->route('panel.trabajadores.edit', $trabajador)
            ->with('status', 'Horario actualizado.');
    }

    /**
     * Sincroniza el horario semanal: por cada día (1=lun…7=dom), si está activo y
     * tiene hora, lo crea/actualiza; si no, lo borra. Compartido entre enrolar y editar.
     *
     * @param  array<int|string>  $diasActivos  días marcados (como strings del form)
     * @param  array<int|string, string|null>  $horas  hora por día
     */
    private function sincronizarHorario(Trabajador $trabajador, array $diasActivos, array $horas): void
    {
        foreach (range(1, 7) as $dia) {
            if (in_array((string) $dia, array_map('strval', $diasActivos), true) && ! empty($horas[$dia])) {
                Horario::updateOrCreate(
                    ['trabajador_id' => $trabajador->id, 'dia_semana' => $dia],
                    ['hora_entrada' => $horas[$dia]],
                );
            } else {
                Horario::where('trabajador_id', $trabajador->id)
                    ->where('dia_semana', $dia)->delete();
            }
        }
    }
}
