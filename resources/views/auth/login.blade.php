<!-- resources/views/auth/login.blade.php -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Bolívar - Iniciar sesión</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Spectral:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --azul-fondo-1: #0B3D91;
            --azul-fondo-2: #1E6FE0;
            --azul-oscuro: #071F4D;   /* antes #0A2E6E */
    --azul-medio: #1F4FA8;
            --texto: #1B2430;
            --texto-tenue: #6D7480;
            --borde: #E3E1DB;
            --superficie: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
        }

        /* Fondo negro con textura sutil: base negra + resplandores difusos de acento azul + grid muy tenue */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--texto);
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
            position: relative;
            background-color: #000;
            background-image:
                radial-gradient(circle at 18% 22%, rgba(30, 111, 224, 0.20), transparent 42%),
                radial-gradient(circle at 82% 78%, rgba(11, 61, 145, 0.22), transparent 46%),
                linear-gradient(135deg, #050608, #000000 60%);
            -webkit-font-smoothing: antialiased;
        }

        /* Grid muy tenue, solo para dar textura profesional, no un negro plano */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
            background-size: 42px 42px;
            pointer-events: none;
        }

        :focus-visible {
            outline: 2px solid var(--azul-medio);
            outline-offset: 2px;
        }

        /* Tarjeta flotante */
        .tarjeta {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 860px;
            background: var(--superficie);
            border-radius: 24px;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.55);
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
        }

        /* Panel izquierdo — marca sobre degradado con círculos */
        .panel-marca {
            position: relative;
            background: linear-gradient(160deg, var(--azul-oscuro), var(--azul-medio));
            color: #fff;
            padding: 44px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .panel-marca .circulo {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }

        .panel-marca .c1 {
            width: 220px;
            height: 220px;
            top: -60px;
            left: -70px;
        }

        .panel-marca .c2 {
            width: 160px;
            height: 160px;
            bottom: -40px;
            right: -40px;
            background: rgba(255, 255, 255, 0.12);
        }

        .panel-marca .c3 {
            width: 90px;
            height: 90px;
            bottom: 90px;
            left: 40px;
            background: rgba(255, 255, 255, 0.14);
        }

        /* Marca: se mantiene en Spectral (serif) — es el único acento de identidad en todo el sistema */
        .panel-marca .marca {
            position: relative;
            z-index: 1;
            font-family: 'Spectral', serif;
            font-weight: 700;
            font-size: 26px;
        }

        .panel-marca .marca .sub {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.75);
            font-family: 'Inter', sans-serif;
        }

        /* Logo centrado dentro de un círculo blanco, mismo lenguaje visual que la insignia de mobile */
        .panel-marca .frase {
            position: relative;
            z-index: 1;
            width: 150px;
            height: 150px;
            margin: 0 auto;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 10px 26px rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .panel-marca .frase img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
        }

        .panel-marca .pie {
            position: relative;
            z-index: 1;
            font-size: 11.5px;
            color: rgba(255, 255, 255, 0.6);
        }

        /* Insignia con logo: solo se usa en mobile, oculta por defecto para no afectar desktop */
        .insignia {
            display: none;
        }

        .insignia img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        /* Panel derecho — formulario */
        .panel-formulario {
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Título: se mantiene en Spectral porque el login sigue siendo pantalla de marca;
           en las vistas internas del panel (Clientes, Dashboard, etc.) los títulos van en Inter */
        .panel-formulario h1 {
            font-family: 'Spectral', serif;
            font-weight: 700;
            font-size: 26px;
            margin-bottom: 6px;
        }

        .panel-formulario>p {
            font-size: 13.5px;
            color: var(--texto-tenue);
            margin-bottom: 26px;
        }

        .campo {
            margin-bottom: 16px;
        }

        .campo label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            margin-bottom: 7px;
            letter-spacing: .01em;
        }

        .campo-con-icono {
            position: relative;
            display: flex;
            align-items: center;
        }

        .campo-con-icono svg.icono {
            position: absolute;
            left: 13px;
            width: 16px;
            height: 16px;
            color: var(--texto-tenue);
            pointer-events: none;
        }

        .campo-con-icono input {
            width: 100%;
            padding: 11px 13px 11px 40px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            background: #FAFAF8;
            transition: border-color .15s, box-shadow .15s;
            font-size: max(13.5px, 16px);
        }

        .campo-con-icono input.con-boton {
            padding-right: 60px;
        }

        .campo-con-icono .toggle-pass {
            position: absolute;
            right: 13px;
            background: none;
            border: none;
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--azul-medio);
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }

        .campo input:focus {
            outline: none;
            border-color: var(--azul-medio);
            box-shadow: 0 0 0 3px rgba(46, 107, 214, 0.15);
        }

        .fila-opciones {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .campo-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--texto-tenue);
            cursor: pointer;
        }

        .campo-checkbox input {
            width: auto;
            accent-color: var(--azul-medio);
            cursor: pointer;
        }

        .btn-entrar {
            width: 100%;
            padding: 12px;
            background: var(--azul-oscuro);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            letter-spacing: .01em;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: background .15s;
        }

        .btn-entrar:hover {
            background: #082456;
        }

        /* ===== Responsive ===== */

        /* Tablet: la tarjeta se angosta pero mantiene las dos columnas — SIN CAMBIOS */
        @media (max-width: 860px) {
            body {
                padding: 20px;
            }

            .tarjeta {
                max-width: 620px;
            }

            .panel-marca {
                padding: 32px;
            }

            .panel-marca .frase {
                width: 120px;
                height: 120px;
            }
        }

        /* ===================================================================
           MOBILE (<= 620px): estilo "tarjeta única" con hero, curva e insignia
           =================================================================== */
        @media (max-width: 620px) {
            body {
                padding: 32px;
                align-items: center;
            }

            .tarjeta {
                grid-template-columns: 1fr;
                grid-template-rows: auto 1fr;
                max-width: 380px;
                border-radius: 28px;
                box-shadow: 0 30px 70px rgba(0, 0, 0, 0.55);
                min-height: 0;
            }

            /* El panel-marca se convierte en el "hero" con curva */
            .panel-marca {
                height: 220px;
                min-height: 0;
                padding: 28px 24px 0;
                justify-content: flex-start;
                /* el texto arranca arriba en vez de centrarse */
                align-items: center;
                text-align: center;
            }

            .panel-marca .c1 {
                width: 160px;
                height: 160px;
                top: -55px;
                left: -55px;
            }

            .panel-marca .c2 {
                width: 120px;
                height: 120px;
                bottom: -45px;
                right: -35px;
                top: auto;
            }

            .panel-marca .c3 {
                display: none;
            }

            .panel-marca::after {
                content: '';
                position: absolute;
                left: -30%;
                right: -30%;
                bottom: -78px;
                height: 140px;
                background: var(--superficie);
                border-radius: 50%;
            }

            /* Ocultamos subtítulo y frase (el círculo con logo de desktop), mostramos solo el nombre de marca centrado */
            .panel-marca .marca {
                font-size: 21px;
                margin-bottom: 42px;
            }

            .panel-marca .marca .sub,
            .panel-marca .frase,
            .panel-marca .pie {
                display: none;
            }

            /* Insignia circular montada sobre la curva */
            .insignia {
                display: block;
                position: absolute;
                z-index: 2;
                left: 50%;
                top: 220px;
                transform: translate(-50%, -50%);
                width: 96px;
                height: 96px;
                border-radius: 50%;
                background: #fff;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.45);
                border: 4px solid #fff;
                overflow: hidden;
            }

            .panel-formulario {
                padding: 52px 32px 32px;
                justify-content: flex-start;
            }

            .panel-formulario h1 {
                font-size: 24px;
                text-align: center;
            }

            .panel-formulario>p {
                text-align: center;
                margin-bottom: 28px;
            }

            /* Inputs: de caja con borde pasan a línea inferior */
            .campo-con-icono {
                border-bottom: 1.5px solid var(--borde);
                padding-bottom: 8px;
                gap: 9px;
            }

            .campo-con-icono:focus-within {
                border-color: var(--azul-medio);
            }

            .campo-con-icono svg.icono {
                position: static;
                flex-shrink: 0;
            }

            .campo-con-icono input {
                border: none;
                border-radius: 0;
                background: transparent;
                padding: 0;
                box-shadow: none !important;
            }

            .campo-con-icono input.con-boton {
                padding-right: 0;
            }

            .campo-con-icono .toggle-pass {
                position: static;
                margin-left: 4px;
            }

            /* Botón tipo píldora */
            .btn-entrar {
                border-radius: 999px;
                padding: 14px;
                text-transform: uppercase;
                letter-spacing: .04em;
            }
        }

        @media (max-width: 380px) {
            .panel-marca {
                height: 190px;
            }

            .insignia {
                top: 190px;
                width: 82px;
                height: 82px;
            }

            .panel-formulario {
                padding: 46px 24px 26px;
            }

            .panel-formulario h1 {
                font-size: 21px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="tarjeta">
        <div class="panel-marca">
            <div class="circulo c1"></div>
            <div class="circulo c2"></div>
            <div class="circulo c3"></div>

            <div class="marca">
                Distribuidora Bolívar
                <span class="sub">Sistema interno</span>
            </div>

            <div class="frase">
                <img src="{{ asset('images/fondo.png') }}" alt="Distribuidora Bolívar">
            </div>

            <div class="pie">&copy; {{ date('Y') }} By M&M TECH</div>
        </div>

        <!-- Solo visible en mobile (ver media query) -->
        <div class="insignia">
            <img src="{{ asset('images/fondo.png') }}" alt="Distribuidora Bolívar">
        </div>

        <div class="panel-formulario">
            <h1>Iniciar sesión</h1>
            <p>Ingresa tus credenciales para continuar.</p>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="campo">
                    <label>Usuario</label>
                    <div class="campo-con-icono">
                        <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="4" width="20" height="16" rx="2.5"></rect>
                            <path d="m3 6.5 9 6.2 9-6.2"></path>
                        </svg>
                        <input type="text" name="email" value="{{ old('email') }}" required autofocus
                            autocomplete="username">
                    </div>
                </div>

                <div class="campo">
                    <label>Contraseña</label>
                    <div class="campo-con-icono">
                        <svg class="icono" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="10.5" width="16" height="10.5" rx="2"></rect>
                            <path d="M7.5 10.5V7a4.5 4.5 0 0 1 9 0v3.5"></path>
                        </svg>
                        <input type="password" id="password" name="password" class="con-boton" required
                            autocomplete="current-password">
                        <button type="button" class="toggle-pass" onclick="mostrarClave()">Ver</button>
                    </div>
                </div>

                <div class="fila-opciones">
                    <label class="campo-checkbox">
                        <input type="checkbox" name="remember">
                        Recordarme
                    </label>
                </div>

                <button type="submit" class="btn-entrar">Ingresar</button>
            </form>
        </div>
    </div>

    @if ($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'No se pudo iniciar sesión',
                text: '{{ $errors->first() }}',
                confirmButtonColor: '#0A2E6E',
            });
        </script>
    @endif

    <script>
        function mostrarClave() {
            const input = document.getElementById('password');
            const btn = document.querySelector('.toggle-pass');
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            btn.textContent = visible ? 'Ver' : 'Ocultar';
        }
    </script>

</body>

</html>