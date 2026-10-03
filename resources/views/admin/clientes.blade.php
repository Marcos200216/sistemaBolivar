@extends('layouts.app')

@section('titulo', 'Clientes')

@push('estilos')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .barra-clientes {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .barra-clientes h1 {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-weight: 700;
            font-size: 22px;
            color: var(--azul-oscuro);
        }

        #tope-clientes {
            scroll-margin-top: 20px;
        }

        #tope-tabla {
            scroll-margin-top: 20px;
        }

        .btn-primario {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--azul-medio);
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            white-space: nowrap;
            transition: background .15s;
        }

        .btn-primario svg {
            width: 16px;
            height: 16px;
        }

        .btn-primario:hover {
            background: var(--azul-oscuro);
        }

        .btn-secundario {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #F0F3F8;
            color: var(--texto);
            border: none;
            padding: 10px 16px;
            border-radius: 9px;
            cursor: pointer;
            font-size: 14px;
            font-family: inherit;
            transition: background .15s;
        }

        .btn-secundario:hover {
            background: #E4E9F1;
        }

        /* Buscador con icono, mismo patrón visual que el login */
        .buscador {
            position: relative;
            margin-bottom: 18px;
            max-width: 360px;
        }

        .buscador svg {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: var(--texto-tenue);
            pointer-events: none;
        }

        .buscador input {
            width: 100%;
            padding: 11px 14px 11px 38px;
            border: 1px solid var(--borde);
            border-radius: 9px;
            font-size: max(13.5px, 16px);
            background: #FAFAF8;
            transition: border-color .15s, box-shadow .15s;
        }

        .buscador input:focus {
            outline: none;
            border-color: var(--azul-medio);
            box-shadow: 0 0 0 3px rgba(46, 107, 214, 0.15);
        }

        /* ===== Tabla (desktop / tablet) ===== */
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
            min-width: 680px;
        }

        .tabla-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .btn-icono {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: none;
            background: none;
            cursor: pointer;
            border-radius: 7px;
            transition: background .15s, color .15s;
        }

        .btn-icono svg {
            width: 16px;
            height: 16px;
        }

        .btn-editar {
            color: var(--azul-medio);
        }

        .btn-editar:hover {
            background: #EAF1FD;
        }

        .btn-eliminar {
            color: #a30000;
        }

        .btn-eliminar:hover {
            background: #FBEAEA;
        }

        .acciones {
            white-space: nowrap;
            display: flex;
            gap: 4px;
        }

        .insignia-credito {
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
            color: var(--azul-oscuro);
            background: var(--azul-50, #eaf2ff);
            padding: 2px 9px;
            border-radius: 999px;
        }

        /* ===== Lista en tarjetas (solo móvil) ===== */
        .lista-clientes-movil {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .tarjeta-cliente {
            background: var(--superficie);
            border: 1px solid var(--borde);
            border-radius: 13px;
            box-shadow: var(--sombra-card);
            padding: 14px 14px 10px;
        }

        .tarjeta-cliente .fila-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 8px;
        }

        .tarjeta-cliente .nombre-cliente {
            font-size: 15px;
            font-weight: 700;
            color: var(--texto);
            overflow-wrap: anywhere;
        }

        .tarjeta-cliente .codigo-cliente {
            display: block;
            margin-top: 2px;
            font-size: 11.5px;
            color: var(--texto-tenue);
        }

        .tarjeta-cliente .datos-contacto {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin: 8px 0 10px;
        }

        .tarjeta-cliente .dato {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--texto-tenue);
        }

        .tarjeta-cliente .dato svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
            color: var(--texto-400);
        }

        .tarjeta-cliente .fila-acciones {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
            padding-top: 8px;
            border-top: 1px solid #F0F3F8;
        }

        .tarjeta-cliente .btn-icono {
            width: 38px;
            height: 38px;
            border: 1px solid var(--borde);
        }

        .tarjeta-cliente .btn-icono svg {
            width: 17px;
            height: 17px;
        }

        .estado-vacio {
            text-align: center;
            padding: 30px 16px;
            color: var(--texto-tenue);
            font-size: 14px;
        }

        /* Visibilidad por breakpoint */
        .solo-movil {
            display: none;
        }

        /* ===== Paginación ===== */
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

        /* ===== Modal ===== */
        .modal-fondo {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(10, 20, 40, 0.45);
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 50;
        }

        .modal-fondo.abierto {
            display: flex;
        }

        .modal-caja {
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 480px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 22px 24px 24px;
        }

        .modal-encabezado {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .modal-encabezado h2 {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-weight: 700;
            font-size: 19px;
            color: var(--azul-oscuro);
        }

        .btn-cerrar-modal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: none;
            background: #F0F3F8;
            color: var(--texto-tenue);
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-cerrar-modal svg {
            width: 16px;
            height: 16px;
        }

        .campo {
            margin-bottom: 13px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .campo label {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--texto-tenue);
        }

        .campo input,
        .campo select {
            padding: 10px 12px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            font-size: max(13.5px, 16px);
            font-family: inherit;
            background: #fff;
            transition: border-color .15s, box-shadow .15s;
        }

        .campo input:focus,
        .campo select:focus {
            outline: none;
            border-color: var(--azul-medio);
            box-shadow: 0 0 0 3px rgba(46, 107, 214, 0.15);
        }

        .campo small {
            font-size: 12px;
            color: var(--texto-tenue);
        }

        .modal-acciones {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 18px;
        }

        @media (max-width: 640px) {
            .solo-movil {
                display: flex;
            }

            .solo-desktop {
                display: none;
            }

            .barra-clientes h1 {
                font-size: 19px;
            }

            .btn-primario {
                flex: 1;
                justify-content: center;
            }

            .buscador {
                max-width: none;
            }

            .modal-caja {
                padding: 18px;
                border-radius: 14px;
            }

            .modal-acciones {
                flex-direction: column-reverse;
            }

            .modal-acciones button {
                width: 100%;
                justify-content: center;
                padding: 12px;
            }

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

        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
            }
        }

        .bloque-ubicacion { border: 1px solid var(--borde); border-radius: 10px; padding: 12px; margin-bottom: 13px; background: #FAFAF8; }
.bloque-ubicacion > label { font-size: 12.5px; font-weight: 600; color: var(--texto-tenue); display: block; margin-bottom: 8px; }
.ubicacion-botones { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
.ubicacion-botones button { padding: 8px 12px; font-size: 13px; }
.ubicacion-link { display: flex; gap: 8px; margin-bottom: 8px; }
.ubicacion-link input { flex: 1; min-width: 0; padding: 9px 10px; border: 1px solid var(--borde); border-radius: 8px; font-size: 16px; font-family: inherit; }
#mapa-cliente { height: 220px; border-radius: 8px; border: 1px solid var(--borde); z-index: 0; }
#ubicacion-estado { font-size: 12px; color: var(--texto-tenue); margin-top: 6px; }
    </style>
@endpush

@section('contenido')
    <div class="barra-clientes" id="tope-clientes">
        <h1>Clientes</h1>
        <button class="btn-primario" onclick="abrirModalCrear()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Nuevo cliente
        </button>
    </div>

    <div class="buscador">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
            stroke-linejoin="round">
            <circle cx="11" cy="11" r="7" />
            <path d="m21 21-4.3-4.3" />
        </svg>
        <input type="text" id="buscador-clientes" placeholder="Buscar por código o nombre...">
    </div>

    {{-- Tabla: visible en tablet/desktop --}}
    <div class="tabla-scroll solo-desktop" id="tope-tabla">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Máx. crédito</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla-clientes">
                <tr>
                    <td colspan="6" class="estado-vacio">Cargando...</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Tarjetas: visibles solo en móvil --}}
    <div class="lista-clientes-movil solo-movil" id="lista-clientes-movil">
        <div class="estado-vacio">Cargando...</div>
    </div>

    <div class="paginacion" id="paginacion-clientes" style="display:none;"></div>

    {{-- Modal único: crear y editar --}}
    <div class="modal-fondo" id="modal-cliente">
        <div class="modal-caja">
            <div class="modal-encabezado">
                <h2 id="modal-cliente-titulo">Nuevo cliente</h2>
                <button type="button" class="btn-cerrar-modal" onclick="cerrarModalCliente()" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <form id="form-cliente">
                @csrf
                <input type="hidden" name="_method" id="form-cliente-metodo" value="POST">

                {{-- Orden de captura: nombre → teléfono → código (se autorrellena, editable) --}}
                <div class="campo"><label>Nombre *</label><input type="text" name="nombre" id="campo-nombre" required>
                </div>
                <div class="campo"><label>Teléfono</label><input type="text" name="telefono" id="campo-telefono"
                        inputmode="tel"></div>
                <div class="campo">
                    <label>Código</label>
                    <input type="text" name="codigo" id="campo-codigo">
                   <small id="ayuda-codigo">Se llena solo con los últimos 4 dígitos del teléfono. Podés cambiarlo.</small>
                </div>
                <div class="campo"><label>Correo</label><input type="email" name="correo" id="campo-correo"></div>
                <div class="campo"><label>Dirección</label><input type="text" name="direccion" id="campo-direccion">
                </div>
                <div class="campo">
                    <label>Sucursal *</label>
                    <select name="sucursal_id" id="campo-sucursal" required>
                        @foreach ($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="campo"><label>Máximo de crédito</label><input type="number" step="0.01"
                        name="maximocredito" id="campo-maximocredito"></div>
                <div class="campo">
                    <label>Género</label>
                    <select name="genero" id="campo-genero">
                        <option value="">Sin especificar</option>
                        <option value="F">F</option>
                        <option value="M">M</option>
                    </select>
                </div>
 
                <div class="bloque-ubicacion" id="bloque-ubicacion" style="display:none;">
    <label>Ubicación del cliente</label>
    <input type="hidden" name="latitud" id="campo-latitud">
    <input type="hidden" name="longitud" id="campo-longitud">

    <div class="ubicacion-botones">
        <button type="button" class="btn-secundario" onclick="usarMiUbicacion()">Usar mi ubicación actual</button>
        <button type="button" class="btn-secundario" onclick="limpiarUbicacion()">Quitar ubicación</button>
    </div>
    <div class="ubicacion-link">
        <input type="text" id="ubicacion-link-input" placeholder="Pegá un link de Google Maps o 'lat, lng'">
        <button type="button" class="btn-secundario" onclick="leerLinkUbicacion()">Usar</button>
    </div>
    <div id="mapa-cliente"></div>
    <div id="ubicacion-estado">Tocá el mapa para poner el pin (se puede arrastrar).</div>
</div>
                <div class="modal-acciones">
                    <button type="button" class="btn-secundario" onclick="cerrarModalCliente()">Cancelar</button>
                    <button type="submit" class="btn-primario">Guardar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;
        let paginaActual = 1;

        document.addEventListener('DOMContentLoaded', () => cargarClientes());

        let timeoutBusqueda = null;
        document.getElementById('buscador-clientes').addEventListener('input', function(e) {
            clearTimeout(timeoutBusqueda);
            timeoutBusqueda = setTimeout(() => cargarClientes(1), 350);
        });

        function cargarClientes(pagina = paginaActual, irAlTope = false) {
            paginaActual = pagina;
            const q = document.getElementById('buscador-clientes').value;

            fetch(`{{ route('clientes.index') }}?q=${encodeURIComponent(q)}&page=${pagina}`, {
                    headers: {
                        'Accept': 'application/json'
                    },
                })
                .then(r => r.json())
                .then(respuesta => {
                    pintarTabla(respuesta.data);
                    pintarTarjetasMovil(respuesta.data);
                    pintarPaginacion(respuesta.current_page, respuesta.last_page, respuesta.total, respuesta.from,
                        respuesta.to);
                    if (irAlTope) irArribaClientes();
                });
        }

        function irArribaClientes() {
            const esMovil = window.matchMedia('(max-width: 640px)').matches;
            const tope = document.getElementById(esMovil ? 'tope-clientes' : 'tope-tabla');

            // La tabla tiene su propio scroll interno (overflow-y: auto) que hay que resetear aparte
            const contenedorTabla = document.querySelector('.tabla-scroll');
            if (contenedorTabla) contenedorTabla.scrollTop = 0;

            if (!tope) return;
            const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            tope.scrollIntoView({
                behavior: sinMovimiento ? 'auto' : 'smooth',
                block: 'start'
            });
        }

        function formatoColones(valor) {
            return '₡' + Number(valor ?? 0).toLocaleString('es-CR', {
                minimumFractionDigits: 2
            });
        }

        const iconoLapiz =
            `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>`;
        const iconoBasura =
            `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>`;
        const iconoTelefono =
            `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>`;
        const iconoCorreo =
            `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2.5"/><path d="m3 6.5 9 6.2 9-6.2"/></svg>`;

        function pintarTabla(clientes) {
            const cuerpo = document.getElementById('cuerpo-tabla-clientes');

            if (clientes.length === 0) {
                cuerpo.innerHTML = `<tr><td colspan="6" class="estado-vacio">No se encontraron clientes.</td></tr>`;
                return;
            }

            cuerpo.innerHTML = clientes.map(c => `
        <tr>
            <td>${c.codigo ?? '—'}</td>
            <td>${c.nombre}</td>
            <td>${c.telefono ?? '—'}</td>
            <td>${c.correo ?? '—'}</td>
            <td><span class="insignia-credito">   ${Number(c.maximocredito) > 0 ? formatoColones(c.maximocredito) : 'Sin límite'}</span></td>
            <td class="acciones">
                <button type="button" class="btn-icono btn-editar" title="Editar" onclick='abrirModalEditar(${JSON.stringify(c)})'>${iconoLapiz}</button>
                <button type="button" class="btn-icono btn-eliminar" title="Eliminar" onclick="confirmarEliminar(${c.id}, '${c.nombre.replace(/'/g, "\\'")}')">${iconoBasura}</button>
            </td>
        </tr>
    `).join('');
        }

        function pintarTarjetasMovil(clientes) {
            const lista = document.getElementById('lista-clientes-movil');

            if (clientes.length === 0) {
                lista.innerHTML = `<div class="estado-vacio">No se encontraron clientes.</div>`;
                return;
            }

            lista.innerHTML = clientes.map(c => `
        <div class="tarjeta-cliente">
            <div class="fila-top">
                <div>
                    <div class="nombre-cliente">${c.nombre}</div>
                    ${c.codigo ? `<span class="codigo-cliente">Código ${c.codigo}</span>` : ''}
                </div>
                <span class="insignia-credito">   ${Number(c.maximocredito) > 0 ? formatoColones(c.maximocredito) : 'Sin límite'}</span>
            </div>
            <div class="datos-contacto">
                ${c.telefono ? `<div class="dato">${iconoTelefono}${c.telefono}</div>` : ''}
                ${c.correo ? `<div class="dato">${iconoCorreo}${c.correo}</div>` : ''}
            </div>
            <div class="fila-acciones">
                <button type="button" class="btn-icono btn-editar" title="Editar" onclick='abrirModalEditar(${JSON.stringify(c)})'>${iconoLapiz}</button>
                <button type="button" class="btn-icono btn-eliminar" title="Eliminar" onclick="confirmarEliminar(${c.id}, '${c.nombre.replace(/'/g, "\\'")}')">${iconoBasura}</button>
            </div>
        </div>
    `).join('');
        }

        function rangoPaginas(actual, ultima) {
            // Siempre muestra 1, la última, la actual y sus vecinas; el resto se reemplaza por "..."
            const paginas = [];
            const visibles = new Set([1, ultima, actual, actual - 1, actual + 1]);

            for (let i = 1; i <= ultima; i++) {
                if (visibles.has(i) && i >= 1 && i <= ultima) paginas.push(i);
            }

            const resultado = [];
            let anterior = 0;
            for (const p of paginas) {
                if (anterior && p - anterior > 1) resultado.push('...');
                resultado.push(p);
                anterior = p;
            }
            return resultado;
        }

        function pintarPaginacion(actual, ultima, total, desde, hasta) {
            const contenedor = document.getElementById('paginacion-clientes');

            if (!total || ultima <= 1) {
                contenedor.style.display = 'none';
                return;
            }
            contenedor.style.display = 'flex';

            const flechaIzq =
                `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>`;
            const flechaDer =
                `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>`;

            const numeros = rangoPaginas(actual, ultima).map(p =>
                p === '...' ?
                `<span class="pag-puntos">···</span>` :
                `<button type="button" class="pag-num ${p === actual ? 'pag-activo' : ''}" onclick="cargarClientes(${p}, true)">${p}</button>`
            ).join('');

            contenedor.innerHTML = `
        <div class="paginacion-info">Mostrando <strong>${desde ?? 0}–${hasta ?? 0}</strong> de <strong>${total}</strong> clientes</div>
        <div class="paginacion-controles">
            <button type="button" class="pag-nav" ${actual === 1 ? 'disabled' : ''} onclick="cargarClientes(${actual - 1}, true)">
                ${flechaIzq} Anterior
            </button>
            <div class="pag-numeros">${numeros}</div>
            <span class="pag-actual-movil">Página ${actual} de ${ultima}</span>
            <button type="button" class="pag-nav" ${actual === ultima ? 'disabled' : ''} onclick="cargarClientes(${actual + 1}, true)">
                Siguiente ${flechaDer}
            </button>
        </div>
    `;
        }

        // ===== Código autorrellenado desde el teléfono =====
        // Mientras el admin no toque el código a mano, se mantiene igual a los
        // últimos 4 dígitos del teléfono. Si lo edita, deja de autorrellenarse.
        // Si borra el código por completo, vuelve a autorrellenarse.
        let codigoManual = false;
let telefonoOriginal = null; // null = cliente nuevo; texto = editando
let codigoOriginal = '';

function ultimos4Digitos(telefono) {
    const digitos = (telefono || '').replace(/\D/g, '');
    return digitos.length >= 4 ? digitos.slice(-4) : '';
}

document.getElementById('campo-telefono').addEventListener('input', function() {
    if (codigoManual) return;
    const campoCodigo = document.getElementById('campo-codigo');

    if (telefonoOriginal !== null) {
        // Editando: si el teléfono vuelve a ser el original, vuelve el código original
        if (this.value.trim() === telefonoOriginal.trim()) {
            campoCodigo.value = codigoOriginal;
            return;
        }
        // Mientras tenga menos de 4 dígitos no se borra el código existente
        const nuevo = ultimos4Digitos(this.value);
        if (nuevo !== '') campoCodigo.value = nuevo;
        return;
    }

    campoCodigo.value = ultimos4Digitos(this.value);
});

document.getElementById('campo-codigo').addEventListener('input', function() {
    codigoManual = this.value.trim() !== '';
    if (!codigoManual) {
        this.value = ultimos4Digitos(document.getElementById('campo-telefono').value);
    }
});

        // ===== Modal crear/editar =====
        function abrirModalCrear() {
    document.getElementById('form-cliente').reset();
    codigoManual = false;
    telefonoOriginal = null;
codigoOriginal = '';
    document.getElementById('campo-sucursal').value = '{{ session('sucursal_id') }}';
    document.getElementById('form-cliente-metodo').value = 'POST';
    document.getElementById('form-cliente').dataset.url = '{{ route('clientes.store') }}';
    document.getElementById('modal-cliente-titulo').textContent = 'Nuevo cliente';
    document.getElementById('modal-cliente').classList.add('abierto');
    cargarUbicacionEnModal(null);   // <-- NUEVA
    document.getElementById('campo-nombre').focus();
}

        function abrirModalEditar(cliente) {
    document.getElementById('campo-nombre').value = cliente.nombre ?? '';
    document.getElementById('campo-telefono').value = cliente.telefono ?? '';
    document.getElementById('campo-codigo').value = cliente.codigo ?? '';
    document.getElementById('campo-correo').value = cliente.correo ?? '';
    document.getElementById('campo-direccion').value = cliente.direccion ?? '';
    document.getElementById('campo-maximocredito').value = cliente.maximocredito ?? '';
    document.getElementById('campo-genero').value = cliente.genero ?? '';
    document.getElementById('campo-sucursal').value = cliente.sucursal_id;

    // Si cambia el teléfono, el código se recalcula; si no, queda el que tenía.
codigoManual = false;
telefonoOriginal = cliente.telefono ?? '';
codigoOriginal = cliente.codigo ?? '';

    document.getElementById('form-cliente-metodo').value = 'PUT';
    document.getElementById('form-cliente').dataset.url = `/clientes/${cliente.id}`;
    document.getElementById('modal-cliente-titulo').textContent = 'Editar cliente';
    document.getElementById('modal-cliente').classList.add('abierto');
    cargarUbicacionEnModal(cliente);   // <-- NUEVA
}

        function cerrarModalCliente() {
            document.getElementById('modal-cliente').classList.remove('abierto');
        }

        document.getElementById('form-cliente').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch(this.dataset.url, {
                    method: 'POST', // Laravel usa el campo _method para simular PUT
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: formData,
                })
                .then(async r => {
                    const data = await r.json();
                    if (!r.ok) throw data;
                    return data;
                })
                .then(data => {
                    cerrarModalCliente();
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: data.mensaje,
                        timer: 2500,
                        showConfirmButton: false
                    });
                    cargarClientes();
                })
                .catch(err => {
    const mensajes = err.errors
        ? Object.values(err.errors).flat().join('\n')
        : (err.message || 'Ocurrió un error inesperado. Intentá de nuevo.');
    Swal.fire({ icon: 'error', title: 'Revisá los datos', text: mensajes });
});
        });

        // ===== Eliminar =====
        function confirmarEliminar(id, nombre) {
            Swal.fire({
                icon: 'warning',
                title: `¿Eliminar a ${nombre}?`,
                text: 'Esta acción no se puede deshacer.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#a30000',
            }).then(resultado => {
                if (!resultado.isConfirmed) return;

                const fd = new FormData();
                fd.append('_method', 'DELETE');

                fetch(`/clientes/${id}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            'Accept': 'application/json'
                        },
                        body: fd,
                    })
                    .then(async r => {
                        let data = {};
                        try {
                            data = await r.json();
                        } catch (e) {}
                        if (!r.ok) throw data;
                        return data;
                    })
                    .then(data => {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: data.mensaje,
                            timer: 2500,
                            showConfirmButton: false
                        });
                        cargarClientes();
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'No se pudo eliminar',
                            text: err.mensaje || 'Ocurrió un error inesperado. Intentá de nuevo.'
                        });
                    });
            });
        }


        // ===== Ubicación (solo sucursales mayoristas) =====
const CANAL_SUCURSAL = @json($sucursales->pluck('canal', 'id'));
const CENTRO_DEFECTO = [10.6350, -85.4377]; // Liberia
let mapa = null, marcador = null;

function esSucursalMayorista() {
    return CANAL_SUCURSAL[document.getElementById('campo-sucursal').value] === 'mayorista';
}

function actualizarBloqueUbicacion() {
    const visible = esSucursalMayorista();
    document.getElementById('bloque-ubicacion').style.display = visible ? '' : 'none';
    // Un input deshabilitado no viaja en el FormData: si no es mayorista, no se envía ubicación
    document.getElementById('campo-latitud').disabled = !visible;
    document.getElementById('campo-longitud').disabled = !visible;
    if (visible) iniciarMapa();
}

function iniciarMapa() {
    if (!mapa) {
        mapa = L.map('mapa-cliente').setView(CENTRO_DEFECTO, 13);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '© OpenStreetMap'
        }).addTo(mapa);
        mapa.on('click', e => fijarUbicacion(e.latlng.lat, e.latlng.lng, false));
    }
    // El mapa se crea dentro de un modal recién abierto: hay que recalcular su tamaño
    setTimeout(() => {
        mapa.invalidateSize();
        const lat = parseFloat(document.getElementById('campo-latitud').value);
        const lng = parseFloat(document.getElementById('campo-longitud').value);
        if (!isNaN(lat) && !isNaN(lng)) mapa.setView([lat, lng], 16);
        else mapa.setView(CENTRO_DEFECTO, 13);
    }, 150);
}

function fijarUbicacion(lat, lng, centrar = true) {
    lat = Number(lat.toFixed(7)); lng = Number(lng.toFixed(7));
    document.getElementById('campo-latitud').value = lat;
    document.getElementById('campo-longitud').value = lng;

    if (!marcador) {
        marcador = L.marker([lat, lng], { draggable: true }).addTo(mapa);
        marcador.on('dragend', () => {
            const p = marcador.getLatLng();
            fijarUbicacion(p.lat, p.lng, false);
        });
    } else {
        marcador.setLatLng([lat, lng]);
    }
    if (centrar) mapa.setView([lat, lng], 16);
    document.getElementById('ubicacion-estado').textContent = `Ubicación guardada en el formulario: ${lat}, ${lng}`;
}

function limpiarUbicacion() {
    document.getElementById('campo-latitud').value = '';
    document.getElementById('campo-longitud').value = '';
    document.getElementById('ubicacion-link-input').value = '';
    if (marcador && mapa) { mapa.removeLayer(marcador); marcador = null; }
    document.getElementById('ubicacion-estado').textContent = 'Sin ubicación. Tocá el mapa para poner el pin.';
}

function usarMiUbicacion() {
    if (!navigator.geolocation) {
        Swal.fire('No disponible', 'Este navegador no permite obtener la ubicación.', 'info');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        pos => fijarUbicacion(pos.coords.latitude, pos.coords.longitude),
        () => Swal.fire('No se pudo obtener', 'Revisá que el navegador tenga permiso de ubicación (y que el sitio use https).', 'warning'),
        { enableHighAccuracy: true, timeout: 15000 }
    );
}

// Acepta links largos de Google Maps o texto "lat, lng"
function leerLinkUbicacion() {
    const texto = document.getElementById('ubicacion-link-input').value.trim();
    const patrones = [
        /@(-?\d+\.\d+),(-?\d+\.\d+)/,
        /!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/,
        /[?&](?:q|ll|query)=(-?\d+\.\d+),(-?\d+\.\d+)/,
        /^(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)$/,
    ];
    for (const p of patrones) {
        const m = texto.match(p);
        if (m) {
            const lat = parseFloat(m[1]), lng = parseFloat(m[2]);
            if (Math.abs(lat) <= 90 && Math.abs(lng) <= 180) { fijarUbicacion(lat, lng); return; }
        }
    }
    Swal.fire('No pude leer el link',
        'Los links cortos (maps.app.goo.gl) no traen coordenadas. Abrilo en el navegador y copiá la URL larga, o pegá "lat, lng".', 'info');
}

function cargarUbicacionEnModal(cliente) {
    document.getElementById('ubicacion-link-input').value = '';
    if (marcador && mapa) { mapa.removeLayer(marcador); marcador = null; }
    document.getElementById('campo-latitud').value = cliente?.latitud ?? '';
    document.getElementById('campo-longitud').value = cliente?.longitud ?? '';
    document.getElementById('ubicacion-estado').textContent = 'Tocá el mapa para poner el pin (se puede arrastrar).';

    actualizarBloqueUbicacion();

    if (cliente?.latitud != null && cliente?.longitud != null && mapa) {
        // el marcador se pone tras iniciarMapa (que ya centró el mapa)
        setTimeout(() => fijarUbicacion(Number(cliente.latitud), Number(cliente.longitud)), 200);
    }
}

document.getElementById('campo-sucursal').addEventListener('change', actualizarBloqueUbicacion);
    </script>
@endpush