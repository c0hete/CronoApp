@extends('layouts.app')

@section('title', 'Marcaciones')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
    <h1 style="margin:0;">Marcaciones</h1>
    <a class="btn btn-light" href="{{ route('panel.trabajadores.index') }}">Trabajadores</a>
</div>

{{-- Filtros --}}
<div class="card" style="margin-bottom:1rem;">
    <form method="GET" action="{{ route('panel.marcajes.index') }}"
          style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap;">
        <div style="flex:1; min-width:180px;">
            <label for="trabajador_id">Trabajador</label>
            <select id="trabajador_id" name="trabajador_id">
                <option value="">Todos</option>
                @foreach ($trabajadores as $t)
                    <option value="{{ $t->id }}" @selected(request('trabajador_id') == $t->id)>{{ $t->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="desde">Desde</label>
            <input id="desde" type="date" name="desde" value="{{ request('desde') }}">
        </div>
        <div>
            <label for="hasta">Hasta</label>
            <input id="hasta" type="date" name="hasta" value="{{ request('hasta') }}">
        </div>
        <button class="btn" type="submit">Filtrar</button>
        @if (request()->hasAny(['trabajador_id', 'desde', 'hasta']))
            <a class="btn btn-light" href="{{ route('panel.marcajes.index') }}">Limpiar</a>
        @endif
    </form>
</div>

<div class="card">
    @if ($marcajes->isEmpty())
        <p style="color:#6b7280;">No hay marcaciones para los filtros seleccionados.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Trabajador</th>
                    <th>Fecha y hora</th>
                    <th>Tipo</th>
                    <th>Atraso</th>
                    <th>Costo de horas no trabajadas</th>
                    <th>Evidencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($marcajes as $m)
                    <tr>
                        <td>{{ $m->trabajador?->nombre ?? '—' }}</td>
                        <td>{{ $m->ts_dispositivo->format('d-m-Y H:i') }}</td>
                        <td>
                            {{ ucfirst($m->tipo) }}
                            @if ($m->reloj_sospechoso)
                                <span title="La hora del dispositivo difería de la del servidor"
                                      style="color:#a16207; font-size:.8rem;">⚠ reloj</span>
                            @endif
                        </td>
                        <td>{{ $m->minutos_atraso > 0 ? $m->minutos_atraso.' min' : '—' }}</td>
                        <td>
                            @if ($m->costo_atraso > 0)
                                ${{ number_format($m->costo_atraso, 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($m->foto_evidencia)
                                <a href="{{ route('panel.marcajes.foto', $m) }}" target="_blank">
                                    <img src="{{ route('panel.marcajes.foto', $m) }}" alt="evidencia"
                                         style="height:44px; width:44px; object-fit:cover; border-radius:6px;">
                                </a>
                            @else
                                <span style="color:#9aa3b2;">sin foto</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:1rem;">{{ $marcajes->links() }}</div>
    @endif
</div>
@endsection
