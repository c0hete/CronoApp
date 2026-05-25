<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Título agnóstico: el nombre del negocio, nunca "Crono". --}}
    <title>{{ $branding->nombre() }}@hasSection('title') — @yield('title')@endif</title>
    {{-- Datepicker liviano (reemplaza el input date nativo, consistente entre navegadores) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4/dist/l10n/es.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @php $pal = $branding->paleta(); @endphp
    <style>
        :root {
            --color-primary: {{ $pal['primary'] }};
            --color-primary-hover: {{ $pal['primary_hover'] }};
            --color-primary-dark: {{ $pal['primary_dark'] }};
            --on-primary: {{ $pal['on_primary'] }};
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
               background: #f4f5f7; color: #1f2430; }
        header.app { background: var(--color-primary); color: var(--on-primary); padding: .9rem 1.25rem;
                     display: flex; justify-content: space-between; align-items: center; }
        header.app a { color: var(--on-primary); text-decoration: none; }
        header.app .marca-logo { height: 32px; vertical-align: middle; }
        main { max-width: 960px; margin: 1.5rem auto; padding: 0 1rem; }
        .card { background: #fff; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .btn { background: var(--color-primary); color: var(--on-primary); border: 0; border-radius: 6px;
               padding: .55rem 1rem; cursor: pointer; font-size: .95rem; text-decoration: none; display: inline-block; }
        .btn:hover { background: var(--color-primary-hover); }
        .btn-light { background: #e9ecf1; color: #1f2430; }
        label { display: block; margin: .6rem 0 .2rem; font-size: .9rem; font-weight: 600; }
        input, select { width: 100%; padding: .5rem .6rem; border: 1px solid #cbd2dc; border-radius: 6px; font-size: .95rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .5rem .6rem; border-bottom: 1px solid #eceff3; }
        .errors { background: #fdecec; border: 1px solid #f5b5b5; color: #a12222; padding: .6rem .8rem; border-radius: 6px; margin-bottom: 1rem; }
        .flash { background: #e8f6ec; border: 1px solid #aadfba; color: #1d6b34; padding: .6rem .8rem; border-radius: 6px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    @auth
    <header class="app">
        <a href="{{ route('panel.reportes.index') }}">
            @if ($branding->logo())
                <img class="marca-logo" src="{{ route('panel.branding.logo') }}" alt="{{ $branding->nombre() }}">
            @else
                <strong>{{ $branding->nombre() }}</strong>
            @endif
        </a>
        <nav style="display:flex; gap:1rem; align-items:center;">
            <a href="{{ route('panel.reportes.index') }}">Reportes</a>
            <a href="{{ route('panel.marcajes.index') }}">Marcaciones</a>
            <a href="{{ route('panel.trabajadores.index') }}">Trabajadores</a>
            <a href="{{ route('panel.branding.edit') }}">Personalización</a>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button class="btn btn-light" type="submit">Salir</button>
            </form>
        </nav>
    </header>
    @endauth

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
</body>
</html>
