{{--
  Estilos del kiosko (tablet de marcaje) — paleta dark sofisticada propia,
  separada del panel del dueño. Filosofía (docs/frontend.md, sección 4): máximo
  mimo — cara del producto, uso diario. Estilo Linear/Vercel en dark, sin
  WebGL/3D/animaciones pesadas (línea roja). Solo CSS + Alpine, sin build.
--}}
<style>
    :root {
        /* Marca (derivada del color del cliente) */
        --color-primary: {{ $branding->colorPrimario() }};

        /* Dark sofisticado — azul-grafito, no negro plano */
        --bg-base: #0f1218;
        --bg-radial: radial-gradient(ellipse at 50% 30%,
                     #1a2030 0%, #11151c 55%, #0c0f15 100%);
        --surface: #181c25;
        --surface-2: #232834;
        --surface-3: #2c3340;
        --border: #2a3140;
        --border-strong: #3a4252;

        --text: #ecedf2;
        --text-muted: #a4abb9;
        --text-faint: #6b7280;

        --ok: #2faa6b;
        --ok-bg: #14361f;
        --warn: #e4a23c;
        --danger: #d96565;

        --font: "Hanken Grotesk", system-ui, -apple-system, sans-serif;
        --font-display: "Bricolage Grotesque", var(--font);

        --radius: 14px;
        --radius-sm: 10px;
        --radius-lg: 20px;

        --shadow-soft: 0 8px 24px rgba(0,0,0,.35), 0 2px 6px rgba(0,0,0,.25);
        --shadow-glow: 0 0 0 1px color-mix(in srgb, var(--color-primary) 25%, transparent),
                       0 0 40px -8px color-mix(in srgb, var(--color-primary) 45%, transparent);

        --ease: cubic-bezier(.4, 0, .2, 1);
        --ease-out-back: cubic-bezier(.34, 1.56, .64, 1);
        --t: 200ms var(--ease);
        --t-fast: 150ms var(--ease);
    }

    * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
    html, body { margin: 0; height: 100%; }
    body {
        font-family: var(--font);
        background: var(--bg-base);
        background-image: var(--bg-radial);
        color: var(--text);
        display: flex; flex-direction: column; min-height: 100vh;
        user-select: none;
        -webkit-font-smoothing: antialiased;
    }
    main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    [x-cloak] { display: none !important; }

    /* ───── Header chip (conexión + pendientes) ───── */
    .topchip {
        position: fixed; top: 1rem; right: 1.2rem; z-index: 10;
        display: flex; gap: .6rem; align-items: center; font-size: .85rem;
    }
    .topchip .pending {
        background: rgba(228,162,60,.12); color: var(--warn);
        border: 1px solid rgba(228,162,60,.32);
        padding: .25rem .7rem; border-radius: 999px; font-weight: 500;
    }
    .topchip .dot {
        width: 10px; height: 10px; border-radius: 50%;
        box-shadow: 0 0 0 4px rgba(255,255,255,.04);
    }
    .topchip .dot.on  { background: #3ddc84; box-shadow: 0 0 0 4px rgba(61,220,132,.12); }
    .topchip .dot.off { background: var(--warn); box-shadow: 0 0 0 4px rgba(228,162,60,.14); }

    /* ───── Pantalla espera ───── */
    .espera {
        cursor: pointer; min-height: 70vh; max-width: 520px;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 2rem; padding: 2rem 1rem;
        transition: transform var(--t);
    }
    .espera:active { transform: scale(.99); }
    .espera__logo img { max-height: 110px; filter: drop-shadow(0 4px 16px rgba(0,0,0,.4)); }
    .espera__nombre {
        font-family: var(--font-display); font-size: 2.4rem; font-weight: 700;
        letter-spacing: -.03em; text-align: center;
    }
    .espera__reloj {
        font-family: var(--font-display); font-weight: 600;
        font-size: 5.5rem; line-height: 1; letter-spacing: -.04em;
        color: var(--text);
        text-shadow: 0 2px 24px rgba(0,0,0,.5);
    }
    .espera__fecha {
        color: var(--text-muted); font-size: 1.05rem;
        text-transform: lowercase; letter-spacing: .02em;
    }
    .espera__cta {
        margin-top: .5rem; color: var(--text-muted); font-size: .98rem;
        display: inline-flex; align-items: center; gap: .55rem;
        padding: .55rem 1.1rem; border-radius: 999px;
        border: 1px solid var(--border-strong); background: rgba(255,255,255,.02);
    }
    .espera__cta .pulse {
        width: 8px; height: 8px; border-radius: 50%; background: var(--color-primary);
        box-shadow: 0 0 0 0 color-mix(in srgb, var(--color-primary) 60%, transparent);
        animation: pulse 2s var(--ease) infinite;
    }
    @keyframes pulse {
        0%   { box-shadow: 0 0 0 0   color-mix(in srgb, var(--color-primary) 50%, transparent); }
        70%  { box-shadow: 0 0 0 12px transparent; }
        100% { box-shadow: 0 0 0 0   transparent; }
    }
    .espera__offline {
        font-size: .95rem; color: var(--warn);
        background: rgba(228,162,60,.08); border: 1px solid rgba(228,162,60,.22);
        padding: .4rem .9rem; border-radius: 999px;
    }

    /* ───── Pantalla marcando ───── */
    .marcando { width: 100%; max-width: 460px; }
    .camara {
        position: relative; width: 220px; height: 220px; margin: 0 auto 1.4rem;
        border-radius: 50%; overflow: hidden;
        background: #000; box-shadow: var(--shadow-glow), var(--shadow-soft);
    }
    .camara::after { /* anillo sutil */
        content: ''; position: absolute; inset: 0; border-radius: 50%; pointer-events: none;
        border: 2px solid color-mix(in srgb, var(--color-primary) 35%, transparent);
    }
    .camara video { width: 100%; height: 100%; object-fit: cover; }
    .camara__msg {
        position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        color: var(--text-faint); text-align: center; padding: 1rem; font-size: .88rem;
        background: linear-gradient(180deg, #0c0f15, #181c25);
    }

    .input-id {
        width: 100%; font-family: var(--font-display); font-size: 1.7rem;
        text-align: center; padding: 1rem; border: 1px solid var(--border);
        border-radius: var(--radius); background: var(--surface);
        color: var(--text); letter-spacing: 3px; margin-bottom: .35rem;
        transition: border-color var(--t-fast), box-shadow var(--t-fast);
    }
    .input-id::placeholder { color: var(--text-faint); letter-spacing: 1px; font-family: var(--font); }
    .hint {
        color: var(--text-muted); font-size: .85rem; margin: 0 0 1rem; text-align: center;
    }
    .hint strong { color: var(--text); font-weight: 600; }

    .keypad { display: grid; grid-template-columns: repeat(3, 1fr); gap: .55rem; margin-bottom: 1.2rem; }
    .key {
        font-family: var(--font-display); font-size: 1.55rem; font-weight: 500;
        padding: 1.05rem 0; border: 1px solid var(--border); border-radius: var(--radius);
        background: linear-gradient(180deg, var(--surface-2), var(--surface));
        color: var(--text); cursor: pointer;
        transition: transform 80ms var(--ease), background var(--t-fast), border-color var(--t-fast);
        box-shadow: 0 1px 0 rgba(255,255,255,.04) inset, 0 2px 6px rgba(0,0,0,.2);
    }
    .key:hover { background: linear-gradient(180deg, var(--surface-3), var(--surface-2)); border-color: var(--border-strong); }
    .key:active { transform: translateY(1px) scale(.98); }
    .key.back { color: var(--text-muted); }

    .acciones { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; margin-bottom: 1rem; }
    .act {
        font-family: var(--font-display); font-size: 1.45rem; font-weight: 600;
        padding: 1.35rem 0; border: 0; border-radius: var(--radius-lg);
        cursor: pointer; transition: transform 80ms var(--ease), box-shadow var(--t-fast), background var(--t-fast);
        display: inline-flex; align-items: center; justify-content: center; gap: .55rem;
    }
    .act:active { transform: translateY(1px); }
    .act:disabled { opacity: .55; cursor: not-allowed; }
    .act--primario {
        background: var(--color-primary); color: #fff;
        box-shadow: 0 8px 22px -6px color-mix(in srgb, var(--color-primary) 60%, transparent);
    }
    .act--primario:hover:not(:disabled) { filter: brightness(1.08); }
    .act--secundario {
        background: var(--surface-2); color: var(--text); border: 1px solid var(--border-strong);
    }
    .act--secundario:hover:not(:disabled) { background: var(--surface-3); }

    .cancelar {
        display: block; margin: 0 auto; background: none; border: 0;
        color: var(--text-muted); font-size: .95rem; cursor: pointer; padding: .5rem 1rem;
    }
    .cancelar:hover { color: var(--text); }

    /* ───── Overlay de resultado (el "wow" del marcaje) ───── */
    .overlay {
        position: fixed; inset: 0; z-index: 50; padding: 2rem;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        text-align: center; backdrop-filter: blur(2px);
    }
    .overlay--ok    { background: linear-gradient(135deg, #0e3d23 0%, #0a2e1a 100%); }
    .overlay--err   { background: linear-gradient(135deg, #3d1a1a 0%, #2a1010 100%); }

    .check {
        width: 120px; height: 120px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 4.5rem; color: #fff; font-weight: 300;
        animation: checkin 520ms var(--ease-out-back) both;
    }
    .check--ok  { background: rgba(47,170,107,.18); box-shadow: 0 0 0 8px rgba(47,170,107,.10), 0 0 60px rgba(47,170,107,.35); }
    .check--err { background: rgba(217,101,101,.18); box-shadow: 0 0 0 8px rgba(217,101,101,.10), 0 0 60px rgba(217,101,101,.35); }
    @keyframes checkin {
        0%   { opacity: 0; transform: scale(.4); }
        60%  { opacity: 1; transform: scale(1.08); }
        100% { opacity: 1; transform: scale(1); }
    }
    .resultado__titulo {
        font-family: var(--font-display); font-size: 2.4rem; font-weight: 700;
        margin: 1.5rem 0 .4rem; letter-spacing: -.02em;
    }
    .resultado__detalle { font-size: 1.1rem; color: rgba(255,255,255,.85); }
    .resultado__continuar {
        margin-top: 2rem; opacity: .65; font-size: .9rem;
        display: inline-flex; align-items: center; gap: .4rem;
    }
</style>
