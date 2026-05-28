@php $pal = $branding->paleta(); @endphp
{{--
  Fundación de estilos del panel del dueño — CSS propio, sin build (decisión del proyecto).
  Filosofía (docs/frontend.md): belleza por sistema. Se definen tokens + componentes base
  UNA vez; las pantallas heredan. Estilo Linear: limpio, denso, legible, sin adornos.
  Presupuesto: dashboard = medio-alto. Nada de WebGL/3D/animaciones pesadas (línea roja).
--}}
<style>
    /* ───────────────────────── Design tokens ───────────────────────── */
    :root {
        /* Marca (derivada del color del cliente) */
        --color-primary: {{ $pal['primary'] }};
        --color-primary-hover: {{ $pal['primary_hover'] }};
        --color-primary-dark: {{ $pal['primary_dark'] }};
        --on-primary: {{ $pal['on_primary'] }};

        /* Neutros (escala fría, estilo Linear) */
        --bg: #f7f8fa;
        --surface: #ffffff;
        --surface-2: #f1f3f6;
        --border: #e5e8ee;
        --border-strong: #d3d8e0;
        --text: #1a1f2b;
        --text-muted: #6b7280;
        --text-faint: #9aa3b2;

        /* Semánticos */
        --ok: #1d6b34;     --ok-bg: #e8f6ec;     --ok-border: #aadfba;
        --danger: #a12222; --danger-bg: #fdecec; --danger-border: #f5b5b5;
        --warn: #a16207;   --warn-bg: #fdf6e3;   --warn-border: #f0dca0;

        /* Tipografía — display con carácter (Bricolage) + texto neutro y legible,
           para no caer en el genérico Inter/Roboto. Cargadas por CDN (sin build). */
        --font: "Hanken Grotesk", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        --font-display: "Bricolage Grotesque", var(--font);
        --fs-xs: .78rem; --fs-sm: .875rem; --fs-base: .95rem; --fs-lg: 1.125rem; --fs-xl: 1.5rem;

        /* Espaciado / radios / sombras / movimiento */
        --sp-1: .25rem; --sp-2: .5rem; --sp-3: .75rem; --sp-4: 1rem; --sp-5: 1.5rem; --sp-6: 2rem;
        --radius: 8px; --radius-sm: 6px; --radius-lg: 12px;
        --shadow-sm: 0 1px 2px rgba(16,24,40,.06), 0 1px 3px rgba(16,24,40,.08);
        --shadow-md: 0 4px 12px rgba(16,24,40,.08), 0 2px 4px rgba(16,24,40,.05);
        --ease: cubic-bezier(.4, 0, .2, 1);
        --t-fast: 150ms var(--ease);
        --t: 200ms var(--ease);

        --sidebar-w: 248px;
    }

    * { box-sizing: border-box; }
    html { -webkit-text-size-adjust: 100%; }
    body {
        margin: 0; font-family: var(--font); font-size: var(--fs-base);
        background: var(--bg); color: var(--text); line-height: 1.5;
        -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility;
    }
    a { color: var(--color-primary); }
    h1 { font-family: var(--font-display); font-size: var(--fs-xl); font-weight: 700; letter-spacing: -.02em; }
    h3 { font-family: var(--font-display); font-weight: 600; letter-spacing: -.01em; }

    /* ───────────────────────── Layout (shell con sidebar) ───────────────────────── */
    .shell { display: flex; min-height: 100vh; }

    .sidebar {
        width: var(--sidebar-w); flex-shrink: 0; background: var(--surface);
        border-right: 1px solid var(--border); display: flex; flex-direction: column;
        position: sticky; top: 0; height: 100vh; z-index: 40;
    }
    .sidebar__brand {
        display: flex; align-items: center; gap: var(--sp-3);
        padding: var(--sp-5) var(--sp-4) var(--sp-4);
        border-bottom: 1px solid var(--border); min-height: 64px;
    }
    .sidebar__brand img { max-height: 34px; max-width: 150px; }
    .sidebar__brand strong { font-family: var(--font-display); font-size: var(--fs-lg); letter-spacing: -.02em; }

    .sidebar__nav { padding: var(--sp-3); display: flex; flex-direction: column; gap: 2px; flex: 1; overflow-y: auto; }
    .nav-link {
        display: flex; align-items: center; gap: var(--sp-3);
        padding: .6rem .75rem; border-radius: var(--radius-sm);
        color: var(--text-muted); text-decoration: none; font-size: var(--fs-sm); font-weight: 550;
        transition: background var(--t-fast), color var(--t-fast);
        white-space: nowrap;
    }
    .nav-link:hover { background: var(--surface-2); color: var(--text); }
    .nav-link[aria-current="page"] {
        background: color-mix(in srgb, var(--color-primary) 12%, transparent);
        color: var(--color-primary-dark);
    }
    .nav-link .ico { width: 18px; text-align: center; flex-shrink: 0; opacity: .85; }
    .nav-sep { height: 1px; background: var(--border); margin: var(--sp-2) var(--sp-1); }
    .nav-label { font-size: var(--fs-xs); color: var(--text-faint); text-transform: uppercase;
                 letter-spacing: .05em; padding: var(--sp-3) .75rem var(--sp-1); font-weight: 600; }

    .sidebar__foot { padding: var(--sp-3); border-top: 1px solid var(--border); }

    .main-area { flex: 1; min-width: 0; display: flex; flex-direction: column; }
    .topbar {
        display: none; align-items: center; gap: var(--sp-3);
        padding: var(--sp-3) var(--sp-4); background: var(--surface);
        border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 30;
    }
    .topbar__title { font-weight: 650; }
    main { width: 100%; max-width: 1080px; margin: 0 auto; padding: var(--sp-6) var(--sp-5); }

    /* Botón hamburguesa (solo móvil) */
    .burger {
        display: inline-flex; align-items: center; justify-content: center;
        width: 40px; height: 40px; border: 1px solid var(--border-strong);
        background: var(--surface); border-radius: var(--radius-sm); cursor: pointer; font-size: 1.1rem;
    }
    .scrim {
        position: fixed; inset: 0; background: rgba(16,24,40,.45);
        z-index: 35; backdrop-filter: blur(1px);
    }

    /* ───────────────────────── Componentes base ───────────────────────── */
    .card {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius-lg); padding: var(--sp-5); box-shadow: var(--shadow-sm);
    }

    .btn {
        display: inline-flex; align-items: center; justify-content: center; gap: var(--sp-2);
        background: var(--color-primary); color: var(--on-primary); border: 1px solid transparent;
        border-radius: var(--radius-sm); padding: .55rem 1rem; cursor: pointer;
        font-size: var(--fs-sm); font-weight: 600; text-decoration: none; line-height: 1.2;
        transition: background var(--t-fast), box-shadow var(--t-fast), transform var(--t-fast);
    }
    .btn:hover { background: var(--color-primary-hover); }
    .btn:active { transform: translateY(1px); }
    .btn:focus-visible { outline: 2px solid var(--color-primary); outline-offset: 2px; }
    .btn-light { background: var(--surface); color: var(--text); border-color: var(--border-strong); }
    .btn-light:hover { background: var(--surface-2); }
    .btn-sm { padding: .35rem .7rem; font-size: var(--fs-xs); }

    label { display: block; margin: var(--sp-3) 0 var(--sp-1); font-size: var(--fs-sm); font-weight: 600; }
    input, select, textarea {
        width: 100%; padding: .55rem .7rem; border: 1px solid var(--border-strong);
        border-radius: var(--radius-sm); font-size: var(--fs-base); font-family: inherit;
        background: var(--surface); color: var(--text); transition: border-color var(--t-fast), box-shadow var(--t-fast);
    }
    input:focus, select:focus, textarea:focus {
        outline: none; border-color: var(--color-primary);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 18%, transparent);
    }

    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; padding: .6rem .7rem; font-size: var(--fs-xs); color: var(--text-muted);
         text-transform: uppercase; letter-spacing: .04em; border-bottom: 1px solid var(--border); font-weight: 600; }
    td { padding: .7rem .7rem; border-bottom: 1px solid var(--border); }
    tr:last-child td { border-bottom: 0; }
    tbody tr { transition: background var(--t-fast); }
    tbody tr:hover { background: var(--surface-2); }

    .badge {
        display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .5rem;
        border-radius: var(--radius-sm); font-size: var(--fs-xs); font-weight: 600; line-height: 1.4;
    }
    .badge-ok { background: var(--ok-bg); color: var(--ok); border: 1px solid var(--ok-border); }
    .badge-danger { background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger-border); }
    .badge-warn { background: var(--warn-bg); color: var(--warn); border: 1px solid var(--warn-border); }
    .badge-neutral { background: var(--surface-2); color: var(--text-muted); border: 1px solid var(--border); }

    .errors { background: var(--danger-bg); border: 1px solid var(--danger-border); color: var(--danger);
              padding: .7rem .9rem; border-radius: var(--radius); margin-bottom: var(--sp-4); }
    .flash { background: var(--ok-bg); border: 1px solid var(--ok-border); color: var(--ok);
             padding: .7rem .9rem; border-radius: var(--radius); margin-bottom: var(--sp-4); }

    /* ───────────────────────── Responsive: sidebar off-canvas ───────────────────────── */
    @media (max-width: 860px) {
        .topbar { display: flex; }
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100dvh;
            transform: translateX(-100%); transition: transform var(--t); box-shadow: var(--shadow-md);
        }
        .sidebar.is-open { transform: translateX(0); }
        main { padding: var(--sp-5) var(--sp-4); }
    }
    @media (min-width: 861px) {
        .scrim { display: none !important; }
    }

    [x-cloak] { display: none !important; }
</style>
