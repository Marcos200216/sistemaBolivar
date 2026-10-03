@extends('layouts.app')

@section('titulo', 'Gastos')

@push('estilos')
<style>
    .encabezado-gastos {
        display: flex; justify-content: space-between; align-items: center;
        gap: 10px; flex-wrap: wrap; margin-bottom: 16px;
    }
    .encabezado-gastos h1 { margin: 0; font-size: 20px; }

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
    .campo input {
        width: 100%; padding: 10px 12px; border: 1px solid var(--borde); border-radius: 8px;
        font-size: 16px; font-family: inherit; color: var(--texto);
    }
    .modal-footer {
        display: flex; justify-content: flex-end; gap: 10px; padding: 14px 20px;
        border-top: 1px solid var(--borde); flex-shrink: 0;
    }

    .sugerencias-categoria {
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
    .sugerencias-categoria.abierto { display: block; }
    .sugerencia-item {
        padding: 10px 12px;
        font-size: 14px;
        cursor: pointer;
    }
    .sugerencia-item:hover { background: var(--azul-50); }

    .lista-gastos-movil { display: none; }
    .fila-gasto-movil {
        background: #fff; border-radius: 10px; box-shadow: var(--sombra-card);
        padding: 14px; margin-bottom: 10px;
    }
    .fila-gasto-movil .categoria { font-weight: 600; font-size: 15px; }
    .fila-gasto-movil .detalle { font-size: 13px; color: var(--texto-tenue); margin-top: 2px; }
    .fila-gasto-movil .linea-monto { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
    .fila-gasto-movil .acciones {
        display: flex; gap: 14px; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--borde);
    }

   

    @media (max-width: 720px) {
        .tabla-desktop { display: none; }
        .lista-gastos-movil { display: block; }
        .encabezado-gastos { flex-direction: column; align-items: stretch; }
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
    <div class="encabezado-gastos">
        <h1>Gastos</h1>
        <button class="btn btn-primario" onclick="abrirModalCrear()">+ Nuevo gasto</button>
    </div>

    <input type="text" id="buscador" placeholder="Buscar por categoría o descripción...">

        <div class="tabla-envoltorio tabla-scroll tabla-desktop">
        <table>
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla"></tbody>
        </table>
    </div>

    <div class="lista-gastos-movil" id="lista-movil"></div>

    <div class="paginacion" id="paginacion" style="display:none;"></div>

    {{-- Modal: crear/editar gasto --}}
    <div class="modal-overlay" id="modal-gasto">
        <div class="modal-caja">
            <div class="modal-header">
                <h2 id="modal-gasto-titulo">Nuevo gasto</h2>
                <button class="modal-cerrar" onclick="cerrarModal('modal-gasto')" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="form-gasto">
                <div class="modal-cuerpo">
                    <input type="hidden" id="gasto-id">

                    <div class="campo" style="position: relative;">
                        <label>Categoría</label>
                        <input type="text" id="gasto-categoria" autocomplete="off" required placeholder="Ej. Alquiler, planilla, servicios...">
                        <div id="sugerencias-categoria" class="sugerencias-categoria"></div>
                    </div>

                    <div class="campo">
                        <label>Descripción</label>
                        <input type="text" id="gasto-descripcion" required>
                    </div>

                    <div class="campo">
                        <label>Monto</label>
                        <input type="number" id="gasto-monto" step="0.01" min="0.01" required>
                    </div>

                    <div class="campo">
                        <label>Fecha</label>
                        <input type="date" id="gasto-fecha" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secundario" onclick="cerrarModal('modal-gasto')">Cancelar</button>
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
    let categoriasCache = [];

    function formatoColones(monto) {
        return '₡' + Number(monto).toLocaleString('es-CR', { minimumFractionDigits: 2 });
    }

    async function cargarCategorias() {
        const resp = await fetch(`{{ route('gastos.categorias') }}`, { headers: { 'Accept': 'application/json' } });
        categoriasCache = await resp.json();
    }

    function mostrarSugerenciasCategoria(filtro) {
        const contenedor = document.getElementById('sugerencias-categoria');
        const texto = filtro.trim().toLowerCase();

        const coincidencias = texto
            ? categoriasCache.filter(c => c.toLowerCase().includes(texto))
            : categoriasCache;

        if (coincidencias.length === 0) {
            contenedor.classList.remove('abierto');
            contenedor.innerHTML = '';
            return;
        }

        contenedor.innerHTML = coincidencias.slice(0, 8).map(c =>
            `<div class="sugerencia-item" onclick="elegirCategoria('${c.replace(/'/g, "\\'")}')">${c}</div>`
        ).join('');
        contenedor.classList.add('abierto');
    }

    function elegirCategoria(nombre) {
        document.getElementById('gasto-categoria').value = nombre;
        document.getElementById('sugerencias-categoria').classList.remove('abierto');
    }

    document.getElementById('gasto-categoria').addEventListener('input', (e) => {
        mostrarSugerenciasCategoria(e.target.value);
    });

    document.getElementById('gasto-categoria').addEventListener('focus', (e) => {
        mostrarSugerenciasCategoria(e.target.value);
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.campo')) {
            document.getElementById('sugerencias-categoria')?.classList.remove('abierto');
        }
    });

        async function cargar(pagina = 1) {
        paginaActual = pagina;
        const q = document.getElementById('buscador').value;
        const resp = await fetch(`{{ route('gastos.index') }}?page=${pagina}&q=${encodeURIComponent(q)}`, {
            headers: { 'Accept': 'application/json' }
        });
        const datos = await resp.json();

        // Si se eliminó el último de la última página, retrocede a la que sí existe
        if (datos.data.length === 0 && datos.current_page > 1) {
            return cargar(datos.last_page);
        }

        pintarTabla(datos.data);
        pintarTarjetasMovil(datos.data);
        pintarPaginador('paginacion', datos, 'gastos', p => { cargar(p); irArribaPagina(); });
    }

    function filaAcciones(g) {
        return `
            <button class="btn btn-texto" onclick='abrirModalEditar(${JSON.stringify(g)})'>Editar</button>
            <button class="btn btn-peligro" onclick="eliminar(${g.id})">Eliminar</button>
        `;
    }

    function pintarTabla(gastos) {
        document.getElementById('cuerpo-tabla').innerHTML = gastos.map(g => `
            <tr>
                <td>${g.categoria}</td>
                <td>${g.descripcion}</td>
                <td>${g.fecha}</td>
                <td>${formatoColones(g.monto)}</td>
                <td style="white-space:nowrap;">${filaAcciones(g)}</td>
            </tr>
        `).join('');
    }

    function pintarTarjetasMovil(gastos) {
        document.getElementById('lista-movil').innerHTML = gastos.map(g => `
            <div class="fila-gasto-movil">
                <div class="categoria">${g.categoria}</div>
                <div class="detalle">${g.descripcion}</div>
                <div class="detalle">${g.fecha}</div>
                <div class="linea-monto">
                    <strong>${formatoColones(g.monto)}</strong>
                </div>
                <div class="acciones">${filaAcciones(g)}</div>
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
            <div class="paginacion-info">Mostrando <strong>${desde ?? 0}–${hasta ?? 0}</strong> de <strong>${total}</strong> gastos</div>
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
        document.getElementById('modal-gasto-titulo').textContent = 'Nuevo gasto';
        document.getElementById('gasto-id').value = '';
        document.getElementById('gasto-categoria').value = '';
        document.getElementById('gasto-descripcion').value = '';
        document.getElementById('gasto-monto').value = '';
        document.getElementById('gasto-fecha').value = new Date().toISOString().slice(0, 10);
        abrirModal('modal-gasto');
    }

    function abrirModalEditar(g) {
        document.getElementById('modal-gasto-titulo').textContent = 'Editar gasto';
        document.getElementById('gasto-id').value = g.id;
        document.getElementById('gasto-categoria').value = g.categoria;
        document.getElementById('gasto-descripcion').value = g.descripcion;
        document.getElementById('gasto-monto').value = g.monto;
        document.getElementById('gasto-fecha').value = g.fecha;
        abrirModal('modal-gasto');
    }

    document.getElementById('form-gasto').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('gasto-id').value;
        const url = id ? `/gastos/${id}` : `{{ route('gastos.store') }}`;

        const resp = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json', 'Accept': 'application/json',
                'X-CSRF-TOKEN': token, ...(id ? { 'X-HTTP-Method-Override': 'PUT' } : {})
            },
            body: JSON.stringify({
                categoria: document.getElementById('gasto-categoria').value,
                descripcion: document.getElementById('gasto-descripcion').value,
                monto: document.getElementById('gasto-monto').value,
                fecha: document.getElementById('gasto-fecha').value,
            })
        });

        const datos = await resp.json();
        if (!resp.ok) {
            const mensaje = datos.errors ? Object.values(datos.errors)[0][0] : (datos.mensaje ?? 'Revisa los datos.');
            Swal.fire('Error', mensaje, 'error');
            return;
        }

        cerrarModal('modal-gasto');
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: datos.mensaje, showConfirmButton: false, timer: 2000 });
        cargarCategorias();
        cargar(paginaActual);
    });

    async function eliminar(id) {
        const confirmar = await Swal.fire({
            title: '¿Eliminar gasto?', text: 'Esta acción no se puede deshacer.', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
        });
        if (!confirmar.isConfirmed) return;

        const resp = await fetch(`/gastos/${id}`, {
            method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
        });
        const datos = await resp.json();
        if (!resp.ok) { Swal.fire('No se pudo eliminar', datos.mensaje ?? '', 'error'); return; }

        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: datos.mensaje, showConfirmButton: false, timer: 2000 });
        cargar(paginaActual);
    }

    cargarCategorias();
    cargar();
</script>
@endpush