{{-- resources/views/admin/abonos.blade.php --}}
@extends('layouts.app')

@section('titulo', 'Listado de Abonos/Facturas')

@push('estilos')
    <style>
        .encabezado-abonos {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .encabezado-abonos h1 {
            margin: 0;
            font-size: 22px;
            color: var(--azul-oscuro);
        }

        .resumen {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .chip-resumen {
            background: var(--superficie);
            border: 1px solid var(--borde);
            border-radius: 10px;
            padding: 8px 14px;
            box-shadow: var(--sombra-card);
        }

        .chip-resumen small {
            display: block;
            color: var(--texto-tenue);
            font-size: 11px;
            font-weight: 600;
        }

        .chip-resumen strong {
            font-size: 16px;
            color: var(--texto);
        }

        /* Pestañas Abono / Factura */
        .tabs-vista {
            display: inline-flex;
            gap: 4px;
            padding: 4px;
            border-radius: 10px;
            background: var(--azul-100);
            margin-bottom: 6px;
        }

        .tabs-vista button {
            border: none;
            background: transparent;
            padding: 8px 22px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            color: var(--azul-oscuro);
        }

        .tabs-vista button.activo {
            background: #fff;
            box-shadow: 0 1px 3px rgba(8, 40, 95, .18);
        }

        .ayuda-vista {
            margin: 0 0 14px;
            font-size: 12.5px;
            color: var(--texto-tenue);
        }

        .filtros {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .filtros input,
        .filtros select {
            border: 1px solid var(--borde);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            font-family: inherit;
            background: var(--superficie);
            color: var(--texto);
        }

        .filtros input {
            flex: 1;
            min-width: 200px;
        }

        .filtros input:focus,
        .filtros select:focus {
            outline: 2px solid var(--azul-medio);
            outline-offset: 0;
        }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            font-family: inherit;
        }

        .btn-primario {
            background: var(--azul-medio);
            color: #fff;
        }

        .btn-primario:hover {
            background: var(--azul-600);
        }

        .btn-secundario {
            background: var(--azul-100);
            color: var(--azul-oscuro);
        }

        .btn:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .btn-peligro {
            background: #fdecec;
            color: #a30000;
        }

        .btn-peligro:hover {
            background: #f9d6d6;
        }

        tbody tr[data-id] {
            cursor: pointer;
        }

        tbody tr[data-id]:hover {
            background: var(--azul-50);
        }

        td.num,
        th.num {
            text-align: right;
        }

        .celda-sub {
            display: block;
            font-size: 11.5px;
            color: var(--texto-tenue);
            font-weight: 400;
        }

        .saldo {
            font-weight: 700;
        }

        .saldo.deuda {
            color: #b42318;
        }

        .saldo.favor {
            color: #067647;
        }

        .saldo.cero {
            color: var(--texto-tenue);
        }

        .vacio {
            display: none;
            text-align: center;
            color: var(--texto-tenue);
            padding: 40px 10px;
            background: var(--superficie);
            border-radius: 8px;
            box-shadow: var(--sombra-card);
        }

        .tarjetas {
            display: none;
            gap: 10px;
        }

        .tarjeta-cliente {
            background: var(--superficie);
            border: 1px solid var(--borde);
            border-radius: 12px;
            padding: 12px 14px;
            box-shadow: var(--sombra-card);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .tarjeta-cliente:active {
            background: var(--azul-50);
        }

        .tarjeta-cliente .nombre {
            font-weight: 600;
            font-size: 15px;
        }

        .tarjeta-cliente .meta {
            color: var(--texto-tenue);
            font-size: 12px;
            margin-top: 2px;
        }

        .tarjeta-cliente .derecha {
            text-align: right;
            flex-shrink: 0;
        }

        .tarjeta-cliente .derecha .btn {
            margin-top: 6px;
        }



        /* ===== Modal del cliente ===== */
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
            max-width: 500px;
            max-height: 90dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
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
            font-size: 16px;
        }

        .modal-header p {
            margin: 3px 0 0;
            font-size: 12.5px;
            color: var(--texto-tenue);
        }

        .modal-cerrar {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--texto-tenue);
            font-size: 22px;
            line-height: 1;
            padding: 0 4px;
            flex-shrink: 0;
        }

        .modal-cuerpo {
            padding: 14px 20px 20px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .mh-controles {
            margin-bottom: 10px;
        }

        .segmentos {
            display: flex;
            gap: 4px;
            padding: 3px;
            border-radius: 9px;
            background: var(--azul-100);
        }

        .segmentos button {
            flex: 1;
            border: none;
            background: transparent;
            padding: 7px 10px;
            border-radius: 7px;
            font-size: 12.5px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            color: var(--azul-oscuro);
        }

        .segmentos button.activo {
            background: #fff;
            box-shadow: 0 1px 3px rgba(8, 40, 95, .18);
        }

        .opcion-sin-abono {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            font-size: 12.5px;
            color: var(--texto-tenue);
            cursor: pointer;
        }

        .leyenda {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 12px;
            margin-top: 8px;
            font-size: 11.5px;
            color: var(--texto-tenue);
        }

        .leyenda span::before {
            content: '';
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
            background: var(--borde);
        }

        .leyenda .l-compra::before {
            background: #e8710a;
        }

        .leyenda .l-devolucion::before {
            background: #2563eb;
        }

        .leyenda .l-abono::before {
            background: #067647;
        }

        .conteo {
            font-size: 12px;
            color: var(--texto-tenue);
            margin: 0 0 4px;
        }

        .fila-recibo {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid var(--borde);
        }

        .fila-recibo:last-child {
            border-bottom: none;
        }

        .fila-recibo .fecha {
            font-size: 13.5px;
            font-weight: 600;
        }

        .fila-recibo .sub {
            font-size: 12px;
            color: var(--texto-tenue);
        }

        .fila-recibo .derecha {
            text-align: right;
            flex-shrink: 0;
        }

        .fila-recibo .monto {
            font-weight: 700;
            font-size: 14px;
        }

        .fila-recibo a {
            font-size: 12.5px;
            color: var(--azul-medio);
            text-decoration: none;
        }

        .fila-recibo a:hover {
            text-decoration: underline;
        }

        /* Compras y devoluciones se hacen notar */
        .fila-recibo.compra,
        .fila-recibo.devolucion {
            padding: 10px 10px;
            margin: 5px 0;
            border-radius: 8px;
            border-bottom: none;
        }

        .fila-recibo.compra {
            background: #fff4e5;
            border-left: 4px solid #e8710a;
        }

        .fila-recibo.compra .monto {
            color: #b54708;
        }

        .fila-recibo.devolucion {
            background: #eef4ff;
            border-left: 4px solid #2563eb;
        }

        .fila-recibo.devolucion .monto {
            color: #1d4ed8;
        }

        .fila-recibo.sin-abono {
            opacity: .65;
        }

        .fila-recibo.sin-abono .monto {
            color: var(--texto-tenue);
        }

        .pill {
            display: inline-block;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .02em;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 20px;
            margin-bottom: 3px;
            background: var(--azul-100);
            color: var(--azul-oscuro);
        }

        .compra .pill {
            background: #fde2bd;
            color: #93400a;
        }

        .devolucion .pill {
            background: #cfe0ff;
            color: #1e40af;
        }

        .abono .pill {
            background: #d1f2df;
            color: #05603a;
        }

        .fila-recibo.traspaso {
            background: #f4effd;
            border-left: 4px solid #7c3aed;
            padding: 10px 10px;
            margin: 5px 0;
            border-radius: 8px;
            border-bottom: none;
        }

        .fila-recibo.traspaso .monto {
            color: #6d28d9;
        }

        .traspaso .pill {
            background: #e4d8fb;
            color: #5b21b6;
        }

        .mensaje-modal {
            text-align: center;
            color: var(--texto-tenue);
            padding: 20px 0;
        }

        .mensaje-modal.error {
            color: #a30000;
        }

        @media (max-width: 720px) {
            .tabla-envoltorio {
                display: none;
            }

            .tarjetas {
                display: grid;
            }

            .resumen {
                width: 100%;
            }

            .chip-resumen {
                flex: 1;
            }

            .tabs-vista {
                display: flex;
            }

            .tabs-vista button {
                flex: 1;
            }

            .modal-overlay {
                padding: 0;
                align-items: flex-end;
            }

            .modal-caja {
                max-width: 100%;
                max-height: 92dvh;
                border-radius: 16px 16px 0 0;
            }
        }

        .tabla-scroll {
            max-height: calc(100dvh - 400px);
            min-height: 240px;
        }

        .recibo-acciones {
            margin-top: 6px;
            font-size: 12.5px;
        }

        .recibo-acciones .btn-link {
            background: none;
            border: none;
            padding: 0;
            font: inherit;
            cursor: pointer;
            color: var(--azul-medio);
        }

        .recibo-acciones .btn-link:hover {
            text-decoration: underline;
        }

        .recibo-acciones .sub {
            font-size: 11.5px;
            color: var(--texto-tenue);
            margin-top: 2px;
        }
    </style>
@endpush

@section('contenido')
    <div class="encabezado-abonos">
        <h1>Listado de Abonos/Facturas</h1>
        <div class="resumen">
            <div class="chip-resumen"><small>Clientes activos</small><strong id="res-clientes">—</strong></div>
            <div class="chip-resumen"><small>Total por cobrar</small><strong id="res-total">—</strong></div>
        </div>
    </div>

    <div class="tabs-vista">
        <button type="button" class="activo" data-vista="abono">Abono</button>
        <button type="button" data-vista="factura">Factura</button>
    </div>
    <p class="ayuda-vista" id="ayuda-vista"></p>

    <div class="filtros">
        <input type="search" id="buscador" placeholder="Buscar por nombre, código o teléfono" autocomplete="off">
        <select id="filtro">
            <option value="todos" selected>Todos</option>
            <option value="activos">Activos</option>
            <option value="cancelados">Cancelados</option>
        </select>
    </div>

    <div class="tabla-envoltorio tabla-scroll">
        <table>
            <thead>
                <tr id="tabla-encabezado"></tr>
            </thead>
            <tbody id="tabla-cuerpo"></tbody>
        </table>
    </div>

    <div class="tarjetas" id="tarjetas"></div>

    <div class="vacio" id="vacio">No hay clientes que coincidan.</div>

    <div class="paginacion" id="paginacion" style="display:none;"></div>

    {{-- Modal del cliente: historial (pestaña Abono) o facturas (pestaña Factura) --}}
    <div class="modal-overlay" id="modal-historial">
        <div class="modal-caja">
            <div class="modal-header">
                <div>
                    <h2 id="mh-nombre"></h2>
                    <p id="mh-meta"></p>
                </div>
                <button type="button" class="modal-cerrar" onclick="cerrarModalHistorial()"
                    aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-cuerpo">
                <div class="mh-controles" id="mh-controles">
                    <div class="segmentos">
                        <button type="button" class="activo" data-modo="linea">Línea de tiempo</button>
                        <button type="button" data-modo="abonos">Solo abonos</button>
                    </div>
                    <label class="opcion-sin-abono">
                        <input type="checkbox" id="chk-sin-abono"> Mostrar visitas sin abono
                    </label>
                    <div class="leyenda" id="mh-leyenda">
                        <span class="l-compra">Compra: suma a la cuenta</span>
                        <span class="l-devolucion">Devolución: resta</span>
                        <span class="l-abono">Abono: resta</span>
                    </div>
                </div>
                <p class="conteo" id="mh-conteo"></p>
                <div id="mh-lista"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const URL_ABONOS = @json(route('abonos.index'));
        const URL_FACTURACION = @json(route('facturacion.index'));
        const URL_HISTORIAL = @json(route('abonos.historial', ['cliente' => '__ID__']));
        const URL_FACTURAS = @json(route('abonos.facturas', ['cliente' => '__ID__']));
        const URL_COMPROBANTE = @json(route('operaciones.comprobante', ['operacion' => '__ID__']));
        const URL_RECIBO_HISTORICO = @json(route('abonos.recibo-historico', ['abono' => '__ID__']));
        const URL_FACTURA_HISTORICA = @json(route('abonos.factura-historica', ['factura' => '__ID__']));
        const URL_ANULAR = @json(route('facturas.anular', ['factura' => '__ID__']));
        const ES_SUPERADMIN = @json(auth()->user()?->es_superadmin ?? false);
        const URL_RECIBO_PDF = @json(route('operaciones.recibo.pdf', ['operacion' => '__ID__']));
        const URL_RECIBO_REENVIAR = @json(route('operaciones.recibo.reenviar', ['operacion' => '__ID__']));
        const URL_RECIBO_ABONO_PDF = @json(route('abonos.recibo.pdf', ['abono' => '__ID__']));
        const URL_RECIBO_ABONO_REENVIAR = @json(route('abonos.recibo.reenviar', ['abono' => '__ID__']));
        const URL_RECIBO_FACTURA_PDF = @json(route('abonos.facturas.recibo.pdf', ['factura' => '__ID__']));
        const URL_RECIBO_FACTURA_REENVIAR = @json(route('abonos.facturas.recibo.reenviar', ['factura' => '__ID__']));
        const RECIBO_URLS = {
            operacion: { pdf: URL_RECIBO_PDF, reenviar: URL_RECIBO_REENVIAR },
            abono: { pdf: URL_RECIBO_ABONO_PDF, reenviar: URL_RECIBO_ABONO_REENVIAR },
            factura: { pdf: URL_RECIBO_FACTURA_PDF, reenviar: URL_RECIBO_FACTURA_REENVIAR },
        };
        const ESTADO_RECIBO = {
            enviado: 'Enviado',
            fallido: 'Falló el envío',
            invalido: 'Teléfono inválido',
            sin_telefono: 'Sin teléfono',
        };
        const token = document.querySelector('meta[name="csrf-token"]').content;

        const POR_BLOQUE = 30;

        const AYUDA = {
            abono: 'Tocá un cliente para ver su historial: compras, devoluciones y abonos en orden.',
            factura: 'Tocá un cliente para ver sus facturas (compras) e imprimir cada una.',
        };

        let pagina = 1;
        let temporizador = null;
        let clientesActuales = [];
        let datosActuales = null; // última respuesta de la lista, para repintar al cambiar de pestaña

        let vista = 'abono'; // 'abono' | 'factura'
        let modoModal = 'linea'; // 'linea' | 'abonos'
        let mostrarSinAbono = false;
        let historialItems = [];
        let limiteFilas = POR_BLOQUE;
        let ticket = 0; // descarta respuestas viejas si se abre otro cliente
        let clienteAbierto = null; // cliente actualmente abierto en el modal, para poder refrescarlo

        const $ = id => document.getElementById(id);
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        } [c]));
        const colones = v => '₡' + Number(v).toLocaleString('es-CR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        const plural = (n, uno, varios) => `${n} ${n === 1 ? uno : varios}`;

        function saldoHtml(v) {
            const n = Number(v);
            if (n < 0) return `<span class="saldo favor">${colones(Math.abs(n))} a favor</span>`;
            if (n === 0) return `<span class="saldo cero">${colones(0)}</span>`;
            return `<span class="saldo deuda">${colones(n)}</span>`;
        }

        function saldoTexto(v) {
            const n = Number(v);
            return n < 0 ? `${colones(Math.abs(n))} a favor` : colones(n);
        }

        function irAFacturar(clienteId) {
            window.location.href = `${URL_FACTURACION}?cliente_id=${clienteId}&operacion=abonar`;
        }

        // ===== Lista de clientes =====

        async function cargar() {
            const params = new URLSearchParams({
                page: pagina,
                filtro: $('filtro').value,
                busqueda: $('buscador').value.trim(),
            });

            try {
                const r = await fetch(`${URL_ABONOS}?${params}`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!r.ok) throw new Error('http ' + r.status);
                datosActuales = await r.json();
                pintar();
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo cargar',
                    text: 'Intentá de nuevo.'
                });
            }
        }

        // Columnas propias de cada pestaña
        function columnasVista(c) {
            if (vista === 'factura') {
                return {
                    col1: c.ultima_factura ? esc(c.ultima_factura) : '—',
                    col2: `${colones(c.facturas_total)}<span class="celda-sub">${plural(c.facturas_n, 'factura', 'facturas')}</span>`,
                    movil: `Última factura: ${c.ultima_factura || '—'} · ${plural(c.facturas_n, 'factura', 'facturas')} (${colones(c.facturas_total)})`,
                };
            }
            return {
                col1: c.ultimo_abono ? esc(c.ultimo_abono) : '—',
                col2: `${colones(c.abonos_total)}<span class="celda-sub">${plural(c.abonos_n, 'abono', 'abonos')}</span>`,
                movil: `Último abono: ${c.ultimo_abono || '—'} · ${plural(c.abonos_n, 'abono', 'abonos')} (${colones(c.abonos_total)})`,
            };
        }

        function pintar() {
            const d = datosActuales;
            if (!d) return;

            $('res-clientes').textContent = d.resumen.clientes_activos;
            $('res-total').textContent = colones(d.resumen.total_por_cobrar);

            $('tabla-encabezado').innerHTML = `
            <th>Código</th>
            <th>Cliente</th>
            <th>Teléfono</th>
            <th class="num">Saldo</th>
            <th>${vista === 'factura' ? 'Última factura' : 'Último abono'}</th>
            <th class="num">${vista === 'factura' ? 'Total comprado' : 'Total abonado'}</th>
            <th></th>`;

            const lista = d.clientes.data;
            clientesActuales = lista;
            $('vacio').style.display = lista.length ? 'none' : 'block';

            $('tabla-cuerpo').innerHTML = lista.map(c => {
                const x = columnasVista(c);
                return `
            <tr data-id="${c.id}">
                <td>${esc(c.codigo || '—')}</td>
                <td>${esc(c.nombre)}</td>
                <td>${esc(c.telefono || '—')}</td>
                <td class="num">${saldoHtml(c.saldo_actual)}</td>
                <td>${x.col1}</td>
                <td class="num">${x.col2}</td>
                <td class="num"><button type="button" class="btn btn-primario" data-abonar>Abonar</button></td>
            </tr>`;
            }).join('');

            $('tarjetas').innerHTML = lista.map(c => {
                const x = columnasVista(c);
                return `
            <div class="tarjeta-cliente" data-id="${c.id}">
                <div>
                    <div class="nombre">${esc(c.nombre)}</div>
                    <div class="meta">${esc(c.codigo || 'Sin código')} · ${esc(c.telefono || 'Sin teléfono')}</div>
                    <div class="meta">${esc(x.movil)}</div>
                </div>
                <div class="derecha">
                    ${saldoHtml(c.saldo_actual)}<br>
                    <button type="button" class="btn btn-primario" data-abonar>Abonar</button>
                </div>
            </div>`;
            }).join('');

            pintarPaginador('paginacion', d.clientes, 'clientes', p => {
                pagina = p;
                cargar();
                irArribaPagina();
            });
        }

        // Clic en "Abonar" → Facturación. Clic en el resto de la fila → modal según la pestaña.
        ['tabla-cuerpo', 'tarjetas'].forEach(id => {
            $(id).addEventListener('click', e => {
                const fila = e.target.closest('[data-id]');
                if (!fila) return;

                if (e.target.closest('[data-abonar]')) {
                    irAFacturar(fila.dataset.id);
                    return;
                }

                const cliente = clientesActuales.find(c => String(c.id) === fila.dataset.id);
                if (cliente) abrirCliente(cliente);
            });
        });

        // ===== Pestañas Abono / Factura =====

        document.querySelectorAll('[data-vista]').forEach(boton => {
            boton.addEventListener('click', () => {
                vista = boton.dataset.vista;
                document.querySelectorAll('[data-vista]').forEach(b => b.classList.toggle('activo', b ===
                    boton));
                $('ayuda-vista').textContent = AYUDA[vista];
                pintar();
            });
        });
        $('ayuda-vista').textContent = AYUDA[vista];

        // ===== Modal =====

        function abrirModal() {
            $('modal-historial').classList.add('abierto');
        }

        function cerrarModalHistorial() {
            ticket++;
            $('modal-historial').classList.remove('abierto');
            clienteAbierto = null;
        }

        $('modal-historial').addEventListener('click', e => {
            if (e.target === $('modal-historial')) cerrarModalHistorial();
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') cerrarModalHistorial();
        });

        function abrirCliente(cliente) {
            clienteAbierto = cliente;
            $('mh-nombre').textContent = cliente.nombre;
            $('mh-meta').textContent = `${cliente.codigo || 'sin código'} · ${cliente.telefono || 'sin teléfono'}`;
            $('mh-controles').style.display = vista === 'abono' ? 'block' : 'none';
            $('mh-conteo').textContent = '';
            $('mh-lista').innerHTML = '<p class="mensaje-modal">Cargando...</p>';
            abrirModal();

            if (vista === 'factura') cargarFacturas(cliente);
            else cargarHistorial(cliente);
        }

        // ----- Pestaña Abono: línea de tiempo -----

        async function cargarHistorial(cliente) {
            const mio = ++ticket;
            historialItems = [];
            limiteFilas = POR_BLOQUE;

            try {
                const r = await fetch(URL_HISTORIAL.replace('__ID__', cliente.id), {
                    headers: {
                        Accept: 'application/json'
                    }
                });
                if (!r.ok) throw new Error('http ' + r.status);
                const data = await r.json();
                if (mio !== ticket) return;
                historialItems = data;
                pintarHistorial();
            } catch (e) {
                if (mio !== ticket) return;
                $('mh-lista').innerHTML = '<p class="mensaje-modal error">No se pudo cargar el historial.</p>';
            }
        }

        function itemsVisibles() {
            let items = historialItems;
            if (modoModal === 'abonos') items = items.filter(i => (i.tipo === 'abono' || i.tipo === 'sin_abono') && !i
                .es_traspaso);
            if (!mostrarSinAbono) items = items.filter(i => i.tipo !== 'sin_abono');
            return items;
        }

        function pintarHistorial() {
            const items = itemsVisibles();
            const visibles = items.slice(0, limiteFilas);

            $('mh-leyenda').style.display = modoModal === 'linea' ? 'flex' : 'none';
            $('mh-conteo').textContent = items.length ?
                `Mostrando ${visibles.length} de ${items.length} movimientos` :
                '';

            if (!items.length) {
                $('mh-lista').innerHTML = `<p class="mensaje-modal">${
                modoModal === 'abonos'
                    ? 'Este cliente no tiene abonos registrados.'
                    : 'Este cliente no tiene movimientos para mostrar.'
            }</p>`;
                return;
            }

            let html = visibles.map(filaHistorial).join('');
            if (items.length > limiteFilas) {
                html += `<div style="text-align:center; padding:12px 0;">
                <button type="button" class="btn btn-secundario" id="mh-mas">Ver más (${items.length - limiteFilas} restantes)</button>
            </div>`;
            }
            $('mh-lista').innerHTML = html;
            if ($('mh-mas')) $('mh-mas').onclick = () => {
                limiteFilas += POR_BLOQUE;
                pintarHistorial();
            };
        }

        function enlaceHistorial(h) {
            if (h.tipo === 'compra') {
                return h.operacion_id ?
                    `<a href="${URL_COMPROBANTE.replace('__ID__', h.operacion_id)}" target="_blank">Ver / imprimir</a>` :
                    `<a href="${URL_FACTURA_HISTORICA.replace('__ID__', h.factura_id)}" target="_blank">Ver / imprimir</a>`;
            }
            if (h.tipo === 'devolucion') {
                return h.operacion_id ?
                    `<a href="${URL_COMPROBANTE.replace('__ID__', h.operacion_id)}" target="_blank">Ver / imprimir</a>` :
                    '';
            }
            // abono y visita sin abono
            if (h.operacion_id) {
                return `<a href="${URL_COMPROBANTE.replace('__ID__', h.operacion_id)}" target="_blank">Ver / imprimir</a>`;
            }
            return h.abono_id ?
                `<a href="${URL_RECIBO_HISTORICO.replace('__ID__', h.abono_id)}" target="_blank">Ver recibo histórico</a>` :
                '';
        }

        function filaHistorial(h) {
            const clase = ({
                compra: 'compra',
                devolucion: 'devolucion',
                abono: 'abono',
                sin_abono: 'sin-abono'
            } [h.tipo] || '') + (h.es_traspaso ? ' traspaso' : '');

            let monto;
            if (h.tipo === 'compra') monto = h.efecto > 0 ? `+ ${colones(h.efecto)}` : colones(0);
            else if (h.tipo === 'devolucion') monto = `− ${colones(Math.abs(h.efecto))}`;
            else if (h.tipo === 'abono') monto = `− ${colones(h.monto)}`;
            else if (h.tipo === 'traspaso') monto = h.efecto > 0 ? `+ ${colones(h.efecto)}` : `− ${colones(Math.abs(h.efecto))}`;
            else monto = colones(0);

            let sub = '';
            if (h.es_traspaso) {
                sub = h.detalle ? esc(h.detalle) : '';
            } else if (h.tipo === 'abono') {
                const desglose = (Number(h.efectivo) + Number(h.sinpe) + 0.005) < Number(h.monto) ?
                    'Sin desglose de pago' :
                    `Efectivo: ${colones(h.efectivo)} · Sinpe: ${colones(h.sinpe)}`;
                sub = desglose + (h.detalle ? '<br>' + esc(h.detalle) : '');
            } else if (h.tipo === 'compra') {
                sub = `Total de la factura: ${colones(h.monto)}${h.detalle ? ' · ' + esc(h.detalle) : ''}`;
            } else if (h.detalle) {
                sub = esc(h.detalle);
            }

            const saldo = (h.saldo !== null && h.saldo !== undefined) ?
                `<div class="sub">Saldo después: ${saldoTexto(h.saldo)}</div>` : '';

            return `
            <div class="fila-recibo ${clase}">
                <div>
                    <span class="pill">${esc(h.etiqueta)}</span>
                    <div class="fecha">${esc(h.fecha ?? '—')}</div>
                    ${sub ? `<div class="sub">${sub}</div>` : ''}
                    ${saldo}
                </div>
                <div class="derecha">
                    <div class="monto">${monto}</div>
                    ${enlaceHistorial(h)}
                    ${accionesRecibo(h)}
                </div>
            </div>`;
        }

               // A qué se le cuelga el reenvío: operación (nuevas), abono migrado o factura migrada.
        // Visitas sin abono y devoluciones migradas no se reenvían.
        function referenciaRecibo(h) {
            if (h.operacion_id) return ['operacion', h.operacion_id];
            if (h.tipo === 'abono' && h.abono_id) return ['abono', h.abono_id];
            if (h.factura_id) return ['factura', h.factura_id];
            return null;
        }

        function accionesRecibo(h) {
            const ref = referenciaRecibo(h);
            if (!ref) return '';
            const [tipo, id] = ref;
            const r = h.recibo;
            const ver = r && r.pdf ?
                `<a href="${RECIBO_URLS[tipo].pdf.replace('__ID__', id)}" target="_blank">Ver PDF enviado</a> · ` :
                '';
            const estado = r ?
                `WhatsApp: ${ESTADO_RECIBO[r.estado] ?? r.estado}${r.fecha ? ' · ' + esc(r.fecha) : ''}` :
                'WhatsApp: sin envío registrado';
            return `<div class="recibo-acciones">${ver}<button type="button" class="btn-link" onclick="reenviarRecibo('${tipo}', ${id})">Reenviar</button><div class="sub">${estado}</div></div>`;
        }
         async function reenviarRecibo(tipo, id) {
            const c = await Swal.fire({
                title: '¿Reenviar el recibo?',
                text: 'Se le manda de nuevo el mismo PDF por WhatsApp al cliente.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, reenviar',
                cancelButtonText: 'Cancelar',
            });
            if (!c.isConfirmed) return;

            Swal.fire({
                title: 'Enviando...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                    const r = await fetch(RECIBO_URLS[tipo].reenviar.replace('__ID__', id), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                });
                const datos = await r.json();

                if (datos.recibo === 'enviado') {
                    Swal.fire('Enviado', 'El recibo salió por WhatsApp.', 'success');
                } else if (datos.recibo === 'invalido' || datos.recibo === 'sin_telefono') {
                    Swal.fire('No se envió', 'El cliente no tiene un teléfono válido para WhatsApp.', 'warning');
                } else {
                    Swal.fire('Falló el envío', 'Intentá de nuevo. Si sigue fallando, avisá.', 'error');
                }

                if (clienteAbierto) {
                    if (vista === 'factura') cargarFacturas(clienteAbierto);
                    else cargarHistorial(clienteAbierto);
                }
            } catch (e) {
                Swal.fire('Error de conexión', 'Intentá de nuevo.', 'error');
            }
        }

        document.querySelectorAll('[data-modo]').forEach(boton => {
            boton.addEventListener('click', () => {
                modoModal = boton.dataset.modo;
                limiteFilas = POR_BLOQUE;
                document.querySelectorAll('[data-modo]').forEach(b => b.classList.toggle('activo', b ===
                    boton));
                pintarHistorial();
            });
        });

        $('chk-sin-abono').addEventListener('change', e => {
            mostrarSinAbono = e.target.checked;
            limiteFilas = POR_BLOQUE;
            pintarHistorial();
        });

        // ----- Pestaña Factura -----

        async function cargarFacturas(cliente) {
            const mio = ++ticket;

            try {
                const r = await fetch(URL_FACTURAS.replace('__ID__', cliente.id), {
                    headers: {
                        Accept: 'application/json'
                    }
                });
                if (!r.ok) throw new Error('http ' + r.status);
                const lista = await r.json();
                if (mio !== ticket) return;

                $('mh-conteo').textContent = lista.length ? plural(lista.length, 'factura', 'facturas') : '';
                $('mh-lista').innerHTML = lista.length ?
                    lista.map(filaFactura).join('') :
                    '<p class="mensaje-modal">Este cliente no tiene facturas registradas.</p>';
            } catch (e) {
                if (mio !== ticket) return;
                $('mh-lista').innerHTML = '<p class="mensaje-modal error">No se pudieron cargar las facturas.</p>';
            }
        }

        function filaFactura(f) {
            const enlace = f.operacion_id ?
                URL_COMPROBANTE.replace('__ID__', f.operacion_id) :
                URL_FACTURA_HISTORICA.replace('__ID__', f.factura_id);

            const puedeAnular = ES_SUPERADMIN && f.estado !== 'anulada' && !f.es_traspaso;

            return `
            <div class="fila-recibo ${f.estado === 'anulada' ? 'sin-abono' : ''}${f.es_traspaso ? ' traspaso' : ''}">
                <div>
                    <span class="pill">${esc(f.etiqueta)}</span>
                    <div class="fecha">${esc(f.fecha ?? '—')}</div>
                    <div class="sub">N° ${esc(f.numero ?? '—')}${f.detalle ? ' · ' + esc(f.detalle) : ''}</div>
                </div>
                <div class="derecha">
                    <div class="monto">${colones(f.total)}</div>
                    <a href="${enlace}" target="_blank">Ver / imprimir</a>
                    ${accionesRecibo(f)}
                    ${puedeAnular ? `<br><button type="button" class="btn btn-peligro" style="margin-top:6px;" onclick="anularFactura(${f.factura_id})">Anular</button>` : ''}
                </div>
            </div>`;
        }

        // ----- Anular factura (ítem 7) -----

        async function anularFactura(facturaId) {
            const {
                value: confirmado,
                isConfirmed
            } = await Swal.fire({
                title: '¿Anular esta factura?',
                html: 'Se le va a quitar la deuda o el efecto al cliente y, si movió stock, se devuelve. Solo funciona con facturas recién hechas (sin abonos ni devoluciones).',
                input: 'text',
                inputLabel: 'Motivo (opcional)',
                inputPlaceholder: 'Ej: se digitó mal el producto',
                showCancelButton: true,
                confirmButtonText: 'Anular factura',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#a30000',
            });

            if (!isConfirmed) return; // canceló

            try {
                const r = await fetch(URL_ANULAR.replace('__ID__', facturaId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        motivo: confirmado || null
                    }),
                });
                const datos = await r.json();

                if (!r.ok) {
                    Swal.fire('No se pudo anular', datos.mensaje ?? 'Ocurrió un error.', 'error');
                    return;
                }

                Swal.fire('Factura anulada', '', 'success');

                // Refrescar el modal (si sigue abierto en el mismo cliente) y la lista/saldos
                if (clienteAbierto) cargarFacturas(clienteAbierto);
                cargar();
            } catch (e) {
                Swal.fire('Error de conexión', 'Intentá de nuevo.', 'error');
            }
        }

        // ===== Buscador y filtro =====

        $('buscador').addEventListener('input', () => {
            clearTimeout(temporizador);
            temporizador = setTimeout(() => {
                pagina = 1;
                cargar();
            }, 300);
        });
        $('filtro').addEventListener('change', () => {
            pagina = 1;
            cargar();
        });


        cargar();
    </script>
@endpush
