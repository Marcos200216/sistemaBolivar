@extends('layouts.app')

@section('titulo', 'Rutas')

@push('estilos')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .btn {
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-primario {
            background: var(--azul-medio);
            color: #fff;
        }

        .btn-secundario {
            background: #fff;
            border: 1px solid var(--borde);
            color: var(--texto);
        }

        .btn-peligro {
            background: none;
            color: #a30000;
            border: 1px solid var(--borde);
        }

        .btn-texto {
            background: none;
            color: var(--azul-medio);
        }

        .btn-advertencia {
            background: #fff7e6;
            color: #8a5a00;
            border: 1px solid #f3d999;
        }

        .btn-exito {
            background: #16794f;
            color: #fff;
        }

        .btn:disabled {
            opacity: .4;
            cursor: default;
        }

        .btn-bloque {
            width: 100%;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 8px;
            font-family: inherit;
        }

        .btn-bloque svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .btn-icono {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            border: 1px solid var(--borde);
            background: #fff;
            color: var(--texto);
            cursor: pointer;
            flex-shrink: 0;
        }

        .btn-icono:disabled {
            opacity: .3;
            cursor: default;
        }

        .btn-icono svg {
            width: 14px;
            height: 14px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-verde {
            background: #e7f7ee;
            color: #16794f;
        }

        .badge-rojo {
            background: #fdecec;
            color: #a30000;
        }

        .badge-naranja {
            background: #fff3e0;
            color: #a35a00;
        }

        /* ===== Pantalla 1: elegir tipo ===== */
        h1.titulo-pagina {
            margin: 0 0 18px;
            font-size: 20px;
        }

        .opciones-tipo {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            max-width: 640px;
        }

        .opcion-tipo {
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--sombra-card);
            padding: 28px 20px;
            text-align: center;
            cursor: pointer;
            border: 2px solid transparent;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .opcion-tipo:hover {
            border-color: var(--azul-medio);
        }

        .opcion-tipo svg {
            width: 32px;
            height: 32px;
            color: var(--azul-medio);
            margin-bottom: 4px;
        }

        .opcion-tipo .titulo {
            font-size: 16px;
            font-weight: 700;
            color: var(--texto);
        }

        .opcion-tipo .desc {
            font-size: 13px;
            color: var(--texto-tenue);
            line-height: 1.4;
        }

        /* ===== Pantalla 2: pestañas + lista ===== */
        .rutas-titulo-tipo {
            font-size: 19px;
            margin: 14px 0 16px;
        }

        .tabs-filtro {
            display: flex;
            gap: 6px;
            padding: 4px;
            background: #eef1f6;
            border-radius: 10px;
            margin-bottom: 18px;
            max-width: 360px;
        }

        .tab-filtro {
            flex: 1;
            text-align: center;
            padding: 9px 14px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: var(--texto-tenue);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }

        .tab-filtro.activo {
            background: #fff;
            color: var(--azul-medio);
            box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
        }

        .cabecera-ruta {
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--sombra-card);
            padding: 16px;
            margin-bottom: 16px;
        }

        .cabecera-ruta h2 {
            margin: 0;
            font-size: 17px;
        }

        .cabecera-ruta-acciones {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid var(--borde);
        }

        .lista-clientes-ruta {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .fila-cliente-ruta {
            background: #fff;
            border-radius: 10px;
            box-shadow: var(--sombra-card);
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }

        .fila-cliente-ruta.recobro {
            border: 1px solid #f3d999;
            background: #fffdf5;
        }

        .fila-cliente-ruta.atrasado {
            border: 1px solid #f3a3a3;
            background: #fff5f5;
        }

        .fila-cliente-ruta .numero {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--azul-50);
            color: var(--azul-medio);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .fila-cliente-ruta .info {
            flex: 1;
            min-width: 0;
        }

        .fila-cliente-ruta .nombre {
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            overflow-wrap: anywhere;
        }

        .fila-cliente-ruta .nombre svg {
            width: 15px;
            height: 15px;
            color: #b8860b;
            flex-shrink: 0;
        }

        .fila-cliente-ruta .saldo {
            font-size: 13px;
            color: var(--texto-tenue);
            margin-top: 2px;
        }

        .fila-cliente-ruta .flechas {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex-shrink: 0;
        }

        .lista-finalizados {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .fila-finalizado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            background: #f7f9fc;
        }

        .fila-finalizado .nombre {
            font-weight: 600;
            font-size: 14px;
        }

        .fila-finalizado .saldo {
            font-size: 12px;
            color: var(--texto-tenue);
            margin-top: 2px;
        }

        .estado-vacio {
            text-align: center;
            padding: 40px 20px;
            color: var(--texto-tenue);
            font-size: 14px;
        }

        /* ===== Modales (base compartida) ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(8, 40, 95, .5);
            z-index: 60;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-overlay.abierto {
            display: flex;
        }

        .modal-caja {
            background: #fff;
            border-radius: 14px;
            width: 100%;
            max-width: 460px;
            max-height: 90dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .modal-caja.modal-ancha {
            max-width: 560px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding: 18px 20px;
            border-bottom: 1px solid var(--borde);
            flex-shrink: 0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 17px;
        }

        .modal-header p {
            margin: 3px 0 0;
            font-size: 13px;
            color: var(--texto-tenue);
        }

        .modal-cerrar {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--texto-tenue);
            padding: 4px;
            line-height: 0;
            flex-shrink: 0;
        }

        .modal-cerrar svg {
            width: 20px;
            height: 20px;
        }

        .modal-cuerpo {
            padding: 20px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 20px;
            border-top: 1px solid var(--borde);
            flex-shrink: 0;
        }

        .campo {
            margin-bottom: 16px;
        }

        .campo:last-child {
            margin-bottom: 0;
        }

        .campo label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--texto-tenue);
            margin-bottom: 6px;
        }

        .campo input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            color: var(--texto);
        }

        /* ===== Modal: administrar ruta ===== */
        .administrar-toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .administrar-toolbar input {
            flex: 1;
            padding: 9px 12px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            min-width: 0;
        }

        .administrar-toolbar .btn {
            flex-shrink: 0;
        }

        /* Los botones de marcar/desmarcar/ver nuevos, agrupados */
        .botones-marcar {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
            flex-wrap: wrap;
        }

        .lista-administrar {
            max-height: 42vh;
            overflow-y: auto;
            border: 1px solid var(--borde);
            border-radius: 8px;
        }

        .fila-administrar-wrap:last-child .fila-administrar {
            border-bottom: none;
        }

        .fila-administrar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 12px;
            border-bottom: 1px solid var(--borde);
            cursor: pointer;
        }

        .fila-administrar input[type="checkbox"] {
            width: 19px;
            height: 19px;
            flex-shrink: 0;
        }

        .fila-administrar .orden-num {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--azul-50);
            color: var(--azul-medio);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .fila-administrar .orden-espacio {
            width: 22px;
            flex-shrink: 0;
        }

        .fila-administrar .info {
            flex: 1;
            min-width: 0;
        }

        .fila-administrar .nombre {
            font-weight: 600;
            font-size: 14px;
            overflow-wrap: anywhere;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
        }

        .fila-administrar .saldo {
            font-size: 12px;
            color: var(--texto-tenue);
            margin-top: 1px;
        }

        .fila-ordenar-inline {
            background: #f7f9fc;
            border-bottom: 1px solid var(--borde);
        }

        .fila-ordenar-inline select.select-ordenar {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            background: #fff;
        }

        /* ===== Modal: acciones sobre cliente ===== */
        .acc-cliente-nombre {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 2px;
            overflow-wrap: anywhere;
        }

        .acc-cliente-saldo {
            font-size: 13px;
            color: var(--texto-tenue);
            margin-bottom: 16px;
        }

        .lista-acciones-cliente {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        /* ===== Scroll interno de la lista de clientes (todas las pantallas) ===== */
        .lista-clientes-ruta {
            overflow-y: auto;
            padding-right: 6px;
            scrollbar-gutter: stable;
        }

        .lista-clientes-ruta::-webkit-scrollbar {
            width: 8px;
        }

        .lista-clientes-ruta::-webkit-scrollbar-thumb {
            background: var(--borde);
            border-radius: 8px;
        }

        /* ===== Modal: ubicación de un cliente (solo Guana) ===== */
        .ubic-botones {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .ubic-link {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }

        .ubic-link input {
            flex: 1;
            min-width: 0;
            padding: 9px 10px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
        }

        #mapa-ubicacion {
            height: 300px;
            border-radius: 8px;
            border: 1px solid var(--borde);
            z-index: 0;
        }

        #ubic-estado {
            font-size: 12px;
            color: var(--texto-tenue);
            margin-top: 8px;
        }

        .btn-ubicar {
            padding: 6px 10px;
            font-size: 12px;
            flex-shrink: 0;
        }

        .btn-ubicar.sin {
            background: #fff7e6;
            color: #8a5a00;
            border-color: #f3d999;
        }

        /* ===== Mapa (solo Guana) ===== */
        .tabs-vista-admin {
            display: flex;
            gap: 6px;
            padding: 4px;
            background: #eef1f6;
            border-radius: 10px;
            margin-bottom: 12px;
        }

        .tabs-vista-admin button {
            flex: 1;
            padding: 8px 12px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: var(--texto-tenue);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }

        .tabs-vista-admin button.activo {
            background: #fff;
            color: var(--azul-medio);
            box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
        }

        #mapa-administrar {
            height: 360px;
            border-radius: 8px;
            border: 1px solid var(--borde);
            z-index: 0;
        }

        #mapa-administrar-aviso {
            font-size: 12px;
            color: var(--texto-tenue);
            margin-top: 8px;
        }

        .pin-ruta {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            border: 2px solid #fff;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .35);
            background: #9aa4b2;
            color: #fff;
        }

        .pin-ruta.sel {
            background: var(--azul-medio);
        }

        .enlaces-tramos {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 10px;
        }

        .enlaces-tramos a {
            display: block;
            padding: 11px 14px;
            border-radius: 8px;
            background: var(--azul-medio);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        @media (max-width: 720px) {
            .opciones-tipo {
                grid-template-columns: 1fr;
            }

            .tabs-filtro {
                max-width: 100%;
            }

            .modal-overlay {
                padding: 0;
                align-items: flex-end;
            }

            .modal-caja,
            .modal-caja.modal-ancha {
                max-width: 100%;
                max-height: 92dvh;
                border-radius: 16px 16px 0 0;
            }

            .modal-footer {
                flex-direction: column-reverse;
            }

            .modal-footer .btn {
                width: 100%;
            }

            /* Botones de la cabecera de ruta: grid de 2 columnas en vez de fila apretada */
            .cabecera-ruta-acciones {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .cabecera-ruta-acciones .btn {
                width: 100%;
                min-width: 0;
                text-align: center;
                padding: 10px 6px;
                font-size: 12.5px;
                white-space: normal;
                line-height: 1.25;
            }

            .cabecera-ruta-acciones .btn-ancho {
                grid-column: 1 / -1;
            }

            .btn-icono {
                width: 34px;
                height: 34px;
            }

            .btn-icono svg {
                width: 16px;
                height: 16px;
            }
        }

        @media (max-width: 480px) {
            .administrar-toolbar {
                flex-direction: column;
            }

            .administrar-toolbar>input {
                width: 100%;
            }

            .botones-marcar {
                width: 100%;
            }

            .botones-marcar .btn {
                flex: 1;
                width: auto;
            }

            .fila-cliente-ruta {
                flex-wrap: wrap;
            }

            .fila-cliente-ruta .flechas {
                flex-direction: row;
                margin-left: 40px;
            }

            /* Se mantiene 2x2, solo se reducen un poco más los botones */
            .cabecera-ruta-acciones {
                gap: 6px;
            }

            .cabecera-ruta-acciones .btn {
                padding: 9px 4px;
                font-size: 11.5px;
            }
        }
    </style>
@endpush

@section('contenido')
    @php
        $sucursalRutas = \App\Models\Sucursal::find(session('sucursal_id'));
        $canalRutas = $sucursalRutas->canal ?? null;
        $canalRutas = $canalRutas instanceof \BackedEnum ? $canalRutas->value : $canalRutas;
        $esGuanaRutas = $canalRutas === 'mayorista';
    @endphp

    {{-- ===================== PANTALLA 1: elegir tipo ===================== --}}
    <div id="vista-tipo">
        <h1 class="titulo-pagina">Rutas</h1>
        <div class="opciones-tipo">
            <div class="opcion-tipo" onclick="elegirTipo('ordenada')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
                </svg>
                <div class="titulo">Ordenada</div>
                <div class="desc">Lista fija que se configura una vez y se reutiliza siempre</div>
            </div>
            <div class="opcion-tipo" onclick="elegirTipo('aleatoria')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M16 3h5v5M4 20 21 3M21 16v5h-5M15 15l6 6M4 4l5 5" />
                </svg>
                <div class="titulo">Aleatoria</div>
                <div class="desc">Armás la lista a tu gusto cada vez, se puede borrar al terminar</div>
            </div>
        </div>
    </div>

    {{-- ===================== PANTALLA 2: pestañas + lista ===================== --}}
    <div id="vista-filtro" style="display:none;">
        <button class="btn btn-secundario" onclick="volverATipo()">← Volver</button>
        <h2 class="rutas-titulo-tipo" id="titulo-tipo"></h2>

        <div class="tabs-filtro">
            <button type="button" class="tab-filtro" id="tab-activos" onclick="elegirFiltro('activos')">Activos</button>
            <button type="button" class="tab-filtro" id="tab-cancelados"
                onclick="elegirFiltro('cancelados')">Cancelados</button>
        </div>

        <div id="contenido-ruta"></div>
    </div>

    {{-- ===================== MODAL: administrar ruta ===================== --}}
    <div class="modal-overlay" id="modal-administrar">
        <div class="modal-caja modal-ancha">
            <div class="modal-header">
                <div>
                    <h2>Administrar ruta</h2>
                    <p>Marcá los clientes que forman parte de esta ruta</p>
                </div>
                <button class="modal-cerrar" onclick="cerrarModal('modal-administrar')" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-cuerpo">
                <div class="campo">
                    <label>Nombre de la ruta</label>
                    <input type="text" id="administrar-nombre" placeholder="Ej. Ruta zona centro">
                </div>

                @if ($esGuanaRutas)
                    <div class="tabs-vista-admin">
                        <button type="button" class="activo" id="tab-vista-lista"
                            onclick="cambiarVistaAdmin('lista')">Lista</button>
                        <button type="button" id="tab-vista-mapa" onclick="cambiarVistaAdmin('mapa')">Mapa</button>
                    </div>
                @endif

                <div id="vista-admin-lista">
                    <div class="administrar-toolbar">
                        <input type="text" id="buscador-administrar" placeholder="Buscar cliente...">
                        <div class="botones-marcar">
                            <button type="button" class="btn btn-secundario" id="btn-marcar-todos"
                                onclick="marcarTodos()">Marcar todos</button>
                            <button type="button" class="btn btn-secundario" id="btn-desmarcar-todos"
                                onclick="desmarcarTodos()">Desmarcar todos</button>
                            <button type="button" class="btn btn-secundario" id="btn-ver-nuevos"
                                onclick="toggleVerSoloCandidatos()">Ver nuevos</button>
                        </div>
                    </div>
                    <div class="lista-administrar" id="lista-administrar"></div>
                </div>

                @if ($esGuanaRutas)
                    <div id="vista-admin-mapa" style="display:none;">
                        <div id="mapa-administrar"></div>
                        <div id="mapa-administrar-aviso"></div>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secundario"
                    onclick="cerrarModal('modal-administrar')">Cancelar</button>
                <button type="button" class="btn btn-primario" onclick="guardarAdministrar()">Guardar</button>
            </div>
        </div>
    </div>

    {{-- ===================== MODAL: acciones sobre un cliente ===================== --}}
    <div class="modal-overlay" id="modal-acciones-cliente">
        <div class="modal-caja">
            <div class="modal-header">
                <h2>Gestionar visita</h2>
                <button class="modal-cerrar" onclick="cerrarModal('modal-acciones-cliente')" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-cuerpo">
                <div class="acc-cliente-nombre" id="acc-cliente-nombre"></div>
                <div class="acc-cliente-saldo" id="acc-cliente-saldo"></div>

                <div class="lista-acciones-cliente">
                    <button type="button" class="btn btn-secundario btn-bloque" onclick="irAFacturacion('abonar')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                        </svg>
                        Abonar
                    </button>
                    <button type="button" class="btn btn-secundario btn-bloque" onclick="irAFacturacion('compra')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                            <path d="M3 6h18" />
                            <path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                        Compra
                    </button>
                    <button type="button" class="btn btn-secundario btn-bloque" onclick="irAFacturacion('devolucion')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 7v6h6" />
                            <path d="M21 17a9 9 0 0 0-15-6.7L3 13" />
                        </svg>
                        Devolución
                    </button>
                    @if ($esGuanaRutas)
                        <button type="button" class="btn btn-secundario btn-bloque"
                            onclick="abrirUbicacionDesdeVisita()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg>
                            Ubicación del cliente
                        </button>
                        <button type="button" class="btn btn-secundario btn-bloque" onclick="navegarACliente('google')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="m3 11 19-9-9 19-2-8-8-2Z" />
                            </svg>
                            Navegar con Google Maps
                        </button>
                        <button type="button" class="btn btn-secundario btn-bloque" onclick="navegarACliente('waze')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="m3 11 19-9-9 19-2-8-8-2Z" />
                            </svg>
                            Navegar con Waze
                        </button>
                    @endif
                    <button type="button" class="btn btn-advertencia btn-bloque" onclick="marcarRecobro()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                        </svg>
                        Recobro (pasar para más tarde)
                    </button>
                    <button type="button" class="btn btn-peligro btn-bloque" onclick="marcarNoAbono()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <path d="m15 9-6 6M9 9l6 6" />
                        </svg>
                        No abonó
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== MODAL: clientes finalizados ===================== --}}
    <div class="modal-overlay" id="modal-finalizados">
        <div class="modal-caja">
            <div class="modal-header">
                <h2>Clientes finalizados</h2>
                <button class="modal-cerrar" onclick="cerrarModal('modal-finalizados')" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-cuerpo">
                <div class="lista-finalizados" id="lista-finalizados"></div>
                <div id="estado-vacio-finalizados" class="estado-vacio" style="display:none;">Todavía no hay clientes
                    finalizados en esta ruta.</div>
            </div>
        </div>
    </div>


    {{-- ===================== MODAL: atrasados (4+ semanas sin abonar) ===================== --}}
    <div class="modal-overlay" id="modal-atrasados">
        <div class="modal-caja">
            <div class="modal-header">
                <h2>Atrasados (4+ semanas sin abonar)</h2>
                <button class="modal-cerrar" onclick="cerrarModal('modal-atrasados')" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-cuerpo">
                <div class="lista-finalizados" id="lista-atrasados"></div>
            </div>
        </div>
    </div>

    {{-- ===================== MODAL: ubicación de un cliente (solo Guana) ===================== --}}
    @if ($esGuanaRutas)
        <div class="modal-overlay" id="modal-ubicacion">
            <div class="modal-caja modal-ancha">
                <div class="modal-header">
                    <div>
                        <h2>Ubicación del cliente</h2>
                        <p id="ubic-nombre"></p>
                    </div>
                    <button class="modal-cerrar" onclick="cerrarModal('modal-ubicacion')" aria-label="Cerrar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="modal-cuerpo">
                    <div class="ubic-botones">
                        <button type="button" class="btn btn-secundario" onclick="usarMiUbicacionRuta()">Usar mi
                            ubicación actual</button>
                    </div>
                    <div class="ubic-link">
                        <input type="text" id="ubic-link" placeholder="Pegá un link de Google Maps o 'lat, lng'">
                        <button type="button" class="btn btn-secundario" onclick="leerLinkUbicacionRuta()">Usar</button>
                    </div>
                    <div id="mapa-ubicacion"></div>
                    <div id="ubic-estado">Tocá el mapa para poner el pin (se puede arrastrar).</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secundario"
                        onclick="cerrarModal('modal-ubicacion')">Cancelar</button>
                    <button type="button" class="btn btn-primario" onclick="guardarUbicacionRuta()">Guardar
                        ubicación</button>
                </div>
            </div>
        </div>
    @endif

