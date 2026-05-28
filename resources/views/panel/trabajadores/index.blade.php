@extends('layouts.app')

@section('title', 'Trabajadores')

@section('content')
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
    <h1 style="margin:0;">Trabajadores</h1>
    <a class="btn" href="{{ route('panel.trabajadores.create') }}">Enrolar trabajador</a>
</div>

<div class="card">
    @if ($trabajadores->isEmpty())
        <p style="color:#6b7280;">Aún no hay trabajadores enrolados.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Identificación</th>
                    <th>Contrato vigente</th>
                    <th>Días que trabaja</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php $abrev = \App\Models\Horario::abreviaturasDias(); @endphp
                @foreach ($trabajadores as $t)
                    @php
                        $c = $t->contratos->first();
                        $diasConHorario = $t->horarios->pluck('dia_semana')->sort()->values();
                    @endphp
                    <tr>
                        <td>{{ $t->nombre }}</td>
                        <td>{{ strtoupper($t->tipo_id) }} {{ $t->identificacion_formateada }}</td>
                        <td>
                            @if ($c)
                                Entrada {{ \Illuminate\Support\Str::of($c->hora_entrada_pactada)->substr(0,5) }}
                                · {{ rtrim(rtrim($c->horas_semanales, '0'), '.') }} h/sem
                            @else
                                <em style="color:#a12222;">sin contrato</em>
                            @endif
                        </td>
                        <td>
                            @if ($diasConHorario->isNotEmpty())
                                <div style="display:flex; gap:.25rem; flex-wrap:wrap;">
                                    @foreach ($diasConHorario as $d)
                                        <span style="background:#e8eefb; color:#27408b; border-radius:4px;
                                                     padding:.1rem .4rem; font-size:.78rem; font-weight:600;">{{ $abrev[$d] }}</span>
                                    @endforeach
                                </div>
                            @else
                                <a href="{{ route('panel.trabajadores.edit', $t) }}"
                                   style="display:inline-flex; align-items:center; gap:.35rem; text-decoration:none;
                                          background:#fdecec; color:#a12222; border:1px solid #f5b5b5; border-radius:6px;
                                          padding:.15rem .5rem; font-size:.78rem; font-weight:600;">
                                    ⚠ Sin días asignados → asignar
                                </a>
                            @endif
                        </td>
                        <td>{{ $t->activo ? 'Activo' : 'Inactivo' }}</td>
                        <td style="text-align:right;">
                            <a class="btn btn-light" style="padding:.3rem .7rem; font-size:.85rem;"
                               href="{{ route('panel.trabajadores.edit', $t) }}">Editar</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
