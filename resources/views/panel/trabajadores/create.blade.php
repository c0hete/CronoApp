@extends('layouts.app')

@section('title', 'Enrolar trabajador')

@section('content')
<h1>Enrolar trabajador</h1>

<div class="card">
    <form method="POST" action="{{ route('panel.trabajadores.store') }}">
        @csrf

        <h3 style="margin-top:0;">Identidad</h3>
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" value="{{ old('nombre') }}" required>

        <div style="display:flex; gap:1rem;">
            <div style="flex:1;">
                <label for="tipo_id">Tipo de identificación</label>
                <select id="tipo_id" name="tipo_id" required>
                    <option value="rut" @selected(old('tipo_id', 'rut') === 'rut')>RUT</option>
                    <option value="pasaporte" @selected(old('tipo_id') === 'pasaporte')>Pasaporte</option>
                </select>
            </div>
            <div style="flex:2;">
                <label for="numero_id">Número</label>
                <input id="numero_id" name="numero_id" value="{{ old('numero_id') }}" required
                       placeholder="RUT con o sin puntos/guión (ej: 12.345.678-5)">
            </div>
        </div>

        <h3>Contrato</h3>
        <p style="color:#6b7280; margin-top:-.4rem; font-size:.9rem;">
            Ingrese al menos un sueldo (bruto o líquido). Base de cálculo: semanal.
        </p>

        <div style="display:flex; gap:1rem;">
            <div style="flex:1;">
                <label for="sueldo_bruto">Sueldo bruto</label>
                <input id="sueldo_bruto" name="sueldo_bruto" type="number" step="1" min="0" value="{{ old('sueldo_bruto') }}">
            </div>
            <div style="flex:1;">
                <label for="sueldo_liquido">Sueldo líquido</label>
                <input id="sueldo_liquido" name="sueldo_liquido" type="number" step="1" min="0" value="{{ old('sueldo_liquido') }}">
            </div>
        </div>

        <div style="display:flex; gap:1rem;">
            <div style="flex:1;">
                <label for="horas_semanales">Horas semanales</label>
                <input id="horas_semanales" name="horas_semanales" type="number" step="0.5" min="1" max="168"
                       value="{{ old('horas_semanales', 45) }}" required>
            </div>
            <div style="flex:1;">
                <label for="hora_entrada_pactada">Hora de entrada</label>
                <input id="hora_entrada_pactada" name="hora_entrada_pactada" type="time"
                       value="{{ old('hora_entrada_pactada', '09:00') }}" required>
            </div>
            <div style="flex:1;">
                <label for="tolerancia_min">Tolerancia (min)</label>
                <input id="tolerancia_min" name="tolerancia_min" type="number" min="0" max="120"
                       value="{{ old('tolerancia_min', 5) }}" required>
            </div>
        </div>

        <label for="vigente_desde">Vigente desde</label>
        <input id="vigente_desde" name="vigente_desde" type="date" value="{{ old('vigente_desde', date('Y-m-d')) }}" required>

        <div style="margin-top:1.25rem; display:flex; gap:.6rem;">
            <button class="btn" type="submit">Enrolar</button>
            <a class="btn btn-light" href="{{ route('panel.trabajadores.index') }}">Cancelar</a>
        </div>
    </form>
</div>
@endsection
