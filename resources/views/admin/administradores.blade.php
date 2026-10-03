@extends('layouts.app')

@section('titulo', 'Administradores')

@push('estilos')
<style>
    .encabezado-admins {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .encabezado-admins h1 { margin: 0; font-size: 20px; }
    .grupo-botones-encabezado { display: flex; gap: 10px; flex-wrap: wrap; }

    .btn {
        border: none;
        border-radius: 8px;
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
    }
    .btn-primario { background: var(--azul-medio); color: #fff; }
    .btn-secundario { background: #fff; border: 1px solid var(--borde); color: var(--texto); }
    .btn-peligro { background: none; color: #a30000; }
    .btn-texto { background: none; color: var(--azul-medio); }

    #buscador {
        width: 100%;
        max-width: 340px;
        padding: 10px 12px;
        border: 1px solid var(--borde);
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 16px; /* evita zoom automático en iOS */
    }

    /* ===== Modales ===== */
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
    .modal-overlay.abierto { display: flex; }

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

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 20px;
        border-bottom: 1px solid var(--borde);
        flex-shrink: 0;
    }
    .modal-header h2 { margin: 0; font-size: 17px; }
    .modal-cerrar {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--texto-tenue);
        padding: 4px;
        line-height: 0;
    }
    .modal-cerrar svg { width: 20px; height: 20px; }

    .modal-cuerpo {
        padding: 20px;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .campo { margin-bottom: 16px; }
    .campo:last-child { margin-bottom: 0; }
    .campo label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--texto-tenue);
        margin-bottom: 6px;
    }
    .campo input,
    .campo select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--borde);
        border-radius: 8px;
        font-size: 16px;
        font-family: inherit;
        color: var(--texto);
    }
    .campo small { display: block; color: var(--texto-tenue); font-size: 12px; margin-top: 4px; }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 14px 20px;
        border-top: 1px solid var(--borde);
        flex-shrink: 0;
    }

    /* Tarjetas de admin en móvil, en vez de tabla */
    .lista-admins-movil { display: none; }
    .fila-admin-movil {
        background: #fff;
        border-radius: 10px;
        box-shadow: var(--sombra-card);
        padding: 14px;
        margin-bottom: 10px;
    }
    .fila-admin-movil .nombre { font-weight: 600; font-size: 15px; }
    .fila-admin-movil .detalle { font-size: 13px; color: var(--texto-tenue); margin-top: 2px; }
    .fila-admin-movil .acciones {
        display: flex;
        gap: 14px;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid var(--borde);
    }

  

    @media (max-width: 720px) {
        .tabla-desktop { display: none; }
        .lista-admins-movil { display: block; }

        .encabezado-admins { flex-direction: column; align-items: stretch; }
        .grupo-botones-encabezado { flex-direction: column; }
        .grupo-botones-encabezado .btn { width: 100%; }

        #buscador { max-width: 100%; }

        .modal-overlay { padding: 0; align-items: flex-end; }
        .modal-caja {
            max-width: 100%;
            max-height: 92dvh;
            border-radius: 16px 16px 0 0;
        }
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
    <div class="encabezado-admins">
        <h1>Administradores</h1>
        <div class="grupo-botones-encabezado">
            <button class="btn btn-secundario" onclick="abrirModalCuenta()">Mi cuenta</button>
            <button class="btn btn-primario" onclick="abrirModalCrear()">+ Nuevo administrador</button>
        </div>
    </div>

    <input type="text" id="buscador" placeholder="Buscar por nombre o correo...">

    {{-- Tabla para escritorio/tablet --}}
       <div class="tabla-envoltorio tabla-scroll tabla-desktop">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario / correo</th>
                    <th>Rol</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla"></tbody>
        </table>
    </div>

    {{-- Tarjetas para móvil --}}
    <div class="lista-admins-movil" id="lista-movil"></div>

    <div class="paginacion" id="paginacion" style="display:none;"></div>

    {{-- Modal: crear/editar administrador --}}
    <div class="modal-overlay" id="modal-admin">
        <div class="modal-caja">
            <div class="modal-header">
                <h2 id="modal-admin-titulo">Nuevo administrador</h2>
                <button class="modal-cerrar" onclick="cerrarModal('modal-admin')" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="form-admin">
                <div class="modal-cuerpo">
                    <input type="hidden" id="admin-id">

                    <div class="campo">
                        <label>Nombre</label>
                        <input type="text" id="admin-name" required>
                    </div>

                    <div class="campo">
                        <label>Usuario / correo</label>
                        <input type="text" id="admin-email" required>
                    </div>

                    <div class="campo">
                        <label id="label-admin-password">Contraseña</label>
                        <input type="password" id="admin-password">
                        <small id="ayuda-admin-password" style="display:none;">Dejar en blanco para no cambiarla</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secundario" onclick="cerrarModal('modal-admin')">Cancelar</button>
                    <button type="submit" class="btn btn-primario">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: mi cuenta --}}
    <div class="modal-overlay" id="modal-cuenta">
        <div class="modal-caja">
            <div class="modal-header">
                <h2>Mi cuenta</h2>
                <button class="modal-cerrar" onclick="cerrarModal('modal-cuenta')" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="form-cuenta">
                <div class="modal-cuerpo">
                    <div class="campo">
                        <label>Nombre</label>
                        <input type="text" id="cuenta-name" value="{{ auth()->user()->name }}" required>
                    </div>

                    <div class="campo">
                        <label>Usuario / correo</label>
                        <input type="text" id="cuenta-email" value="{{ auth()->user()->email }}" required>
                    </div>

                    <div class="campo">
                        <label>Nueva contraseña</label>
                        <input type="password" id="cuenta-password">
                        <small>Dejar en blanco para no cambiarla</small>
                    </div>

                    <div class="campo">
                        <label>Confirmar nueva contraseña</label>
                        <input type="password" id="cuenta-password-confirm">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secundario" onclick="cerrarModal('modal-cuenta')">Cancelar</button>
                    <button type="submit" class="btn btn-primario">Guardar cambios</button>
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

        async function cargar(pagina = 1) {
        paginaActual = pagina;
        const q = document.getElementById('buscador').value;
        const resp = await fetch(`{{ route('administradores.index') }}?page=${pagina}&q=${encodeURIComponent(q)}`, {
            headers: { 'Accept': 'application/json' }
        });
        const datos = await resp.json();

        // Si se eliminó el último de la última página, retrocede a la que sí existe
        if (datos.data.length === 0 && datos.current_page > 1) {
            return cargar(datos.last_page);
        }

        pintarTabla(datos.data);
        pintarTarjetasMovil(datos.data);
        pintarPaginador('paginacion', datos, 'administradores', p => { cargar(p); irArribaPagina(); });
    }

    function pintarTabla(admins) {
        document.getElementById('cuerpo-tabla').innerHTML = admins.map(a => `
            <tr>
                <td>${a.name}</td>
                <td>${a.email}</td>
                <td>${a.es_superadmin ? 'Superadmin' : 'Admin'}</td>
                <td style="white-space:nowrap;">
                    <button class="btn btn-texto" onclick='abrirModalEditar(${JSON.stringify(a)})'>Editar</button>
                    ${a.es_superadmin ? '' : `<button class="btn btn-peligro" onclick="eliminar(${a.id})">Eliminar</button>`}
                </td>
            </tr>
        `).join('');
    }

    function pintarTarjetasMovil(admins) {
        document.getElementById('lista-movil').innerHTML = admins.map(a => `
            <div class="fila-admin-movil">
                <div class="nombre">${a.name}</div>
                <div class="detalle">${a.email}</div>
                <div class="detalle">${a.es_superadmin ? 'Superadmin' : 'Admin'}</div>
                <div class="acciones">
                    <button class="btn-texto btn" style="padding:0;" onclick='abrirModalEditar(${JSON.stringify(a)})'>Editar</button>
                    ${a.es_superadmin ? '' : `<button class="btn-peligro btn" style="padding:0;" onclick="eliminar(${a.id})">Eliminar</button>`}
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

    function pintarPaginacion(datos) {
        const contenedor = document.getElementById('paginacion');
        const { current_page: actual, last_page: ultima, total, from: desde, to: hasta } = datos;

        if (!total || ultima <= 1) {
            contenedor.style.display = 'none';
            return;
        }
        contenedor.style.display = 'flex';

        const flechaIzq = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>`;
        const flechaDer = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>`;

        const numeros = rangoPaginas(actual, ultima).map(p =>
            p === '...'
                ? `<span class="pag-puntos">···</span>`
                : `<button type="button" class="pag-num ${p === actual ? 'pag-activo' : ''}" onclick="cargar(${p})">${p}</button>`
        ).join('');

        contenedor.innerHTML = `
            <div class="paginacion-info">Mostrando <strong>${desde ?? 0}–${hasta ?? 0}</strong> de <strong>${total}</strong> administradores</div>
            <div class="paginacion-controles">
                <button type="button" class="pag-nav" ${actual === 1 ? 'disabled' : ''} onclick="cargar(${actual - 1})">
                    ${flechaIzq} Anterior
                </button>
                <div class="pag-numeros">${numeros}</div>
                <span class="pag-actual-movil">Página ${actual} de ${ultima}</span>
                <button type="button" class="pag-nav" ${actual === ultima ? 'disabled' : ''} onclick="cargar(${actual + 1})">
                    Siguiente ${flechaDer}
                </button>
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
        document.getElementById('modal-admin-titulo').textContent = 'Nuevo administrador';
        document.getElementById('admin-id').value = '';
        document.getElementById('admin-name').value = '';
        document.getElementById('admin-email').value = '';
        document.getElementById('admin-password').value = '';
        document.getElementById('admin-password').required = true;
        document.getElementById('label-admin-password').textContent = 'Contraseña';
        document.getElementById('ayuda-admin-password').style.display = 'none';
        abrirModal('modal-admin');
    }

    function abrirModalEditar(admin) {
        document.getElementById('modal-admin-titulo').textContent = 'Editar administrador';
        document.getElementById('admin-id').value = admin.id;
        document.getElementById('admin-name').value = admin.name;
        document.getElementById('admin-email').value = admin.email;
        document.getElementById('admin-password').value = '';
        document.getElementById('admin-password').required = false;
        document.getElementById('label-admin-password').textContent = 'Nueva contraseña';
        document.getElementById('ayuda-admin-password').style.display = 'block';
        abrirModal('modal-admin');
    }

    function abrirModalCuenta() {
        document.getElementById('cuenta-password').value = '';
        document.getElementById('cuenta-password-confirm').value = '';
        abrirModal('modal-cuenta');
    }

    document.getElementById('form-admin').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('admin-id').value;
        const url = id ? `/administradores/${id}` : `{{ route('administradores.store') }}`;

        const resp = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                ...(id ? { 'X-HTTP-Method-Override': 'PUT' } : {})
            },
            body: JSON.stringify({
                name: document.getElementById('admin-name').value,
                email: document.getElementById('admin-email').value,
                password: document.getElementById('admin-password').value,
            })
        });

        const datos = await resp.json();

        if (!resp.ok) {
            const mensaje = datos.errors ? Object.values(datos.errors)[0][0] : (datos.mensaje ?? 'Revisa los datos.');
            Swal.fire('Error', mensaje, 'error');
            return;
        }

        cerrarModal('modal-admin');
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: datos.mensaje, showConfirmButton: false, timer: 2000 });
        cargar(paginaActual);
    });

    document.getElementById('form-cuenta').addEventListener('submit', async (e) => {
        e.preventDefault();

        const password = document.getElementById('cuenta-password').value;
        const passwordConfirm = document.getElementById('cuenta-password-confirm').value;

        if (password && password !== passwordConfirm) {
            Swal.fire('Error', 'Las contraseñas no coinciden.', 'error');
            return;
        }

        const resp = await fetch(`{{ route('cuenta.actualizar') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-HTTP-Method-Override': 'PUT'
            },
            body: JSON.stringify({
                name: document.getElementById('cuenta-name').value,
                email: document.getElementById('cuenta-email').value,
                password: password,
                password_confirmation: passwordConfirm,
            })
        });

        const datos = await resp.json();

        if (!resp.ok) {
            const mensaje = datos.errors ? Object.values(datos.errors)[0][0] : (datos.mensaje ?? 'Revisa los datos.');
            Swal.fire('Error', mensaje, 'error');
            return;
        }

        cerrarModal('modal-cuenta');
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: datos.mensaje, showConfirmButton: false, timer: 2000 });
    });

    async function eliminar(id) {
        const confirmar = await Swal.fire({
            title: '¿Eliminar administrador?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirmar.isConfirmed) return;

        const resp = await fetch(`/administradores/${id}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
        });

        const datos = await resp.json();

        if (!resp.ok) {
            Swal.fire('No se pudo eliminar', datos.mensaje ?? '', 'error');
            return;
        }

        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: datos.mensaje, showConfirmButton: false, timer: 2000 });
        cargar(paginaActual);
    }

    cargar();
</script>
@endpush