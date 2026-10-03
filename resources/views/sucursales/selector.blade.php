<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#061a3f">
<title>Bolívar · Seleccionar sucursal</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --azul-950: #061a3f;
    --azul-900: #08285f;
    --azul-800: #0d3478;
    --azul-700: #174a9b;
    --azul-600: #2563c7;
    --azul-500: #3b82e8;
    --azul-100: #eaf2ff;
    --azul-50: #f5f8ff;
    --texto-900: #172033;
    --texto-700: #344054;
    --texto-500: #5b6678;
    --texto-400: #8792a5;
    --borde: #e4e8ef;
    --gap: 16px;
    --cols: 5;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { min-height: 100%; -webkit-text-size-adjust: 100%; }

body {
    min-height: 100dvh;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    color: #fff;
    background-color: var(--azul-950);
    background-image:
        /* cuadrícula muy sutil */
        linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px),
        /* luces de ambiente */
        radial-gradient(ellipse 70% 50% at 50% -8%, rgba(59,130,232,.38), transparent 70%),
        radial-gradient(ellipse 50% 40% at 100% 100%, rgba(37,99,199,.22), transparent 70%),
        /* base */
        linear-gradient(170deg, #071f4b 0%, var(--azul-950) 60%, #04122d 100%);
    background-size: 44px 44px, 44px 44px, auto, auto, auto;
    background-repeat: repeat, repeat, no-repeat, no-repeat, no-repeat;
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

button, a { -webkit-tap-highlight-color: transparent; }
:focus-visible { outline: 3px solid rgba(147,197,253,.85); outline-offset: 3px; border-radius: 14px; }

/* ========== Estructura ========== */
.pagina {
    position: relative;
    min-height: 100dvh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding:
        max(40px, calc(env(safe-area-inset-top) + 28px))
        max(20px, env(safe-area-inset-right))
        max(36px, calc(env(safe-area-inset-bottom) + 24px))
        max(20px, env(safe-area-inset-left));
}

.contenedor { width: 100%; max-width: 1120px; margin: auto; }

/* ========== Encabezado ========== */
.encabezado { text-align: center; margin-bottom: 34px; }

.marca {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 20px;
    padding: 6px 14px 6px 7px;
    border: 1px solid rgba(255,255,255,.16);
    border-radius: 999px;
    background: rgba(255,255,255,.08);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.marca-icono {
    width: 26px;
    height: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #fff;
    color: var(--azul-900);
}
.marca-icono svg { width: 15px; height: 15px; }

.marca-texto {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #fff;
}

.encabezado h1 {
    font-size: clamp(26px, 4vw, 38px);
    line-height: 1.15;
    letter-spacing: -.025em;
    font-weight: 700;
    color: #fff;
    margin-bottom: 12px;
}

.encabezado p {
    max-width: 480px;
    margin: 0 auto;
    font-size: 14.5px;
    line-height: 1.6;
    color: rgba(255,255,255,.76);
}

/* ========== Rejilla de tarjetas ==========
   Flex centrado: si hay 5, 6 o más sucursales las filas incompletas
   quedan centradas en vez de pegadas a la izquierda. */
.rejilla {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--gap);
}

.card-sucursal {
    position: relative;
    display: flex;
    flex: 0 0 calc((100% - (var(--cols) - 1) * var(--gap)) / var(--cols));
    min-width: 0;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 1px 2px rgba(0,0,0,.18), 0 14px 34px rgba(2,10,30,.38);
    overflow: hidden;
    transition: transform .2s cubic-bezier(.2,.8,.2,1), box-shadow .2s ease;
}

.card-sucursal::before {
    content: "";
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--azul-900), var(--azul-500));
    opacity: .0;
    transition: opacity .2s ease;
}

.card-sucursal:hover { transform: translateY(-5px); box-shadow: 0 2px 4px rgba(0,0,0,.2), 0 22px 48px rgba(2,10,30,.5); }
.card-sucursal:hover::before { opacity: 1; }

.card-sucursal form { flex: 1; display: flex; min-width: 0; }

.card-sucursal button {
    flex: 1;
    width: 100%;
    min-height: 268px;
    border: 0;
    padding: 26px 16px 18px;
    background: transparent;
    color: var(--texto-900);
    cursor: pointer;
    font: inherit;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}
.card-sucursal button:active { transform: scale(.985); }

.tipo {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 11px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--azul-700);
    background: var(--azul-100);
    margin-bottom: 18px;
}
.tipo-punto { width: 6px; height: 6px; border-radius: 50%; background: var(--azul-600); }

