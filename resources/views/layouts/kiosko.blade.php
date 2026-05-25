<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Título agnóstico: el negocio, nunca "Crono". --}}
    <title>{{ $branding->nombre() }}</title>
    <style>
        :root { --color-primary: {{ $branding->colorPrimario() }}; }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body { margin: 0; height: 100%; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #11151c; color: #f4f5f7;
            display: flex; flex-direction: column; min-height: 100vh;
            user-select: none;
        }
        main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        [x-cloak] { display: none !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <main>
        @yield('content')
    </main>
</body>
</html>
