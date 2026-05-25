@extends('layouts.app')

@section('title', 'Personalización')

@section('content')
<h1>Personalización</h1>
<p style="color:#6b7280; margin-top:-.5rem;">Cómo se ve tu negocio en el panel y en la tablet de marcaje.</p>

<div class="card" style="max-width:560px;">
    <form method="POST" action="{{ route('panel.branding.update') }}" enctype="multipart/form-data">
        @csrf

        <label for="marca_nombre">Nombre del negocio</label>
        <input id="marca_nombre" name="marca_nombre" value="{{ old('marca_nombre', $nombre) }}"
               maxlength="60" required placeholder="Ej: Fugo Sushi">

        <label for="marca_color_primario" style="margin-top:1rem;">Color principal</label>
        <div style="display:flex; gap:.6rem; align-items:center;">
            <input id="marca_color_primario" name="marca_color_primario" type="color"
                   value="{{ old('marca_color_primario', $color) }}"
                   style="width:56px; height:40px; padding:0; border:1px solid #cbd2dc; border-radius:6px; cursor:pointer;">
            <span style="color:#6b7280; font-size:.9rem;">Elegí un color; los tonos se ajustan solos para mantener la legibilidad.</span>
        </div>

        <label for="logo" style="margin-top:1rem;">Logo (opcional)</label>
        @if ($logo)
            <div style="margin-bottom:.5rem;">
                <img src="{{ route('panel.branding.logo') }}" alt="logo actual"
                     style="height:48px; background:#fff; border:1px solid #eceff3; border-radius:6px; padding:4px;">
                <span style="color:#6b7280; font-size:.85rem;"> logo actual</span>
            </div>
        @endif
        <input id="logo" name="logo" type="file" accept="image/png,image/svg+xml">
        <p style="color:#9aa3b2; font-size:.8rem; margin:.3rem 0 0;">PNG o SVG, hasta 1 MB. Si no subís logo, se muestra el nombre.</p>

        <button class="btn" type="submit" style="margin-top:1.25rem;">Guardar</button>
    </form>
</div>
@endsection
