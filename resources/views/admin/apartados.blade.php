@extends('layouts.app')

@section('titulo', 'Apartados')

@push('estilos')
    <style>
        .btn {
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            font-family: inherit;
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
        }

        .btn-texto {
            background: none;
            color: var(--azul-medio);
            padding: 4px 8px;
        }

        .btn:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .btn-ver {
            padding: 6px 12px;
            font-size: 13px;
        }

        .encabezado-apartados {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .encabezado-apartados h1 {
            margin: 0;
            font-size: 20px;
        }

        .buscador-caja {
            position: relative;
        }

        .sugerencias {
            display: none;
            margin-top: 4px;
            background: #fff;
            border: 1px solid var(--borde);
            border-radius: 8px;
            max-height: 220px;
            overflow-y: auto;
        }

        .sugerencias.abierto {
            display: block;
        }

        .sugerencia-item {
            padding: 10px 12px;
            font-size: 14px;
            cursor: pointer;
        }

        .sugerencia-item:hover {
            background: var(--azul-50);
        }

        .sugerencia-item .sug-nombre {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .sugerencia-item .sec {
            font-size: 12px;
            color: var(--texto-tenue);
        }

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
            max-width: 480px;
            max-height: 90dvh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 20px;
            border-bottom: 1px solid var(--borde);
            flex-shrink: 0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 17px;
        }

        .modal-cerrar {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--texto-tenue);
            padding: 4px;
            line-height: 0;
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
            margin-bottom: 18px;
        }

        .campo:last-child {
            margin-bottom: 0;
        }

        .campo label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--texto-tenue);
            margin-bottom: 7px;
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

        .cliente-elegido {
            background: var(--azul-50);
            border: 1px solid var(--azul-100);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .cliente-elegido .nombre {
            font-weight: 700;
            font-size: 14px;
            color: var(--azul-oscuro);
        }

        .producto-elegido-caja {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid var(--borde);
        }

        .producto-elegido-caja p {
            margin: 0 0 14px;
        }

        .fila-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .lineas-caja {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid var(--borde);
        }

        .lineas-caja h4 {
            margin: 0 0 10px;
            font-size: 13px;
            font-weight: 600;
            color: var(--texto-tenue);
        }

        .fila-linea {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 12px 0;
            border-bottom: 1px solid var(--borde);
        }

        .fila-linea:last-child {
            border-bottom: none;
        }

        .fila-linea .desc {
            font-size: 14px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .fila-linea .sub {
            font-size: 12px;
            color: var(--texto-tenue);
            margin-top: 2px;
        }

        /* Modal "Ver productos" */
        .vp-resumen {
            font-size: 13px;
            color: var(--texto-tenue);
            margin-bottom: 4px;
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
            background: #fff;
        }

        th,
        td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid var(--borde);
            font-size: 14px;
        }

        th {
            background: #F0F3F8;
            color: var(--texto-tenue);
            font-weight: 600;
            font-size: 13px;
        }

        .lista-apartados-movil {
            display: none;
        }

        .fila-apartado-movil {
            background: #fff;
            border-radius: 10px;
            box-shadow: var(--sombra-card);
            padding: 14px;
            margin-bottom: 10px;
        }

        .fila-apartado-movil .cliente {
            font-weight: 600;
            font-size: 15px;
            overflow-wrap: anywhere;
        }

        .fila-apartado-movil .fecha {
            font-size: 12px;
            color: var(--texto-tenue);
            margin-top: 2px;
        }

        .fila-apartado-movil .btn-ver {
            width: 100%;
            margin-top: 10px;
            padding: 10px 12px;
        }

        .fila-apartado-movil .acciones {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--borde);
        }

        .fila-apartado-movil .acciones .btn {
            flex: 1;
            text-align: center;
        }

        .estado-vacio {
            text-align: center;
            color: var(--texto-tenue);
            padding: 30px 10px;
            font-size: 14px;
        }

        @media (max-width: 720px) {
            .tabla-desktop {
                display: none;
            }

            .lista-apartados-movil {
                display: block;
            }

            .fila-2 {
                grid-template-columns: 1fr;
            }

            .modal-overlay {
                padding: 16px;
                align-items: center;
            }

            .modal-caja {
                max-width: 100%;
                max-height: 88dvh;
                border-radius: 14px;
            }

            .modal-footer {
                flex-direction: column-reverse;
            }

            .modal-footer .btn {
                width: 100%;
            }
        }

        .nota-previa {
            font-size: 12px;
            color: var(--texto-tenue);
            margin-top: 2px;
            overflow-wrap: anywhere;
        }

        .nota-caja {
            background: var(--azul-50);
            border: 1px solid var(--azul-100);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            margin-bottom: 10px;
            overflow-wrap: anywhere;
        }

        .campo textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            color: var(--texto);
            resize: vertical;
        }
    </style>
