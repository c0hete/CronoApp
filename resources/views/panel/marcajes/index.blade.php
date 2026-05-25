@extends('layouts.app')

@section('title', 'Marcaciones')

@section('content')
<div x-data="marcajesView()">
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
                <input id="desde" class="fecha" type="text" name="desde" value="{{ request('desde') }}" placeholder="Seleccionar" autocomplete="off">
            </div>
            <div>
                <label for="hasta">Hasta</label>
                <input id="hasta" class="fecha" type="text" name="hasta" value="{{ request('hasta') }}" placeholder="Seleccionar" autocomplete="off">
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
                                    {{-- clic → modal (no redirige) --}}
                                    <img src="{{ route('panel.marcajes.foto', $m) }}" alt="evidencia"
                                         @click="abrir({
                                            url: '{{ route('panel.marcajes.foto', $m) }}',
                                            trabajador: @js($m->trabajador?->nombre ?? '—'),
                                            fecha: '{{ $m->ts_dispositivo->format('d-m-Y H:i') }}',
                                            tipo: '{{ ucfirst($m->tipo) }}',
                                            id: {{ $m->id }},
                                         })"
                                         style="height:44px; width:44px; object-fit:cover; border-radius:6px; cursor:pointer;">
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

    {{-- Modal de evidencia: foto grande + datos al costado --}}
    <div x-show="modal" x-cloak @click.self="cerrar()" @keydown.escape.window="cerrar()"
         style="position:fixed; inset:0; background:rgba(0,0,0,.6); display:flex; align-items:center; justify-content:center; z-index:60; padding:1rem;">
        <div style="background:#fff; border-radius:12px; overflow:hidden; max-width:760px; width:100%; display:flex; flex-wrap:wrap;">
            <img :src="datos.url" alt="evidencia"
                 style="flex:1; min-width:260px; max-height:70vh; object-fit:contain; background:#000;">
            <div style="flex:1; min-width:220px; padding:1.5rem;">
                <h3 style="margin-top:0;">Evidencia de marcaje</h3>
                <p style="margin:.4rem 0;"><strong>Trabajador:</strong> <span x-text="datos.trabajador"></span></p>
                <p style="margin:.4rem 0;"><strong>Fecha y hora:</strong> <span x-text="datos.fecha"></span></p>
                <p style="margin:.4rem 0;"><strong>Tipo:</strong> <span x-text="datos.tipo"></span></p>
                <p style="margin:.4rem 0; color:#6b7280; font-size:.85rem;">Marcaje #<span x-text="datos.id"></span></p>
                <div style="margin-top:1.2rem; display:flex; gap:.6rem;">
                    <a class="btn btn-light" :href="datos.url" target="_blank">Abrir original</a>
                    <button class="btn" @click="cerrar()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function marcajesView() {
    return {
        modal: false,
        datos: {},
        abrir(d) { this.datos = d; this.modal = true; },
        cerrar() { this.modal = false; },
    };
}
document.addEventListener('DOMContentLoaded', () => {
    if (window.flatpickr) {
        flatpickr.localize(flatpickr.l10ns.es);
        flatpickr('.fecha', { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd-m-Y', allowInput: true });
    }
});
</script>
<style>[x-cloak]{display:none!important;}</style>
@endsection
