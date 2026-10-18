@extends('layouts.app')

@section('titulo', 'Reservas')

@push('estilos')
    <style>
        .btn { border: none; border-radius: 8px; padding: 10px 16px; font-size: 14px; font-weight: 600; cursor: pointer; white-space: nowrap; font-family: inherit; }
        .btn-primario { background: var(--azul-medio); color: #fff; }
        .btn-secundario { background: #fff; border: 1px solid var(--borde); color: var(--texto); }
        .btn-exito { background: #16794f; color: #fff; }
        .btn-peligro { background: #fff; border: 1px solid var(--borde); color: #a30000; }
        .btn-texto { background: none; color: var(--azul-medio); padding: 4px 8px; }
        .btn-chico { padding: 6px 12px; font-size: 13px; }
        .btn:disabled { opacity: .45; cursor: not-allowed; }

        .encabezado-reservas { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
        .encabezado-reservas h1 { margin: 0; }

        .filtros-reservas { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
        .filtros-reservas select, .filtros-reservas input { padding: 10px 12px; border: 1px solid var(--borde); border-radius: 8px; font-size: 16px; font-family: inherit; color: var(--texto); background: #fff; }
        .filtros-reservas input { flex: 1; min-width: 200px; }

        .tabla-envoltorio { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 8px; box-shadow: var(--sombra-card); }
        table { width: 100%; min-width: 760px; border-collapse: collapse; background: #fff; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--borde); font-size: 14px; vertical-align: top; white-space: normal; }
        th { background: #F0F3F8; color: var(--texto-tenue); font-weight: 600; font-size: 13px; white-space: nowrap; }
        tr.hoy td { background: var(--azul-50); }
        td.acciones { white-space: nowrap; }
        td.acciones .btn { margin-right: 4px; }

        .cli-nombre { font-weight: 600; overflow-wrap: anywhere; }
        .cli-sub { font-size: 12px; color: var(--texto-tenue); margin-top: 2px; overflow-wrap: anywhere; }

        .badge { display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 600; white-space: nowrap; }
        .badge-azul { background: var(--azul-100); color: var(--azul-oscuro); }
        .badge-celeste { background: #eef1f6; color: var(--texto-tenue); }
        .badge-verde { background: #e7f7ee; color: #16794f; }
        .badge-rojo { background: #fdecec; color: #a30000; }
        .badge-gris { background: #f0f0f0; color: #666; }

        .lista-movil { display: none; }
        .fila-movil { background: #fff; border-radius: 10px; box-shadow: var(--sombra-card); padding: 14px; margin-bottom: 10px; }
        .fila-movil.hoy { border: 1px solid var(--azul-medio); background: var(--azul-50); }
        .fila-movil .cab { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
        .fila-movil .cliente { font-weight: 700; font-size: 15px; overflow-wrap: anywhere; }
        .fila-movil .cuando { font-size: 13px; color: var(--texto-tenue); margin-top: 2px; }
        .fila-movil .motivo { margin-top: 8px; font-size: 14px; overflow-wrap: anywhere; }
        .fila-movil .acciones { display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--borde); flex-wrap: wrap; }
        .fila-movil .acciones .btn { flex: 1; text-align: center; }

        .estado-vacio { text-align: center; color: var(--texto-tenue); padding: 30px 10px; font-size: 14px; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(8, 40, 95, .5); z-index: 60; align-items: center; justify-content: center; padding: 16px; }
        .modal-overlay.abierto { display: flex; }
        .modal-caja { background: #fff; border-radius: 14px; width: 100%; max-width: 480px; max-height: 90dvh; display: flex; flex-direction: column; overflow: hidden; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 18px 20px; border-bottom: 1px solid var(--borde); flex-shrink: 0; }
        .modal-header h2 { margin: 0; font-size: 17px; }
        .modal-cerrar { background: none; border: none; cursor: pointer; color: var(--texto-tenue); padding: 4px; line-height: 0; }
        .modal-cerrar svg { width: 20px; height: 20px; }
        .modal-cuerpo { padding: 20px; overflow-y: auto; -webkit-overflow-scrolling: touch; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 14px 20px; border-top: 1px solid var(--borde); flex-shrink: 0; }

        .campo { margin-bottom: 18px; }
        .campo:last-child { margin-bottom: 0; }
        .campo label { display: block; font-size: 13px; font-weight: 600; color: var(--texto-tenue); margin-bottom: 7px; }
        .campo input, .campo textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--borde); border-radius: 8px; font-size: 16px; font-family: inherit; color: var(--texto); background: #fff; }
        .campo textarea { resize: vertical; }
        .fila-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px; }
        .fila-2 .campo { margin-bottom: 0; }

        .buscador-caja { position: relative; }
        .sugerencias { display: none; margin-top: 4px; background: #fff; border: 1px solid var(--borde); border-radius: 8px; max-height: 220px; overflow-y: auto; }
        .sugerencias.abierto { display: block; }
        .sugerencia-item { padding: 10px 12px; font-size: 14px; cursor: pointer; }
        .sugerencia-item:hover { background: var(--azul-50); }
        .sugerencia-item .sug-nombre { font-weight: 600; margin-bottom: 2px; }
        .sugerencia-item .sec { font-size: 12px; color: var(--texto-tenue); }

        .cliente-elegido { background: var(--azul-50); border: 1px solid var(--azul-100); border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
        .cliente-elegido .nombre { font-weight: 700; font-size: 14px; color: var(--azul-oscuro); overflow-wrap: anywhere; }

        @media (max-width: 720px) {
            .tabla-desktop { display: none; }
            .lista-movil { display: block; }
            .filtros-reservas select { width: 100%; }
            .fila-2 { grid-template-columns: 1fr; margin-bottom: 18px; }
            .fila-2 .campo { margin-bottom: 18px; }
            .fila-2 .campo:last-child { margin-bottom: 0; }
            .modal-caja { max-width: 100%; max-height: 88dvh; }
            .modal-footer { flex-direction: column-reverse; }
            .modal-footer .btn { width: 100%; }
        }
    </style>
@endpush

@section('contenido')
    <div class="encabezado-reservas">
        <h1>Reservas</h1>
        <button class="btn btn-primario" onclick="abrirModalCrear()">+ Nueva reserva</button>
    </div>

    <div class="filtros-reservas">
        <select id="filtro-estado" onchange="cargar(1)">
            <option value="pendiente">Pendientes</option>
            <option value="terminada">Terminadas</option>
            <option value="cancelada">Canceladas</option>
            <option value="todas">Todas</option>
        </select>
        <input type="text" id="filtro-q" placeholder="Buscar por nombre o código del cliente" autocomplete="off">
    </div>

    <div class="tabla-envoltorio tabla-scroll tabla-desktop">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Cliente</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla"></tbody>
        </table>
    </div>

    <div class="lista-movil" id="lista-movil"></div>
    <div id="estado-vacio" class="estado-vacio" style="display:none;"></div>

    <div class="paginacion" id="paginacion-reservas" style="display:none;"></div>

    {{-- Modal: crear / editar --}}
    <div class="modal-overlay" id="modal-reserva">
        <div class="modal-caja">
            <div class="modal-header">
                <h2 id="modal-titulo">Nueva reserva</h2>
                <button class="modal-cerrar" onclick="cerrarModal()" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-cuerpo">
                <div id="paso-cliente">
                    <div class="campo buscador-caja">
                        <label>Cliente</label>
                        <input type="text" id="buscar-cliente" placeholder="Buscar por nombre o código" autocomplete="off">
                        <div class="sugerencias" id="sug-cliente"></div>
                    </div>
                </div>

                <div id="cliente-elegido-caja" style="display:none;">
                    <div class="cliente-elegido">
                        <span class="nombre" id="ce-nombre"></span>
                        <button type="button" class="btn btn-texto" onclick="quitarCliente()">Cambiar</button>
                    </div>
                </div>

                <div class="fila-2">
                    <div class="campo">
                        <label>Fecha</label>
                        <input type="date" id="res-fecha">
                    </div>
                    <div class="campo">
                        <label>Hora</label>
                        <input type="time" id="res-hora">
                    </div>
                </div>

                <div class="campo">
                    <label>Motivo (opcional)</label>
                    <textarea id="res-motivo" rows="3" maxlength="500" placeholder="Ej: entrega de pedido, revisar cuenta..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secundario" onclick="cerrarModal()">Cancelar</button>
                <button type="button" class="btn btn-primario" onclick="guardarReserva()">Guardar</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const $ = (id) => document.getElementById(id);

        let paginaActual = 1;
        let reservasCache = [];
        let clienteElegido = null;
        let reservaEditando = null; // id de la reserva que se edita; null = nueva

        function esc(texto) {
            return String(texto ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            } [c]));
        }

        function hoyISO() {
            const d = new Date();
            const p = n => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
        }

        function toast(icono, titulo) {
            Swal.fire({ toast: true, position: 'top-end', icon: icono, title: titulo, showConfirmButton: false, timer: 2200 });
        }

        /* ==================== MODAL ==================== */

        function abrirModalCrear() {
            reservaEditando = null;
            $('modal-titulo').textContent = 'Nueva reserva';
            quitarCliente();
            $('res-fecha').value = hoyISO();
            $('res-hora').value = '';
            $('res-motivo').value = '';
            $('modal-reserva').classList.add('abierto');
        }

        function abrirModalEditar(id) {
            const r = reservasCache.find(x => x.id === id);
            if (!r) return;

            reservaEditando = id;
            $('modal-titulo').textContent = 'Editar reserva';
            elegirCliente({ id: r.cliente_id, nombre: r.cliente_nombre });
            $('res-fecha').value = r.fecha;
            $('res-hora').value = r.hora;
            $('res-motivo').value = r.motivo ?? '';
            $('modal-reserva').classList.add('abierto');
        }

        function cerrarModal() {
            $('modal-reserva').classList.remove('abierto');
            $('sug-cliente').classList.remove('abierto');
        }

        /* -------- Buscar cliente (por nombre o código) -------- */
        let temporizadorCliente = null;
        $('buscar-cliente').addEventListener('input', (e) => {
            clearTimeout(temporizadorCliente);
            const q = e.target.value.trim();
            if (q.length < 2) {
                $('sug-cliente').classList.remove('abierto');
                return;
            }
            temporizadorCliente = setTimeout(async () => {
                const r = await fetch(`{{ route('facturacion.clientes') }}?buscar=${encodeURIComponent(q)}`, {
                    headers: { Accept: 'application/json' }
                });
                const lista = await r.json();
                $('sug-cliente').innerHTML = lista.map(c => `
                    <div class="sugerencia-item" onclick='elegirCliente(${JSON.stringify(c).replace(/'/g, "&#39;")})'>
                        <div class="sug-nombre">${esc(c.nombre)}</div>
                        <div class="sec">${esc(c.codigo ?? 'sin código')} · ${esc(c.telefono ?? 'sin teléfono')}</div>
                    </div>`).join('') || '<div class="sugerencia-item sec">Sin resultados</div>';
                $('sug-cliente').classList.add('abierto');
            }, 300);
        });

        function elegirCliente(c) {
            clienteElegido = c;
            $('ce-nombre').textContent = c.nombre;
            $('paso-cliente').style.display = 'none';
            $('cliente-elegido-caja').style.display = 'block';
            $('sug-cliente').classList.remove('abierto');
        }

        function quitarCliente() {
            clienteElegido = null;
            $('buscar-cliente').value = '';
            $('paso-cliente').style.display = 'block';
            $('cliente-elegido-caja').style.display = 'none';
            $('sug-cliente').classList.remove('abierto');
        }

        async function guardarReserva() {
            if (!clienteElegido) {
                Swal.fire('Elegí un cliente', 'Buscá y seleccioná el cliente.', 'error');
                return;
            }
            const fecha = $('res-fecha').value;
            const hora = $('res-hora').value;
            if (!fecha) {
                Swal.fire('Falta la fecha', 'Elegí la fecha de la reserva.', 'error');
                return;
            }
            if (!hora) {
                Swal.fire('Falta la hora', 'Elegí la hora de la reserva.', 'error');
                return;
            }

            const editando = reservaEditando !== null;
            const url = editando ? `{{ url('/reservas') }}/${reservaEditando}` : `{{ route('reservas.store') }}`;

            const resp = await fetch(url, {
                method: editando ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    cliente_id: clienteElegido.id,
                    fecha,
                    hora,
                    motivo: $('res-motivo').value.trim()
                })
            });
            const datos = await resp.json().catch(() => ({}));

            if (!resp.ok) {
                const msg = datos.errors
                    ? Object.values(datos.errors).flat().join('\n')
                    : (datos.mensaje ?? datos.message ?? 'No se pudo guardar.');
                Swal.fire('Revisá los datos', msg, 'error');
                return;
            }

            cerrarModal();
            toast('success', datos.mensaje);
            cargar(editando ? paginaActual : 1);
        }

        /* ==================== LISTADO ==================== */

        let temporizadorBusqueda = null;
        $('filtro-q').addEventListener('input', () => {
            clearTimeout(temporizadorBusqueda);
            temporizadorBusqueda = setTimeout(() => cargar(1), 350);
        });

        async function cargar(pagina = paginaActual) {
            const params = new URLSearchParams({
                estado: $('filtro-estado').value,
                q: $('filtro-q').value.trim(),
                page: pagina
            });
            const resp = await fetch(`{{ route('reservas.index') }}?${params.toString()}`, {
                headers: { Accept: 'application/json' }
            });
            if (!resp.ok) {
                Swal.fire('No se pudo cargar', 'Intentá de nuevo. Las reservas solo están disponibles en Guana.', 'error');
                return;
            }
            const datos = await resp.json();

            // Si se vació la última página, retrocede a la que sí existe
            if (datos.data.length === 0 && datos.current_page > 1) {
                return cargar(datos.last_page);
            }

            paginaActual = datos.current_page;
            reservasCache = datos.data;
            pintarLista();
            pintarPaginador('paginacion-reservas', datos, 'reservas', p => {
                cargar(p);
                irArribaPagina();
            });
        }

        function badgeEstado(r) {
            if (r.estado === 'terminada') return '<span class="badge badge-verde">Terminada</span>';
            if (r.estado === 'cancelada') return '<span class="badge badge-gris">Cancelada</span>';
            if (r.vencida) return '<span class="badge badge-rojo">Vencida</span>';
            if (r.es_hoy) return '<span class="badge badge-azul">Hoy</span>';
            return '<span class="badge badge-celeste">Pendiente</span>';
        }

        function botonesReserva(r, chico) {
            const c = chico ? 'btn-chico' : '';
            const editar = `<button class="btn btn-secundario ${c}" onclick="abrirModalEditar(${r.id})">Editar</button>`;
            if (r.estado === 'pendiente') {
                return editar +
                    `<button class="btn btn-exito ${c}" onclick="cambiarEstado(${r.id}, 'terminada')">Terminada</button>` +
                    `<button class="btn btn-peligro ${c}" onclick="cambiarEstado(${r.id}, 'cancelada')">Cancelar</button>`;
            }
            return editar +
                `<button class="btn btn-secundario ${c}" onclick="cambiarEstado(${r.id}, 'pendiente')">Reactivar</button>`;
        }

        function pintarLista() {
            const vacio = $('estado-vacio');
            if (reservasCache.length) {
                vacio.style.display = 'none';
            } else {
                const hayFiltro = $('filtro-q').value.trim() !== '' || $('filtro-estado').value !== 'pendiente';
                vacio.textContent = hayFiltro ? 'No hay reservas con ese filtro.' : 'No hay reservas pendientes.';
                vacio.style.display = 'block';
            }

            $('cuerpo-tabla').innerHTML = reservasCache.map(r => `
                <tr class="${r.es_hoy ? 'hoy' : ''}">
                    <td>${esc(r.fecha_texto)}</td>
                    <td style="white-space:nowrap;">${esc(r.hora_texto)}</td>
                    <td>
                        <div class="cli-nombre">${esc(r.cliente_nombre)}</div>
                        <div class="cli-sub">${esc(r.cliente_codigo ?? 'sin código')} · ${esc(r.cliente_telefono ?? 'sin teléfono')}</div>
                        ${r.cliente_direccion ? `<div class="cli-sub">${esc(r.cliente_direccion)}</div>` : ''}
                    </td>
                    <td>${r.motivo ? esc(r.motivo) : '—'}</td>
                    <td>${badgeEstado(r)}</td>
                    <td class="acciones">${botonesReserva(r, true)}</td>
                </tr>`).join('');

            $('lista-movil').innerHTML = reservasCache.map(r => `
                <div class="fila-movil ${r.es_hoy ? 'hoy' : ''}">
                    <div class="cab">
                        <div>
                            <div class="cliente">${esc(r.cliente_nombre)}</div>
                            <div class="cuando">${esc(r.fecha_texto)} · ${esc(r.hora_texto)}</div>
                        </div>
                        ${badgeEstado(r)}
                    </div>
                    <div class="cli-sub">${esc(r.cliente_codigo ?? 'sin código')} · ${esc(r.cliente_telefono ?? 'sin teléfono')}</div>
                    ${r.cliente_direccion ? `<div class="cli-sub">${esc(r.cliente_direccion)}</div>` : ''}
                    ${r.motivo ? `<div class="motivo">${esc(r.motivo)}</div>` : ''}
                    <div class="acciones">${botonesReserva(r, false)}</div>
                </div>`).join('');
        }

        async function cambiarEstado(id, estado) {
            if (estado === 'cancelada') {
                const confirmar = await Swal.fire({
                    title: '¿Cancelar esta reserva?',
                    text: 'Queda en el historial y se puede reactivar.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cancelar',
                    cancelButtonText: 'Volver'
                });
                if (!confirmar.isConfirmed) return;
            }

            const resp = await fetch(`{{ url('/reservas') }}/${id}/estado`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ estado })
            });
            const datos = await resp.json().catch(() => ({}));

            if (!resp.ok) {
                Swal.fire('No se pudo cambiar', datos.mensaje ?? datos.message ?? 'Intentá de nuevo.', 'error');
                return;
            }

            toast('success', datos.mensaje);
            cargar();
        }

        cargar(1);
    </script>
@endpush