.logo-envoltorio {
    flex-shrink: 0;
    width: 104px;
    height: 104px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
    border-radius: 22px;
    background: #fff;
    border: 1px solid #e9edf3;
    box-shadow: 0 6px 20px rgba(16,24,40,.08);
    transition: transform .22s ease;
}
.card-sucursal:hover .logo-envoltorio { transform: scale(1.04); }
.logo-envoltorio img { display: block; width: 86%; height: 86%; object-fit: contain; }

.texto-sucursal { display: flex; flex-direction: column; align-items: center; min-width: 0; flex: 1; }
.nombre-sucursal { font-size: 15.5px; line-height: 1.3; font-weight: 700; color: var(--texto-900); max-width: 100%; overflow-wrap: anywhere; }
.canal-sucursal { margin-top: 5px; font-size: 12px; line-height: 1.4; color: var(--texto-500); }

.card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    margin-top: 16px;
    padding-top: 13px;
    border-top: 1px solid #edf0f4;
    color: var(--texto-500);
    font-size: 11.5px;
    font-weight: 600;
    transition: color .2s ease;
}
.card-sucursal:hover .card-footer { color: var(--azul-600); }
.seleccionar { display: flex; align-items: center; gap: 4px; }
.flecha { width: 15px; height: 15px; flex-shrink: 0; transition: transform .2s ease; }
.card-sucursal:hover .flecha { transform: translateX(3px); }

