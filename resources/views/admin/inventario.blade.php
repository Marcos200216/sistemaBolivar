@extends('layouts.app')

@section('titulo', 'Inventario')

@push('estilos')
<style>
    .btn {
        border: none; border-radius: 8px; padding: 10px 16px; font-size: 14px;
        font-weight: 600; cursor: pointer; white-space: nowrap;
    }
    .btn-primario { background: var(--azul-medio); color: #fff; }
    .btn-secundario { background: #fff; border: 1px solid var(--borde); color: var(--texto); }
    .btn-peligro { background: none; color: #a30000; border: 1px solid var(--borde); }
    .btn-advertencia { background: #fff7e6; color: #8a5a00; border: 1px solid #f3d999; }
    .btn-exito { background: #16794f; color: #fff; }
    .btn:disabled { opacity: .4; cursor: default; }

    .badge {
        display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px;
        border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap;
    }
    .badge-verde { background: #e7f7ee; color: #16794f; }
    .badge-rojo { background: #fdecec; color: #a30000; }
    .badge-ambar { background: #fff7e6; color: #8a5a00; }
    .badge-gris { background: #eef1f6; color: var(--texto-tenue); }

    .barra-inventario {
        display: flex; justify-content: space-between; align-items: center;
        gap: 12px; flex-wrap: wrap; margin-bottom: 18px;
    }
    .barra-inventario h1 {
        margin: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        font-weight: 700; font-size: 22px; color: var(--azul-oscuro);
    }
    #tope-inventario, #tope-tabla-inventario { scroll-margin-top: 20px; }

    .resumen-canal { font-size: 13px; color: var(--texto-tenue); margin-bottom: 14px; }
    .resumen-canal strong { color: var(--texto); }

    /* ===== Barra de filtros ===== */
    .barra-filtros { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
    .barra-filtros input, .barra-filtros select {
        padding: 10px 12px; border: 1px solid var(--borde); border-radius: 8px;
        font-size: max(13.5px, 16px); font-family: inherit; color: var(--texto); background: #fff;
    }
    .barra-filtros input { flex: 1; min-width: 200px; }
    .barra-filtros select { min-width: 180px; }

    /* ===== Tabla (desktop / tablet) — idéntico patrón a Clientes ===== */
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
        min-width: 760px;
    }
    .tabla-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 1;
    }
    .fila-inactiva-tabla { opacity: .55; }

    .btn-icono {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border: none; background: none; cursor: pointer;
        border-radius: 7px; transition: background .15s, color .15s;
    }
    .btn-icono svg { width: 16px; height: 16px; }
    .btn-editar { color: var(--azul-medio); }
    .btn-editar:hover { background: #EAF1FD; }
    .acciones { white-space: nowrap; display: flex; gap: 4px; }

    .nombre-prod-tabla { font-weight: 600; }
    .codigo-prod-tabla { font-family: monospace; color: var(--texto-tenue); font-size: 13px; }

    /* ===== Tarjetas (solo móvil) ===== */
    .lista-productos-movil { display: flex; flex-direction: column; gap: 10px; }

    .tarjeta-producto {
        background: var(--superficie, #fff); border: 1px solid var(--borde); border-radius: 13px;
        box-shadow: var(--sombra-card); padding: 14px 14px 10px; cursor: pointer;
    }
    .tarjeta-producto.inactivo { opacity: .55; }
    .tarjeta-producto .fila-top {
        display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 8px;
    }
    .tarjeta-producto .nombre-producto {
        font-size: 15px; font-weight: 700; color: var(--texto); overflow-wrap: anywhere;
    }
    .tarjeta-producto .codigo-producto {
        display: block; margin-top: 2px; font-size: 11.5px; color: var(--texto-tenue);
    }
    .tarjeta-producto .precio-producto { font-weight: 700; font-size: 14px; white-space: nowrap; flex-shrink: 0; }
    .tarjeta-producto .datos-producto {
        display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; margin: 8px 0 10px;
        font-size: 13px; color: var(--texto-tenue);
    }
    .tarjeta-producto .fila-badges {
        display: flex; justify-content: flex-end; gap: 6px; padding-top: 8px; border-top: 1px solid #F0F3F8;
    }

    .estado-vacio { text-align: center; padding: 30px 16px; color: var(--texto-tenue); font-size: 14px; }

    .solo-movil { display: none; }

    /* ===== Paginación — idéntico a Clientes ===== */
    .paginacion {
        display: flex; align-items: center; justify-content: space-between; gap: 14px;
        flex-wrap: wrap; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--borde);
    }
    .paginacion-info { font-size: 13px; color: var(--texto-tenue); white-space: nowrap; }
    .paginacion-info strong { color: var(--texto); font-weight: 600; }
    .paginacion-controles { display: flex; align-items: center; gap: 10px; }
    .pag-nav {
        display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px;
        border-radius: 9px; border: 1px solid var(--borde); background: #fff; color: var(--texto);
        font-size: 13px; font-weight: 600; font-family: inherit; cursor: pointer;
        transition: background .15s, border-color .15s;
    }
    .pag-nav svg { width: 14px; height: 14px; }
    .pag-nav:hover:not(:disabled) { background: #F5F7FA; border-color: var(--borde-hover, var(--borde)); }
    .pag-nav:disabled { color: var(--texto-400, var(--texto-tenue)); cursor: default; background: #fff; }
    .pag-numeros { display: flex; align-items: center; gap: 2px; }
    .pag-num {
        display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px;
        padding: 0 4px; border-radius: 8px; border: none; background: none; cursor: pointer;
        color: var(--texto-tenue); font-size: 13px; font-weight: 500; font-family: inherit;
    }
    .pag-num:hover { background: #EAF1FD; color: var(--texto); }
    .pag-num.pag-activo { background: var(--azul-medio); color: #fff; font-weight: 600; }
    .pag-puntos {
        display: inline-flex; align-items: center; justify-content: center; min-width: 22px; height: 34px;
        color: var(--texto-400, var(--texto-tenue)); font-size: 13px;
    }
    .pag-actual-movil { display: none; font-size: 13px; font-weight: 600; color: var(--texto); }

    /* ===== Modal ===== */
    .modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(8, 40, 95, .5);
        z-index: 60; align-items: center; justify-content: center; padding: 16px;
    }
    .modal-overlay.abierto { display: flex; }
    .modal-caja {
        background: #fff; border-radius: 14px; width: 100%; max-width: 520px;
        max-height: 90dvh; display: flex; flex-direction: column; overflow: hidden;
    }
    .modal-header {
        display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;
        padding: 18px 20px; border-bottom: 1px solid var(--borde); flex-shrink: 0;
    }
    .modal-header h2 { margin: 0; font-size: 17px; }
    .modal-header p { margin: 3px 0 0; font-size: 13px; color: var(--texto-tenue); }
    .modal-cerrar { background: none; border: none; cursor: pointer; color: var(--texto-tenue); padding: 4px; line-height: 0; flex-shrink: 0; }
    .modal-cerrar svg { width: 20px; height: 20px; }
    .modal-cuerpo { padding: 20px; overflow-y: auto; -webkit-overflow-scrolling: touch; }

    /* ===== Footer del modal: 4 botones del mismo tamaño ===== */
    .modal-footer {
        display: flex;
        gap: 10px;
        padding: 14px 20px;
        border-top: 1px solid var(--borde);
        flex-shrink: 0;
    }
    .modal-footer .btn {
        flex: 1;
        text-align: center;
        padding: 11px 10px;
    }

    .fila-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .campo { margin-bottom: 16px; }
    .campo:last-child { margin-bottom: 0; }
    .campo label { display: block; font-size: 13px; font-weight: 600; color: var(--texto-tenue); margin-bottom: 6px; }
    .campo input {
        width: 100%; padding: 10px 12px; border: 1px solid var(--borde); border-radius: 8px;
        font-size: max(13.5px, 16px); font-family: inherit; color: var(--texto);
    }

    .titulo-seccion-modal {
        font-size: 13px; font-weight: 700; color: var(--texto-tenue); text-transform: uppercase;
        letter-spacing: .03em; margin: 22px 0 10px;
    }
    .lista-variantes { display: flex; flex-direction: column; gap: 8px; }
    .fila-variante {
        display: flex; align-items: center; gap: 12px; padding: 10px 12px;
        border: 1px solid var(--borde); border-radius: 8px;
    }
    .fila-variante .etiqueta { flex: 1; font-size: 14px; font-weight: 600; }
    .fila-variante input {
        width: 90px; padding: 8px 10px; border: 1px solid var(--borde); border-radius: 8px;
        font-size: 15px; font-family: inherit; text-align: center;
    }

    @media (max-width: 720px) {
        .solo-movil { display: flex; }
        .solo-desktop { display: none; }

        .barra-inventario h1 { font-size: 19px; }
        .barra-filtros { flex-direction: column; }
        .barra-filtros select { width: 100%; }
        .fila-2 { grid-template-columns: 1fr; }
        .modal-overlay { padding: 0; align-items: flex-end; }
        .modal-caja { max-width: 100%; max-height: 92dvh; border-radius: 16px 16px 0 0; }

        /* Grid 2x2 en vez de columna de 4 */
        .modal-footer {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .modal-footer .btn {
            width: 100%;
            padding: 12px 8px;
        }

        .paginacion-info { display: none; }
        .paginacion-controles { width: 100%; justify-content: space-between; }
        .pag-numeros { display: none; }
        .pag-actual-movil { display: inline-flex; }
        .pag-nav { flex: 1; justify-content: center; }
    }

    @media (prefers-reduced-motion: reduce) {
        * { transition: none !important; }
    }
</style>
@endpush

@section('contenido')

<div class="barra-inventario" id="tope-inventario">
    <h1>Inventario</h1>
</div>

<div class="resumen-canal" id="resumen-canal"></div>

<div class="barra-filtros">
    <input type="text" id="buscador" placeholder="Buscar por nombre o código...">
    <select id="filtro-subcategoria">
        <option value="">Todas las subcategorías</option>
    </select>
</div>

{{-- Tabla: visible en tablet/desktop — mismo patrón que Clientes --}}
<div class="tabla-scroll solo-desktop" id="tope-tabla-inventario">
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Subcategoría</th>
                <th>Precio</th>
                <th id="th-stock">Stock</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="cuerpo-tabla-inventario">
            <tr><td colspan="7" class="estado-vacio">Cargando...</td></tr>
        </tbody>
    </table>
</div>

{{-- Tarjetas: visibles solo en móvil --}}
<div class="lista-productos-movil solo-movil" id="lista-productos-movil">
    <div class="estado-vacio">Cargando...</div>
</div>

<div class="paginacion" id="paginacion" style="display:none;"></div>

{{-- ===================== MODAL: editar producto ===================== --}}
<div class="modal-overlay" id="modal-producto">
    <div class="modal-caja">
        <div class="modal-header">
            <div>
                <h2>Editar producto</h2>
                <p id="modal-producto-subcategoria"></p>
            </div>
            <button class="modal-cerrar" onclick="cerrarModal()" aria-label="Cerrar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="modal-cuerpo">
            <div class="campo">
                <label>Nombre</label>
                <input type="text" id="input-nombre" placeholder="Nombre del producto">
            </div>
            <div class="fila-2">
                <div class="campo">
                    <label>Código</label>
                    <input type="text" id="input-codigo" placeholder="Ej. 0092" maxlength="50">
                </div>
                <div class="campo">
                    <label>Precio (opcional)</label>
                    <input type="number" id="input-precio" min="0" step="0.01" placeholder="0.00">
                </div>
            </div>

            <div id="bloque-stock">
                <div class="titulo-seccion-modal">Stock por variante</div>
                <div class="lista-variantes" id="lista-variantes-modal"></div>
            </div>
        </div>

        {{-- Orden: Cancelar, Desactivar, Eliminar permanente, Guardar — mismo tamaño, la acción principal queda a la derecha --}}
        <div class="modal-footer">
            <button type="button" class="btn btn-secundario" onclick="cerrarModal()">Cancelar</button>
            <button type="button" class="btn btn-advertencia" id="btn-toggle-activo" onclick="alternarActivo()"></button>
            <button type="button" class="btn btn-peligro" onclick="eliminarProducto()">Eliminar</button>
            <button type="button" class="btn btn-primario" onclick="guardarProducto()">Guardar</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const token = document.querySelector('meta[name="csrf-token"]').content;

    let paginaActual = 1;
    let productoEditando = null;
    let sinStock = false; // true en Guana (stock infinito)
    let timeoutBusqueda = null;

    const iconoLapiz =
        `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>`;

    function formatoColones(monto) {
        if (monto === null || monto === undefined) return '—';
        return '₡' + Number(monto).toLocaleString('es-CR', { minimumFractionDigits: 2 });
    }

    function abrirModal() { document.getElementById('modal-producto').classList.add('abierto'); }
    function cerrarModal() { document.getElementById('modal-producto').classList.remove('abierto'); }

    /* ==================== SCROLL AL TOPE AL CAMBIAR DE PÁGINA (idéntico a Clientes) ==================== */

    function irArribaInventario() {
        const esMovil = window.matchMedia('(max-width: 720px)').matches;
        const tope = document.getElementById(esMovil ? 'tope-inventario' : 'tope-tabla-inventario');

        const contenedorTabla = document.querySelector('.tabla-scroll');
        if (contenedorTabla) contenedorTabla.scrollTop = 0;

        if (!tope) return;
        const sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        tope.scrollIntoView({ behavior: sinMovimiento ? 'auto' : 'smooth', block: 'start' });
    }

    /* ==================== CARGA / FILTROS ==================== */

    async function cargarInventario(pagina = 1, irAlTope = false) {
        paginaActual = pagina;
        const busqueda = document.getElementById('buscador').value.trim();
        const subcategoriaId = document.getElementById('filtro-subcategoria').value;

        const params = new URLSearchParams({ page: pagina });
        if (busqueda) params.set('busqueda', busqueda);
        if (subcategoriaId) params.set('subcategoria_id', subcategoriaId);

        const resp = await fetch(`{{ route('inventario.index') }}?${params.toString()}`, {
            headers: { 'Accept': 'application/json' }
        });
        const datos = await resp.json();

        // Guana (canal distinto de 'normal') tiene stock infinito: se oculta todo lo de stock
        sinStock = datos.canal !== 'normal';
        document.getElementById('th-stock').style.display = sinStock ? 'none' : '';

        pintarResumenCanal(datos.categorias);
        pintarFiltroSubcategorias(datos.categorias, subcategoriaId);
        pintarTabla(datos.productos.data);
        pintarTarjetasMovil(datos.productos.data);
        pintarPaginacion(datos.productos);
        if (irAlTope) irArribaInventario();
    }

    function pintarResumenCanal(categorias) {
        const totalSubcategorias = categorias.reduce((acc, c) => acc + c.subcategorias.length, 0);
        document.getElementById('resumen-canal').innerHTML =
            `Catálogo de esta sucursal · <strong>${categorias.length}</strong> categorías, <strong>${totalSubcategorias}</strong> subcategorías`;
    }

    function pintarFiltroSubcategorias(categorias, seleccionActual) {
        const select = document.getElementById('filtro-subcategoria');
        const opciones = categorias.map(c => `
            <optgroup label="${c.nombre}">
                ${c.subcategorias.map(s => `<option value="${s.id}">${s.nombre}</option>`).join('')}
            </optgroup>
        `).join('');
        select.innerHTML = `<option value="">Todas las subcategorías</option>${opciones}`;
        select.value = seleccionActual || '';
    }

    function stockTotal(producto) {
        return producto.variantes.reduce((acc, v) => acc + Number(v.stock), 0);
    }

    function badgeStock(stock) {
        if (stock <= 0) return `<span class="badge badge-rojo">Agotado</span>`;
        if (stock <= 5) return `<span class="badge badge-ambar">${stock} und.</span>`;
        return `<span class="badge badge-verde">${stock} und.</span>`;
    }

    /* ==================== PINTAR TABLA (desktop) ==================== */

    function pintarTabla(productos) {
        window._productosCache = productos;
        const cuerpo = document.getElementById('cuerpo-tabla-inventario');

        if (productos.length === 0) {
            cuerpo.innerHTML = `<tr><td colspan="${sinStock ? 6 : 7}" class="estado-vacio">No se encontraron productos con ese filtro.</td></tr>`;
            return;
        }

        cuerpo.innerHTML = productos.map(p => `
            <tr class="${!p.activo ? 'fila-inactiva-tabla' : ''}">
                <td class="codigo-prod-tabla">${p.codigo ?? '—'}</td>
                <td class="nombre-prod-tabla">${p.nombre}</td>
                <td>${p.subcategoria?.nombre ?? '—'}</td>
                <td>${formatoColones(p.precio)}</td>
                ${sinStock ? '' : `<td>${badgeStock(stockTotal(p))}</td>`}
                <td>${p.activo ? '<span class="badge badge-verde">Activo</span>' : '<span class="badge badge-gris">Inactivo</span>'}</td>
                <td class="acciones">
                    <button type="button" class="btn-icono btn-editar" title="Editar" onclick="abrirModalProducto(${p.id})">${iconoLapiz}</button>
                </td>
            </tr>
        `).join('');
    }

    /* ==================== PINTAR TARJETAS (móvil) ==================== */

    function pintarTarjetasMovil(productos) {
        const lista = document.getElementById('lista-productos-movil');

        if (productos.length === 0) {
            lista.innerHTML = `<div class="estado-vacio">No se encontraron productos con ese filtro.</div>`;
            return;
        }

        lista.innerHTML = productos.map(p => `
            <div class="tarjeta-producto ${!p.activo ? 'inactivo' : ''}" onclick="abrirModalProducto(${p.id})">
                <div class="fila-top">
                    <div>
                        <div class="nombre-producto">${p.nombre}</div>
                        <span class="codigo-producto">${p.codigo ?? 'sin código'}</span>
                    </div>
                    <div class="precio-producto">${formatoColones(p.precio)}</div>
                </div>
                <div class="datos-producto">
                    <span>${p.subcategoria?.nombre ?? '—'}</span>
                </div>
                <div class="fila-badges">
                    ${!p.activo ? '<span class="badge badge-gris">Inactivo</span>' : ''}
                    ${sinStock ? '' : badgeStock(stockTotal(p))}
                </div>
            </div>
        `).join('');
    }

    function rangoPaginas(actual, ultima) {
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

    function pintarPaginacion(pag) {
        const cont = document.getElementById('paginacion');

        if (!pag.total || pag.last_page <= 1) {
            cont.style.display = 'none';
            return;
        }
        cont.style.display = 'flex';

        const flechaIzq = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>`;
        const flechaDer = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>`;

        const numeros = rangoPaginas(pag.current_page, pag.last_page).map(p =>
            p === '...'
                ? `<span class="pag-puntos">···</span>`
                : `<button type="button" class="pag-num ${p === pag.current_page ? 'pag-activo' : ''}" onclick="cargarInventario(${p}, true)">${p}</button>`
        ).join('');

        cont.innerHTML = `
            <div class="paginacion-info">Mostrando <strong>${pag.from ?? 0}–${pag.to ?? 0}</strong> de <strong>${pag.total}</strong> productos</div>
            <div class="paginacion-controles">
                <button type="button" class="pag-nav" ${pag.current_page === 1 ? 'disabled' : ''} onclick="cargarInventario(${pag.current_page - 1}, true)">
                    ${flechaIzq} Anterior
                </button>
                <div class="pag-numeros">${numeros}</div>
                <span class="pag-actual-movil">Página ${pag.current_page} de ${pag.last_page}</span>
                <button type="button" class="pag-nav" ${pag.current_page === pag.last_page ? 'disabled' : ''} onclick="cargarInventario(${pag.current_page + 1}, true)">
                    Siguiente ${flechaDer}
                </button>
            </div>
        `;
    }

    /* ==================== MODAL EDITAR PRODUCTO ==================== */

    function abrirModalProducto(id) {
        productoEditando = window._productosCache.find(p => p.id === id);
        if (!productoEditando) return;

        document.getElementById('modal-producto-subcategoria').textContent = productoEditando.subcategoria?.nombre ?? '';
        document.getElementById('input-nombre').value = productoEditando.nombre ?? '';
        document.getElementById('input-codigo').value = productoEditando.codigo ?? '';
        document.getElementById('input-precio').value = productoEditando.precio ?? '';

        const btnToggle = document.getElementById('btn-toggle-activo');
        btnToggle.textContent = productoEditando.activo ? 'Desactivar' : 'Activar';
        btnToggle.className = 'btn ' + (productoEditando.activo ? 'btn-advertencia' : 'btn-exito');

        const soloUnaVarianteSimple = productoEditando.variantes.length === 1
            && !productoEditando.variantes[0].talla
            && !productoEditando.variantes[0].color;

        document.getElementById('lista-variantes-modal').innerHTML = productoEditando.variantes.map(v => {
            const etiqueta = soloUnaVarianteSimple
                ? 'Stock'
                : [v.talla, v.color].filter(Boolean).join(' · ') || 'Variante';
            return `
                <div class="fila-variante">
                    <div class="etiqueta">${etiqueta}</div>
                    <input type="number" min="0" step="1" value="${v.stock}" data-variante-id="${v.id}">
                </div>
            `;
        }).join('');

        document.getElementById('bloque-stock').style.display = sinStock ? 'none' : '';

        abrirModal();
    }

    async function guardarProducto() {
        if (!productoEditando) return;

        const nombre = document.getElementById('input-nombre').value.trim();
        const codigo = document.getElementById('input-codigo').value.trim();
        const precio = document.getElementById('input-precio').value;

        if (!nombre) {
            Swal.fire('Nombre requerido', 'El producto necesita un nombre.', 'error');
            return;
        }
        if (precio !== '' && Number(precio) < 0) {
            Swal.fire('Precio inválido', 'El precio no puede ser negativo.', 'error');
            return;
        }

        // En Guana no se manda stock (el servidor además lo ignora)
        const variantes = sinStock ? [] : Array.from(document.querySelectorAll('#lista-variantes-modal input')).map(input => ({
            id: Number(input.dataset.varianteId),
            stock: Number(input.value),
        }));

        const resp = await fetch(`/inventario/productos/${productoEditando.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json', 'Accept': 'application/json',
                'X-CSRF-TOKEN': token, 'X-HTTP-Method-Override': 'PUT'
            },
            body: JSON.stringify({ nombre, codigo: codigo || null, precio: precio === '' ? null : precio, variantes })
        });

        if (!resp.ok) {
            const datos = await resp.json();
            const mensaje = datos.errors ? Object.values(datos.errors).flat().join(' ') : (datos.mensaje ?? 'No se pudo guardar.');
            Swal.fire('Error', mensaje, 'error');
            return;
        }

        cerrarModal();
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Producto actualizado', showConfirmButton: false, timer: 2000 });
        cargarInventario(paginaActual);
    }

    async function alternarActivo() {
        if (!productoEditando) return;

        const resp = await fetch(`/inventario/productos/${productoEditando.id}/activo`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-HTTP-Method-Override': 'PUT'
            }
        });

        if (!resp.ok) {
            Swal.fire('Error', 'No se pudo cambiar el estado del producto.', 'error');
            return;
        }

        const actualizado = await resp.json();
        productoEditando.activo = actualizado.activo;
        cerrarModal();
        Swal.fire({
            toast: true, position: 'top-end', icon: 'success',
            title: actualizado.activo ? 'Producto activado' : 'Producto desactivado',
            showConfirmButton: false, timer: 2000
        });
        cargarInventario(paginaActual);
    }

    async function eliminarProducto() {
        if (!productoEditando) return;

        const confirmar = await Swal.fire({
            title: '¿Eliminar este producto permanentemente?',
            html: `Esta acción <strong>no se puede deshacer</strong>. Se borra "${productoEditando.nombre}" y todas sus variantes.`,
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
            confirmButtonColor: '#a30000',
        });
        if (!confirmar.isConfirmed) return;

        const resp = await fetch(`/inventario/productos/${productoEditando.id}`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-HTTP-Method-Override': 'DELETE'
            }
        });

        const datos = await resp.json();

        if (!resp.ok) {
            Swal.fire('No se puede eliminar', datos.mensaje ?? '', 'error');
            return;
        }

        cerrarModal();
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Producto eliminado', showConfirmButton: false, timer: 2000 });
        cargarInventario(paginaActual);
    }

    /* ==================== EVENTOS ==================== */

    document.getElementById('buscador').addEventListener('input', () => {
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(() => cargarInventario(1), 400);
    });

    document.getElementById('filtro-subcategoria').addEventListener('change', () => cargarInventario(1));

    cargarInventario();
</script>
@endpush