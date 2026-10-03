{{-- resources/views/admin/generarpdf.blade.php --}}
@extends('layouts.app')

@section('titulo', 'Generar PDF')

@push('estilos')
<style>
    .encabezado-pdf { margin-bottom: 20px; }
    .encabezado-pdf h1 { margin: 0 0 4px; font-size: 22px; font-weight: 700; color: var(--azul-oscuro); }
    .encabezado-pdf p { margin: 0; font-size: 13.5px; color: var(--texto-tenue); }

    .panel-pdf {
        max-width: 560px;
        background: var(--superficie);
        border: 1px solid var(--borde);
        border-radius: 14px;
        box-shadow: var(--sombra-card);
        padding: 22px;
    }
    .campo { margin-bottom: 16px; }
    .campo label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: var(--texto); }
    .campo .ayuda { margin-top: 5px; font-size: 12px; color: var(--texto-tenue); }
    .campo input,
    .campo textarea {
        width: 100%;
        padding: 11px 12px;
        border: 1px solid var(--borde);
        border-radius: 10px;
        font: inherit;
        font-size: 15px;
        color: var(--texto);
        background: #fff;
    }
    .campo textarea { resize: vertical; min-height: 84px; }
    .campo input:focus,
    .campo textarea:focus { outline: none; border-color: var(--azul-medio); box-shadow: 0 0 0 3px rgba(46, 107, 214, .15); }

    .buscador { position: relative; }
    .lista-clientes {
        position: absolute; left: 0; right: 0; top: calc(100% + 4px); z-index: 10;
        max-height: 260px; overflow-y: auto; margin: 0; padding: 4px; list-style: none;
        background: #fff; border: 1px solid var(--borde); border-radius: 10px; box-shadow: var(--sombra-hover);
    }
    .lista-clientes[hidden] { display: none; }
    .lista-clientes li { padding: 9px 10px; border-radius: 8px; cursor: pointer; font-size: 14px; color: var(--texto); }
    .lista-clientes li small { display: block; margin-top: 1px; font-size: 12px; color: var(--texto-tenue); }
    .lista-clientes li:hover, .lista-clientes li.activo { background: var(--azul-50); }
    .lista-clientes li.sin-resultados { cursor: default; color: var(--texto-tenue); }
    .lista-clientes li.sin-resultados:hover { background: transparent; }

    .separador { height: 1px; background: var(--borde); margin: 4px 0 18px; }

    .btn-generar {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        padding: 12px 20px; border: 0; border-radius: 10px; cursor: pointer;
        font: inherit; font-size: 15px; font-weight: 600; color: #fff;
        background: linear-gradient(160deg, var(--azul-medio), var(--azul-oscuro));
    }
    .btn-generar svg { width: 18px; height: 18px; }

    @media (max-width: 640px) {
        .panel-pdf { padding: 16px; }
        .btn-generar { width: 100%; }
        /* 16px evita el zoom automático de iOS al enfocar un campo */
        .campo input, .campo textarea { font-size: 16px; }
    }
</style>
@endpush

@section('contenido')
    <div class="encabezado-pdf">
        <h1>Generar PDF</h1>
        <p>Completá los datos del cliente para armar la guía de envío que va pegada en el paquete.</p>
    </div>

    <div class="panel-pdf">
        <form method="POST" action="{{ route('generar-pdf.generar') }}" target="_blank">
            @csrf

            <div class="campo">
                <label for="buscar">Buscar cliente</label>
                <div class="buscador">
                    <input type="text" id="buscar" autocomplete="off" role="combobox" aria-expanded="false"
                        aria-controls="lista-clientes" placeholder="Tocá para ver todos o escribí un nombre o teléfono">
                    <ul id="lista-clientes" class="lista-clientes" role="listbox" hidden></ul>
                </div>
                <div class="ayuda"><span id="contador-clientes"></span> Opcional: también podés escribir los datos a mano abajo.</div>
            </div>

            <div class="separador"></div>

            <div class="campo">
                <label for="nombre">Nombre del cliente</label>
                <input type="text" id="nombre" name="nombre" required maxlength="150" value="{{ old('nombre') }}">
            </div>

            <div class="campo">
                <label for="direccion">Dirección</label>
                <textarea id="direccion" name="direccion" required maxlength="300">{{ old('direccion') }}</textarea>
            </div>

            <div class="campo">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" inputmode="tel" maxlength="30" value="{{ old('telefono') }}">
            </div>

            <button type="submit" class="btn-generar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z" />
                    <path d="M14 3v5h5" />
                    <path d="M9 13h6M9 17h4" />
                </svg>
                Generar PDF
            </button>
        </form>
    </div>
