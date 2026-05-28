@extends('layouts.app')

@section('title', 'Cómo usar')

{{--
  VISTA TEMPORAL (beta) — guía de uso + encuadre de fase para el cliente piloto.
  Pensada para quitarse cuando la beta termine. Para eliminarla, borrar:
    - este archivo
    - la ruta panel.ayuda en routes/web.php
    - el método ayuda() en Panel\AyudaController (o el controller entero)
    - el enlace "Cómo usar" en layouts/app.blade.php
--}}

@section('content')
<style>
    .ayuda h2 { color: var(--color-primary); border-bottom: 2px solid #eef1f5; padding-bottom:.3rem; margin-top:2rem; }
    .ayuda .badge { display:inline-block; background:#b7791f; color:#fff; font-size:.7rem; font-weight:700;
                    letter-spacing:.5px; padding:.2rem .6rem; border-radius:20px; vertical-align:middle; }
    .ayuda .paso { display:flex; gap:.8rem; align-items:flex-start; margin:.7rem 0; }
    .ayuda .num { flex:none; width:30px; height:30px; border-radius:50%; background:var(--color-primary);
                  color:var(--on-primary,#fff); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.9rem; }
    .ayuda .fases { display:flex; gap:.6rem; flex-wrap:wrap; margin:1rem 0; }
    .ayuda .fase { flex:1; min-width:160px; border:1px solid #e2e7ee; border-radius:10px; padding:.8rem 1rem; }
    .ayuda .fase.activa { border:2px solid var(--color-primary); background:#f3f8fd; }
    .ayuda .et { font-size:.68rem; font-weight:700; letter-spacing:.5px; text-transform:uppercase; }
    .ayuda .si  { border-left:4px solid #2e8b57; background:#eef7f1; padding:.7rem 1rem; border-radius:0 8px 8px 0; margin:.8rem 0; }
    .ayuda .aun { border-left:4px solid #b7791f; background:#fbf4e7; padding:.7rem 1rem; border-radius:0 8px 8px 0; margin:.8rem 0; }
    .ayuda .info{ border-left:4px solid var(--color-primary); background:#eef4fb; padding:.7rem 1rem; border-radius:0 8px 8px 0; margin:.8rem 0; }
    .ayuda ul { padding-left:1.2rem; } .ayuda li { margin:.2rem 0; }
    .ayuda code { background:#e9edf2; padding:.1rem .4rem; border-radius:4px; font-size:.92em; }
</style>

<div class="ayuda">
    <h1 style="margin-bottom:.2rem;">Cómo usar {{ $branding->nombre() }} <span class="badge">beta</span></h1>
    <p style="color:#6b7280; margin-top:0;">Guía rápida para el período de pruebas.</p>

    <div class="card" style="margin-bottom:1rem;">
        <strong>En una frase:</strong> tus trabajadores marcan entrada y salida en una tablet (con foto),
        y vos ves desde tu computador o teléfono quién llegó, a qué hora, y cuánto representan los atrasos.
    </div>

    {{-- ===== Encuadre de fase ===== --}}
    <h2>Esto es una versión beta funcional</h2>
    <p>Las funciones <strong>ya están construidas y operativas</strong>, pero el acabado visual todavía es
       sencillo y a propósito. En esta etapa lo importante es que <strong>uses la app de verdad</strong> y
       nos digas si hace lo que tu negocio necesita.</p>

    <div class="fases">
        <div class="fase activa">
            <div class="et" style="color:var(--color-primary);">▶ Estás acá</div>
            <strong>1. Funcionalidad</strong>
            <p style="margin:.3rem 0 0; font-size:.92rem;">Verificar que la app hace lo correcto y buscar mejoras.</p>
        </div>
        <div class="fase">
            <div class="et" style="color:#6b7280;">Después</div>
            <strong>2. Ajustes</strong>
            <p style="margin:.3rem 0 0; font-size:.92rem;">Sumar lo que pidas y corregir lo que no calce con tu día a día.</p>
        </div>
        <div class="fase">
            <div class="et" style="color:#6b7280;">Al final</div>
            <strong>3. Terminación visual</strong>
            <p style="margin:.3rem 0 0; font-size:.92rem;">Diseño “pro”, pulido, y la app optimizada e instalable en el teléfono.</p>
        </div>
    </div>
    <p style="font-size:.95rem;"><strong>¿Por qué en ese orden?</strong> Pulir pantallas que aún pueden cambiar es perder tiempo.
       Primero confirmamos <em>qué</em> hace la app con tu operación real; vestirla bien es la parte rápida y rinde más al final.</p>

    <div class="info">
        <strong>Desde el teléfono:</strong> por ahora se abre desde el navegador (entrás a la dirección y listo).
        Todavía <strong>no tiene ícono de app</strong> ni vista optimizada para celular — eso llega en la fase 3,
        cuando va a poder instalarse como una app más. Mientras tanto, abrirla desde el buscador funciona perfecto para probar.
    </div>

    {{-- ===== Cómo se usa ===== --}}
    <h2>1 · Entrar al panel</h2>
    <div class="paso"><div class="num">1</div><div>Abrí la dirección de la app e ingresá con tu <strong>correo</strong> y <strong>contraseña</strong>.</div></div>
    <div class="paso"><div class="num">2</div><div>Arriba tenés el menú: <strong>Reportes · Marcaciones · Trabajadores · Personalización · Configuración</strong>.</div></div>

    <h2>2 · Cargar trabajadores</h2>
    <div class="paso"><div class="num">1</div><div><strong>Trabajadores → “Enrolar trabajador”</strong>.</div></div>
    <div class="paso"><div class="num">2</div><div>Nombre e identificación (RUT con o sin puntos/guion, la app lo ordena). Luego el contrato: sueldo, horas semanales, hora de entrada y <strong>tolerancia</strong> en minutos.</div></div>
    <div class="paso"><div class="num">3</div><div>Guardás. Ya puede marcar. (Con “Editar” corregís datos o lo marcás inactivo.)</div></div>

    <h2>3 · Marcar en la tablet</h2>
    <div class="paso"><div class="num">1</div><div>La tablet muestra “<strong>Toca la pantalla para marcar</strong>” (la cámara está apagada hasta ese momento).</div></div>
    <div class="paso"><div class="num">2</div><div>El trabajador toca, escribe su <strong>RUT sin puntos ni guion</strong> (si termina en K, usa la tecla K) y elige <strong>Entrada</strong> o <strong>Salida</strong>. Se saca una foto y confirma.</div></div>
    <div class="info"><strong>Sin internet también funciona:</strong> el marcaje se guarda en la tablet y se envía solo cuando vuelve la señal.</div>

    <h2>4 · Reportes</h2>
    <p>En <strong>Reportes</strong>: el número grande es el <strong>costo de horas no trabajadas</strong> del período.
       Cambiás entre Semanal/Mensual y te movés con ← →. El gráfico de torta reparte el costo entre trabajadores;
       la tabla los detalla. Si nadie llegó tarde, te lo dice.</p>

    <h2>5 · Marcaciones y personalización</h2>
    <p>En <strong>Marcaciones</strong> ves cada marcaje con su foto (tocá la foto para verla grande); podés filtrar por
       trabajador y fecha. En <strong>Personalización</strong> ponés nombre, color y logo de tu negocio.</p>

    {{-- ===== Qué pedimos ===== --}}
    <h2>Lo que te pedimos en esta fase</h2>
    <div class="aun">
        Usá la app como si fuera el día a día y contanos: ¿hace lo que necesitás?, ¿falta algo?,
        ¿hay algo confuso o que harías distinto? <strong>No te fijes todavía en lo estético</strong> —
        tus observaciones de funcionalidad son las que más valen ahora.
    </div>
</div>
@endsection
