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
@endsection