@endsection

@php
    $clientesJs = [];
    foreach ($clientes as $c) {
        $clientesJs[] = ['nombre' => $c->nombre, 'telefono' => $c->telefono, 'direccion' => $c->direccion];
    }
@endphp

@push('scripts')
<script>
    const CLIENTES = @json($clientesJs);

    const inputBuscar = document.getElementById('buscar');
    const lista = document.getElementById('lista-clientes');
    let visibles = [];
    let activo = -1;

    document.getElementById('contador-clientes').textContent =
        CLIENTES.length + (CLIENTES.length === 1 ? ' cliente disponible.' : ' clientes disponibles.');

    // Sin tildes y en minúsculas, para que "jose" encuentre "José"
    const norm = s => (s || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

    function pintar() {
        const q = norm(inputBuscar.value.trim());
        visibles = CLIENTES.filter(c => !q || norm(c.nombre).includes(q) || norm(c.telefono).includes(q));
        lista.innerHTML = '';
        activo = -1;

        if (!visibles.length) {
            const vacio = document.createElement('li');
            vacio.className = 'sin-resultados';
            vacio.textContent = 'No hay clientes con ese dato';
            lista.appendChild(vacio);
        }

        visibles.forEach(c => {
            const li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.textContent = c.nombre;
            if (c.telefono) {
                const tel = document.createElement('small');
                tel.textContent = c.telefono;
                li.appendChild(tel);
            }
            li.addEventListener('click', () => elegir(c));
            lista.appendChild(li);
        });

        lista.hidden = false;
        inputBuscar.setAttribute('aria-expanded', 'true');
    }

    function cerrar() {
        lista.hidden = true;
        inputBuscar.setAttribute('aria-expanded', 'false');
    }

    // Al elegir un cliente se rellenan los tres campos (se pueden corregir a mano).
    function elegir(c) {
        document.getElementById('nombre').value = c.nombre || '';
        document.getElementById('direccion').value = c.direccion || '';
        document.getElementById('telefono').value = c.telefono || '';
        inputBuscar.value = c.nombre || '';
        cerrar();
    }

    inputBuscar.addEventListener('focus', pintar);
    inputBuscar.addEventListener('input', pintar);

    inputBuscar.addEventListener('keydown', e => {
        if (e.key === 'Escape') { cerrar(); return; }
        if (e.key === 'Enter') { e.preventDefault(); if (activo >= 0) elegir(visibles[activo]); return; }
        if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && visibles.length) {
            e.preventDefault();
            if (lista.hidden) pintar();
            activo = (activo + (e.key === 'ArrowDown' ? 1 : -1) + visibles.length) % visibles.length;
            [...lista.children].forEach((li, i) => li.classList.toggle('activo', i === activo));
            lista.children[activo].scrollIntoView({ block: 'nearest' });
        }
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('.buscador')) cerrar();
    });

    // Al generar el PDF se limpian los campos para dejar la pantalla lista para el siguiente cliente.
// El setTimeout es necesario: el navegador arma los datos del envío justo después del evento
// "submit", así que si se vacía antes, el PDF saldría con los campos en blanco.
document.querySelector('.panel-pdf form').addEventListener('submit', () => {
    setTimeout(() => {
        ['nombre', 'direccion', 'telefono', 'buscar'].forEach(id => {
            document.getElementById(id).value = '';
        });
        cerrar();
        inputBuscar.blur();
    }, 0);
});
</script>
@endpush