<!DOCTYPE html>
{{-- resources/views/admin/recibo-historico.blade.php --}}
@php $paraPdf = $paraPdf ?? false; @endphp
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recibo de abono</title>
    <style>
    * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
   @if ($paraPdf)
@page { margin: 40mm 12mm 22mm 12mm; }
.cab-fijo { position: fixed; top: -36mm; left: 0; width: 100%; }
.pie-fijo { position: fixed; bottom: -17mm; left: 0; width: 100%; margin: 0; }
.pagina:after { content: "Página " counter(page); }
@else
@page { margin: 14mm 12mm; }
@endif

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
            <button type="button" class="btn" onclick="window.print()">Imprimir</button>
        </div>
    @endif

    @php
        $marcaNombre = $nombreCanal ?? ($negocio ?? 'Distribuidora Bolívar');
        $logoSrc = !empty($logo) ? ($paraPdf ? public_path('images/' . $logo) : asset('images/' . $logo)) : null;
    @endphp
    <div class="banda {{ $paraPdf ? 'cab-fijo' : '' }}">
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
                    <div class="tipo-doc">Recibo</div>
                    <div class="num-doc">{{ 'Recibo de abono' }}</div>
                    @if ($abono->fecha)
                        <div class="fecha-doc">{{ optional($abono->fecha)->format('d/m/Y H:i') }}</div>
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
                    <div class="dato-fuerte">{{ $cliente->nombre }}</div>
                    <div class="dato">{{ $cliente->codigo ?? 'sin código' }} · {{ $cliente->telefono ?? 'sin teléfono' }}</div>
                </div>
            </td>
            <td class="b">
                <div class="tarjeta">
                    <div class="rotulo">Aplicado a</div>
                    @if ($factura)
                        <div class="dato-fuerte">Factura N° {{ $numeroFactura ?? '—' }}</div>
                        @if ($facturaAnulada)<div class="dato">(anulada)</div>@endif
                    @else
                        <div class="dato-fuerte">Cuenta del cliente</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="seccion">Detalle del abono</div>
    <table class="resumen" style="width:100%;">
        <tr>
            <td>{{ $factura ? 'Saldo de la factura antes' : 'Saldo anterior' }}</td>
            <td class="monto">₡{{ number_format($antes, 2) }}</td>
        </tr>

        @if ($sinDesglose)
            <tr class="tenue-fila">
                <td colspan="2">Sin desglose de pago (efectivo / sinpe) en el sistema anterior</td>
            </tr>
        @else
            <tr>
                <td>Efectivo</td>
                <td class="monto">₡{{ number_format($abono->efectivo, 2) }}</td>
            </tr>
            <tr>
                <td>Sinpe</td>
                <td class="monto">₡{{ number_format($abono->sinpe, 2) }}</td>
            </tr>
        @endif

        <tr class="total">
            <td>Total abonado</td>
            <td class="monto">₡{{ number_format($abono->monto_abono, 2) }}</td>
        </tr>
        <tr class="destacada">
            <td class="ini">{{ $factura ? 'Saldo de la factura después' : 'Saldo después' }}</td>
            <td class="monto fin">₡{{ number_format($despues, 2) }}</td>
        </tr>
    </table>

    <div class="pie {{ $paraPdf ? 'pie-fijo' : '' }}">
    <div class="gracias">Gracias por su preferencia</div>
    <div>{{ $marcaNombre }}</div>
    @if ($paraPdf)<div class="tenue pagina"></div>@endif
</div>

</div>
</body>
</html>