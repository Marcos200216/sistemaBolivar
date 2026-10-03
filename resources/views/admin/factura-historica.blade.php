<!DOCTYPE html>
{{-- resources/views/admin/factura-historica.blade.php --}}
@php
    $paraPdf = $paraPdf ?? false;
    $c = fn ($v) => '₡' . number_format((float) $v, 2);
    $numero = $factura->factura_id_legacy ?? $factura->id;
    $hayDescuento = (float) $factura->descuento > 0;
@endphp
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Factura N° {{ $numero }}</title>
    <style>
    * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    @page { margin: 14mm 12mm; }

    body {
        margin: 0;
        color: #1B2430;
        font-size: 12px;
        line-height: 1.45;
        @if ($paraPdf)
        font-family: 'DejaVu Sans', sans-serif;
        background: #ffffff;
        @else
        font-family: 'Inter', Arial, sans-serif;
        background: #EEF1F6;
        padding: 16px;
        @endif
    }

    .hoja { max-width: 760px; margin: 0 auto; background: #ffffff; }
    @if (!$paraPdf)
    .hoja { padding: 28px 32px; border-radius: 14px; box-shadow: 0 2px 14px rgba(16, 24, 40, .10); }
    @endif

    /* ---------- Botones (solo en pantalla) ---------- */
    .acciones { text-align: right; margin-bottom: 16px; }
    .btn {
        display: inline-block; border: 0; border-radius: 8px; padding: 8px 16px; margin-left: 6px;
        font-family: inherit; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none;
        background: #2E6BD6; color: #ffffff;
    }
    .btn-sec { background: #E6EEFC; color: #0A2E6E; }

    /* ---------- Encabezado ---------- */
    .banda { background: #0A2E6E; border-radius: 12px; }
    table.cab { width: 100%; border-collapse: collapse; }
    table.cab td { padding: 18px 22px; vertical-align: middle; }
    td.cab-der { text-align: right; }
    .logo-caja { display: inline-block; background: #ffffff; border-radius: 10px; padding: 7px 12px; }
    .logo-caja img { display: block; height: 44px; }
    .marca-sub { margin-top: 8px; font-size: 12px; font-weight: bold; color: #ffffff; letter-spacing: .3px; }
    .marca-txt { font-size: 18px; font-weight: bold; color: #ffffff; }
    .tipo-doc { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: #9FB4D9; }
    .num-doc { margin-top: 2px; font-size: 24px; font-weight: bold; color: #ffffff; }
    .fecha-doc { margin-top: 3px; font-size: 11px; color: #C9D5E8; }

    /* ---------- Tarjetas de datos (cliente, detalle) ---------- */
    table.info { width: 100%; border-collapse: collapse; margin-top: 16px; }
    table.info td { width: 50%; vertical-align: top; padding: 0; }
    table.info td.a { padding-right: 6px; }
    table.info td.b { padding-left: 6px; }
    .tarjeta { background: #F5F8FF; border: 1px solid #E1E9F7; border-radius: 10px; padding: 11px 14px; }
    .rotulo { margin-bottom: 4px; font-size: 9.5px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: #6D7480; }
    .dato-fuerte { font-size: 13.5px; font-weight: bold; color: #0A2E6E; }
    .dato { color: #4A5565; line-height: 1.55; }

    /* ---------- Secciones y tablas ---------- */
    .seccion {
        margin: 20px 0 8px; padding-left: 9px; border-left: 4px solid #2E6BD6;
        font-size: 12.5px; font-weight: bold; letter-spacing: .6px; text-transform: uppercase; color: #0A2E6E;
    }
    .chip {
        display: inline-block; margin-left: 6px; padding: 2px 9px; border-radius: 999px;
        background: #EAF2FF; color: #174A9B; font-size: 10px; font-weight: bold; letter-spacing: .3px; text-transform: none;
    }

    table.tabla { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .tabla th {
        padding: 8px 10px; text-align: left; background: #F0F3F8; border-bottom: 2px solid #D5DEEE;
        font-size: 10px; letter-spacing: .8px; text-transform: uppercase; color: #0A2E6E;
    }
    .tabla td { padding: 8px 10px; border-bottom: 1px solid #E6EAF1; vertical-align: top; }
    .tabla th.monto, .tabla td.monto { text-align: right; white-space: nowrap; }
    tr { page-break-inside: avoid; }

    tr.fila-resaltada td { background: #EAF2FF; font-weight: bold; }
    tr.fila-total td { border-top: 2px solid #0A2E6E; border-bottom: none; font-weight: bold; font-size: 13px; }

    .linea-info {
        margin-top: 4px; padding: 8px 12px; background: #F8FAFC; border: 1px solid #E6EAF1;
        border-radius: 8px; color: #4A5565;
    }
    .linea-info a { color: #2E6BD6; }

    /* ---------- Resumen de montos ---------- */
    table.resumen { width: 58%; margin: 12px 0 0 auto; border-collapse: separate; border-spacing: 0; }
    .resumen td { padding: 6px 12px; }
    .resumen td.monto { text-align: right; white-space: nowrap; }
    .resumen tr.total td { border-top: 2px solid #0A2E6E; font-weight: bold; font-size: 13px; }
    .resumen tr.destacada td { padding: 11px 12px; background: #0A2E6E; color: #ffffff; font-weight: bold; font-size: 14px; }
    .resumen tr.destacada td.ini { border-radius: 9px 0 0 9px; }
    .resumen tr.destacada td.fin { border-radius: 0 9px 9px 0; }
    .resumen tr.destacada.favor td { background: #067647; }
    .resumen tr.tenue-fila td { color: #6D7480; font-size: 11px; }

    /* ---------- Textos auxiliares ---------- */
    .tenue { color: #6D7480; font-size: 11px; font-weight: normal; }
    .favor { color: #067647; }
    .deuda { color: #B42318; }
    .vacio { color: #8A93A2; font-style: italic; }
    .nota { margin-top: 14px; font-size: 10.5px; line-height: 1.55; color: #6D7480; }
    .nota-aviso {
        margin-top: 16px; padding: 9px 12px; background: #FFF7E6; border: 1px solid #F3D999;
        border-radius: 8px; font-size: 10.5px; line-height: 1.55; color: #8A5A00;
    }

    /* ---------- Pie ---------- */
    .pie { margin-top: 28px; padding-top: 12px; border-top: 1px solid #E3E1DB; text-align: center; font-size: 10.5px; color: #6D7480; }
    .pie .gracias { margin-bottom: 2px; font-size: 12px; font-weight: bold; color: #0A2E6E; }

    @media print {
        body { background: #ffffff; padding: 0; }
        .hoja { max-width: none; padding: 0; border-radius: 0; box-shadow: none; }
        .no-imprimir { display: none; }
    }

    @media (max-width: 560px) {
        body { padding: 8px; }
        .hoja { padding: 16px; }
        table.resumen { width: 100%; }
        table.cab td { padding: 14px 14px; }
        .num-doc { font-size: 20px; }
    }
</style>
</head>
<body>
<div class="hoja">

    @if (!$paraPdf)
        <div class="acciones no-imprimir">
            <button type="button" class="btn btn-sec" onclick="window.close()">Cerrar</button>
            <button type="button" class="btn" onclick="window.print()">Imprimir</button>
        </div>
    @endif

    @php
        $marcaNombre = $nombreCanal ?? ($negocio ?? 'Distribuidora Bolívar');
        $logoSrc = !empty($logo) ? ($paraPdf ? public_path('images/' . $logo) : asset('images/' . $logo)) : null;
    @endphp
    <div class="banda">
        <table class="cab">
            <tr>
                <td class="cab-izq">
                    @if ($logoSrc)
                        <div class="logo-caja"><img src="{{ $logoSrc }}" alt="{{ $marcaNombre }}"></div>
                        <div class="marca-sub">{{ $marcaNombre }}</div>
                    @else
                        <div class="marca-txt">{{ $marcaNombre }}</div>
                    @endif
                </td>
                <td class="cab-der">
                    <div class="tipo-doc">Factura</div>
                    <div class="num-doc">{{ 'N° ' . $numero }}</div>
                    @if ($fecha)
                        <div class="fecha-doc">{{ $fecha->format('d/m/Y H:i') }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="info">
        <tr>
            <td class="a">
                <div class="tarjeta">
                    <div class="rotulo">Cliente</div>
                    <div class="dato-fuerte">{{ $factura->cliente->nombre ?? '—' }}</div>
                    <div class="dato">Código: {{ $factura->cliente->codigo ?? '—' }}</div>
                </div>
            </td>
            <td class="b">
                <div class="tarjeta">
                    <div class="rotulo">Tipo de venta</div>
                    <div class="dato-fuerte">{{ $etiqueta }}</div>
                    @if ($factura->plazo && $etiqueta !== 'Contado')
                        <div class="dato">Plazo: {{ $factura->plazo }} días</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="seccion">Detalle</div>
    <table class="tabla">
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="monto">Cant.</th>
                <th class="monto">Precio</th>
                <th class="monto">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($factura->lineas as $l)
                @php $totalLinea = ((float) $l->precio_unit * (int) $l->cantidad) - (float) $l->descuento; @endphp
                <tr>
                    <td>{{ $l->descripcion }}</td>
                    <td class="monto">{{ $l->cantidad }}</td>
                    <td class="monto">{{ $c($l->precio_unit) }}</td>
                    <td class="monto">{{ $c($totalLinea) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="vacio" style="text-align:center;">Esta factura no tiene líneas guardadas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="resumen">
        @if ($hayDescuento)
            <tr>
                <td>Subtotal</td>
                <td class="monto">{{ $c($factura->montototal) }}</td>
            </tr>
            <tr>
                <td>Descuento</td>
                <td class="monto">− {{ $c($factura->descuento) }}</td>
            </tr>
        @endif
        <tr class="destacada">
            <td class="ini">Total</td>
            <td class="monto fin">{{ $c($factura->total) }}</td>
        </tr>
    </table>

    <div class="pie">
        <div class="gracias">Gracias por su preferencia</div>
        <div>{{ $marcaNombre }}</div>
    </div>

</div>
</body>
</html>