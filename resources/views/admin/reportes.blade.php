{{-- resources/views/admin/reportes.blade.php  (pantalla de entrada: solo tarjetas) --}}
@extends('layouts.app')

@section('titulo', 'Reportes')

@push('estilos')
<style>
    .grid-reportes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .card-reporte {
        display: flex; flex-direction: column; gap: 10px; text-decoration: none; color: var(--texto);
        background: var(--superficie); border: 2px solid var(--borde); border-radius: 14px;
        padding: 20px; box-shadow: var(--sombra-card); transition: border-color .15s, transform .15s;
    }
    .card-reporte:hover { border-color: var(--azul-medio); transform: translateY(-2px); }
    .card-reporte .icono {
        width: 46px; height: 46px; border-radius: 12px; background: var(--azul-50);
        display: flex; align-items: center; justify-content: center; color: var(--azul-medio);
    }
    .card-reporte .icono svg { width: 24px; height: 24px; }
    .card-reporte strong { font-size: 16px; color: var(--azul-oscuro); }
    .card-reporte p { margin: 0; font-size: 13px; color: var(--texto-tenue); line-height: 1.4; }

    @media (max-width: 900px) { .grid-reportes { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 560px) {
        .grid-reportes { gap: 10px; }
        .card-reporte { padding: 14px; }
        .card-reporte p { font-size: 12px; }
    }
</style>
@endpush

@section('contenido')
@php
    // Orden de las tarjetas + ícono y descripción de cada una
    $tarjetas = [
        'rutas' => ['Rutas', 'Reportes semanales de cobro por ruta: cobrado en efectivo y sinpe, quién abonó y quién no.',
            '<path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>'],
        'ventas' => ['Ventas', 'Facturas del período con su estado, descuentos y forma de pago.',
            '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>'],
        'abonos' => ['Abonos', 'Pagos recibidos de los clientes, en efectivo y sinpe.',
            '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>'],
        'gastos' => ['Gastos', 'Gastos de la sucursal por categoría y monto.',
            '<path d="M22 17l-8.5-8.5-5 5L2 7"/><path d="M16 17h6v-6"/>'],
        'compras' => ['Compras', 'Compras a proveedores y lo que queda pendiente de pagar.',
            '<path d="M1 3h15v13H1z"/><path d="M16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
        'inventario' => ['Inventario', 'Productos más vendidos y menos vendidos del período.',
            '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>'],
    ];
@endphp

<h1 style="margin: 0 0 4px; font-size: 20px;">Reportes</h1>
<div style="font-size:13px; color:var(--texto-tenue); margin-bottom:20px;">Elegí qué reporte querés ver. En cada uno podés filtrar por fechas y exportar a Excel.</div>

<div class="grid-reportes">
    @foreach ($tarjetas as $clave => [$titulo, $descripcion, $icono])
        @continue(!array_key_exists($clave, $tipos))
        <a class="card-reporte" href="{{ route('reportes.ver', $clave) }}">
            <div class="icono">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $icono !!}</svg>
            </div>
            <strong>{{ $titulo }}</strong>
            <p>{{ $descripcion }}</p>
        </a>
    @endforeach
</div>
@endsection