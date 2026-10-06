{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Panel') — Distribuidora Bolívar</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --azul-900: #08285f;
            --azul-oscuro: #0A2E6E;
            --azul-medio: #2E6BD6;
            --azul-600: #2563c7;
            --azul-100: #eaf2ff;
            --azul-50: #f5f8ff;
            --texto: #1B2430;
            --texto-tenue: #6D7480;
            --texto-400: #98a2b3;
            --borde: #E3E1DB;
            --borde-hover: #c9d5e8;
            --superficie: #ffffff;
            --fondo: #F7F8FA;
            --sombra-card: 0 1px 2px rgba(16, 24, 40, .03), 0 8px 30px rgba(16, 24, 40, .04);
            --sombra-hover: 0 2px 4px rgba(16, 24, 40, .04), 0 18px 45px rgba(16, 24, 40, .10);
            --barra-inferior-alto: 64px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            overflow-x: hidden;
            max-width: 100%;
        }

        html {
            -webkit-text-size-adjust: 100%;
        }

        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--texto);
            background: var(--fondo);
            display: flex;
            min-height: 100dvh;
            -webkit-font-smoothing: antialiased;
        }

        button,
        a {
            -webkit-tap-highlight-color: transparent;
        }

        :focus-visible {
            outline: 2px solid var(--azul-medio);
            outline-offset: 2px;
        }

        /* ===== Sidebar (desktop) ===== */
        .sidebar {
            width: 240px;
            flex-shrink: 0;
            background: linear-gradient(180deg, var(--azul-oscuro), var(--azul-900));
            color: #fff;
            display: flex;
            flex-direction: column;
            padding: 24px 0;
            position: relative;
            overflow: hidden;
            max-width: 100vw;
        }

        .sidebar .marca {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 0 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            margin-bottom: 16px;
            text-align: center;
            flex-shrink: 0;
        }

        .sidebar .marca .logo {
            width: 84px;
            height: 84px;
            object-fit: contain;
            border-radius: 14px;
            background: #fff;
            padding: 6px;
        }

        .sidebar .marca .admin-nombre {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-weight: 600;
            font-size: 14px;
            color: #fff;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 100%;
        }

        .sidebar nav {
            position: relative;
            z-index: 1;
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;
            overflow-y: auto;
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* Edge viejo */
        }

        .sidebar nav::-webkit-scrollbar {
            display: none;
            /* Chrome, Edge, Safari */
        }

        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 11px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            padding: 12px 24px;
            font-size: 14px;
            border-left: 3px solid transparent;
            transition: background .15s, color .15s;
            flex-shrink: 0;
        }

        .sidebar nav a svg {
            width: 17px;
            height: 17px;
            flex-shrink: 0;
        }

        .sidebar nav a:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        .sidebar nav a.activo {
            background: rgba(255, 255, 255, 0.12);
            border-left-color: #fff;
            font-weight: 600;
            color: #fff;
        }

        .sidebar .cerrar-sesion {
            position: relative;
            z-index: 1;
            padding: 12px 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            flex-shrink: 0;
        }

        .sidebar .cerrar-sesion button {
            display: flex;
            align-items: center;
            gap: 9px;
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.85);
            font-size: 14px;
            font-family: inherit;
            cursor: pointer;
            padding: 0;
        }

        .sidebar .cerrar-sesion button svg {
            width: 16px;
            height: 16px;
        }

        .sidebar .cerrar-sesion button:hover {
            color: #fff;
        }



        /* ===== Badge de contador (Apartados) ===== */
        .badge-contador {
            display: none;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #e5484d;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            margin-left: auto;
            flex-shrink: 0;
        }

        /* Contenido */
        .contenido {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .topbar {
            background: var(--superficie);
            border-bottom: 1px solid var(--borde);
            padding: 14px 32px;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 20;
        }

        .topbar .sucursal {
            font-weight: 600;
            color: var(--azul-oscuro);
            font-size: 15px;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Botón "Cambiar de sucursal": lleva a la pantalla del selector */
        .btn-cambiar-sucursal {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            flex-shrink: 0;
            padding: 7px 12px;
            border: 1px solid var(--borde);
            border-radius: 9px;
            background: #fff;
            color: var(--azul-oscuro);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background .15s, border-color .15s;
        }

        .btn-cambiar-sucursal svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .btn-cambiar-sucursal:hover {
            background: var(--azul-50);
            border-color: var(--borde-hover);
        }

        .btn-menu {
            display: none;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: 1px solid var(--borde);
            border-radius: 9px;
            background: #fff;
            color: var(--texto-tenue);
            cursor: pointer;
            flex-shrink: 0;
        }

        .btn-menu svg {
            width: 18px;
            height: 18px;
        }

        .main {
            padding: 32px;
            flex: 1;
            min-width: 0;
        }

        /* Utilidades compartidas para las vistas hijas */
        .exito {
            background: #e6ffed;
            color: #036b26;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .errores {
            background: #ffecec;
            color: #a30000;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .tabla-envoltorio {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            box-shadow: var(--sombra-card);
        }

        table {
            width: 100%;
            min-width: 560px;
            border-collapse: collapse;
            background: var(--superficie);
        }

        th,
        td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid var(--borde);
            font-size: 14px;
            white-space: nowrap;
        }

        th {
            background: #F0F3F8;
            color: var(--texto-tenue);
            font-weight: 600;
            font-size: 13px;
        }

        /* Overlay para el menú móvil */
        .overlay-menu {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(8, 40, 95, 0.45);
            z-index: 39;
        }

        /* ===== Barra inferior (solo móvil) ===== */
        .barra-inferior {
            display: none;
        }

        /* =========================================================
           RESPONSIVE
           ========================================================= */
        @media (max-width: 980px) {
            .main {
                padding: 24px;
            }
        }

        @media (max-width: 760px) {
            body {
                flex-direction: column;
            }

            /* La sidebar se convierte en panel deslizable (drawer) */
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 78vw;
                max-width: 300px;
                z-index: 40;
                transform: translateX(-100%);
                will-change: transform;
                transition: transform .25s ease;
                padding-top: max(24px, env(safe-area-inset-top));
                padding-bottom: max(16px, env(safe-area-inset-bottom));
                box-shadow: 0 0 40px rgba(0, 0, 0, .25);
            }

            body.menu-abierto .sidebar {
                transform: translateX(0);
            }

            body.menu-abierto .overlay-menu {
                display: block;
            }

            .btn-menu {
                display: inline-flex;
            }

            .topbar {
                padding: 12px 16px;
                padding-top: max(12px, env(safe-area-inset-top));
            }

            .main {
                padding: 18px 16px;
                padding-bottom: calc(var(--barra-inferior-alto) + 18px + env(safe-area-inset-bottom));
            }

            table {
                min-width: 480px;
            }

            th,
            td {
                padding: 9px 10px;
                font-size: 13px;
            }

            /* Barra inferior tipo app, con desplazamiento horizontal */
            .barra-inferior {
                display: block;
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 30;
                background: rgba(255, 255, 255, .96);
                backdrop-filter: blur(10px);
                border-top: 1px solid var(--borde);
                padding-bottom: env(safe-area-inset-bottom);
            }

            .barra-scroll {
                position: relative;
                display: flex;
                overflow-x: auto;
                overscroll-behavior-x: contain;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                padding: 6px max(8px, env(safe-area-inset-right)) 6px max(8px, env(safe-area-inset-left));
            }

            .barra-scroll::-webkit-scrollbar {
                display: none;
            }

            /* Degradados en los bordes: avisan que hay más opciones a ese lado */
            .barra-inferior::before,
            .barra-inferior::after {
                content: "";
                position: absolute;
                top: 1px;
                bottom: env(safe-area-inset-bottom);
                width: 26px;
                z-index: 2;
                pointer-events: none;
                opacity: 0;
                transition: opacity .2s;
            }

            .barra-inferior::before {
                left: 0;
                background: linear-gradient(90deg, rgba(255, 255, 255, .98), rgba(255, 255, 255, 0));
            }

            .barra-inferior::after {
                right: 0;
                background: linear-gradient(270deg, rgba(255, 255, 255, .98), rgba(255, 255, 255, 0));
            }

            .barra-inferior.hay-izq::before,
            .barra-inferior.hay-der::after {
                opacity: 1;
            }

            .barra-inferior a {
                flex: 0 0 auto;
                width: 78px;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 3px;
                padding: 6px 2px;
                text-decoration: none;
                color: var(--texto-400);
                font-size: 10px;
                font-weight: 600;
                white-space: nowrap;
                border-radius: 10px;
                transition: color .15s, background .15s;
                position: relative;
            }

            .barra-inferior a svg {
                width: 20px;
                height: 20px;
            }

            .barra-inferior a:active {
                background: var(--azul-50);
            }

            .barra-inferior a.activo {
                color: var(--azul-600);
                background: var(--azul-50);
            }

            /* En la barra inferior el badge flota sobre el ícono, no al lado del texto */
            .barra-inferior a .badge-contador {
                position: absolute;
                top: 2px;
                right: 12px;
                margin-left: 0;
                min-width: 16px;
                height: 16px;
                font-size: 10px;
                padding: 0 4px;
            }
        }


        /* ===== Desktop: layout fijo, solo scrollea el contenido ===== */
        @media (min-width: 761px) {
            body {
                height: 100dvh;
                overflow: hidden;
            }

            .contenido {
                height: 100dvh;
                min-height: 0;
                overflow: hidden;
            }

            .topbar {
                flex-shrink: 0;
            }

            .main {
                flex: 1 1 auto;
                min-height: 0;
                overflow-y: auto;
                overscroll-behavior: contain;
            }
        }

        @media (max-width: 420px) {

            /* En pantallas muy angostas queda solo el ícono para que quepa el nombre */
            .btn-cambiar-texto {
                display: none;
            }

            .btn-cambiar-sucursal {
                padding: 7px 9px;
            }
        }

        @media (max-width: 380px) {
            .barra-inferior a {
                font-size: 9px;
            }

            .barra-inferior a svg {
                width: 18px;
                height: 18px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
            }
        }

        /* ===== Paginación compartida ===== */
        .paginacion {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--borde);
        }

        .paginacion-info {
            font-size: 13px;
            color: var(--texto-tenue);
            white-space: nowrap;
        }

        .paginacion-info strong {
            color: var(--texto);
            font-weight: 600;
        }

        .paginacion-controles {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pag-nav {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 36px;
            padding: 0 14px;
            border-radius: 9px;
            border: 1px solid var(--borde);
            background: #fff;
            color: var(--texto);
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background .15s, border-color .15s;
        }

        .pag-nav svg {
            width: 14px;
            height: 14px;
        }

        .pag-nav:hover:not(:disabled) {
            background: #F5F7FA;
            border-color: var(--borde-hover);
        }

        .pag-nav:disabled {
            color: var(--texto-400);
            cursor: default;
            background: #fff;
        }

        .pag-numeros {
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .pag-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 4px;
            border-radius: 8px;
            border: none;
            background: none;
            cursor: pointer;
            color: var(--texto-tenue);
            font-size: 13px;
            font-weight: 500;
            font-family: inherit;
        }

        .pag-num:hover {
            background: #EAF1FD;
            color: var(--texto);
        }

        .pag-num.pag-activo {
            background: var(--azul-medio);
            color: #fff;
            font-weight: 600;
        }

        .pag-puntos {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 34px;
            color: var(--texto-400);
            font-size: 13px;
        }

        .pag-actual-movil {
            display: none;
            font-size: 13px;
            font-weight: 600;
            color: var(--texto);
        }

        @media (max-width: 640px) {
            .paginacion-info {
                display: none;
            }

            .paginacion-controles {
                width: 100%;
                justify-content: space-between;
            }

            .pag-numeros {
                display: none;
            }

            .pag-actual-movil {
                display: inline-flex;
            }

            .pag-nav {
                flex: 1;
                justify-content: center;
            }
        }

        /* ===== Tabla con scroll interno (desktop) ===== */
        .tabla-scroll {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 60vh;
            border-radius: 10px;
            border: 1px solid var(--borde);
            box-shadow: var(--sombra-card);
        }

        .tabla-scroll table {
            border-radius: 0;
        }

        .tabla-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 1;
        }

        @media (min-width: 761px) {
            .sidebar {
                padding: clamp(16px, 2.5vh, 28px) 0;
            }

            .sidebar .marca {
                gap: clamp(6px, 1vh, 12px);
                padding-bottom: clamp(12px, 2vh, 22px);
                margin-bottom: clamp(8px, 1.5vh, 18px);
            }

            .sidebar .marca .logo {
                width: clamp(64px, 9vh, 96px);
                height: clamp(64px, 9vh, 96px);
            }

            .sidebar nav a {
                padding: clamp(9px, 1.55vh, 16px) 24px;
                font-size: clamp(14px, 1.7vh, 15.5px);
            }

            .sidebar nav a svg {
                width: clamp(17px, 2vh, 20px);
                height: clamp(17px, 2vh, 20px);
            }
        }

        /* Escritorio: se ve como a 90% de zoom. Cambiá --escala (.85, .8...) para ajustar. */
        @media (min-width: 761px) {
            :root {
                --escala: .9;
            }

            .sidebar,
            .topbar,
            .main {
                zoom: var(--escala);
            }
        }
        /* Títulos de pantalla: mismo color y peso que Generar PDF */