/* ========== Cerrar sesión ========== */
.cerrar-sesion {
    position: absolute;
    top: max(18px, env(safe-area-inset-top));
    right: max(18px, env(safe-area-inset-right));
    width: 42px;
    height: 42px;
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 12px;
    background: rgba(255,255,255,.1);
    color: rgba(255,255,255,.9);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    transition: color .2s ease, border-color .2s ease, background .2s ease;
}
.cerrar-sesion:hover { color: #fff; border-color: rgba(255,255,255,.4); background: rgba(255,255,255,.18); }
.cerrar-sesion svg { width: 18px; height: 18px; }
.cerrar-sesion button {
    background: none; border: 0; padding: 0; width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: inherit; border-radius: inherit;
}

/* ========== Pie ========== */
.pie-pagina {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    margin-top: 28px; font-size: 11.5px; color: rgba(255,255,255,.62);
}
.seguridad-icono { width: 14px; height: 14px; flex-shrink: 0; color: rgba(255,255,255,.62); }

/* =========================================================
   RESPONSIVE
   ≥1101px : 5 por fila
   761–1100: 3 por fila
   561–760 : 2 por fila
   ≤560    : 1 por fila, tarjeta horizontal (logo · nombre · flecha),
             compacta para que las 5 sucursales entren sin scroll
   ========================================================= */
@media (max-width: 1100px) {
    :root { --cols: 3; }
    .contenedor { max-width: 780px; }
    .card-sucursal button { min-height: 250px; }
}

@media (max-width: 760px) {
    :root { --cols: 2; --gap: 14px; }
    .pagina { align-items: flex-start; }
    .contenedor { max-width: 560px; }
    .encabezado { margin-bottom: 28px; }
    .card-sucursal button { min-height: 236px; padding: 22px 14px 16px; }
    .logo-envoltorio { width: 92px; height: 92px; }
}

@media (max-width: 560px) {
    :root { --cols: 1; --gap: 8px; }

    .pagina {
        padding:
            max(14px, env(safe-area-inset-top))
            max(16px, env(safe-area-inset-right))
            max(16px, calc(env(safe-area-inset-bottom) + 10px))
            max(16px, env(safe-area-inset-left));
    }
    .cerrar-sesion { width: 38px; height: 38px; top: max(12px, env(safe-area-inset-top)); right: max(14px, env(safe-area-inset-right)); }

    .encabezado { margin-bottom: 14px; }
    .marca { margin-bottom: 10px; padding: 5px 12px 5px 6px; }
    .encabezado h1 { font-size: 22px; margin-bottom: 6px; }
    .encabezado p { font-size: 13px; line-height: 1.45; max-width: 320px; }

    /* Tarjeta horizontal compacta */
    .card-sucursal { border-radius: 14px; }
    .card-sucursal:hover { transform: none; }
    .card-sucursal button {
        flex-direction: row;
        align-items: center;
        text-align: left;
        min-height: 68px;
        padding: 10px 14px;
        gap: 12px;
    }
    .card-sucursal button:active { background: var(--azul-50); transform: none; }

    .tipo { display: none; }            /* el texto de canal ya lo dice */
    .logo-envoltorio { width: 48px; height: 48px; border-radius: 13px; margin-bottom: 0; box-shadow: 0 3px 10px rgba(16,24,40,.08); }
    .card-sucursal:hover .logo-envoltorio { transform: none; }

    .texto-sucursal { align-items: flex-start; }
    .nombre-sucursal { font-size: 15px; }
    .canal-sucursal { font-size: 12px; margin-top: 2px; }

    .card-footer {
        width: auto; margin: 0 0 0 auto; padding: 0; border: 0;
        flex-shrink: 0;
    }
    .seleccionar { display: none; }
    .flecha {
        width: 30px; height: 30px; padding: 7px; border-radius: 50%;
        background: var(--azul-100); color: var(--azul-700);
    }
    .card-sucursal:hover .flecha { transform: none; }

    .pie-pagina { display: none; }
}

/* Celulares bajitos: se quita el texto de ayuda para que siga sin scroll */
@media (max-width: 560px) and (max-height: 700px) {
    .encabezado p { display: none; }
    .encabezado { margin-bottom: 12px; }
}

@media (max-width: 360px) {
    .pagina { padding-left: 12px; padding-right: 12px; }
    .encabezado h1 { font-size: 21px; }
    .card-sucursal button { padding: 9px 12px; gap: 10px; min-height: 64px; }
    .logo-envoltorio { width: 44px; height: 44px; }
    .nombre-sucursal { font-size: 14.5px; }
    .flecha { width: 28px; height: 28px; padding: 6px; }
}

/* Celulares en horizontal (poca altura) */
@media (max-height: 480px) and (orientation: landscape) {
    .pagina { align-items: flex-start; }
    .encabezado { margin-bottom: 18px; }
    .encabezado h1 { font-size: 22px; }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { scroll-behavior: auto !important; transition: none !important; animation: none !important; }
}
</style>
</head>

<body>
<main class="pagina">

<form method="POST" action="{{ route('logout') }}" class="cerrar-sesion" title="Cerrar sesión">
    @csrf
    <button type="submit" aria-label="Cerrar sesión">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <path d="M16 17l5-5-5-5" />
            <path d="M21 12H9" />
        </svg>
    </button>
</form>

<div class="contenedor">

<header class="encabezado">
    <div class="marca">
        <span class="marca-icono">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 21V5a2 2 0 0 1 2-2h12v18" />
                <path d="M4 21h16" />
                <path d="M8 7h2" />
                <path d="M14 7h2" />
                <path d="M8 11h2" />
                <path d="M14 11h2" />
                <path d="M8 15h2" />
                <path d="M14 15h2" />
            </svg>
        </span>
        <span class="marca-texto">Distribuidora Bolívar</span>
    </div>

    <h1>Selecciona tu sucursal</h1>
    <p>Elige la sucursal con la que deseas trabajar. Esta selección se aplicará durante tu sesión actual.</p>
</header>

<section class="rejilla" aria-label="Seleccionar sucursal">
    @foreach ($sucursales as $sucursal)
    <article class="card-sucursal">
        <form method="POST" action="{{ route('sucursales.elegir') }}">
            @csrf
            <input type="hidden" name="sucursal_id" value="{{ $sucursal->id }}">
            <button type="submit" aria-label="Seleccionar {{ $sucursal->nombre }}">
                <span class="tipo">
                    <span class="tipo-punto"></span>
                    {{ $sucursal->canal === 'mayorista' ? 'Mayorista' : 'Detalle' }}
                </span>

                <div class="logo-envoltorio">
                    @if ($sucursal->canal === 'mayorista')
                        <img src="{{ asset('images/logo_guana.png') }}" alt="Logo {{ $sucursal->nombre }}" loading="lazy">
                    @else
                        <img src="{{ asset('images/logo_azur.png') }}" alt="Logo {{ $sucursal->nombre }}" loading="lazy">
                    @endif
                </div>

                <div class="texto-sucursal">
                    <span class="nombre-sucursal">{{ $sucursal->nombre }}</span>
                    <span class="canal-sucursal">{{ $sucursal->canal === 'mayorista' ? 'Venta al por mayor' : 'Venta al detalle' }}</span>
                </div>

                <div class="card-footer">
                    <span class="seleccionar">Ingresar</span>
                    <svg class="flecha" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14" />
                        <path d="m13 6 6 6-6 6" />
                    </svg>
                </div>
            </button>
        </form>
    </article>
    @endforeach
</section>

<footer class="pie-pagina">
    <svg class="seguridad-icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 3 5 6v5c0 4.7 2.9 8.6 7 10 4.1-1.4 7-5.3 7-10V6l-7-3Z" />
        <path d="m9.5 12 1.7 1.7 3.5-3.5" />
    </svg>
    <span>Acceso seguro · Sesión individual</span>
</footer>

</div>
</main>
</body>
</html>