@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const token = document.querySelector('meta[name="csrf-token"]').content;

        const ES_GUANA = @json($esGuanaRutas);
        const URL_RECIBO = @json(route('operaciones.recibo', ['operacion' => '__ID__']));
        let mapaAdmin = null,
            capaPinesAdmin = null;

        let tipoActual = null;
        let filtroActual = null;
        let rutaActual = null;
        let clientesActivos = [];
        let clienteSeleccionadoAccion = null;

        // Estado del modal "administrar": mapa clienteId -> {nombre, telefono, saldo_actual, latitud, longitud, estadoOriginal, trasladado}
        // y un arreglo con el ORDEN de selección actual.
        let administrarPersonas = {};
        let ordenSeleccion = [];

        // Ítem 4: toggle para ver solo los candidatos sin posición asignada todavía
        // (los "Nuevo" y "Se pasó de ruta").
        let verSoloCandidatos = false;

        function formatoColones(monto) {
            return '₡' + Number(monto).toLocaleString('es-CR', {
                minimumFractionDigits: 2
            });
        }

        function abrirModal(id) {
            document.getElementById(id).classList.add('abierto');
        }

        function cerrarModal(id) {
            document.getElementById(id).classList.remove('abierto');
        }

        function iconoAdvertencia() {
            return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>`;
        }

        function etiquetaEstado(estado) {
            return {
                finalizado: 'Finalizado',
                no_abono: 'No abonó',
                recobro: 'Recobro'
            } [estado] ?? estado;
        }

        /* ==================== AJUSTE DE ALTURA DE LA LISTA (solo desktop) ==================== */

        function ajustarAlturaLista() {
            const lista = document.querySelector('.lista-clientes-ruta');
            if (!lista) return;
            const margenInferior = window.innerWidth <= 720 ? 16 : 24;
            const top = lista.getBoundingClientRect().top;
            const alto = window.innerHeight - top - margenInferior;
            lista.style.maxHeight = Math.max(alto, 160) + 'px';
        }

        window.addEventListener('resize', ajustarAlturaLista);

        /* ==================== NAVEGACIÓN ==================== */

        function elegirTipo(tipo) {
            tipoActual = tipo;
            document.getElementById('vista-tipo').style.display = 'none';
            document.getElementById('vista-filtro').style.display = 'block';
            document.getElementById('titulo-tipo').textContent = tipo === 'ordenada' ? 'Ruta Ordenada' : 'Ruta Aleatoria';
            elegirFiltro('activos');
        }

        function volverATipo() {
            tipoActual = null;
            filtroActual = null;
            rutaActual = null;
            document.getElementById('vista-filtro').style.display = 'none';
            document.getElementById('vista-tipo').style.display = 'block';
        }

        async function elegirFiltro(filtro) {
            filtroActual = filtro;
            document.getElementById('tab-activos').classList.toggle('activo', filtro === 'activos');
            document.getElementById('tab-cancelados').classList.toggle('activo', filtro === 'cancelados');
            await cargarRuta();
        }

        /* ==================== CARGAR / PINTAR LA RUTA ACTUAL ==================== */

        async function cargarRuta() {
            const resp = await fetch(`/rutas/${tipoActual}/${filtroActual}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            rutaActual = await resp.json();
            clientesActivos = rutaActual.activos;
            pintarContenidoRuta();
        }

        function pintarContenidoRuta() {
            const cont = document.getElementById('contenido-ruta');
            const todosFinalizados = clientesActivos.length === 0 && rutaActual.finalizados.length > 0;
            const cantidadAtrasados = clientesActivos.filter(rc => rc.atrasado).length;

            // El reordenamiento manual aplica a los dos tipos (ordenada y aleatoria).
            const listaHTML = clientesActivos.length ?
                clientesActivos.map((rc, i) => `
                                <div class="fila-cliente-ruta ${rc.estado === 'recobro' ? 'recobro' : ''} ${rc.atrasado ? 'atrasado' : ''}" onclick="abrirModalAccionesCliente(${rc.id})">
                    <div class="numero">${i + 1}</div>
                    <div class="info">
                        <div class="nombre">${rc.estado === 'recobro' ? iconoAdvertencia() : ''} ${rc.cliente.nombre}</div>
                        <div class="saldo">${formatoColones(rc.cliente.saldo_actual)}${rc.estado === 'recobro' ? ' · Recobro' : ''}${rc.atrasado ? ' · <span class="badge badge-rojo" style="padding:1px 8px;">Atrasado</span>' : ''}</div>
                    </div>
                    <div class="flechas" onclick="event.stopPropagation()">
                        <button type="button" class="btn-icono" ${i === 0 ? 'disabled' : ''} onclick="moverCliente(${i}, -1)" aria-label="Subir">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
                        </button>
                        <button type="button" class="btn-icono" ${i === clientesActivos.length - 1 ? 'disabled' : ''} onclick="moverCliente(${i}, 1)" aria-label="Bajar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                    </div>
                </div>
            `).join('') :
                `<div class="estado-vacio">No hay clientes en esta ruta todavía. Usá "Administrar ruta" para agregar.</div>`;

            cont.innerHTML = `
            <div class="cabecera-ruta">
                <h2>${rutaActual.ruta.nombre}</h2>
                                <div class="cabecera-ruta-acciones">
                    <button class="btn btn-secundario" onclick="abrirModalAdministrar()">Administrar ruta</button>
                    <button class="btn btn-secundario" onclick="abrirModalFinalizados()">Ver finalizados</button>
                                        <button class="btn btn-advertencia" ${todosFinalizados ? 'disabled title="Exportá el reporte primero"' : ''} onclick="empezarDeNuevo()">Empezar de nuevo</button>
                    <button class="btn btn-exito" ${todosFinalizados ? '' : 'disabled'} onclick="exportarReporte()">Exportar reporte de ruta</button>
                    ${cantidadAtrasados > 0 ? `<button class="btn btn-peligro" onclick="abrirModalAtrasados()">Ver atrasados (${cantidadAtrasados})</button>` : ''}
                    ${ES_GUANA ? `<button class="btn btn-secundario btn-ancho" onclick="abrirEnGoogleMaps()">Abrir ruta en Google Maps</button>` : ''}
                </div>
            </div>
            <div class="lista-clientes-ruta">${listaHTML}</div>
        `;

            ajustarAlturaLista();
        }

        async function moverCliente(indice, direccion) {
            const nuevoIndice = indice + direccion;
            if (nuevoIndice < 0 || nuevoIndice >= clientesActivos.length) return;

            [clientesActivos[indice], clientesActivos[nuevoIndice]] = [clientesActivos[nuevoIndice], clientesActivos[
                indice]];
            pintarContenidoRuta();

            const orden = clientesActivos.map(rc => rc.id);
            await fetch(`/rutas/${rutaActual.ruta.id}/reordenar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    orden
                })
            });
        }

        /* ==================== MODAL ADMINISTRAR RUTA ==================== */

        async function abrirModalAdministrar() {
            const resp = await fetch(`/rutas/${rutaActual.ruta.id}/gestionar`, {
                headers: {
                    'Accept': 'application/json'
                }
            });
            const datos = await resp.json();

            document.getElementById('administrar-nombre').value = datos.ruta.nombre ?? '';
            document.getElementById('buscador-administrar').value = '';

            administrarPersonas = {};
            ordenSeleccion = [];
            verSoloCandidatos = false;
            document.getElementById('btn-ver-nuevos').classList.remove('btn-primario');
            document.getElementById('btn-ver-nuevos').classList.add('btn-secundario');

            datos.actuales.forEach(rc => {
                administrarPersonas[rc.cliente_id] = {
                    nombre: rc.cliente.nombre,
                    telefono: rc.cliente.telefono,
                    saldo_actual: rc.cliente.saldo_actual,
                    latitud: rc.cliente.latitud,
                    longitud: rc.cliente.longitud,
                    estadoOriginal: rc.estado, // pendiente | finalizado | no_abono | recobro
                };
                ordenSeleccion.push(rc.cliente_id);
            });

            datos.candidatos.forEach(c => {
                administrarPersonas[c.id] = {
                    nombre: c.nombre,
                    telefono: c.telefono,
                    saldo_actual: c.saldo_actual,
                    latitud: c.latitud,
                    longitud: c.longitud,
                    trasladado: c.trasladado,
                };
            });

            pintarListaAdministrar();
            if (ES_GUANA) cambiarVistaAdmin('lista');
            abrirModal('modal-administrar');
        }

        // Ítem 4: toggle "Ver nuevos" — muestra solo los candidatos sin posición
        // asignada (Nuevo o Se pasó de ruta), ocultando los ya seleccionados.
        function toggleVerSoloCandidatos() {
            verSoloCandidatos = !verSoloCandidatos;
            document.getElementById('btn-ver-nuevos').classList.toggle('btn-primario', verSoloCandidatos);
            document.getElementById('btn-ver-nuevos').classList.toggle('btn-secundario', !verSoloCandidatos);
            pintarListaAdministrar(document.getElementById('buscador-administrar').value);
        }

        function pintarListaAdministrar(filtroTexto = '') {
            const cont = document.getElementById('lista-administrar');
            const texto = filtroTexto.trim().toLowerCase();

            const idsSeleccionados = new Set(ordenSeleccion);
            const idsRestantes = Object.keys(administrarPersonas)
                .map(Number)
                .filter(id => !idsSeleccionados.has(id))
                .sort((a, b) => administrarPersonas[a].nombre.localeCompare(administrarPersonas[b].nombre));

            const idsOrdenVisual = [...ordenSeleccion, ...idsRestantes];

            const idsFiltrados = (texto ?
                idsOrdenVisual.filter(id => administrarPersonas[id].nombre.toLowerCase().includes(texto)) :
                idsOrdenVisual
            ).filter(id => !verSoloCandidatos || !idsSeleccionados.has(id));

            cont.innerHTML = idsFiltrados.map(id => {
                const p = administrarPersonas[id];
                const marcado = idsSeleccionados.has(id);
                const numeroOrden = marcado ? ordenSeleccion.indexOf(id) + 1 : null;

                // Ítem 4: badge "Nuevo" (recién creado en esta sucursal, nunca
                // estuvo en ninguna ruta) vs "Se pasó de ruta" (venía de otra
                // sucursal, trae o no saldo).
                const badge = !marcado ?
                    (p.trasladado ?
                        '<span class="badge badge-naranja">Se pasó de ruta</span>' :
                        '<span class="badge badge-verde">Nuevo</span>') :
                    '';

                return `
                <div class="fila-administrar-wrap">
                    <label class="fila-administrar">
                        <input type="checkbox" value="${id}" ${marcado ? 'checked' : ''} onchange="toggleSeleccion(${id}, this.checked)">
                        ${marcado ? `<div class="orden-num">${numeroOrden}</div>` : '<div class="orden-espacio"></div>'}
                        <div class="info">
                            <div class="nombre">${p.nombre}${badge}</div>
                            <div class="saldo">${formatoColones(p.saldo_actual)}${p.telefono ? ' · ' + p.telefono : ''}</div>
                        </div>
                        ${ES_GUANA ? `<button type="button" class="btn btn-secundario btn-ubicar ${p.latitud == null ? 'sin' : ''}"
                                onclick="event.preventDefault(); event.stopPropagation(); abrirUbicacionDesdeAdmin(${id})">${p.latitud == null ? 'Ubicar' : 'Cambiar ubicación'}</button>` : ''}
                        ${!marcado ? `<button type="button" class="btn btn-secundario" onclick="event.preventDefault(); event.stopPropagation(); toggleOrdenarInline(${id})">Ordenar</button>` : ''}
                    </label>
                    ${!marcado ? `<div class="fila-ordenar-inline" id="ordenar-inline-${id}" style="display:none; padding:8px 12px 12px 44px;">
                            <select class="select-ordenar" onchange="insertarConOrden(${id}, this.value)">
                                <option value="">Elegí una posición...</option>
                                ${ordenSeleccion.map((idExistente, i) => `
                                <option value="antes:${idExistente}">Antes de: ${i + 1}. ${administrarPersonas[idExistente].nombre}</option>
                                <option value="despues:${idExistente}">Después de: ${i + 1}. ${administrarPersonas[idExistente].nombre}</option>
                            `).join('')}
                                ${ordenSeleccion.length === 0 ? '<option value="antes:__inicio__">Primero en la lista</option>' : ''}
                            </select>
                        </div>` : ''}
                </div>
            `;
            }).join('');

            // Mantiene el mapa sincronizado con la selección (solo Guana)
            if (ES_GUANA) pintarMapaAdministrar();
            actualizarBotonesMarcar();
        }

        // Si se desmarca a alguien que ya tenía un resultado registrado (no "pendiente"),
        // se pide confirmación antes de quitarlo, porque se pierde ese historial.
        async function toggleSeleccion(id, marcado) {
            if (!marcado) {
                const persona = administrarPersonas[id];
                const tieneHistorial = persona?.estadoOriginal && persona.estadoOriginal !== 'pendiente';

                if (tieneHistorial) {
                    const confirmar = await Swal.fire({
                        title: '¿Quitar este cliente?',
                        text: `Ya tiene un resultado registrado en esta ruta (${etiquetaEstado(persona.estadoOriginal)}). Si lo quitás, se pierde ese historial.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, quitar',
                        cancelButtonText: 'Cancelar'
                    });
                    if (!confirmar.isConfirmed) {
                        pintarListaAdministrar(document.getElementById('buscador-administrar').value);
                        return;
                    }
                }

                ordenSeleccion = ordenSeleccion.filter(x => x !== id);
            } else {
                if (!ordenSeleccion.includes(id)) ordenSeleccion.push(id);
            }

            pintarListaAdministrar(document.getElementById('buscador-administrar').value);
        }

        // Ítem 4: abre/cierra el selector inline de "antes de / después de" para
        // un candidato. Solo uno abierto a la vez, para no acumular selects.
        function toggleOrdenarInline(id) {
            const el = document.getElementById(`ordenar-inline-${id}`);
            if (!el) return;
            const abierto = el.style.display !== 'none';
            document.querySelectorAll('.fila-ordenar-inline').forEach(x => x.style.display = 'none');
            el.style.display = abierto ? 'none' : 'block';
        }

        // Ítem 4: inserta al candidato en ordenSeleccion en la posición elegida
        // (antes o después de un cliente ya asignado), todo en memoria — se
        // manda igual que siempre al guardar, sin tocar el backend.
        function insertarConOrden(id, valor) {
            if (!valor) return;
            const [posicion, refId] = valor.split(':');

            if (refId === '__inicio__') {
                ordenSeleccion.unshift(id);
            } else {
                const idxRef = ordenSeleccion.indexOf(Number(refId));
                const idx = posicion === 'antes' ? idxRef : idxRef + 1;
                ordenSeleccion.splice(idx, 0, id);
            }

            pintarListaAdministrar(document.getElementById('buscador-administrar').value);
        }

        // Agrega al final (orden alfabético) a todos los que faltan por marcar.
        function marcarTodos() {
            const totalIds = Object.keys(administrarPersonas).map(Number);
            const restantesAlfabetico = totalIds
                .filter(id => !ordenSeleccion.includes(id))
                .sort((a, b) => administrarPersonas[a].nombre.localeCompare(administrarPersonas[b].nombre));

            ordenSeleccion = [...ordenSeleccion, ...restantesAlfabetico];
            pintarListaAdministrar(document.getElementById('buscador-administrar').value);
        }

        // Igual que al desmarcar uno solo: si alguno de los que se van a quitar
        // ya tiene un resultado registrado, se pide confirmación una sola vez
        // (no una por cliente) antes de vaciar toda la selección.
        async function desmarcarTodos() {
            if (!ordenSeleccion.length) return;

            const conHistorial = ordenSeleccion.filter(id => {
                const p = administrarPersonas[id];
                return p?.estadoOriginal && p.estadoOriginal !== 'pendiente';
            });

            if (conHistorial.length) {
                const confirmar = await Swal.fire({
                    title: '¿Desmarcar todos?',
                    text: `${conHistorial.length} cliente(s) ya tienen un resultado registrado en esta ruta (${conHistorial.map(id => etiquetaEstado(administrarPersonas[id].estadoOriginal)).join(', ')}). Si los quitás a todos, se pierde ese historial.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, desmarcar todos',
                    cancelButtonText: 'Cancelar'
                });
                if (!confirmar.isConfirmed) return;
            }

            ordenSeleccion = [];
            pintarListaAdministrar(document.getElementById('buscador-administrar').value);
        }

        // Deshabilita cada botón cuando no tiene nada que hacer: "Marcar todos" si
        // ya está todo marcado, "Desmarcar todos" si no hay nada marcado.
        function actualizarBotonesMarcar() {
            const btnMarcar = document.getElementById('btn-marcar-todos');
            const btnDesmarcar = document.getElementById('btn-desmarcar-todos');
            if (!btnMarcar || !btnDesmarcar) return;

            const totalIds = Object.keys(administrarPersonas).length;
            const todosMarcados = totalIds > 0 && ordenSeleccion.length === totalIds;

            btnMarcar.disabled = todosMarcados;
            btnDesmarcar.disabled = ordenSeleccion.length === 0;
        }

        document.getElementById('buscador-administrar').addEventListener('input', (e) => {
            pintarListaAdministrar(e.target.value);
        });

        async function guardarAdministrar() {
            const nombre = document.getElementById('administrar-nombre').value;

            const resp = await fetch(`/rutas/${rutaActual.ruta.id}/administrar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-HTTP-Method-Override': 'PUT'
                },
                body: JSON.stringify({
                    nombre,
                    clientes: ordenSeleccion
                })
            });

            if (!resp.ok) {
                const datos = await resp.json();
                Swal.fire('Error', datos.mensaje ?? 'No se pudo guardar.', 'error');
                return;
            }

            cerrarModal('modal-administrar');
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Ruta actualizada',
                showConfirmButton: false,
                timer: 2000
            });
            cargarRuta();
        }

        /* ==================== MAPA (solo Guana) ==================== */

        function cambiarVistaAdmin(vista) {
            if (!ES_GUANA) return;
            const mapa = vista === 'mapa';
            document.getElementById('vista-admin-lista').style.display = mapa ? 'none' : '';
            document.getElementById('vista-admin-mapa').style.display = mapa ? '' : 'none';
            document.getElementById('tab-vista-lista').classList.toggle('activo', !mapa);
            document.getElementById('tab-vista-mapa').classList.toggle('activo', mapa);
            if (mapa) iniciarMapaAdmin();
        }

        function iniciarMapaAdmin() {
            if (!mapaAdmin) {
                mapaAdmin = L.map('mapa-administrar').setView([10.6350, -85.4377], 12);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(mapaAdmin);
                capaPinesAdmin = L.layerGroup().addTo(mapaAdmin);
            }
            // El mapa vive en un modal que estaba oculto: hay que recalcular su tamaño
            setTimeout(() => {
                mapaAdmin.invalidateSize();
                pintarMapaAdministrar(true);
            }, 150);
        }

        function pintarMapaAdministrar(ajustar = false) {
            if (!mapaAdmin || !capaPinesAdmin) return;
            capaPinesAdmin.clearLayers();

            const seleccionados = new Set(ordenSeleccion);
            const puntos = [];
            let sinUbicacion = 0;

            Object.keys(administrarPersonas).map(Number).forEach(id => {
                const p = administrarPersonas[id];
                if (p.latitud == null || p.longitud == null) {
                    sinUbicacion++;
                    return;
                }

                const sel = seleccionados.has(id);
                const numero = sel ? ordenSeleccion.indexOf(id) + 1 : '';
                const icono = L.divIcon({
                    className: '',
                    html: `<div class="pin-ruta ${sel ? 'sel' : ''}">${numero}</div>`,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                });

                const marcador = L.marker([p.latitud, p.longitud], {
                    icon: icono
                });
                marcador.bindPopup(`
                <strong>${p.nombre}</strong><br>
                ${formatoColones(p.saldo_actual)}<br>
                <button type="button" class="btn ${sel ? 'btn-peligro' : 'btn-primario'}" style="margin-top:8px;padding:7px 12px;font-size:13px;"
                    onclick="toggleDesdeMapa(${id})">${sel ? 'Quitar de la ruta' : 'Agregar a la ruta'}</button>
            `);
                marcador.addTo(capaPinesAdmin);
                puntos.push([p.latitud, p.longitud]);
            });

            document.getElementById('mapa-administrar-aviso').textContent =
                `${puntos.length} cliente(s) con ubicación en el mapa` +
                (sinUbicacion ?
                    ` · ${sinUbicacion} sin ubicación (agregalos desde la lista o cargales la ubicación en Clientes)` : '');

            if (ajustar && puntos.length) mapaAdmin.fitBounds(puntos, {
                padding: [30, 30],
                maxZoom: 16
            });
        }

        async function toggleDesdeMapa(id) {
            if (mapaAdmin) mapaAdmin.closePopup();
            await toggleSeleccion(id, !ordenSeleccion.includes(id));
        }

        /* ==================== ABRIR RUTA EN GOOGLE MAPS ==================== */

        // Google acepta hasta ~9 paradas intermedias por link, así que la ruta se
        // parte en tramos de 10 paradas (9 intermedias + destino). El primer tramo
        // sale de la ubicación actual; cada tramo siguiente arranca donde terminó el anterior.
        function abrirEnGoogleMaps() {
            const conUbicacion = clientesActivos.filter(rc => rc.cliente.latitud != null && rc.cliente.longitud != null);
            const sinUbicacion = clientesActivos.length - conUbicacion.length;

            if (!conUbicacion.length) {
                Swal.fire('Sin ubicaciones', 'Ningún cliente de esta ruta tiene ubicación cargada todavía.', 'info');
                return;
            }

            const TAM = 10;
            const tramos = [];
            for (let i = 0; i < conUbicacion.length; i += TAM) tramos.push(conUbicacion.slice(i, i + TAM));

            const coord = rc => `${rc.cliente.latitud},${rc.cliente.longitud}`;
            const urlTramo = (tramo, previo) => {
                const p = new URLSearchParams({
                    api: '1',
                    destination: coord(tramo[tramo.length - 1]),
                    travelmode: 'driving'
                });
                const paradas = tramo.slice(0, -1).map(coord).join('|');
                if (paradas) p.set('waypoints', paradas);
                // Sin 'origin': Google Maps parte de "Tu ubicación" y así ofrece el botón Iniciar.
                // (Si se fija un origen, la app solo muestra la vista previa de la ruta.)
                return 'https://www.google.com/maps/dir/?' + p.toString();
            };

            const urls = tramos.map((t, i) => urlTramo(t, i > 0 ? tramos[i - 1][tramos[i - 1].length - 1] : null));

            if (urls.length === 1 && !sinUbicacion) {
                window.open(urls[0], '_blank');
                return;
            }

            const enlaces = urls.map((u, i) => {
                const desde = i * TAM + 1,
                    hasta = Math.min((i + 1) * TAM, conUbicacion.length);
                return `<a href="${u}" target="_blank" rel="noopener">Tramo ${i + 1} · paradas ${desde} a ${hasta}</a>`;
            }).join('');

            Swal.fire({
                title: 'Abrir en Google Maps',
                html: `
                ${sinUbicacion ? `<p style="font-size:13px;color:#a36a00;">${sinUbicacion} cliente(s) sin ubicación no se incluyen.</p>` : ''}
                <p style="font-size:13px;">${urls.length > 1 ? 'La ruta es larga, se divide en tramos. Abrí cada uno cuando termines el anterior.' : ''}</p>
                <div class="enlaces-tramos">${enlaces}</div>`,
                showConfirmButton: false,
                showCloseButton: true,
            });
        }

        /* ==================== NAVEGAR A UN CLIENTE (Google Maps / Waze) ==================== */

        // Un solo destino desde tu ubicación actual: es el caso en que ambas apps
        // ofrecen iniciar la navegación paso a paso.
        function navegarACliente(app) {
            if (!clienteSeleccionadoAccion) return;
            const c = clienteSeleccionadoAccion.cliente;

            if (c.latitud == null || c.longitud == null) {
                Swal.fire('Sin ubicación',
                    'Este cliente todavía no tiene ubicación. Usá "Ubicación del cliente" para cargarla.', 'info');
                return;
            }

            const url = app === 'waze' ?
                `https://waze.com/ul?ll=${c.latitud},${c.longitud}&navigate=yes` :
                `https://www.google.com/maps/dir/?api=1&destination=${c.latitud},${c.longitud}&travelmode=driving&dir_action=navigate`;

            window.open(url, '_blank');
        }

        /* ==================== UBICACIÓN DE UN CLIENTE (solo Guana) ==================== */

        let mapaUbic = null,
            marcadorUbic = null,
            clienteUbic = null,
            ubicLat = null,
            ubicLng = null;

        function abrirUbicacionDesdeAdmin(id) {
            const p = administrarPersonas[id];
            if (!p) return;
            abrirModalUbicacion({
                id,
                nombre: p.nombre,
                latitud: p.latitud,
                longitud: p.longitud
            });
        }

        function abrirUbicacionDesdeVisita() {
            if (!clienteSeleccionadoAccion) return;
            const c = clienteSeleccionadoAccion.cliente;
            abrirModalUbicacion({
                id: c.id,
                nombre: c.nombre,
                latitud: c.latitud,
                longitud: c.longitud
            });
        }

        function abrirModalUbicacion(c) {
            clienteUbic = c;
            ubicLat = null;
            ubicLng = null;
            document.getElementById('ubic-nombre').textContent = c.nombre;
            document.getElementById('ubic-link').value = '';
            document.getElementById('ubic-estado').textContent = 'Tocá el mapa para poner el pin (se puede arrastrar).';
            abrirModal('modal-ubicacion');

            if (!mapaUbic) {
                mapaUbic = L.map('mapa-ubicacion').setView([10.6350, -85.4377], 12);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(mapaUbic);
                mapaUbic.on('click', e => fijarUbic(e.latlng.lat, e.latlng.lng, false));
            }

            setTimeout(() => {
                mapaUbic.invalidateSize();
                if (marcadorUbic) {
                    mapaUbic.removeLayer(marcadorUbic);
                    marcadorUbic = null;
                }
                if (c.latitud != null && c.longitud != null) fijarUbic(Number(c.latitud), Number(c.longitud));
                else mapaUbic.setView([10.6350, -85.4377], 12);
            }, 150);
        }

        function fijarUbic(lat, lng, centrar = true) {
            lat = Number(lat.toFixed(7));
            lng = Number(lng.toFixed(7));
            ubicLat = lat;
            ubicLng = lng;

            if (!marcadorUbic) {
                marcadorUbic = L.marker([lat, lng], {
                    draggable: true
                }).addTo(mapaUbic);
                marcadorUbic.on('dragend', () => {
                    const p = marcadorUbic.getLatLng();
                    fijarUbic(p.lat, p.lng, false);
                });
            } else {
                marcadorUbic.setLatLng([lat, lng]);
            }
            if (centrar) mapaUbic.setView([lat, lng], 16);
            document.getElementById('ubic-estado').textContent = `Pin en: ${lat}, ${lng} (falta guardar)`;
        }

        function usarMiUbicacionRuta() {
            if (!navigator.geolocation) {
                Swal.fire('No disponible', 'Este navegador no permite obtener la ubicación.', 'info');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                pos => fijarUbic(pos.coords.latitude, pos.coords.longitude),
                () => Swal.fire('No se pudo obtener',
                    'Revisá que el navegador tenga permiso de ubicación (y que el sitio use https).', 'warning'), {
                    enableHighAccuracy: true,
                    timeout: 15000
                }
            );
        }

        function leerLinkUbicacionRuta() {
            const texto = document.getElementById('ubic-link').value.trim();
            const patrones = [
                /@(-?\d+\.\d+),(-?\d+\.\d+)/,
                /!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/,
                /[?&](?:q|ll|query)=(-?\d+\.\d+),(-?\d+\.\d+)/,
                /^(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)$/,
            ];
            for (const p of patrones) {
                const m = texto.match(p);
                if (m) {
                    const lat = parseFloat(m[1]),
                        lng = parseFloat(m[2]);
                    if (Math.abs(lat) <= 90 && Math.abs(lng) <= 180) {
                        fijarUbic(lat, lng);
                        return;
                    }
                }
            }
            Swal.fire('No pude leer el link',
                'Los links cortos (maps.app.goo.gl) no traen coordenadas. Abrilo en el navegador y copiá la URL larga, o pegá "lat, lng".',
                'info');
        }

        async function guardarUbicacionRuta() {
            if (ubicLat == null || ubicLng == null) {
                Swal.fire('Falta el pin', 'Tocá el mapa, usá tu ubicación o pegá un link antes de guardar.', 'info');
                return;
            }

            const resp = await fetch(`/clientes/${clienteUbic.id}/ubicacion`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-HTTP-Method-Override': 'PUT'
                },
                body: JSON.stringify({
                    latitud: ubicLat,
                    longitud: ubicLng
                })
            });

            if (!resp.ok) {
                let msg = 'No se pudo guardar la ubicación.';
                try {
                    const d = await resp.json();
                    msg = d.mensaje ?? Object.values(d.errors ?? {}).flat().join('\n') ?? msg;
                } catch (e) {}
                Swal.fire('Error', msg, 'error');
                return;
            }

            // Actualiza los datos ya cargados en pantalla para no tener que recargar
            const id = clienteUbic.id;
            if (administrarPersonas[id]) {
                administrarPersonas[id].latitud = ubicLat;
                administrarPersonas[id].longitud = ubicLng;
                pintarListaAdministrar(document.getElementById('buscador-administrar').value);
            }
            [...clientesActivos, ...(rutaActual?.finalizados ?? [])].forEach(rc => {
                if (rc.cliente.id === id) {
                    rc.cliente.latitud = ubicLat;
                    rc.cliente.longitud = ubicLng;
                }
            });

            cerrarModal('modal-ubicacion');
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Ubicación guardada',
                showConfirmButton: false,
                timer: 2000
            });
        }

        /* ==================== ACCIONES SOBRE UN CLIENTE ==================== */

        function abrirModalAccionesCliente(rutaClienteId) {
            clienteSeleccionadoAccion = clientesActivos.find(rc => rc.id === rutaClienteId);
            if (!clienteSeleccionadoAccion) return;

            document.getElementById('acc-cliente-nombre').textContent = clienteSeleccionadoAccion.cliente.nombre;
            document.getElementById('acc-cliente-saldo').textContent = 'Saldo actual: ' + formatoColones(
                clienteSeleccionadoAccion.cliente.saldo_actual);
            abrirModal('modal-acciones-cliente');
        }

        function irAFacturacion(tipoOperacion) {
            if (!clienteSeleccionadoAccion) return;
            const params = new URLSearchParams({
                cliente_id: clienteSeleccionadoAccion.cliente.id,
                ruta_cliente_id: clienteSeleccionadoAccion.id,
                ruta_id: rutaActual.ruta.id,
                operacion: tipoOperacion,
            });
            window.location.href = `{{ route('facturacion.index') }}?${params.toString()}`;
        }

        async function cambiarEstadoRutaCliente(estado) {
            if (!clienteSeleccionadoAccion) return;

            await fetch(`/ruta-clientes/${clienteSeleccionadoAccion.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-HTTP-Method-Override': 'PUT'
                },
                body: JSON.stringify({
                    estado
                })
            });

            cerrarModal('modal-acciones-cliente');
            cargarRuta();
        }

        function marcarRecobro() {
            cambiarEstadoRutaCliente('recobro');
        }

                async function marcarNoAbono() {
            if (!clienteSeleccionadoAccion) return;
            const rc = clienteSeleccionadoAccion;

            const r = await Swal.fire({
                title: '¿Marcar como "No abonó"?',
                text: rc.cliente.nombre,
                input: 'text',
                inputPlaceholder: 'Motivo (opcional)',
                inputAttributes: { maxlength: 500 },
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, marcar',
                cancelButtonText: 'Cancelar'
            });
            if (!r.isConfirmed) return;

            const resp = await fetch(`/ruta-clientes/${rc.id}/no-abono`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ descripcion: r.value || null })
            });
            const datos = await resp.json().catch(() => ({}));

            cerrarModal('modal-acciones-cliente');

            if (!resp.ok) {
                Swal.fire('No se pudo marcar', datos.mensaje ?? 'Intentá de nuevo.', 'error');
                cargarRuta();
                return;
            }

            // Como en Facturación: el recibo de "No abonó" se genera y envía en una petición aparte
                      // Primero se refresca la lista; el recibo se manda después para que no la haga esperar
            await cargarRuta();

            fetch(URL_RECIBO.replace('__ID__', datos.operacion_id), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                keepalive: true
            }).catch(() => {});
        }
        /* ==================== CLIENTES FINALIZADOS ==================== */

        function abrirModalFinalizados() {
            const cont = document.getElementById('lista-finalizados');
            const lista = rutaActual.finalizados;

            document.getElementById('estado-vacio-finalizados').style.display = lista.length ? 'none' : 'block';
            cont.innerHTML = lista.map(rc => `
            <div class="fila-finalizado">
                <div>
                    <div class="nombre">${rc.cliente.nombre}</div>
                    <div class="saldo">${formatoColones(rc.cliente.saldo_actual)}</div>
                </div>
                <span class="badge ${rc.estado === 'no_abono' ? 'badge-rojo' : 'badge-verde'}">
                    ${rc.estado === 'no_abono' ? 'No abonó' : 'Finalizado'}
                </span>
            </div>
        `).join('');

            abrirModal('modal-finalizados');
        }

        function abrirModalAtrasados() {
            const cont = document.getElementById('lista-atrasados');
            const lista = clientesActivos.filter(rc => rc.atrasado);

            cont.innerHTML = lista.map(rc => `
            <div class="fila-finalizado" style="cursor:pointer;" onclick="cerrarModal('modal-atrasados'); abrirModalAccionesCliente(${rc.id})">
                <div>
                    <div class="nombre">${rc.cliente.nombre}</div>
                    <div class="saldo">${formatoColones(rc.cliente.saldo_actual)}</div>
                </div>
                <span class="badge badge-rojo">Atrasado</span>
            </div>
        `).join('');

            abrirModal('modal-atrasados');
        }
        /* ==================== EMPEZAR DE NUEVO / EXPORTAR ==================== */

        async function empezarDeNuevo() {
            const esAleatoria = tipoActual === 'aleatoria';
            const confirmar = await Swal.fire({
                title: '¿Empezar de nuevo?',
                text: esAleatoria ?
                    'Se borrará por completo la lista de clientes de esta ruta.' :
                    'Todos los clientes vuelven a estado pendiente (la lista y el orden se conservan).',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            });
            if (!confirmar.isConfirmed) return;

            await fetch(`/rutas/${rutaActual.ruta.id}/reiniciar`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Listo',
                showConfirmButton: false,
                timer: 2000
            });
            cargarRuta();
        }

        async function exportarReporte() {
        const resp = await fetch(`/rutas/${rutaActual.ruta.id}/reportes`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
        });
        const datos = await resp.json();

        if (!resp.ok) {
            Swal.fire('No se pudo exportar', datos.mensaje ?? '', 'error');
            return;
        }

        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Reporte guardado, ruta lista para el siguiente ciclo', showConfirmButton: false, timer: 2500 });
        cargarRuta();
    }
    </script>
@endpush
