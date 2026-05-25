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
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($trabajadores as $t)
                    @php $c = $t->contratos->first(); @endphp
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
                        <td>{{ $t->activo ? 'Activo' : 'Inactivo' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
