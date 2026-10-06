{{-- resources/views/admin/reportes_ver.blade.php  (un reporte por pantalla) --}}
@extends('layouts.app')

@section('titulo', 'Reporte de ' . strtolower($tipos[$tipo]))

@push('estilos')
<style>
    .volver { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--azul-medio); text-decoration: none; margin-bottom: 10px; }
    .volver:hover { text-decoration: underline; }

    .filtro-form { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 20px; }
    .filtro-form .campo label { display: block; font-size: 12px; font-weight: 600; color: var(--texto-tenue); margin-bottom: 4px; }
    .filtro-form input, .filtro-form select {
        padding: 9px 12px; border: 1px solid var(--borde); border-radius: 8px;
        font-size: 14px; font-family: inherit; color: var(--texto); background: var(--superficie);
        width: 100%; box-sizing: border-box;
    }
    .btn-r {
        padding: 10px 18px; border-radius: 8px; border: none; background: var(--azul-medio);
        color: #fff; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-block;
        font-family: inherit;
    }
    .btn-r.verde { background: #16794f; }

    .pestanas { display: inline-flex; gap: 4px; background: var(--azul-50); padding: 4px; border-radius: 10px; margin-bottom: 16px; }
    .pestanas a { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; color: var(--texto-tenue); }
    .pestanas a.activa { background: var(--superficie); color: var(--azul-oscuro); box-shadow: var(--sombra-card); }

    .tarjeta-reporte {
        background: var(--superficie); border: 1px solid var(--borde); border-radius: 12px;
        padding: 16px; box-shadow: var(--sombra-card); margin-bottom: 16px;
    }
    .tarjeta-reporte h3 { margin: 0 0 2px; font-size: 15px; color: var(--azul-oscuro); }
    .tarjeta-reporte .meta { font-size: 12px; color: var(--texto-tenue); margin-bottom: 12px; }

    .resumen-totales { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
    .resumen-totales .pill { background: var(--azul-50); border-radius: 8px; padding: 8px 12px; font-size: 12.5px; min-width: 110px; }
    .resumen-totales .pill strong { display: block; font-size: 15px; margin-top: 2px; }

    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 7px 8px; border-bottom: 1px solid var(--borde); font-size: 13px; white-space: nowrap; }
    th { color: var(--texto-tenue); font-weight: 600; font-size: 12px; background: var(--superficie); }
    td.num, th.num { text-align: right; }

    .badge { display: inline-flex; padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
    .badge-verde { background: #e7f7ee; color: #16794f; }
    .badge-rojo { background: #fdecec; color: #a30000; }

    .estado-vacio { text-align: center; padding: 36px 16px; color: var(--texto-tenue); font-size: 14px; }
    .caja { background: var(--superficie); border: 1px solid var(--borde); border-radius: 12px; padding: 12px; box-shadow: var(--sombra-card); }

        .sub-titulo { margin: 18px 0 8px; font-size: 13.5px; color: var(--azul-oscuro); }
    tr.fila-total td { font-weight: 700; }
    .cuadre { margin-top: 12px; border-radius: 10px; padding: 10px 12px; font-size: 12.5px; line-height: 1.7; }
    .cuadre.ok { background: #e7f7ee; color: #16794f; }
    .cuadre.mal { background: #fdecec; color: #a30000; }
    .nota-sin-detalle { margin-top: 14px; font-size: 12.5px; color: var(--texto-tenue); }
    /* Tarjetas móviles (listados y clientes de ruta) */
    .lista-movil { display: none; }
    .fila-movil { border: 1px solid var(--borde); border-radius: 10px; padding: 12px; margin-bottom: 10px; background: var(--superficie); }
    .fila-movil .cabecera { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .fila-movil .titulo { font-size: 14px; font-weight: 700; color: var(--azul-oscuro); word-break: break-word; }
    .fila-movil .monto { font-size: 15px; font-weight: 700; color: var(--azul-oscuro); white-space: nowrap; }
    .fila-movil .sub { font-size: 12px; color: var(--texto-tenue); margin-top: 2px; }
    .fila-movil .detalle { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 12px; margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--borde); }
    .fila-movil .detalle .k { font-size: 11px; color: var(--texto-tenue); }
    .fila-movil .detalle .v { font-size: 13px; word-break: break-word; }

    /* Desktop: la tabla scrollea por dentro y la paginación queda a la vista */
    @media (min-width: 761px) {
        .tabla-reporte { max-height: calc(100dvh - 300px); min-height: 240px; overflow: auto; }
        .tabla-reporte thead th { position: sticky; top: 0; z-index: 1; box-shadow: 0 1px 0 var(--borde); }
        .tarjeta-reporte .tabla-reporte { max-height: calc(100dvh - 460px); min-height: 240px; }
                     .tabla-reporte.tabla-corta { max-height: 340px; min-height: 200px; }
    }

    @media (max-width: 760px) {
        .solo-desktop { display: none !important; }
        .lista-movil { display: block; }
        .resumen-totales { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .resumen-totales .pill { min-width: 0; }
        .caja { padding: 10px; }
        .pestanas { display: flex; }
        .pestanas a { flex: 1; text-align: center; }
    }
    @media (max-width: 720px) {
        .filtro-form { display: grid; grid-template-columns: 1fr 1fr; align-items: end; }
        .filtro-form .campo.ancho { grid-column: 1 / -1; }
        .filtro-form .btn-r { grid-column: 1 / -1; width: 100%; text-align: center; box-sizing: border-box; }
    }
</style>
@endpush

@section('contenido')
@php
    $fmtMoney = fn ($v) => '₡' . number_format((float) $v, 2);
    $fmtNum = function ($v) {
        $v = (float) $v;
        return number_format($v, floor($v) == $v ? 0 : 2);
    };
    $fmtCelda = function ($v, $tipoCol) use ($fmtMoney, $fmtNum) {
        if ($v === null || $v === '') return '—';
        return match ($tipoCol) {
            'money' => $fmtMoney($v),
            'num' => $fmtNum($v),
            'fecha' => \Carbon\Carbon::parse($v)->format('d/m/Y'),
            'fechahora' => \Carbon\Carbon::parse($v)->format('d/m/Y H:i'),
            default => (string) $v,
        };
    };

    // Paginador (listados y rutas usan paginate() en el controlador)
    $paginador = $tipo === 'rutas' ? $reportes : ($data['filas'] ?? null);
          $etiquetasPag = ['ventas' => 'facturas', 'abonos' => 'abonos', 'gastos' => 'gastos', 'compras' => 'compras', 'inventario' => 'productos', 'rutas' => 'rutas', 'cancelados' => 'clientes', 'atrasados' => 'clientes'];
    $pag = ($paginador && $paginador->total() > 0) ? [
        'current_page' => $paginador->currentPage(),
        'last_page' => $paginador->lastPage(),
        'per_page' => $paginador->perPage(),
        'from' => $paginador->firstItem(),
        'to' => $paginador->lastItem(),
        'total' => $paginador->total(),
        'data' => [],
    ] : null;

    $parametros = fn (array $extra = []) => array_merge(['tipo' => $tipo], request()->except(['page', 'orden']), $extra);
@endphp

<a class="volver" href="{{ route('reportes.index') }}">← Reportes</a>
<h1 style="margin: 0 0 4px; font-size: 20px;">Reporte de {{ strtolower($tipos[$tipo]) }}</h1>
<div style="font-size:13px; color:var(--texto-tenue); margin-bottom:16px;">{{ $nombreSel }}</div>

{{-- Filtros: sucursal (solo admin libre) + fechas --}}
<form method="GET" action="{{ route('reportes.ver', $tipo) }}" class="filtro-form" id="form-filtros">
    @if ($tipo === 'inventario')
        <input type="hidden" name="orden" value="{{ $orden }}">
    @endif
    @if ($esLibre)
        <div class="campo ancho">
            <label>Sucursal</label>
            <select name="sucursal" onchange="this.form.submit()">
                <option value="todas" @selected($sel === 'todas')>Todas las sucursales</option>
                @foreach ($sucursales as $s)
                    <option value="{{ $s->id }}" @selected($sel === (string) $s->id)>{{ $s->nombre }}</option>
                @endforeach
            </select>
        </div>
    @endif
       <div class="campo" @if ($tipo === 'atrasados') style="display:none;" @endif>
        <label>Desde</label>
        <input type="date" name="desde" value="{{ $desde }}">
    </div>
    <div class="campo" @if ($tipo === 'atrasados') style="display:none;" @endif>
        <label>Hasta</label>
        <input type="date" name="hasta" value="{{ $hasta }}">
    </div>
    <button type="submit" class="btn-r">Filtrar</button>
    <a class="btn-r verde" id="btn-excel" href="{{ route('reportes.excel', array_merge(['tipo' => $tipo], request()->except('page'))) }}">Exportar a Excel</a>
</form>

@if ($tipo === 'inventario')
    <div class="pestanas">
        <a class="{{ $orden === 'mas' ? 'activa' : '' }}" href="{{ route('reportes.ver', $parametros(['orden' => 'mas'])) }}">Más vendidos</a>
        <a class="{{ $orden === 'menos' ? 'activa' : '' }}" href="{{ route('reportes.ver', $parametros(['orden' => 'menos'])) }}">Menos vendidos</a>
    </div>
@endif

<div id="tabla-reporte"></div>

@if ($tipo === 'rutas')
    {{-- ===================== RUTAS ===================== --}}
    @php
        $linksComprobante = function ($d, $puede) {
            $ops = $d['operaciones'] ?? [];
            if (!$ops) return '—';
            $partes = [];
            foreach ($ops as $op) {
                $num = e($op['numero'] ?? '');
                $partes[] = $puede
                    ? '<a href="' . e(route('operaciones.comprobante', $op['id'])) . '" target="_blank">#' . $num . '</a>'
                    : '#' . $num;
            }
            return implode(', ', $partes);
        };
    @endphp

    @if (($resumenRutas['reportes'] ?? 0) > 0)
    <div class="tarjeta-reporte">
        <h3>Resumen del filtro</h3>
        <div class="meta">Suma de todas las rutas del rango, no solo la de esta página</div>
        <div class="resumen-totales" style="margin-bottom:0;">
            <div class="pill">Rutas<strong>{{ $resumenRutas['reportes'] }}</strong></div>
            <div class="pill">Efectivo<strong>{{ $fmtMoney($resumenRutas['efectivo']) }}</strong></div>
            <div class="pill">Sinpe<strong>{{ $fmtMoney($resumenRutas['sinpe']) }}</strong></div>
            <div class="pill">Total cobrado<strong>{{ $fmtMoney($resumenRutas['efectivo'] + $resumenRutas['sinpe']) }}</strong></div>
            <div class="pill">Finalizados<strong>{{ $resumenRutas['finalizados'] }}</strong></div>
            <div class="pill">No abonó<strong>{{ $resumenRutas['noAbono'] }}</strong></div>
        </div>
    </div>
@endif

    @forelse ($reportes as $reporte)
        @php
            $datos = collect($reporte->datos);
            $totalEfectivo = $datos->sum(fn ($d) => $d['efectivo'] ?? 0);
            $totalSinpe = $datos->sum(fn ($d) => $d['sinpe'] ?? 0);
            $finalizados = $datos->where('estado', 'finalizado')->count();
            $noAbono = $datos->where('estado', 'no_abono')->count();
            // El comprobante solo abre si la operación es de la sucursal activa en sesión
            $puedeAbrirComprobante = (string) $reporte->sucursal_id === (string) session('sucursal_id');
        @endphp
        <div class="tarjeta-reporte">
            <h3>{{ $reporte->nombre_ruta ?? 'Ruta #' . $reporte->ruta_id }}</h3>
            <div class="meta">
                {{ $reporte->sucursal?->nombre }} · {{ ucfirst($reporte->tipo) }} · {{ ucfirst($reporte->filtro) }} ·
                {{ $reporte->generado_en->format('d/m/Y H:i') }}
            </div>

            <div class="resumen-totales">
                <div class="pill">Efectivo<strong>{{ $fmtMoney($totalEfectivo) }}</strong></div>
                <div class="pill">Sinpe<strong>{{ $fmtMoney($totalSinpe) }}</strong></div>
                <div class="pill">Total cobrado<strong>{{ $fmtMoney($totalEfectivo + $totalSinpe) }}</strong></div>
                <div class="pill">Finalizados<strong>{{ $finalizados }}</strong></div>
                <div class="pill">No abonó<strong>{{ $noAbono }}</strong></div>
            </div>

            {{-- Desktop: tabla --}}
           <div class="tabla-reporte tabla-scroll solo-desktop">
                <table>
                    <thead>
                        <tr><th>#</th><th>Cliente</th><th>Estado</th><th class="num">Efectivo</th><th class="num">Sinpe</th><th>Comprobante</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($datos as $d)
                            <tr>
                                <td>{{ $d['orden'] ?? '—' }}</td>
                                <td>{{ $d['nombre'] ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ ($d['estado'] ?? '') === 'no_abono' ? 'badge-rojo' : 'badge-verde' }}">
                                        {{ ($d['estado'] ?? '') === 'no_abono' ? 'No abonó' : 'Finalizado' }}
                                    </span>
                                </td>
                                <td class="num">{{ $fmtMoney($d['efectivo'] ?? 0) }}</td>
                                <td class="num">{{ $fmtMoney($d['sinpe'] ?? 0) }}</td>
                                <td>{!! $linksComprobante($d, $puedeAbrirComprobante) !!}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Móvil: una tarjeta por cliente --}}
            <div class="lista-movil">
                @foreach ($datos as $d)
                    @php $esNoAbono = ($d['estado'] ?? '') === 'no_abono'; @endphp
                    <div class="fila-movil">
                        <div class="cabecera">
                            <div>
                                <div class="titulo">{{ $d['orden'] ?? '—' }}. {{ $d['nombre'] ?? '—' }}</div>
                                <div class="sub">
                                    <span class="badge {{ $esNoAbono ? 'badge-rojo' : 'badge-verde' }}">{{ $esNoAbono ? 'No abonó' : 'Finalizado' }}</span>
                                </div>
                            </div>
                            <div class="monto">{{ $fmtMoney(($d['efectivo'] ?? 0) + ($d['sinpe'] ?? 0)) }}</div>
                        </div>
                        <div class="detalle">
                            <div><div class="k">Efectivo</div><div class="v">{{ $fmtMoney($d['efectivo'] ?? 0) }}</div></div>
                            <div><div class="k">Sinpe</div><div class="v">{{ $fmtMoney($d['sinpe'] ?? 0) }}</div></div>
                            <div style="grid-column:1/-1;"><div class="k">Comprobante</div><div class="v">{!! $linksComprobante($d, $puedeAbrirComprobante) !!}</div></div>
                        </div>
                    </div>
                             @endforeach
            </div>

            {{-- Cobrado por administrador + cuadre --}}
            @php $admins = $cobroAdmins[$reporte->id] ?? null; @endphp
            @if ($admins === null)
                <div class="nota-sin-detalle">Este reporte es anterior al detalle por administrador (no guardó sus operaciones), así que no se puede desglosar ni cuadrar.</div>
            @else
                @php
                    $opEfectivo = collect($admins)->sum('efectivo');
                    $opSinpe = collect($admins)->sum('sinpe');
                    $difEfectivo = round($totalEfectivo - $opEfectivo, 2);
                    $difSinpe = round($totalSinpe - $opSinpe, 2);
                    $cuadra = $difEfectivo == 0.0 && $difSinpe == 0.0;
                @endphp

                <h4 class="sub-titulo">Cobrado por administrador</h4>

                <div class="solo-desktop" style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Administrador</th><th class="num">Efectivo</th><th class="num">Sinpe</th><th class="num">Total cobrado</th>
                                <th class="num">Ventas</th><th class="num">Devoluciones</th><th class="num">No abonó</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($admins as $ad)
                                <tr>
                                    <td>{{ $ad['admin'] }}</td>
                                    <td class="num">{{ $fmtMoney($ad['efectivo']) }}</td>
                                    <td class="num">{{ $fmtMoney($ad['sinpe']) }}</td>
                                    <td class="num">{{ $fmtMoney($ad['efectivo'] + $ad['sinpe']) }}</td>
                                    <td class="num">{{ $ad['ventas'] }} · {{ $fmtMoney($ad['vendido']) }}</td>
                                    <td class="num">{{ $fmtMoney($ad['devuelto']) }}</td>
                                    <td class="num">{{ $ad['sin_abono'] }}</td>
                                </tr>
                            @endforeach
                            @if (count($admins) > 1)
                                <tr class="fila-total">
                                    <td>Total</td>
                                    <td class="num">{{ $fmtMoney($opEfectivo) }}</td>
                                    <td class="num">{{ $fmtMoney($opSinpe) }}</td>
                                    <td class="num">{{ $fmtMoney($opEfectivo + $opSinpe) }}</td>
                                    <td class="num">{{ collect($admins)->sum('ventas') }} · {{ $fmtMoney(collect($admins)->sum('vendido')) }}</td>
                                    <td class="num">{{ $fmtMoney(collect($admins)->sum('devuelto')) }}</td>
                                    <td class="num">{{ collect($admins)->sum('sin_abono') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="lista-movil">
                    @foreach ($admins as $ad)
                        <div class="fila-movil">
                            <div class="cabecera">
                                <div class="titulo">{{ $ad['admin'] }}</div>
                                <div class="monto">{{ $fmtMoney($ad['efectivo'] + $ad['sinpe']) }}</div>
                            </div>
                            <div class="detalle">
                                <div><div class="k">Efectivo</div><div class="v">{{ $fmtMoney($ad['efectivo']) }}</div></div>
                                <div><div class="k">Sinpe</div><div class="v">{{ $fmtMoney($ad['sinpe']) }}</div></div>
                                <div><div class="k">Ventas</div><div class="v">{{ $ad['ventas'] }} · {{ $fmtMoney($ad['vendido']) }}</div></div>
                                <div><div class="k">Devoluciones</div><div class="v">{{ $fmtMoney($ad['devuelto']) }}</div></div>
                                <div><div class="k">No abonó</div><div class="v">{{ $ad['sin_abono'] }}</div></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="cuadre {{ $cuadra ? 'ok' : 'mal' }}">
                    <strong>{{ $cuadra ? 'Cuadra' : 'No cuadra' }}</strong><br>
                    Reportado en la ruta: efectivo {{ $fmtMoney($totalEfectivo) }} · sinpe {{ $fmtMoney($totalSinpe) }}<br>
                    Según las operaciones: efectivo {{ $fmtMoney($opEfectivo) }} · sinpe {{ $fmtMoney($opSinpe) }}
                    @unless ($cuadra)
                        <br>Diferencia: efectivo {{ $fmtMoney($difEfectivo) }} · sinpe {{ $fmtMoney($difSinpe) }}
                        <br>Puede venir de vuelto en ventas de contado, facturas anuladas después de exportar, o de un reporte generado antes del cambio C.
                    @endunless
                </div>
            @endif
        </div>
    @empty
        <div class="estado-vacio">No hay reportes de rutas en el rango y sucursal seleccionados.</div>
    @endforelse

    @if ($pag)
        <div class="paginacion" id="paginacion-reportes" style="display:none;"></div>
    @endif
@else
    {{-- ===================== VENTAS / ABONOS / GASTOS / COMPRAS / INVENTARIO ===================== --}}
    <div class="resumen-totales">
        @foreach ($data['totales'] as $etiqueta => $t)
            <div class="pill">{{ $etiqueta }}<strong>{{ $t['tipo'] === 'money' ? $fmtMoney($t['valor']) : $fmtNum($t['valor']) }}</strong></div>
        @endforeach
    </div>

    <div class="caja">
        @if ($data['filas']->count())
            @php
                // Para las tarjetas móviles: título, monto principal, fecha e id se eligen por nombre de columna
                $cols = collect($data['columnas']);
                $keys = $cols->pluck('key')->all();
                $keyTitulo = collect(['cliente', 'producto', 'proveedor', 'descripcion', 'categoria'])->first(fn ($k) => in_array($k, $keys));
                $keyMonto = collect(['total', 'monto', 'saldo'])->first(fn ($k) => in_array($k, $keys));
                $keyFecha = in_array('fecha', $keys) ? 'fecha' : null;
                $keyId = in_array('id', $keys) ? 'id' : null;
                $usadas = array_filter([$keyTitulo, $keyMonto, $keyFecha, $keyId]);
                $colResto = $cols->reject(fn ($c) => in_array($c['key'], $usadas));
                $colDe = fn ($k) => $k ? $cols->firstWhere('key', $k) : null;
            @endphp

            {{-- Desktop: tabla con scroll interno --}}
                       <div class="tabla-reporte tabla-scroll solo-desktop {{ in_array($tipo, ['cancelados', 'atrasados']) ? 'tabla-corta' : '' }}">
                <table>
                    <thead>
                        <tr>
                            @foreach ($data['columnas'] as $col)
                                <th class="{{ in_array($col['tipo'], ['money', 'num']) ? 'num' : '' }}">{{ $col['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['filas'] as $fila)
                            <tr>
                                @foreach ($data['columnas'] as $col)
                                    <td class="{{ in_array($col['tipo'], ['money', 'num']) ? 'num' : '' }}">{{ $fmtCelda($fila->{$col['key']} ?? null, $col['tipo']) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Móvil: una tarjeta por fila --}}
            <div class="lista-movil">
                @foreach ($data['filas'] as $fila)
                    <div class="fila-movil">
                        <div class="cabecera">
                            <div>
                                <div class="titulo">{{ $keyTitulo ? $fmtCelda($fila->{$keyTitulo} ?? null, 'text') : '—' }}</div>
                                <div class="sub">
                                    @if ($keyId){{ $colDe($keyId)['label'] }} {{ $fila->{$keyId} }}@endif
                                    @if ($keyId && $keyFecha) · @endif
                                    @if ($keyFecha){{ $fmtCelda($fila->{$keyFecha} ?? null, $colDe($keyFecha)['tipo']) }}@endif
                                </div>
                            </div>
                            @if ($keyMonto)
                                <div class="monto">{{ $fmtCelda($fila->{$keyMonto} ?? null, $colDe($keyMonto)['tipo']) }}</div>
                            @endif
                        </div>
                        @if ($colResto->count())
                            <div class="detalle">
                                @foreach ($colResto as $col)
                                    <div>
                                        <div class="k">{{ $col['label'] }}</div>
                                        <div class="v">{{ $fmtCelda($fila->{$col['key']} ?? null, $col['tipo']) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="paginacion" id="paginacion-reportes" style="display:none;"></div>
        @else
            <div class="estado-vacio">No hay datos en el rango y sucursal seleccionados.</div>
        @endif
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-filtros');
    const btnExcel = document.getElementById('btn-excel');
    const inputDesde = form.querySelector('[name="desde"]');
    const inputHasta = form.querySelector('[name="hasta"]');

    // Devuelve true si el rango es válido; si no, muestra SweetAlert y devuelve false.
    function rangoValido() {
        const d = inputDesde.value, h = inputHasta.value;
        if (d && h && h < d) {   // formato YYYY-MM-DD: la comparación de texto es correcta
            Swal.fire({
                icon: 'warning',
                title: 'Rango de fechas inválido',
                text: 'La fecha "Hasta" no puede ser anterior a la fecha "Desde".',
                confirmButtonText: 'Entendido',
            });
            return false;
        }
        return true;
    }

    form.addEventListener('submit', function (e) {
        if (!rangoValido()) e.preventDefault();
    });

    btnExcel.addEventListener('click', function (e) {
        if (!rangoValido()) e.preventDefault();
    });

    // Paginación compartida del layout. La página se carga en el servidor,
    // así que al cambiar de página se recarga la URL con ?page=N y se vuelve a la tabla.
    const PAG = @json($pag);
    if (PAG && typeof pintarPaginador === 'function') {
        pintarPaginador('paginacion-reportes', PAG, @json($etiquetasPag[$tipo]), function (pagina) {
            const u = new URL(window.location.href);
            u.searchParams.set('page', pagina);
            u.hash = 'tabla-reporte';
            window.location.href = u.toString();
        });
    }

    // Red de seguridad: si el servidor recibió fechas inválidas (ej. URL escrita a mano)
    @if (!empty($alerta))
        Swal.fire({
            icon: 'warning',
            title: 'Rango de fechas inválido',
            text: @json($alerta),
            confirmButtonText: 'Entendido',
        });
    @endif
});
</script>
@endsection