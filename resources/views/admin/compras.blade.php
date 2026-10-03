@extends('layouts.app')

@section('titulo', 'Compras')

@push('estilos')
<style>
    .encabezado-compras {
        display: flex; justify-content: space-between; align-items: center;
        gap: 10px; flex-wrap: wrap; margin-bottom: 16px;
    }
    .encabezado-compras h1 { margin: 0; font-size: 20px; }
    .encabezado-compras .acciones-encabezado { display: flex; gap: 10px; flex-wrap: wrap; }

    .btn {
        border: none; border-radius: 8px; padding: 10px 16px; font-size: 14px;
        font-weight: 600; cursor: pointer; white-space: nowrap;
    }
    .btn-primario { background: var(--azul-medio); color: #fff; }
    .btn-secundario { background: #fff; border: 1px solid var(--borde); color: var(--texto); }
    .btn-peligro { background: none; color: #a30000; }
    .btn-texto { background: none; color: var(--azul-medio); }

    #buscador {
        width: 100%; max-width: 340px; padding: 10px 12px; border: 1px solid var(--borde);
        border-radius: 8px; margin-bottom: 16px; font-size: 16px;
    }

    .badge {
        display: inline-block; padding: 3px 10px; border-radius: 999px;
        font-size: 12px; font-weight: 600;
    }
    .badge-pagada { background: #e6ffed; color: #036b26; }
    .badge-pendiente { background: #fff4e5; color: #b95000; }

    .modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(8, 40, 95, .5);
        z-index: 60; align-items: center; justify-content: center; padding: 16px;
    }
    .modal-overlay.abierto { display: flex; }
    .modal-caja {
        background: #fff; border-radius: 14px; width: 100%; max-width: 460px;
        max-height: 90dvh; display: flex; flex-direction: column; overflow: hidden;
    }
    .modal-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 18px 20px; border-bottom: 1px solid var(--borde); flex-shrink: 0;
    }
    .modal-header h2 { margin: 0; font-size: 17px; }
    .modal-cerrar { background: none; border: none; cursor: pointer; color: var(--texto-tenue); padding: 4px; line-height: 0; }
    .modal-cerrar svg { width: 20px; height: 20px; }
    .modal-cuerpo { padding: 20px; overflow-y: auto; -webkit-overflow-scrolling: touch; }
    .campo { margin-bottom: 16px; }
    .campo:last-child { margin-bottom: 0; }
    .campo label { display: block; font-size: 13px; font-weight: 600; color: var(--texto-tenue); margin-bottom: 6px; }
    .campo input, .campo select {
        width: 100%; padding: 10px 12px; border: 1px solid var(--borde); border-radius: 8px;
        font-size: 16px; font-family: inherit; color: var(--texto);
    }
    .campo small { display: block; color: var(--texto-tenue); font-size: 12px; margin-top: 4px; }
    .modal-footer {
        display: flex; justify-content: flex-end; gap: 10px; padding: 14px 20px;
        border-top: 1px solid var(--borde); flex-shrink: 0;
    }

    .sugerencias-proveedor {
        display: none;
        position: absolute;
        left: 0; right: 0;
        top: 100%;
        margin-top: 4px;
        background: #fff;
        border: 1px solid var(--borde);
        border-radius: 8px;
        box-shadow: var(--sombra-hover);
        max-height: 180px;
        overflow-y: auto;
        z-index: 10;
    }
    .sugerencias-proveedor.abierto { display: block; }
    .sugerencia-item {
        padding: 10px 12px;
        font-size: 14px;
        cursor: pointer;
    }
    .sugerencia-item:hover, .sugerencia-item.resaltado {
        background: var(--azul-50);
    }

    .lista-compras-movil { display: none; }
    .fila-compra-movil {
        background: #fff; border-radius: 10px; box-shadow: var(--sombra-card);
        padding: 14px; margin-bottom: 10px;
    }
    .fila-compra-movil .proveedor { font-weight: 600; font-size: 15px; }
    .fila-compra-movil .detalle { font-size: 13px; color: var(--texto-tenue); margin-top: 2px; }
    .fila-compra-movil .linea-monto { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
    .fila-compra-movil .acciones {
        display: flex; gap: 14px; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--borde);
    }

   
    #buscador-proveedores {
        width: 100%; padding: 10px 12px; border: 1px solid var(--borde);
        border-radius: 8px; margin-bottom: 14px; font-size: 16px; font-family: inherit;
    }
    .fila-proveedor {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 0; border-bottom: 1px solid var(--borde); gap: 10px;
    }
    .fila-proveedor:last-child { border-bottom: none; }
    .fila-proveedor .info .nombre { font-weight: 600; font-size: 14px; }
    .fila-proveedor .info .detalle { font-size: 12px; color: var(--texto-tenue); margin-top: 2px; }
    .fila-proveedor .acciones { display: flex; gap: 12px; flex-shrink: 0; }
    .sin-resultados-proveedor { color: var(--texto-tenue); font-size: 14px; text-align: center; padding: 20px 0; }

    @media (max-width: 720px) {
        .tabla-desktop { display: none; }
        .lista-compras-movil { display: block; }
        .encabezado-compras { flex-direction: column; align-items: stretch; }
        .encabezado-compras .acciones-encabezado { flex-direction: column; }
        .encabezado-compras .acciones-encabezado .btn { width: 100%; }
        #buscador { max-width: 100%; }
        .modal-overlay { padding: 0; align-items: flex-end; }
        .modal-caja { max-width: 100%; max-height: 92dvh; border-radius: 16px 16px 0 0; }
        .modal-footer { flex-direction: column-reverse; }
        .modal-footer .btn { width: 100%; }
        
    }
        .tabla-scroll {
        max-height: calc(100dvh - 330px);
        min-height: 240px;
    }
</style>
@endpush

@section('contenido')
    <div class="encabezado-compras">
        <h1>Compras</h1>
        <div class="acciones-encabezado">
            <button class="btn btn-secundario" onclick="abrirModalProveedores()">Ver proveedores</button>
            <button class="btn btn-primario" onclick="abrirModalCrear()">+ Nueva compra</button>
        </div>
    </div>

    <input type="text" id="buscador" placeholder="Buscar por proveedor o descripción...">

        <div class="tabla-envoltorio tabla-scroll tabla-desktop">
        <table>
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Saldo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla"></tbody>
        </table>
    </div>

    <div class="lista-compras-movil" id="lista-movil"></div>

    <div class="paginacion" id="paginacion" style="display:none;"></div>

    {{-- Modal: crear/editar compra --}}
    <div class="modal-overlay" id="modal-compra">
        <div class="modal-caja">
            <div class="modal-header">
                <h2 id="modal-compra-titulo">Nueva compra</h2>
                <button class="modal-cerrar" onclick="cerrarModal('modal-compra')" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="form-compra">
                <div class="modal-cuerpo">
                    <input type="hidden" id="compra-id">

                    <div class="campo" style="position: relative;">
                        <label>Proveedor</label>
                        <input type="text" id="compra-proveedor" autocomplete="off" required placeholder="Escribí o elegí uno existente">
                        <div id="sugerencias-proveedor" class="sugerencias-proveedor"></div>
                    </div>

                    <div class="campo">
                        <label>Descripción</label>
                        <input type="text" id="compra-descripcion" required>
                    </div>

                    <div class="campo">
                        <label>Monto total</label>
                        <input type="number" id="compra-monto" step="0.01" min="0.01" required>
                    </div>

                    <div class="campo" id="campo-tipo">
                        <label>Tipo</label>
                        <select id="compra-tipo" required>
                            <option value="contado">Contado</option>
                            <option value="credito">Crédito</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label>Fecha</label>
                        <input type="date" id="compra-fecha" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secundario" onclick="cerrarModal('modal-compra')">Cancelar</button>
                    <button type="submit" class="btn btn-primario">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: abonar --}}
    <div class="modal-overlay" id="modal-abono">
        <div class="modal-caja">
            <div class="modal-header">
                <h2>Registrar abono</h2>
                <button class="modal-cerrar" onclick="cerrarModal('modal-abono')" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="form-abono">
                <div class="modal-cuerpo">
                    <input type="hidden" id="abono-compra-id">
                    <p style="margin:0 0 16px; font-size:14px; color:var(--texto-tenue);">
                        Saldo pendiente: <strong id="abono-saldo-actual" style="color:var(--texto);"></strong>
                    </p>
                    <div class="campo">
                        <label>Monto a abonar</label>
                        <input type="number" id="abono-monto" step="0.01" min="0.01" required>
                    </div>
                    <div class="campo">
                        <label>Fecha</label>
                        <input type="date" id="abono-fecha" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secundario" onclick="cerrarModal('modal-abono')">Cancelar</button>
                    <button type="submit" class="btn btn-primario">Abonar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: listado de proveedores --}}
    <div class="modal-overlay" id="modal-proveedores">
        <div class="modal-caja">
            <div class="modal-header">
                <h2>Proveedores</h2>
                <button class="modal-cerrar" onclick="cerrarModal('modal-proveedores')" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="modal-cuerpo">
                <input type="text" id="buscador-proveedores" placeholder="Buscar proveedor...">
                <div id="lista-proveedores"></div>
            </div>
        </div>
    </div>

    {{-- Modal: editar proveedor --}}
    <div class="modal-overlay" id="modal-proveedor-editar">
        <div class="modal-caja">
            <div class="modal-header">
                <h2>Editar proveedor</h2>
                <button class="modal-cerrar" onclick="cerrarModal('modal-proveedor-editar')" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="form-proveedor-editar">
                <div class="modal-cuerpo">
                    <input type="hidden" id="proveedor-editar-id">
                    <div class="campo">
                        <label>Nombre</label>
                        <input type="text" id="proveedor-editar-nombre" required>
                    </div>
                    <div class="campo">
                        <label>Teléfono</label>
                        <input type="text" id="proveedor-editar-telefono">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secundario" onclick="volverAListaProveedores()">Cancelar</button>
                    <button type="submit" class="btn btn-primario">Guardar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const token = document.querySelector('meta[name="csrf-token"]').content;
    let paginaActual = 1;
    let temporizadorBusqueda = null;
    let proveedoresCache = [];

    function formatoColones(monto) {
        return '₡' + Number(monto).toLocaleString('es-CR', { minimumFractionDigits: 2 });
    }

    async function cargarProveedores() {
        const resp = await fetch(`{{ route('compras.proveedores') }}`, { headers: { 'Accept': 'application/json' } });
        proveedoresCache = await resp.json();
    }

    function mostrarSugerencias(filtro) {
        const contenedor = document.getElementById('sugerencias-proveedor');
        const texto = filtro.trim().toLowerCase();

        const coincidencias = texto
            ? proveedoresCache.filter(p => p.nombre.toLowerCase().includes(texto))
            : proveedoresCache;

        if (coincidencias.length === 0) {
            contenedor.classList.remove('abierto');
            contenedor.innerHTML = '';
            return;
        }

        contenedor.innerHTML = coincidencias.slice(0, 8).map(p =>
            `<div class="sugerencia-item" onclick="elegirProveedor('${p.nombre.replace(/'/g, "\\'")}')">${p.nombre}</div>`
        ).join('');
        contenedor.classList.add('abierto');
    }

    function elegirProveedor(nombre) {
        document.getElementById('compra-proveedor').value = nombre;
        document.getElementById('sugerencias-proveedor').classList.remove('abierto');
    }

    document.getElementById('compra-proveedor').addEventListener('input', (e) => {
        mostrarSugerencias(e.target.value);
    });

    document.getElementById('compra-proveedor').addEventListener('focus', (e) => {
        mostrarSugerencias(e.target.value);
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.campo')) {
            document.getElementById('sugerencias-proveedor')?.classList.remove('abierto');
        }
    });

        async function cargar(pagina = 1) {
        paginaActual = pagina;
        const q = document.getElementById('buscador').value;
        const resp = await fetch(`{{ route('compras.index') }}?page=${pagina}&q=${encodeURIComponent(q)}`, {
            headers: { 'Accept': 'application/json' }
        });
        const datos = await resp.json();

        // Si se eliminó el último de la última página, retrocede a la que sí existe
        if (datos.data.length === 0 && datos.current_page > 1) {
            return cargar(datos.last_page);
        }

        pintarTabla(datos.data);
        pintarTarjetasMovil(datos.data);
        pintarPaginador('paginacion', datos, 'compras', p => { cargar(p); irArribaPagina(); });
    }

    function filaAcciones(c) {
        const btnAbonar = c.estado === 'pendiente'
            ? `<button class="btn btn-texto" onclick='abrirModalAbono(${c.id}, ${c.saldo_pendiente})'>Abonar</button>`
            : '';
        return `
            ${btnAbonar}
            <button class="btn btn-texto" onclick='abrirModalEditar(${JSON.stringify(c)})'>Editar</button>
            <button class="btn btn-peligro" onclick="eliminar(${c.id})">Eliminar</button>
        `;
    }

    function pintarTabla(compras) {
        document.getElementById('cuerpo-tabla').innerHTML = compras.map(c => `
            <tr>
                <td>${c.proveedor}</td>
                <td>${c.descripcion}</td>
                <td>${c.fecha}</td>
                <td>${formatoColones(c.monto_total)}</td>
                <td>${c.tipo_label}</td>
                <td><span class="badge badge-${c.estado}">${c.estado_label}</span></td>
                <td>${c.estado === 'pendiente' ? formatoColones(c.saldo_pendiente) : '—'}</td>
                <td style="white-space:nowrap;">${filaAcciones(c)}</td>
            </tr>
        `).join('');
    }

    function pintarTarjetasMovil(compras) {
        document.getElementById('lista-movil').innerHTML = compras.map(c => `
            <div class="fila-compra-movil">
                <div class="proveedor">${c.proveedor}</div>
                <div class="detalle">${c.descripcion}</div>
                <div class="detalle">${c.fecha} · ${c.tipo_label}</div>
                <div class="linea-monto">
                    <span class="badge badge-${c.estado}">${c.estado_label}</span>
                    <strong>${c.estado === 'pendiente' ? formatoColones(c.saldo_pendiente) : formatoColones(c.monto_total)}</strong>
                </div>
                <div class="acciones">${filaAcciones(c)}</div>
            </div>
        `).join('');
    }

    function rangoPaginas(actual, ultima) {
        const paginas = [];
        const visibles = new Set([1, ultima, actual, actual - 1, actual + 1]);
        for (let i = 1; i <= ultima; i++) if (visibles.has(i)) paginas.push(i);
        const resultado = [];
        let anterior = 0;
        for (const p of paginas) {
            if (anterior && p - anterior > 1) resultado.push('...');
            resultado.push(p);
            anterior = p;
        }
        return resultado;
    }

    function pintarPaginacion(datos) {
        const contenedor = document.getElementById('paginacion');
        const { current_page: actual, last_page: ultima, total, from: desde, to: hasta } = datos;
        if (!total || ultima <= 1) { contenedor.style.display = 'none'; return; }
        contenedor.style.display = 'flex';

        const flechaIzq = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>`;
        const flechaDer = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>`;
        const numeros = rangoPaginas(actual, ultima).map(p =>
            p === '...' ? `<span class="pag-puntos">···</span>`
                : `<button type="button" class="pag-num ${p === actual ? 'pag-activo' : ''}" onclick="cargar(${p})">${p}</button>`
        ).join('');

        contenedor.innerHTML = `
            <div class="paginacion-info">Mostrando <strong>${desde ?? 0}–${hasta ?? 0}</strong> de <strong>${total}</strong> compras</div>
            <div class="paginacion-controles">
                <button type="button" class="pag-nav" ${actual === 1 ? 'disabled' : ''} onclick="cargar(${actual - 1})">${flechaIzq} Anterior</button>
                <div class="pag-numeros">${numeros}</div>
                <span class="pag-actual-movil">Página ${actual} de ${ultima}</span>
                <button type="button" class="pag-nav" ${actual === ultima ? 'disabled' : ''} onclick="cargar(${actual + 1})">Siguiente ${flechaDer}</button>
            </div>
        `;
    }

    document.getElementById('buscador').addEventListener('input', () => {
        clearTimeout(temporizadorBusqueda);
        temporizadorBusqueda = setTimeout(() => cargar(1), 350);
    });

    function abrirModal(id) { document.getElementById(id).classList.add('abierto'); }
    function cerrarModal(id) { document.getElementById(id).classList.remove('abierto'); }

    function abrirModalCrear() {
        document.getElementById('modal-compra-titulo').textContent = 'Nueva compra';
        document.getElementById('compra-id').value = '';
        document.getElementById('compra-proveedor').value = '';
        document.getElementById('compra-descripcion').value = '';
        document.getElementById('compra-monto').value = '';
        document.getElementById('compra-tipo').value = 'contado';
        document.getElementById('compra-tipo').disabled = false;
        document.getElementById('compra-fecha').value = new Date().toISOString().slice(0, 10);
        abrirModal('modal-compra');
    }

    function abrirModalEditar(c) {
        document.getElementById('modal-compra-titulo').textContent = 'Editar compra';
        document.getElementById('compra-id').value = c.id;
        document.getElementById('compra-proveedor').value = c.proveedor;
        document.getElementById('compra-descripcion').value = c.descripcion;
        document.getElementById('compra-monto').value = c.monto_total;
        document.getElementById('compra-tipo').value = c.tipo;
        document.getElementById('compra-tipo').disabled = true; // el tipo no se cambia después de creada
        document.getElementById('compra-fecha').value = c.fecha;
        abrirModal('modal-compra');
    }

    function abrirModalAbono(compraId, saldoPendiente) {
        document.getElementById('abono-compra-id').value = compraId;
        document.getElementById('abono-saldo-actual').textContent = formatoColones(saldoPendiente);
        document.getElementById('abono-monto').value = '';
        document.getElementById('abono-monto').max = saldoPendiente;
        document.getElementById('abono-fecha').value = new Date().toISOString().slice(0, 10);
        abrirModal('modal-abono');
    }

    document.getElementById('form-compra').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('compra-id').value;
        const url = id ? `/compras/${id}` : `{{ route('compras.store') }}`;

        const resp = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json', 'Accept': 'application/json',
                'X-CSRF-TOKEN': token, ...(id ? { 'X-HTTP-Method-Override': 'PUT' } : {})
            },
            body: JSON.stringify({
                proveedor_nombre: document.getElementById('compra-proveedor').value,
                descripcion: document.getElementById('compra-descripcion').value,
                monto_total: document.getElementById('compra-monto').value,
                tipo: document.getElementById('compra-tipo').value,
                fecha: document.getElementById('compra-fecha').value,
            })
        });

        const datos = await resp.json();
        if (!resp.ok) {
            const mensaje = datos.errors ? Object.values(datos.errors)[0][0] : (datos.mensaje ?? 'Revisa los datos.');
            Swal.fire('Error', mensaje, 'error');
            return;
        }

        cerrarModal('modal-compra');
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: datos.mensaje, showConfirmButton: false, timer: 2000 });
        cargarProveedores();
        cargar(paginaActual);
    });

    document.getElementById('form-abono').addEventListener('submit', async (e) => {
        e.preventDefault();
        const compraId = document.getElementById('abono-compra-id').value;

        const resp = await fetch(`/compras/${compraId}/pagos`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({
                monto: document.getElementById('abono-monto').value,
                fecha: document.getElementById('abono-fecha').value,
            })
        });

        const datos = await resp.json();
        if (!resp.ok) {
            const mensaje = datos.errors ? Object.values(datos.errors)[0][0] : (datos.mensaje ?? 'Revisa los datos.');
            Swal.fire('Error', mensaje, 'error');
            return;
        }

        cerrarModal('modal-abono');
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: datos.mensaje, showConfirmButton: false, timer: 2000 });
        cargar(paginaActual);
    });

    async function eliminar(id) {
        const confirmar = await Swal.fire({
            title: '¿Eliminar compra?', text: 'Esta acción no se puede deshacer.', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
        });
        if (!confirmar.isConfirmed) return;

        const resp = await fetch(`/compras/${id}`, {
            method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
        });
        const datos = await resp.json();
        if (!resp.ok) { Swal.fire('No se pudo eliminar', datos.mensaje ?? '', 'error'); return; }

        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: datos.mensaje, showConfirmButton: false, timer: 2000 });
        cargar(paginaActual);
    }

    // --- Gestión de proveedores (ver / editar / eliminar) ---

    function pintarListaProveedores(filtro = '') {
        const contenedor = document.getElementById('lista-proveedores');
        const texto = filtro.trim().toLowerCase();

        const proveedores = texto
            ? proveedoresCache.filter(p => p.nombre.toLowerCase().includes(texto))
            : proveedoresCache;

        if (proveedoresCache.length === 0) {
            contenedor.innerHTML = '<p class="sin-resultados-proveedor">No hay proveedores registrados.</p>';
            return;
        }

        if (proveedores.length === 0) {
            contenedor.innerHTML = '<p class="sin-resultados-proveedor">Ningún proveedor coincide con la búsqueda.</p>';
            return;
        }

        contenedor.innerHTML = proveedores.map(p => `
            <div class="fila-proveedor">
                <div class="info">
                    <div class="nombre">${p.nombre}</div>
                    <div class="detalle">${p.telefono ?? 'Sin teléfono'} · ${p.compras_count} compra(s)</div>
                </div>
                <div class="acciones">
                    <button class="btn btn-texto" onclick='abrirEdicionProveedor(${JSON.stringify(p)})'>Editar</button>
                    <button class="btn btn-peligro" onclick="eliminarProveedor(${p.id})">Eliminar</button>
                </div>
            </div>
        `).join('');
    }

    async function abrirModalProveedores() {
        await cargarProveedores();
        document.getElementById('buscador-proveedores').value = '';
        pintarListaProveedores();
        abrirModal('modal-proveedores');
    }

    document.getElementById('buscador-proveedores').addEventListener('input', (e) => {
        pintarListaProveedores(e.target.value);
    });

    function abrirEdicionProveedor(p) {
        document.getElementById('proveedor-editar-id').value = p.id;
        document.getElementById('proveedor-editar-nombre').value = p.nombre;
        document.getElementById('proveedor-editar-telefono').value = p.telefono ?? '';
        cerrarModal('modal-proveedores');
        abrirModal('modal-proveedor-editar');
    }

    function volverAListaProveedores() {
        cerrarModal('modal-proveedor-editar');
        abrirModal('modal-proveedores');
    }

    document.getElementById('form-proveedor-editar').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('proveedor-editar-id').value;

        const resp = await fetch(`/compras/proveedores/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json', 'Accept': 'application/json',
                'X-CSRF-TOKEN': token, 'X-HTTP-Method-Override': 'PUT'
            },
            body: JSON.stringify({
                nombre: document.getElementById('proveedor-editar-nombre').value,
                telefono: document.getElementById('proveedor-editar-telefono').value,
            })
        });

        const datos = await resp.json();
        if (!resp.ok) {
            const mensaje = datos.errors ? Object.values(datos.errors)[0][0] : (datos.mensaje ?? 'Revisa los datos.');
            Swal.fire('Error', mensaje, 'error');
            return;
        }

        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: datos.mensaje, showConfirmButton: false, timer: 2000 });
        await cargarProveedores();
        pintarListaProveedores(document.getElementById('buscador-proveedores').value);
        cerrarModal('modal-proveedor-editar');
        abrirModal('modal-proveedores');
        cargar(paginaActual); // por si el nombre cambió y ya hay compras listadas con ese proveedor
    });

    async function eliminarProveedor(id) {
        const confirmar = await Swal.fire({
            title: '¿Eliminar proveedor?', text: 'Esta acción no se puede deshacer.', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
        });
        if (!confirmar.isConfirmed) return;

        const resp = await fetch(`/compras/proveedores/${id}`, {
            method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
        });
        const datos = await resp.json();
        if (!resp.ok) { Swal.fire('No se pudo eliminar', datos.mensaje ?? '', 'error'); return; }

        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: datos.mensaje, showConfirmButton: false, timer: 2000 });
        await cargarProveedores();
        pintarListaProveedores(document.getElementById('buscador-proveedores').value);
    }

    cargarProveedores();
    cargar();
</script>
@endpush