@endpush

@section('contenido')
    <div class="encabezado-apartados">
        <h1>Apartados</h1>
        <button class="btn btn-primario" onclick="abrirModalCrear()">+ Nuevo apartado</button>
    </div>

    <div class="tabla-envoltorio tabla-scroll tabla-desktop">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Productos</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla"></tbody>
        </table>
    </div>

    <div class="lista-apartados-movil" id="lista-movil"></div>
    <div id="estado-vacio" class="estado-vacio" style="display:none;">Todavía no hay apartados registrados.</div>

    <div class="paginacion" id="paginacion-apartados" style="display:none;"></div>

    {{-- Modal: ver productos de un apartado --}}
    <div class="modal-overlay" id="modal-productos">
        <div class="modal-caja">
            <div class="modal-header">
                <h2>Productos apartados</h2>
                <button class="modal-cerrar" onclick="cerrarModal('modal-productos')" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-cuerpo">
                <div class="vp-resumen" id="vp-resumen"></div>
                <div id="vp-lista"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secundario" onclick="cerrarModal('modal-productos')">Cerrar</button>
            </div>
        </div>
    </div>

    {{-- Modal: nuevo apartado --}}
    <div class="modal-overlay" id="modal-crear">
        <div class="modal-caja">
            <div class="modal-header">
                <h2>Nuevo apartado</h2>
                <button class="modal-cerrar" onclick="cerrarModal('modal-crear')" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-cuerpo">
                <div id="paso-cliente">
                    <div class="campo buscador-caja">
                        <label>Cliente</label>
                        <input type="text" id="buscar-cliente" placeholder="Buscar por nombre, código o teléfono"
                            autocomplete="off">
                        <div class="sugerencias" id="sug-cliente"></div>
                    </div>
                </div>

                <div id="cliente-elegido-caja" style="display:none;">
                    <div class="cliente-elegido">
                        <span class="nombre" id="ce-nombre"></span>
                        <button type="button" class="btn btn-texto" onclick="quitarClienteModal()">Cambiar</button>
                    </div>

                    <div class="campo buscador-caja">
                        <label>Producto (opcional)</label>
                        <input type="text" id="buscar-producto" placeholder="Buscar por nombre, código o subcategoría"
                            autocomplete="off">
                        <div class="sugerencias" id="sug-producto"></div>
                    </div>

                    <div id="producto-elegido-caja" class="producto-elegido-caja" style="display:none;">
                        <p style="font-size:14px;"><strong id="pe-nombre"></strong></p>
                        <div class="fila-2">
                            <div class="campo">
                                <label>Cantidad</label>
                                <input type="number" id="pe-cantidad" min="1" step="1" value="1">
                            </div>
                        </div>
                        <button type="button" class="btn btn-secundario" style="width:100%;"
                            onclick="agregarLinea()">Agregar a la lista</button>
                    </div>

                    <div id="lineas-caja" class="lineas-caja" style="display:none;">
                        <h4>Productos agregados</h4>
                        <div id="lista-lineas"></div>
                    </div>

                    <div class="campo" style="margin-top:20px;">
                        <label>Texto libre (opcional)</label>
                        <textarea id="apartado-nota" rows="2" maxlength="500" placeholder="Ej: pantalones 32"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secundario" onclick="cerrarModal('modal-crear')">Cancelar</button>
                <button type="button" class="btn btn-primario" onclick="guardarApartado()">Guardar</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const token = document.querySelector('meta[name="csrf-token"]').content;

        let clienteElegido = null;
        let productoElegido = null;
        let lineasTmp = [];
        let apartadosCache = []; // solo los de la página actual
        let paginaActual = 1;

        const $ = (id) => document.getElementById(id);

        function esc(texto) {
            return String(texto ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
        }

        function abrirModal(id) {
            $(id).classList.add('abierto');
        }

        function cerrarModal(id) {
            $(id).classList.remove('abierto');
            if (id === 'modal-crear') reiniciarModalCrear();
        }

        function reiniciarModalCrear() {
            clienteElegido = null;
            productoElegido = null;
            lineasTmp = [];
            $('buscar-cliente').value = '';
            $('apartado-nota').value = '';
            $('paso-cliente').style.display = 'block';
            $('cliente-elegido-caja').style.display = 'none';
            $('producto-elegido-caja').style.display = 'none';
            $('lineas-caja').style.display = 'none';
            $('buscar-producto').value = '';
        }

        function abrirModalCrear() {
            reiniciarModalCrear();
            abrirModal('modal-crear');
        }

        /* -------- Buscar cliente -------- */
        let temporizadorCliente = null;
        $('buscar-cliente').addEventListener('input', (e) => {
            clearTimeout(temporizadorCliente);
            const q = e.target.value.trim();
            if (q.length < 2) {
                $('sug-cliente').classList.remove('abierto');
                return;
            }
            temporizadorCliente = setTimeout(async () => {
                const r = await fetch(
                    `{{ route('facturacion.clientes') }}?buscar=${encodeURIComponent(q)}`, {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                const lista = await r.json();
                $('sug-cliente').innerHTML = lista.map(c => `
                <div class="sugerencia-item" onclick='elegirCliente(${JSON.stringify(c).replace(/'/g, "&#39;")})'>
                    <div class="sug-nombre">${c.nombre}</div>
                    <div class="sec">${c.codigo ?? 'sin código'} · ${c.telefono ?? 'sin teléfono'}</div>
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

        function quitarClienteModal() {
            clienteElegido = null;
            lineasTmp = [];
            $('paso-cliente').style.display = 'block';
            $('cliente-elegido-caja').style.display = 'none';
            $('lineas-caja').style.display = 'none';
            pintarLineas();
        }

        /* -------- Buscar producto -------- */
        let temporizadorProducto = null;
        $('buscar-producto').addEventListener('input', (e) => {
            clearTimeout(temporizadorProducto);
            const q = e.target.value.trim();
            if (q.length < 2) {
                $('sug-producto').classList.remove('abierto');
                return;
            }
            temporizadorProducto = setTimeout(async () => {
                const r = await fetch(
                    `{{ route('facturacion.productos') }}?buscar=${encodeURIComponent(q)}`, {
                        headers: {
                            Accept: 'application/json'
                        }
                    });
                const lista = await r.json();
                $('sug-producto').innerHTML = lista.map(p => {
                    const catSub = [p.categoria, p.subcategoria].filter(Boolean).join(' - ') ||
                        'Sin categoría';
                    const talla = p.variantes[0]?.talla ? ` / Talla: ${p.variantes[0].talla}` :
                        '';
                    return `<div class="sugerencia-item" onclick='elegirProducto(${JSON.stringify(p).replace(/'/g, "&#39;")})'>
                    <div class="sug-nombre">${p.nombre}</div>
                    <div class="sec">${catSub}${talla}</div>
                </div>`;
                }).join('') || '<div class="sugerencia-item sec">Sin resultados</div>';
                $('sug-producto').classList.add('abierto');
            }, 300);
        });

        function elegirProducto(p) {
            productoElegido = p;
            $('buscar-producto').value = '';
            $('sug-producto').classList.remove('abierto');
            $('pe-nombre').textContent = p.nombre;
            $('pe-cantidad').value = 1;
            $('producto-elegido-caja').style.display = 'block';
        }

        function agregarLinea() {
            if (!productoElegido) return;
            const cantidad = parseInt($('pe-cantidad').value || '0', 10);
            if (cantidad < 1) {
                Swal.fire('Cantidad inválida', 'Escribí una cantidad mayor a 0.', 'error');
                return;
            }

            const variante = productoElegido.variantes[0] ?? null;
            lineasTmp.push({
                producto_id: productoElegido.id,
                producto_variante_id: variante ? variante.id : null,
                nombre: productoElegido.nombre + (variante?.talla ? ` (Talla: ${variante.talla})` : ''),
                cantidad,
            });

            productoElegido = null;
            $('producto-elegido-caja').style.display = 'none';
            pintarLineas();
        }

        function quitarLinea(i) {
            lineasTmp.splice(i, 1);
            pintarLineas();
        }

        function pintarLineas() {
            $('lineas-caja').style.display = lineasTmp.length ? 'block' : 'none';
            $('lista-lineas').innerHTML = lineasTmp.map((l, i) => `
            <div class="fila-linea">
                <div><div class="desc">${esc(l.nombre)}</div><div class="sub">Cantidad: ${l.cantidad}</div></div>
                <button type="button" class="btn btn-texto" onclick="quitarLinea(${i})">Quitar</button>
            </div>`).join('');
        }

        async function guardarApartado() {
            if (!clienteElegido) {
                Swal.fire('Elegí un cliente', 'Buscá y seleccioná el cliente.', 'error');
                return;
            }
            const nota = $('apartado-nota').value.trim();
            if (!lineasTmp.length && !nota) {
                Swal.fire('Falta el detalle', 'Escribí un texto o agregá al menos un producto.', 'error');
                return;
            }

            const resp = await fetch(`{{ route('apartados.store') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    cliente_id: clienteElegido.id,
                    nota,
                    lineas: lineasTmp
                })
            });
            const datos = await resp.json();
            if (!resp.ok) {
                Swal.fire('Error', datos.mensaje ?? 'No se pudo guardar.', 'error');
                return;
            }

            cerrarModal('modal-crear');
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: datos.mensaje,
                showConfirmButton: false,
                timer: 2000
            });
            cargar(1);
            if (window.actualizarBadgeApartados) actualizarBadgeApartados();
        }

        /* -------- Listado -------- */
        async function cargar(pagina = paginaActual) {
            const resp = await fetch(`{{ route('apartados.index') }}?page=${pagina}`, {
                headers: {
                    Accept: 'application/json'
                }
            });
            const datos = await resp.json();

            // Si se eliminó el último de la última página, retrocede a la que sí existe
            if (datos.data.length === 0 && datos.current_page > 1) {
                return cargar(datos.last_page);
            }

            paginaActual = datos.current_page;
            apartadosCache = datos.data;
            pintarLista();
            pintarPaginador('paginacion-apartados', datos, 'apartados', p => {
                cargar(p);
                irArribaPagina();
            });
        }

        function pintarLista() {
            $('estado-vacio').style.display = apartadosCache.length ? 'none' : 'block';

            $('cuerpo-tabla').innerHTML = apartadosCache.map(a => `
            <tr>
               <td>${esc(a.cliente_nombre)}${a.nota ? `<div class="nota-previa">${esc(a.nota)}</div>` : ''}</td>
                <td>
                    <button class="btn btn-secundario btn-ver" onclick="verProductos(${a.id})">
                        ${a.lineas.length ? `Ver productos (${a.lineas.length})` : 'Ver detalle'}
                    </button>
                </td>
                <td>${a.fecha}</td>
                <td style="white-space:nowrap;">
                    <button class="btn btn-primario btn-ver" onclick="comprar(${a.id})">Comprar</button>
                    <button class="btn btn-peligro" onclick="eliminar(${a.id})">Eliminar</button>
                </td>
            </tr>`).join('');

            $('lista-movil').innerHTML = apartadosCache.map(a => `
            <div class="fila-apartado-movil">
                <div class="cliente">${esc(a.cliente_nombre)}</div>
                <div class="fecha">${a.fecha}</div>
                ${a.nota ? `<div class="nota-previa">${esc(a.nota)}</div>` : ''}
                <button class="btn btn-secundario btn-ver" onclick="verProductos(${a.id})">
                  ${a.lineas.length ? `Ver productos (${a.lineas.length})` : 'Ver detalle'}
                </button>
                <div class="acciones">
                    <button class="btn btn-primario" onclick="comprar(${a.id})">Comprar</button>
                    <button class="btn btn-peligro" onclick="eliminar(${a.id})">Eliminar</button>
                </div>
            </div>`).join('');
        }

        function verProductos(id) {
            const a = apartadosCache.find(x => x.id === id);
            if (!a) return;

            $('vp-resumen').textContent = `${a.cliente_nombre} · ${a.fecha}`;
            $('vp-lista').innerHTML =
                (a.nota ? `<div class="nota-caja">${esc(a.nota)}</div>` : '') +
                a.lineas.map(l => `
            <div class="fila-linea">
                <div>
                    <div class="desc">${esc(l.nombre)}</div>
                    <div class="sub">Cantidad: ${l.cantidad}</div>
                </div>
            </div>`).join('');
            abrirModal('modal-productos');
        }

        function comprar(id) {
            window.location.href = `{{ route('facturacion.index') }}?apartado_id=${id}`;
        }

        async function eliminar(id) {
            const confirmar = await Swal.fire({
                title: '¿Eliminar apartado?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });
            if (!confirmar.isConfirmed) return;

            const resp = await fetch(`/apartados/${id}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token
                }
            });
            const datos = await resp.json();
            if (!resp.ok) {
                Swal.fire('No se pudo eliminar', datos.mensaje ?? '', 'error');
                return;
            }

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: datos.mensaje,
                showConfirmButton: false,
                timer: 2000
            });
            cargar();
            if (window.actualizarBadgeApartados) actualizarBadgeApartados();
        }

        cargar();
    </script>
@endpush
