<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Título agnóstico: el negocio, nunca "Crono". --}}
    <title>{{ $branding->nombre() }}</title>
    {{-- PWA: instalable como app del negocio, con su branding. --}}
    <link rel="manifest" href="{{ route('kiosko.manifest') }}">
    <meta name="theme-color" content="{{ $branding->colorPrimario() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    {{-- Tipografía con carácter (display + texto), por CDN — sin build. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    @include('partials.estilos-kiosko')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
    <main>
        @yield('content')
    </main>
    <script>
        // Registrar el service worker (PWA) — solo en contexto seguro (HTTPS/localhost).
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js', { scope: '/marcar' }).catch(() => {});
            });
        }
    </script>
</body>
</html>
