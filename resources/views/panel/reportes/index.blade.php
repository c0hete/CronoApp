@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:1rem;">
    <h1 style="margin:0;">Reportes</h1>

    {{-- Selector de período --}}
    <form method="GET" action="{{ route('panel.reportes.index') }}" style="display:flex; gap:.5rem; align-items:center;">
        <input type="hidden" name="ref" value="{{ $ref }}">
        <a class="btn btn-light @if($periodo!=='semanal') @endif"
           href="{{ route('panel.reportes.index', ['periodo'=>'semanal','ref'=>$ref]) }}"
           style="@if($periodo==='semanal') background:var(--color-primary); color:#fff; @endif">Semanal</a>
        <a class="btn btn-light"
           href="{{ route('panel.reportes.index', ['periodo'=>'mensual','ref'=>$ref]) }}"
           style="@if($periodo==='mensual') background:var(--color-primary); color:#fff; @endif">Mensual</a>
    </form>
</div>

{{-- Navegación de período --}}
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
    <a class="btn btn-light" href="{{ route('panel.reportes.index', ['periodo'=>$periodo,'ref'=>$anterior]) }}">← Anterior</a>
    <strong style="font-size:1.05rem;">{{ $etiqueta }}</strong>
    <a class="btn btn-light" href="{{ route('panel.reportes.index', ['periodo'=>$periodo,'ref'=>$siguiente]) }}">Siguiente →</a>
</div>

{{-- Visión general: número que importa + torta de distribución --}}
<div class="card" style="margin-bottom:1rem; display:flex; gap:2rem; align-items:center; flex-wrap:wrap; justify-content:center;">
    <div style="text-align:center;">
        <div style="color:#6b7280; font-size:.9rem;">Costo de horas no trabajadas — {{ $etiqueta }}</div>
        <div style="font-size:2.6rem; font-weight:800; color:var(--color-primary); margin:.2rem 0;">
            ${{ number_format((float) $datos['total_costo'], 0, ',', '.') }}
        </div>
        <div style="color:#6b7280; font-size:.9rem;">
            {{ $datos['total_minutos'] }} minutos de atraso · {{ $datos['total_marcajes'] }} marcajes de entrada
        </div>
    </div>

    @if ($datos['torta']->isNotEmpty())
        @php
            // construir el conic-gradient acumulando porcentajes
            $stops = [];
            $acc = 0;
            foreach ($datos['torta'] as $seg) {
                $desde = $acc;
                $acc += $seg['pct'];
                $stops[] = "{$seg['color']} {$desde}% {$acc}%";
            }
            $gradient = 'conic-gradient(' . implode(', ', $stops) . ')';
        @endphp
        <div style="display:flex; gap:1.2rem; align-items:center;">
            <div style="width:140px; height:140px; border-radius:50%; background:{{ $gradient }};"
                 title="Distribución del costo por trabajador"></div>
            <div style="font-size:.85rem;">
                @foreach ($datos['torta'] as $seg)
                    <div style="display:flex; align-items:center; gap:.4rem; margin:.2rem 0;">
                        <span style="width:12px; height:12px; border-radius:3px; background:{{ $seg['color'] }}; display:inline-block;"></span>
                        <span>{{ $seg['trabajador'] }} — {{ $seg['pct'] }}%</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

{{-- Desglose por trabajador --}}
<div class="card">
    <h3 style="margin-top:0;">Por trabajador</h3>
    @if ($datos['filas']->isEmpty())
        <p style="color:#6b7280;">Sin marcajes de entrada en este período.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Trabajador</th>
                    <th>Marcajes</th>
                    <th>Atraso acumulado</th>
                    <th>Costo de horas no trabajadas</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($datos['filas'] as $f)
                    <tr>
                        <td>
                            <span style="width:10px; height:10px; border-radius:50%; background:{{ $f['color'] }}; display:inline-block; margin-right:.5rem;"></span>
                            {{ $f['trabajador'] }}
                        </td>
                        <td>{{ $f['marcajes'] }}</td>
                        <td>{{ $f['minutos'] > 0 ? $f['minutos'].' min' : '—' }}</td>
                        <td>
                            @if ($f['costo'] > 0)
                                ${{ number_format((float) $f['costo'], 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <p style="color:#9aa3b2; font-size:.8rem; margin-top:1rem;">
        Indicador de gestión interno. No es registro oficial de jornada ni base de cálculo para remuneraciones.
    </p>
</div>
@endsection
