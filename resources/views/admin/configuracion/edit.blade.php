@extends('layouts.app')

@section('title', 'Configuración técnica')

@section('content')
<h1>Configuración técnica</h1>
<p style="color:#6b7280; margin-top:-.5rem;">Parámetros de retención, monitoreo y captura. Cambios aplican de inmediato.</p>

<div class="card" style="max-width:560px;">
    <form method="POST" action="{{ route('admin.configuracion.update') }}">
        @csrf
        @method('PUT')

        @foreach ($valores as $clave => $def)
            <label for="{{ $clave }}">{{ $def['label'] }}</label>
            <input id="{{ $clave }}" name="{{ $clave }}" type="number"
                   value="{{ old($clave, $def['valor']) }}" required>
        @endforeach

        <p style="color:#9aa3b2; font-size:.8rem; margin-top:1rem;">
            Las fotos se purgan automáticamente al superar la retención (el registro de marcaje
            se conserva). El monitor de disco avisa al superar el umbral; nunca borra solo.
        </p>

        <button class="btn" type="submit" style="margin-top:1rem;">Guardar configuración</button>
    </form>
</div>
@endsection
