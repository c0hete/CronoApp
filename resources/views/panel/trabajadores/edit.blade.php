@extends('layouts.app')

@section('title', 'Editar trabajador')

@section('content')
<h1>Editar trabajador</h1>

<div class="card" style="max-width:560px;">
    <form method="POST" action="{{ route('panel.trabajadores.update', $trabajador) }}">
        @csrf
        @method('PUT')

        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" value="{{ old('nombre', $trabajador->nombre) }}" required>

        <div style="display:flex; gap:1rem;">
            <div style="flex:1;">
                <label for="tipo_id">Tipo de identificación</label>
                <select id="tipo_id" name="tipo_id" required>
                    <option value="rut" @selected(old('tipo_id', $trabajador->tipo_id) === 'rut')>RUT</option>
                    <option value="pasaporte" @selected(old('tipo_id', $trabajador->tipo_id) === 'pasaporte')>Pasaporte</option>
                </select>
            </div>
            <div style="flex:2;">
                <label for="numero_id">Número</label>
                <input id="numero_id" name="numero_id"
                       value="{{ old('numero_id', $trabajador->identificacion_formateada) }}" required>
            </div>
        </div>

        <label for="activo" style="margin-top:1rem;">Estado</label>
        <select id="activo" name="activo" required>
            <option value="1" @selected(old('activo', $trabajador->activo) == 1)>Activo</option>
            <option value="0" @selected(old('activo', $trabajador->activo) == 0)>Inactivo</option>
        </select>

        <p style="color:#9aa3b2; font-size:.8rem; margin-top:1rem;">
            El sueldo y el horario son parte del contrato (se gestionan aparte para no alterar el histórico).
        </p>

        <div style="margin-top:1.25rem; display:flex; gap:.6rem;">
            <button class="btn" type="submit">Guardar cambios</button>
            <a class="btn btn-light" href="{{ route('panel.trabajadores.index') }}">Cancelar</a>
        </div>
    </form>
</div>

{{-- Horario semanal esperado: define qué días trabaja y a qué hora entra.
     Con esto el sistema avisa el retraso aunque no marque (ver "Hoy"). --}}
@php $horariosPorDia = $trabajador->horarios->keyBy('dia_semana'); @endphp
<div class="card" style="max-width:560px; margin-top:1.5rem;">
    <h3 style="margin-top:0;">Horario semanal</h3>
    <p style="color:#6b7280; margin-top:-.4rem;">Marcá los días que trabaja y su hora de entrada. El atraso se calcula contra esta hora.</p>

    <form method="POST" action="{{ route('panel.trabajadores.horarios', $trabajador) }}">
        @csrf
        @method('PUT')
        @foreach (\App\Models\Horario::nombresDias() as $dia => $nombre)
            @php $h = $horariosPorDia->get($dia); @endphp
            <div style="display:flex; align-items:center; gap:1rem; padding:.4rem 0; border-bottom:1px solid #f0f2f5;">
                <label style="display:flex; align-items:center; gap:.5rem; width:130px; margin:0; font-weight:400;">
                    <input type="checkbox" name="dias[]" value="{{ $dia }}" style="width:auto;" @checked($h)>
                    {{ $nombre }}
                </label>
                <input type="time" name="hora[{{ $dia }}]" value="{{ $h ? \Illuminate\Support\Str::substr($h->hora_entrada, 0, 5) : '09:00' }}"
                       style="width:140px;">
            </div>
        @endforeach
        <button class="btn" type="submit" style="margin-top:1rem;">Guardar horario</button>
    </form>
</div>
@endsection
