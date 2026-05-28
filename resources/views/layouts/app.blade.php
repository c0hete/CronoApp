<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Título agnóstico: el nombre del negocio, nunca "Crono". --}}
    <title>{{ $branding->nombre() }}@hasSection('title') — @yield('title')@endif</title>

    {{-- Tipografía con carácter (display + texto), por CDN — sin build. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Datepicker liviano (reemplaza el input date nativo, consistente entre navegadores) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4/dist/l10n/es.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @include('partials.estilos-panel')
</head>
<body>
    @auth
    @php
        // Navegación del panel. is($pattern) marca el link activo según la ruta actual.
        $nav = [
            ['esperados',       'panel.esperados.index',  'panel/esperados*',     '☀', 'Hoy'],
            ['reportes',        'panel.reportes.index',   'panel/reportes*',      '▦', 'Reportes'],
            ['marcajes',        'panel.marcajes.index',   'panel/marcajes*',      '◷', 'Marcaciones'],
            ['trabajadores',    'panel.trabajadores.index','panel/trabajadores*', '☻', 'Trabajadores'],
        ];
        $navConfig = [
            ['personalizacion', 'panel.branding.edit',     'panel/personalizacion*', '✎', 'Personalización'],
            ['configuracion',   'admin.configuracion.edit','admin/configuracion*',   '⚙', 'Configuración'],
        ];
    @endphp
    <div class="shell" x-data="{ open: false }">
        {{-- Scrim para cerrar el sidebar en móvil --}}
        <div class="scrim" x-show="open" x-cloak x-transition.opacity @click="open = false"></div>

        <aside class="sidebar" :class="{ 'is-open': open }">
            <div class="sidebar__brand">
                <a href="{{ route('panel.reportes.index') }}" style="display:flex; align-items:center; gap:.6rem; text-decoration:none; color:inherit;">
                    @if ($branding->logo())
                        <img src="{{ route('panel.branding.logo') }}" alt="{{ $branding->nombre() }}">
                    @else
                        <strong>{{ $branding->nombre() }}</strong>
                    @endif
                </a>
            </div>

            <nav class="sidebar__nav">
                @foreach ($nav as [$id, $route, $pattern, $ico, $label])
                    <a class="nav-link" href="{{ route($route) }}"
                       @if (request()->is($pattern)) aria-current="page" @endif>
                        <span class="ico">{{ $ico }}</span> {{ $label }}
                    </a>
                @endforeach

                <div class="nav-label">Ajustes</div>
                @foreach ($navConfig as [$id, $route, $pattern, $ico, $label])
                    <a class="nav-link" href="{{ route($route) }}"
                       @if (request()->is($pattern)) aria-current="page" @endif>
                        <span class="ico">{{ $ico }}</span> {{ $label }}
                    </a>
                @endforeach

                {{-- TEMPORAL (beta): guía de uso para el cliente. Quitar al terminar la beta. --}}
                <a class="nav-link" href="{{ route('panel.ayuda') }}"
                   @if (request()->is('panel/ayuda')) aria-current="page" @endif>
                    <span class="ico">?</span> Cómo usar
                </a>
            </nav>

            <div class="sidebar__foot">
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button class="btn btn-light" type="submit" style="width:100%;">Salir</button>
                </form>
            </div>
        </aside>

        <div class="main-area">
            {{-- Topbar solo visible en móvil: hamburguesa + nombre --}}
            <div class="topbar">
                <button class="burger" @click="open = true" aria-label="Abrir menú">☰</button>
                <span class="topbar__title">{{ $branding->nombre() }}</span>
            </div>

            <main>
                @if (session('status'))
                    <div class="flash">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="errors">
                        <ul style="margin:0; padding-left:1.1rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @else
    {{-- Sin sesión (no debería renderizar este layout, pero por las dudas) --}}
    <main style="max-width:960px; margin:1.5rem auto; padding:0 1rem;">
        @yield('content')
    </main>
    @endauth
</body>
</html>
