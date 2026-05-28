@extends('layouts.app')

@section('title', 'Esperados hoy')

@section('content')
@php
    $info = [
        'pendiente'   => ['#6b7280', '⏳', 'Pendiente'],
        'a_tiempo'    => ['#1d6b34', '✅', 'Llegó a tiempo'],
        'atrasado'    => ['#a12222', '🔴', 'Atrasado'],
        'ausente'     => ['#111111', '⚫', 'Ausente'],
        'justificado' => ['#a16207', '🟡', 'No viene (justificado)'],
        'sin_horario' => ['#9aa3b2', '·',  'Sin horario'],
    ];
@endphp

<div x-data="{ menu: null }">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.3rem; flex-wrap:wrap; gap:.5rem;">
        <h1 style="margin:0;">Esperados hoy</h1>
        <span style="color:#6b7280;">{{ $ahora->locale('es')->isoFormat('dddd D [de] MMMM · HH:mm') }}</span>
    </div>
    <p style="color:#6b7280; margin-top:0;">El atraso corre desde la hora de entrada, aunque la persona todavía no marque. La vista se actualiza sola.</p>

    <div class="card">
        @if ($esperados->isEmpty())
            <p style="color:#6b7280;">Hoy no se espera a nadie (ningún trabajador tiene horario para este día).</p>
        @else
            <table>
                <thead>
                    <tr><th>Trabajador</th><th>Entra</th><th>Estado</th><th>Atraso</th><th style="text-align:right;">Acción</th></tr>
                </thead>
                <tbody>
                    @foreach ($esperados as $e)
                        @php [$color, $icono, $texto] = $info[$e['estado']] ?? $info['sin_horario']; @endphp
                        <tr>
                            <td>{{ $e['trabajador']->nombre }}</td>
                            <td>{{ $e['hora_esperada'] ?? '—' }}</td>
                            <td>
                                <span style="color:{{ $color }}; font-weight:600;">{{ $icono }} {{ $texto }}</span>
                            </td>
                            <td>
                                @if ($e['minutos_atraso'] > 0)
                                    {{ $e['minutos_atraso'] }} min{{ $e['en_curso'] ? ' y contando…' : '' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td style="text-align:right; position:relative;">
                                @if ($e['tiene_excepcion'])
                                    {{-- ya tiene excepción marcada (justificado/ausente a mano): ofrecer deshacer --}}
                                    <form method="POST" action="{{ route('panel.esperados.deshacer', $e['trabajador']) }}" style="display:inline; margin:0;">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-light" style="padding:.3rem .7rem; font-size:.85rem;">Deshacer</button>
                                    </form>
                                @elseif (! $e['marco'])
                                    {{-- dropdown: No viene (justificado) / Marcar ausente --}}
                                    <button class="btn btn-light" style="padding:.3rem .7rem; font-size:.85rem;"
                                            @click="menu === {{ $e['trabajador']->id }} ? menu=null : menu={{ $e['trabajador']->id }}">No vino ▾</button>
                                    <div x-show="menu === {{ $e['trabajador']->id }}" x-cloak @click.outside="menu=null"
                                         style="position:absolute; right:0; top:100%; background:#fff; border:1px solid #e2e7ee; border-radius:8px; box-shadow:0 4px 14px rgba(0,0,0,.12); z-index:5; min-width:200px; text-align:left;">
                                        <form method="POST" action="{{ route('panel.esperados.marcar', $e['trabajador']) }}" style="margin:0;">
                                            @csrf
                                            <input type="hidden" name="tipo" value="justificado">
                                            <button type="submit" style="display:block; width:100%; text-align:left; border:0; background:none; padding:.6rem .9rem; cursor:pointer;">🟡 No viene hoy (justificado)</button>
                                        </form>
                                        <form method="POST" action="{{ route('panel.esperados.marcar', $e['trabajador']) }}" style="margin:0;">
                                            @csrf
                                            <input type="hidden" name="tipo" value="ausente">
                                            <button type="submit" style="display:block; width:100%; text-align:left; border:0; background:none; padding:.6rem .9rem; cursor:pointer; border-top:1px solid #eef1f5;">⚫ Marcar ausente</button>
                                        </form>
                                    </div>
                                @else
                                    <span style="color:#9aa3b2; font-size:.85rem;">marcó</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<script>
    // Auto-refresh: el atraso "corre" sin que Luis recargue. Cada 60s, salvo que haya un menú abierto.
    setInterval(() => {
        if (!document.querySelector('[x-cloak]:not([style*="display: none"])')) location.reload();
    }, 60000);
</script>
<style>[x-cloak]{display:none!important;}</style>
@endsection
