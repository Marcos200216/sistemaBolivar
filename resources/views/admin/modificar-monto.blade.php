{{-- resources/views/admin/modificar-monto.blade.php --}}
@extends('layouts.app')

@section('titulo', 'Modificar monto')

@push('estilos')
    <style>
        .mm-sub {
            margin: 4px 0 18px;
            color: var(--texto-tenue);
            font-size: 14px;
        }

        /* ===== Filtros ===== */
        .mm-filtros {
            display: grid;
            grid-template-columns: 240px minmax(0, 1fr);
            gap: 14px;
            margin-bottom: 18px;
            align-items: start;
        }

        .mm-campo {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .mm-campo label {
            font-size: 13px;
            font-weight: 600;
            color: var(--texto-tenue);
        }

        .mm-campo input,
        .mm-campo select {
            height: 42px;
            padding: 0 12px;
            border: 1px solid var(--borde);
            border-radius: 10px;
            background: #fff;
            font-family: inherit;
            font-size: 14px;
            color: var(--texto);
            width: 100%;
        }

        .mm-resultados {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 4px;
            max-height: 320px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid var(--borde);
            border-radius: 10px;
            box-shadow: var(--sombra-hover);
            z-index: 15;
        }

        .mm-res {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 2px 12px;
            width: 100%;
            padding: 10px 12px;
            border: none;
            border-bottom: 1px solid var(--borde);
            background: #fff;
            text-align: left;
            font-family: inherit;
            cursor: pointer;
        }

        .mm-res:last-child {
            border-bottom: none;
        }

        .mm-res:hover {
            background: var(--azul-50);
        }

        .mm-res-nombre {
            font-size: 14px;
            font-weight: 600;
            color: var(--texto);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .mm-res-sub {
            grid-column: 1;
            font-size: 12px;
            color: var(--texto-tenue);
        }

        .mm-res-saldo {
            grid-column: 2;
            grid-row: 1 / span 2;
            align-self: center;
            font-size: 13px;
            font-weight: 600;
            color: var(--texto-tenue);
        }

        .mm-res-saldo.debe {
            color: #b42318;
        }

        .mm-vacio {
            padding: 14px;
            color: var(--texto-tenue);
            font-size: 14px;
        }

        /* ===== Tarjetas ===== */
        .mm-card {
            background: var(--superficie);
            border: 1px solid var(--borde);
            border-radius: 12px;
            box-shadow: var(--sombra-card);
            padding: 18px;
            margin-bottom: 16px;
        }

        .mm-card h2 {
            margin: 0 0 4px;
            font-size: 16px;
            color: var(--azul-oscuro);
        }

        .mm-ayuda {
            margin: 4px 0 12px;
            font-size: 13px;
            color: var(--texto-tenue);
        }

        .mm-cliente {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .mm-cliente-nombre {
            font-size: 18px;
            font-weight: 700;
            color: var(--texto);
        }

        .mm-cliente-sub {
            font-size: 13px;
            color: var(--texto-tenue);
            margin-top: 2px;
        }

        .mm-saldo-caja {
            text-align: right;
        }

        .mm-saldo-etq {
            font-size: 12px;
            color: var(--texto-tenue);
            font-weight: 600;
        }

        .mm-saldo-valor {
            font-size: 24px;
            font-weight: 700;
            color: var(--azul-oscuro);
        }

        .mm-saldo-valor.debe {
            color: #b42318;
        }

        .mm-aviso {
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 13px;
            background: #fff4e5;
            color: #8a4b00;
            flex-basis: 100%;
        }

        .mm-aviso.rojo {
            background: #ffecec;
            color: #a30000;
        }

        /* ===== Filas de formulario y botones ===== */
        .mm-fila {
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .mm-fila .mm-campo {
            width: 200px;
        }

        .mm-btn {
            height: 42px;
            padding: 0 16px;
            border: none;
            border-radius: 10px;
            background: var(--azul-medio);
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .mm-btn:hover {
            background: var(--azul-600);
        }

        .mm-btn.mm-peligro {
            background: #fff;
            color: #b42318;
            border: 1px solid #f0b4ae;
        }

        .mm-btn.mm-peligro:hover {
            background: #ffecec;
        }

        .mm-btn.mm-claro {
            background: #fff;
            color: var(--azul-oscuro);
            border: 1px solid var(--borde);
        }

        .mm-btn.mm-claro:hover {
            background: var(--azul-50);
        }

        /* ===== Vista previa del reparto ===== */
        #mm-previa {
            margin-top: 14px;
        }

        .mm-tabla-caja {
            overflow-x: auto;
            border: 1px solid var(--borde);
            border-radius: 10px;
        }

        .mm-tabla {
            min-width: 0;
        }

        .mm-tabla td.num,
        .mm-tabla th.num {
            text-align: right;
        }

        .mm-previa-pie {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 12px;
            font-size: 14px;
        }

        /* ===== Facturas desplegables ===== */
        .mm-titulo-fila {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .mm-titulo-fila h2 {
            margin: 0;
        }

        .mm-fac {
            border: 1px solid var(--borde);
            border-radius: 10px;
            margin-bottom: 8px;
            background: #fff;
        }

        .mm-fac summary {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            padding: 12px 14px;
            cursor: pointer;
            list-style: none;
        }

        .mm-fac summary::-webkit-details-marker {
            display: none;
        }

        .mm-fac summary::before {
            content: '▸';
            color: var(--texto-tenue);
            transition: transform .15s;
        }

        .mm-fac[open] summary::before {
            transform: rotate(90deg);
        }

        .mm-fac-num {
            font-weight: 600;
            font-size: 14px;
        }

        .mm-fac-fecha {
            font-size: 13px;
            color: var(--texto-tenue);
        }

        .mm-fac-pend {
            margin-left: auto;
            font-weight: 700;
            color: #b42318;
        }

        .mm-chip {
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 999px;
            background: var(--azul-100);
            color: var(--azul-oscuro);
        }

        .mm-fac-cuerpo {
            padding: 4px 14px 14px;
            border-top: 1px solid var(--borde);
        }

        .mm-dato {
            margin: 12px 0;
            font-size: 14px;
            color: var(--texto-tenue);
        }

        .mm-dato strong {
            color: var(--texto);
        }

        .mm-cargando,
        .mm-hint {
            padding: 24px 4px;
            color: var(--texto-tenue);
            font-size: 14px;
        }

        @media (max-width: 760px) {
            .mm-filtros {
                grid-template-columns: 1fr;
            }

            .mm-saldo-caja {
                text-align: left;
            }

            .mm-fila .mm-campo {
                width: 100%;
            }

            .mm-fila .mm-btn {
                width: 100%;
            }

            .mm-fac-pend {
                margin-left: 0;
                flex-basis: 100%;
            }
        }
    </style>
@endpush

@section('contenido')
    <h1>Modificar monto</h1>
    <p class="mm-sub">Elegí una sucursal (opcional) y buscá al cliente. Se muestran solo sus facturas pendientes.</p>

    <div class="mm-filtros">
        <div class="mm-campo">
            <label for="mm-sucursal">Sucursal</label>
            <select id="mm-sucursal">
                <option value="">Todas las sucursales</option>
                @foreach ($sucursales as $s)
                    <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="mm-campo mm-campo-cliente">
            <label for="mm-buscar">Cliente</label>
            <input type="search" id="mm-buscar" placeholder="Buscar por nombre, código o teléfono" autocomplete="off">
            <div class="mm-resultados" id="mm-resultados" hidden></div>
        </div>
    </div>

    <div id="mm-panel">
        <p class="mm-hint">Todavía no elegiste ningún cliente.</p>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const BASE = @json(url('/modificar-monto'));
            const CSRF = document.querySelector('meta[name="csrf-token"]').content;

            const $suc = document.getElementById('mm-sucursal');
            const $buscar = document.getElementById('mm-buscar');
            const $res = document.getElementById('mm-resultados');
            const $panel = document.getElementById('mm-panel');

            // detalle: respuesta de /clientes/{id}; previa: monto ya simulado y listo para aplicar
            const estado = {
                detalle: null,
                previa: null,
                timer: null,
                token: 0
            };

            // ---------- Utilidades ----------
            const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
            const fmt = (n) => '₡' + Number(n || 0).toLocaleString('es-CR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            const redondear = (n) => Math.round(n * 100) / 100;
            const avisar = (msg) => Swal.fire({
                icon: 'warning',
                text: msg,
                confirmButtonText: 'Entendido'
            });

            async function api(url, metodo = 'GET', datos = null) {
                const opts = {
                    method: metodo,
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': CSRF
                    }
                };
                if (datos) {
                    opts.headers['Content-Type'] = 'application/json';
                    opts.body = JSON.stringify(datos);
                }
                const r = await fetch(url, opts);
                const j = await r.json().catch(() => ({}));
                if (!r.ok) {
                    const primerError = j.errors ? Object.values(j.errors).flat()[0] : null;
                    throw new Error(j.mensaje || primerError || 'Ocurrió un error inesperado.');
                }
                return j;
            }

            // Pide el motivo (obligatorio). Devuelve el texto, o null si cancelan.
            async function pedirMotivo(titulo, detalleHtml) {
                const r = await Swal.fire({
                    title: titulo,
                    html: detalleHtml,
                    input: 'textarea',
                    inputLabel: 'Motivo (obligatorio)',
                    inputAttributes: {
                        maxlength: 500
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Confirmar',
                    cancelButtonText: 'Cancelar',
                    inputValidator: (v) => (!v || v.trim().length < 3) ? 'Escribí el motivo (mínimo 3 letras).' : null,
                });
                return r.isConfirmed ? r.value.trim() : null;
            }

            // ---------- Buscador de clientes ----------
            async function buscar() {
                const mi = ++estado.token;
                const params = new URLSearchParams({
                    q: $buscar.value.trim(),
                    sucursal: $suc.value
                });
                try {
                    const lista = await api(`${BASE}/clientes?${params}`);
                    if (mi !== estado.token) return;
                    pintarResultados(lista);
                } catch (e) {
                    $res.innerHTML = `<div class="mm-vacio">${esc(e.message)}</div>`;
                    $res.hidden = false;
                }
            }

            function pintarResultados(lista) {
                if (!lista.length) {
                    $res.innerHTML = '<div class="mm-vacio">No se encontraron clientes.</div>';
                } else {
                    $res.innerHTML = lista.map((c) => `
                        <button type="button" class="mm-res" data-id="${c.id}">
                            <span class="mm-res-nombre">${esc(c.nombre)}</span>
                            <span class="mm-res-sub">${esc(c.codigo || 'sin código')} · ${esc(c.sucursal)}</span>
                            <span class="mm-res-saldo ${c.saldo > 0 ? 'debe' : ''}">${fmt(c.saldo)}</span>
                        </button>`).join('');
                }
                $res.hidden = false;
            }

            $buscar.addEventListener('input', () => {
                clearTimeout(estado.timer);
                estado.timer = setTimeout(buscar, 250);
            });
            $buscar.addEventListener('focus', buscar);

            $res.addEventListener('click', (e) => {
                const b = e.target.closest('.mm-res');
                if (!b) return;
                $res.hidden = true;
                $buscar.value = b.querySelector('.mm-res-nombre').textContent;
                cargarCliente(Number(b.dataset.id));
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.mm-campo-cliente')) $res.hidden = true;
            });

            $suc.addEventListener('change', () => {
                $buscar.value = '';
                estado.detalle = null;
                estado.previa = null;
                $panel.innerHTML = '<p class="mm-hint">Todavía no elegiste ningún cliente.</p>';
                buscar();
            });

            // ---------- Panel del cliente ----------
            async function cargarCliente(id) {
                estado.previa = null;
                $panel.innerHTML = '<div class="mm-cargando">Cargando…</div>';
                try {
                    estado.detalle = await api(`${BASE}/clientes/${id}`);
                    pintarPanel();
                } catch (e) {
                    $panel.innerHTML = '';
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo cargar el cliente',
                        text: e.message
                    });
                }
            }

            function pintarPanel() {
                const d = estado.detalle;
                const c = d.cliente;
                const n = d.facturas.length;

                let html = `
                    <section class="mm-card mm-cliente">
                        <div>
                            <div class="mm-cliente-nombre">${esc(c.nombre)}</div>
                            <div class="mm-cliente-sub">${esc(c.codigo || 'sin código')} · ${esc(c.sucursal || '')}</div>
                        </div>
                        <div class="mm-saldo-caja">
                            <div class="mm-saldo-etq">Saldo total</div>
                            <div class="mm-saldo-valor ${c.saldo > 0 ? 'debe' : ''}">${fmt(c.saldo)}</div>
                        </div>
                        ${d.descuadrado ? `<div class="mm-aviso rojo">El saldo del cliente no coincide con la suma de sus facturas. Los ajustes se bloquean hasta revisarlo.</div>` : ''}
                        ${d.deuda_sin_factura > 0 ? `<div class="mm-aviso">Tiene ${fmt(d.deuda_sin_factura)} de deuda sin factura asociada (se rebaja primero al bajar el total).</div>` : ''}
                    </section>`;

                if (c.saldo > 0) {
                    html += `
                    <section class="mm-card">
                        <h2>Bajar el saldo total</h2>
                        <p class="mm-ayuda">Se rebaja de la factura más vieja a la más nueva. No cuenta como plata cobrada.</p>
                        <div class="mm-fila">
                            <div class="mm-campo">
                                <label for="mm-bajar">Cuánto bajar (₡)</label>
                                <input id="mm-bajar" type="number" min="0.01" step="0.01" inputmode="decimal">
                            </div>
                            <div class="mm-campo">
                                <label for="mm-nuevo">Nuevo saldo (₡)</label>
                                <input id="mm-nuevo" type="number" min="0" step="0.01" inputmode="decimal">
                            </div>
                            <button type="button" class="mm-btn mm-claro" id="mm-ver">Ver reparto</button>
                        </div>
                        <div id="mm-previa"></div>
                    </section>`;
                }

                html += `
                    <section class="mm-card">
                        <div class="mm-titulo-fila">
                            <h2>Facturas pendientes (${n})</h2>
                            ${n ? '<button type="button" class="mm-btn mm-peligro" id="mm-anular-todas">Anular todas</button>' : ''}
                        </div>
                        ${n ? d.facturas.map(facturaHtml).join('') : '<p class="mm-vacio" style="padding:0">Este cliente no tiene facturas pendientes.</p>'}
                    </section>`;

                $panel.innerHTML = html;
            }

            function facturaHtml(f) {
                return `
                    <details class="mm-fac" data-id="${f.id}" data-pend="${f.pendiente}">
                        <summary>
                            <span class="mm-fac-num">Factura N° ${esc(f.numero ?? '—')}</span>
                            <span class="mm-fac-fecha">${esc(f.fecha || '')}</span>
                            ${f.migrada ? '<span class="mm-chip">Sistema anterior</span>' : ''}
                            <span class="mm-fac-pend">${fmt(f.pendiente)}</span>
                        </summary>
                        <div class="mm-fac-cuerpo">
                            <div class="mm-dato">Total de la factura: <strong>${fmt(f.total)}</strong></div>
                            <div class="mm-fila">
                                <div class="mm-campo">
                                    <label>Nuevo pendiente (₡)</label>
                                    <input type="number" class="mm-fac-nuevo" min="0" step="0.01" inputmode="decimal" value="${f.pendiente}">
                                </div>
                                <button type="button" class="mm-btn mm-fac-guardar">Guardar cambio</button>
                                <button type="button" class="mm-btn mm-peligro mm-fac-anular">Anular factura</button>
                            </div>
                            <p class="mm-ayuda">Bajarlo queda como rebaja sin dinero. Subirlo aumenta la factura y el saldo del cliente.</p>
                        </div>
                    </details>`;
            }

            // ---------- Eventos del panel (delegados) ----------
            $panel.addEventListener('click', (e) => {
                const t = e.target;
                if (t.closest('#mm-ver')) return verReparto();
                if (t.closest('#mm-aplicar')) return aplicarBaja();
                if (t.closest('#mm-anular-todas')) return anularTodas();
                const fac = t.closest('.mm-fac');
                if (fac && t.closest('.mm-fac-guardar')) return editarFactura(fac);
                if (fac && t.closest('.mm-fac-anular')) return anularFactura(fac);
            });

            // "Cuánto bajar" y "Nuevo saldo" se mantienen sincronizados
            $panel.addEventListener('input', (e) => {
                if (!estado.detalle) return;
                const saldo = estado.detalle.cliente.saldo;
                const otro = e.target.id === 'mm-bajar' ? 'mm-nuevo' : (e.target.id === 'mm-nuevo' ? 'mm-bajar' : null);
                if (!otro) return;
                const v = parseFloat(e.target.value);
                document.getElementById(otro).value = isNaN(v) ? '' : Math.max(0, redondear(saldo - v));
                limpiarPrevia();
            });

            function limpiarPrevia() {
                estado.previa = null;
                const p = document.getElementById('mm-previa');
                if (p) p.innerHTML = '';
            }

            // ---------- Bajar el saldo total ----------
            async function verReparto() {
                const saldo = estado.detalle.cliente.saldo;
                const monto = parseFloat(document.getElementById('mm-bajar').value);
                if (isNaN(monto) || monto <= 0) return avisar('Escribí cuánto querés bajar.');
                if (monto > saldo) return avisar('No se puede bajar más de lo que debe (' + fmt(saldo) + ').');
                try {
                    const r = await api(`${BASE}/clientes/${estado.detalle.cliente.id}/simular?monto=${encodeURIComponent(monto)}`);
                    estado.previa = {
                        monto
                    };
                    pintarPrevia(r);
                } catch (e) {
                    avisar(e.message);
                }
            }

            function pintarPrevia(r) {
                document.getElementById('mm-previa').innerHTML = `
                    <div class="mm-tabla-caja">
                        <table class="mm-tabla">
                            <thead><tr><th>Cuenta</th><th class="num">Antes</th><th class="num">Se baja</th><th class="num">Queda</th></tr></thead>
                            <tbody>
                                ${r.lineas.map((l) => `
                                    <tr>
                                        <td>${esc(l.etiqueta)}</td>
                                        <td class="num">${fmt(l.antes)}</td>
                                        <td class="num">${l.rebaja > 0 ? '−' + fmt(l.rebaja) : '—'}</td>
                                        <td class="num">${fmt(l.despues)}</td>
                                    </tr>`).join('')}
                            </tbody>
                        </table>
                    </div>
                    <div class="mm-previa-pie">
                        <span>Saldo del cliente: <strong>${fmt(r.saldo_antes)}</strong> → <strong>${fmt(r.saldo_despues)}</strong></span>
                        <button type="button" class="mm-btn" id="mm-aplicar">Aplicar baja</button>
                    </div>`;
            }

            async function aplicarBaja() {
                if (!estado.previa) return;
                const id = estado.detalle.cliente.id;
                const monto = estado.previa.monto;
                const motivo = await pedirMotivo('Bajar el saldo', `Se bajan <strong>${fmt(monto)}</strong> a ${esc(estado.detalle.cliente.nombre)}.`);
                if (motivo === null) return;
                try {
                    const r = await api(`${BASE}/clientes/${id}/bajar`, 'POST', {
                        monto,
                        motivo
                    });
                    await Swal.fire({
                        icon: 'success',
                        title: 'Listo',
                        text: `Saldo: ${fmt(r.saldo_antes)} → ${fmt(r.saldo_despues)}`
                    });
                    cargarCliente(id);
                } catch (e) {
                    avisar(e.message);
                }
            }

            // ---------- Una factura ----------
            async function editarFactura(fac) {
                const id = Number(fac.dataset.id);
                const actual = parseFloat(fac.dataset.pend);
                const nuevo = parseFloat(fac.querySelector('.mm-fac-nuevo').value);
                if (isNaN(nuevo) || nuevo < 0) return avisar('Escribí el nuevo pendiente.');
                if (redondear(nuevo) === redondear(actual)) return avisar('Es el mismo monto: no hay nada que cambiar.');

                const num = fac.querySelector('.mm-fac-num').textContent;
                const subir = nuevo > actual;
                const motivo = await pedirMotivo(
                    subir ? 'Subir el pendiente' : 'Bajar el pendiente',
                    `${esc(num)}: ${fmt(actual)} → <strong>${fmt(nuevo)}</strong>`
                );
                if (motivo === null) return;
                try {
                    const r = await api(`${BASE}/facturas/${id}/editar`, 'POST', {
                        pendiente: nuevo,
                        motivo
                    });
                    await Swal.fire({
                        icon: 'success',
                        title: 'Listo',
                        text: `Pendiente: ${fmt(r.pendiente_antes)} → ${fmt(r.pendiente_despues)}. Saldo del cliente: ${fmt(r.saldo_despues)}`
                    });
                    cargarCliente(estado.detalle.cliente.id);
                } catch (e) {
                    avisar(e.message);
                }
            }

            async function anularFactura(fac) {
                const id = Number(fac.dataset.id);
                const actual = parseFloat(fac.dataset.pend);
                const num = fac.querySelector('.mm-fac-num').textContent;
                const motivo = await pedirMotivo(
                    'Anular la factura',
                    `${esc(num)}: se sacan <strong>${fmt(actual)}</strong> del saldo del cliente. No se mueve stock ni se borran abonos.`
                );
                if (motivo === null) return;
                try {
                    const r = await api(`${BASE}/facturas/${id}/anular`, 'POST', {
                        motivo
                    });
                    await Swal.fire({
                        icon: 'success',
                        title: 'Factura anulada',
                        text: `Saldo del cliente: ${fmt(r.saldo_antes)} → ${fmt(r.saldo_despues)}`
                    });
                    cargarCliente(estado.detalle.cliente.id);
                } catch (e) {
                    avisar(e.message);
                }
            }

            async function anularTodas() {
                const d = estado.detalle;
                const total = d.facturas.reduce((s, f) => s + f.pendiente, 0);
                const motivo = await pedirMotivo(
                    'Anular todas las facturas',
                    `Se anulan <strong>${d.facturas.length}</strong> facturas por un total de <strong>${fmt(total)}</strong>.`
                );
                if (motivo === null) return;
                try {
                    const r = await api(`${BASE}/clientes/${d.cliente.id}/anular-todas`, 'POST', {
                        motivo
                    });
                    const resto = r.queda_sin_factura > 0 ? ` Le quedan ${fmt(r.queda_sin_factura)} de deuda sin factura.` : '';
                    await Swal.fire({
                        icon: 'success',
                        title: 'Facturas anuladas',
                        text: `Saldo: ${fmt(r.saldo_antes)} → ${fmt(r.saldo_despues)}.${resto}`
                    });
                    cargarCliente(d.cliente.id);
                } catch (e) {
                    avisar(e.message);
                }
            }
        })();
    </script>
@endpush