.main h1 {
    color: var(--azul-oscuro) !important;
    font-size: 22px;
    font-weight: 700;
}
    </style>
    @stack('estilos')
</head>

<body>

    @php
        // "Generar PDF" solo existe para la sucursal mayorista (Guana).
        $canalActual = $sucursalActual->canal ?? null;
        $canalActual = $canalActual instanceof \BackedEnum ? $canalActual->value : $canalActual;
        $esGuana = $canalActual === 'mayorista';
    @endphp

    <div class="overlay-menu" onclick="cerrarMenu()"></div>

    <aside class="sidebar">
        <div class="marca">
            <img src="{{ asset('images/fondo.png') }}" alt="Distribuidora Bolívar" class="logo">
            <span class="admin-nombre">{{ auth()->user()->name }}</span>
        </div>

        <nav>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <rect x="3" y="3" width="8" height="8" rx="1.5" />
                    <rect x="13" y="3" width="8" height="8" rx="1.5" />
                    <rect x="3" y="13" width="8" height="8" rx="1.5" />
                    <rect x="13" y="13" width="8" height="8" rx="1.5" />
                </svg>
                Dashboard
            </a>
            <a href="{{ route('clientes.index') }}" class="{{ request()->routeIs('clientes.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="12" cy="8" r="3.5" />
                    <path d="M5 20c0-3.5 3-6 7-6s7 2.5 7 6" />
                </svg>
                Clientes
            </a>
            <a href="{{ route('abonos.index') }}" class="{{ request()->routeIs('abonos.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 7v10M9.5 9.5h4a1.5 1.5 0 0 1 0 3h-3a1.5 1.5 0 0 0 0 3H15" />
                </svg>
                Lista abono/factura
            </a>
            <a href="{{ route('facturacion.index') }}"
                class="{{ request()->routeIs('facturacion.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M7 3h8l4 4v14H7Z" />
                    <path d="M15 3v4h4" />
                    <path d="M9 13h6M9 17h6M9 9h2" />
                </svg>
                Facturación
            </a>
            <a href="{{ route('apartados.index') }}" class="{{ request()->routeIs('apartados.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M21 8 12 3 3 8l9 5 9-5Z" />
                    <path d="M3 8v8l9 5 9-5V8" />
                    <path d="M12 13v8" />
                </svg>
                Apartados
                <span class="badge-contador" id="badge-apartados"></span>
            </a>
            <a href="{{ route('traspaso.index') }}" class="{{ request()->routeIs('traspaso.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M4 8h14" />
                    <path d="m14 4 4 4-4 4" />
                    <path d="M20 16H6" />
                    <path d="m10 12-4 4 4 4" />
                </svg>
                Traspaso de cuenta
            </a>
            @if (auth()->user()->es_superadmin)
                <a href="{{ route('compras.index') }}" class="{{ request()->routeIs('compras.*') ? 'activo' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 8h12l-1 12H7L6 8Z" />
                        <path d="M9 8V6a3 3 0 0 1 6 0v2" />
                    </svg>
                    Compras
                </a>
            @endif
            <a href="{{ route('gastos.index') }}" class="{{ request()->routeIs('gastos.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M20 12V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-3" />
                    <path d="M20 12h-4a2 2 0 0 0 0 4h4v-4Z" />
                </svg>
                Gastos
            </a>
            <a href="{{ route('inventario.index') }}"
                class="{{ request()->routeIs('inventario.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8 12 3 3 8l9 5 9-5Z" />
                    <path d="M3 8v8l9 5 9-5V8" />
                    <path d="M12 13v8" />
                </svg>
                Inventario
            </a>
            <a href="{{ route('rutas.index') }}" class="{{ request()->routeIs('rutas.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="6" cy="19" r="2" />
                    <circle cx="18" cy="5" r="2" />
                    <path d="M8 19h7a4 4 0 0 0 0-8H9a4 4 0 0 1 0-8h7" />
                </svg>
                Rutas
            </a>
            @if (auth()->user()->es_superadmin)
                <a href="{{ route('reportes.index') }}"
                    class="{{ request()->routeIs('reportes.*') ? 'activo' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 20V10M10 20V4M16 20v-7M4 20h16" />
                    </svg>
                    Reportes
                </a>
            @endif
            @if ($esGuana)
                <a href="{{ route('generar-pdf.index') }}"
                    class="{{ request()->routeIs('generar-pdf.*') ? 'activo' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z" />
                        <path d="M14 3v5h5" />
                        <path d="M9 13h6M9 17h4" />
                    </svg>
                    Generar PDF
                </a>
            @endif
            @if (auth()->user()->es_superadmin)
                <a href="{{ route('administradores.index') }}"
                    class="{{ request()->routeIs('administradores.*') ? 'activo' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3l7 3v6c0 5-3.5 8-7 9-3.5-1-7-4-7-9V6l7-3Z" />
                        <path d="M9 12l2 2 4-4" />
                    </svg>
                    Administradores
                </a>
            @endif

        </nav>



        <div class="cerrar-sesion">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <path d="M16 17l5-5-5-5" />
                        <path d="M21 12H9" />
                    </svg>
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    <div class="contenido">
        <header class="topbar">
            <button type="button" class="btn-menu" onclick="abrirMenu()" aria-label="Abrir menú">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <span class="sucursal">{{ $sucursalActual->nombre ?? 'Sin sucursal' }}</span>

            <a href="{{ route('sucursales.selector') }}" class="btn-cambiar-sucursal" title="Cambiar de sucursal"
                aria-label="Cambiar de sucursal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l1.5-5h15L21 9" />
                    <path d="M4 9v11h16V9" />
                    <path d="M9 20v-6h6v6" />
                </svg>
                <span class="btn-cambiar-texto">Cambiar de sucursal</span>
            </a>
        </header>

        <main class="main">
            @if (session('exito'))
                <div class="exito">{{ session('exito') }}</div>
            @endif

            @if ($errors->any())
                <div class="errores">
                    <ul style="margin:0; padding-left:18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('contenido')
        </main>
    </div>

    {{-- Barra inferior tipo app, visible solo en móvil. Se desliza a los lados
         y trae las mismas secciones que el menú lateral. --}}
    <nav class="barra-inferior">
        <div class="barra-scroll">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="8" height="8" rx="1.5" />
                    <rect x="13" y="3" width="8" height="8" rx="1.5" />
                    <rect x="3" y="13" width="8" height="8" rx="1.5" />
                    <rect x="13" y="13" width="8" height="8" rx="1.5" />
                </svg>
                Inicio
            </a>
            <a href="{{ route('clientes.index') }}" class="{{ request()->routeIs('clientes.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="8" r="3.5" />
                    <path d="M5 20c0-3.5 3-6 7-6s7 2.5 7 6" />
                </svg>
                Clientes
            </a>
            <a href="{{ route('abonos.index') }}" class="{{ request()->routeIs('abonos.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 7v10M9.5 9.5h4a1.5 1.5 0 0 1 0 3h-3a1.5 1.5 0 0 0 0 3H15" />
                </svg>
                Listado
            </a>
            <a href="{{ route('facturacion.index') }}"
                class="{{ request()->routeIs('facturacion.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M7 3h8l4 4v14H7Z" />
                    <path d="M15 3v4h4" />
                    <path d="M9 13h6M9 17h6M9 9h2" />
                </svg>
                Facturación
            </a>
            <a href="{{ route('apartados.index') }}"
                class="{{ request()->routeIs('apartados.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8 12 3 3 8l9 5 9-5Z" />
                    <path d="M3 8v8l9 5 9-5V8" />
                    <path d="M12 13v8" />
                </svg>
                Apartados
                <span class="badge-contador" id="badge-apartados-movil"></span>
            </a>
            <a href="{{ route('rutas.index') }}" class="{{ request()->routeIs('rutas.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="6" cy="19" r="2" />
                    <circle cx="18" cy="5" r="2" />
                    <path d="M8 19h7a4 4 0 0 0 0-8H9a4 4 0 0 1 0-8h7" />
                </svg>
                Rutas
            </a>
            <a href="{{ route('traspaso.index') }}" class="{{ request()->routeIs('traspaso.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 8h14" />
                    <path d="m14 4 4 4-4 4" />
                    <path d="M20 16H6" />
                    <path d="m10 12-4 4 4 4" />
                </svg>
                Traspaso
            </a>
            @if (auth()->user()->es_superadmin)
                <a href="{{ route('compras.index') }}"
                    class="{{ request()->routeIs('compras.*') ? 'activo' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 8h12l-1 12H7L6 8Z" />
                        <path d="M9 8V6a3 3 0 0 1 6 0v2" />
                    </svg>
                    Compras
                </a>
            @endif
            <a href="{{ route('gastos.index') }}" class="{{ request()->routeIs('gastos.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 12V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-3" />
                    <path d="M20 12h-4a2 2 0 0 0 0 4h4v-4Z" />
                </svg>
                Gastos
            </a>
            <a href="{{ route('inventario.index') }}"
                class="{{ request()->routeIs('inventario.*') ? 'activo' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8 12 3 3 8l9 5 9-5Z" />
                    <path d="M3 8v8l9 5 9-5V8" />
                    <path d="M12 13v8" />
                </svg>
                Inventario
            </a>
            @if (auth()->user()->es_superadmin)
                <a href="{{ route('reportes.index') }}"
                    class="{{ request()->routeIs('reportes.*') ? 'activo' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 20V10M10 20V4M16 20v-7M4 20h16" />
                    </svg>
                    Reportes
                </a>
            @endif
            @if ($esGuana)
                <a href="{{ route('generar-pdf.index') }}"
                    class="{{ request()->routeIs('generar-pdf.*') ? 'activo' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z" />
                        <path d="M14 3v5h5" />
                        <path d="M9 13h6M9 17h4" />
                    </svg>
                    Generar PDF
                </a>
            @endif
            @if (auth()->user()->es_superadmin)
                <a href="{{ route('administradores.index') }}"
                    class="{{ request()->routeIs('administradores.*') ? 'activo' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3l7 3v6c0 5-3.5 8-7 9-3.5-1-7-4-7-9V6l7-3Z" />
                        <path d="M9 12l2 2 4-4" />
                    </svg>
                    Admins
                </a>
            @endif
        </div>
    </nav>

    <script>
        function abrirMenu() {
            document.body.classList.add('menu-abierto');
        }

        function cerrarMenu() {
            document.body.classList.remove('menu-abierto');
        }

        // Barra inferior: centra el ítem activo y muestra/oculta los degradados de los bordes
        (function() {
            const barra = document.querySelector('.barra-inferior');
            if (!barra) return;
            const scroller = barra.querySelector('.barra-scroll');
            const activo = scroller.querySelector('a.activo');

            function actualizar() {
                const max = scroller.scrollWidth - scroller.clientWidth;
                barra.classList.toggle('hay-izq', scroller.scrollLeft > 4);
                barra.classList.toggle('hay-der', scroller.scrollLeft < max - 4);
            }

            function centrarActivo() {
                if (!activo || !scroller.clientWidth) return;
                scroller.scrollLeft = activo.offsetLeft - (scroller.clientWidth - activo.offsetWidth) / 2;
            }

            centrarActivo();
            actualizar();
            scroller.addEventListener('scroll', actualizar, {
                passive: true
            });
            window.addEventListener('resize', () => {
                centrarActivo();
                actualizar();
            });
        })();

        // Contador de Apartados: alimenta el numerito de la sidebar y de la barra inferior
        // Contador de Apartados: alimenta el numerito de la sidebar y de la barra inferior.
        // Expuesto como función global para que otras pantallas (como Apartados) puedan
        // pedirle que se actualice al toque después de guardar/eliminar, sin esperar
        // el refresco automático.
        async function actualizarBadgeApartados() {
            try {
                const r = await fetch('{{ route('apartados.contador') }}', {
                    headers: {
                        Accept: 'application/json'
                    }
                });
                if (!r.ok) return;
                const {
                    total
                } = await r.json();
                document.querySelectorAll('#badge-apartados, #badge-apartados-movil').forEach(el => {
                    el.textContent = total;
                    el.style.display = total > 0 ? 'inline-flex' : 'none';
                });
            } catch (e) {}
        }

        actualizarBadgeApartados();
        setInterval(actualizarBadgeApartados, 20000); // refresco automático cada 20s

        // ===== Paginador compartido =====
        // Recibe el JSON de paginate() de Laravel tal cual.
        //   id:        id del <div class="paginacion"> de la pantalla
        //   resp:      respuesta del fetch (current_page, last_page, total, from, to)
        //   etiqueta:  texto plural para "Mostrando 1–15 de 40 <etiqueta>"
        //   alCambiar: función que recibe el número de página a cargar
        function pintarPaginador(id, resp, etiqueta, alCambiar) {
            const el = document.getElementById(id);
            if (!el) return;

            const actual = resp.current_page,
                ultima = resp.last_page,
                total = resp.total;
            if (!total || ultima <= 1) {
                el.style.display = 'none';
                el.innerHTML = '';
                return;
            }
            el.style.display = 'flex';

            const visibles = new Set([1, ultima, actual, actual - 1, actual + 1]);
            const numeros = [];
            let anterior = 0;
            for (let p = 1; p <= ultima; p++) {
                if (!visibles.has(p)) continue;
                if (anterior && p - anterior > 1) numeros.push('...');
                numeros.push(p);
                anterior = p;
            }

            const flechaIzq =
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>';
            const flechaDer =
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>';

            el.innerHTML = `
        <div class="paginacion-info">Mostrando <strong>${resp.from ?? 0}–${resp.to ?? 0}</strong> de <strong>${total}</strong> ${etiqueta}</div>
        <div class="paginacion-controles">
            <button type="button" class="pag-nav" ${actual === 1 ? 'disabled' : ''} data-pagina="${actual - 1}">${flechaIzq} Anterior</button>
            <div class="pag-numeros">
                ${numeros.map(p => p === '...'
                    ? '<span class="pag-puntos">···</span>'
                    : `<button type="button" class="pag-num ${p === actual ? 'pag-activo' : ''}" data-pagina="${p}">${p}</button>`
                ).join('')}
            </div>
            <span class="pag-actual-movil">Página ${actual} de ${ultima}</span>
            <button type="button" class="pag-nav" ${actual === ultima ? 'disabled' : ''} data-pagina="${actual + 1}">Siguiente ${flechaDer}</button>
        </div>`;

            el.querySelectorAll('[data-pagina]').forEach(b =>
                b.addEventListener('click', () => alCambiar(Number(b.dataset.pagina)))
            );
        }

        // Sube al tope: en desktop scrollea .main, en móvil scrollea la ventana
        function irArribaPagina() {
            const opts = {
                top: 0,
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
            };
            document.querySelectorAll('.tabla-scroll').forEach(t => t.scrollTo(opts));
            const main = document.querySelector('.main');
            if (main) main.scrollTo(opts);
            window.scrollTo(opts);
        }
    </script>

    @stack('scripts')
</body>

</html>
