<!DOCTYPE html>
{{--
    resources/views/admin/comprobante.blade.php

    Vista independiente (sin @extends del layout con sidebar) porque se usa
    para dos cosas: 1) pantalla, para imprimir o capturar desde el celular, y
    2) la misma plantilla la usa dompdf para generar el PDF. $paraPdf decide
    cómo se arma la ruta del logo y las partes que solo van en pantalla.
--}}
@php
    $paraPdf = $paraPdf ?? false;
    $dinero = fn ($v) => ($v < 0 ? '−' : '') . '₡' . number_format(abs($v), 2);
    $movimiento = fn ($v) => $v == 0 ? '—' : (($v > 0 ? '+ ' : '− ') . '₡' . number_format(abs($v), 2));
    $saldoFavor = $operacion->saldo_final < 0;
@endphp
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }} #{{ $operacion->numero }}</title>
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

    /* ---------- Aviso de factura anulada (ítem 7) ---------- */
    .aviso-anulada {
        margin-bottom: 16px; padding: 12px 16px; background: #FDECEC; border: 1px solid #F3A3A3;
        border-radius: 10px; color: #A30000; font-size: 12.5px; line-height: 1.6;
    }
    .aviso-anulada .titulo { font-size: 14px; font-weight: bold; letter-spacing: .3px; text-transform: uppercase; }

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

    /* Tabla "Estado de la cuenta": que continúe bien en otra hoja */
table.tabla thead { display: table-header-group; }
table.tabla tfoot { display: table-row-group; }
.pie { page-break-inside: avoid; }
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
            <button type="button" class="btn btn-sec" onclick="window.print()">Imprimir</button>
            <a class="btn" href="{{ route('operaciones.comprobante.pdf', $operacion) }}" target="_blank">Descargar PDF</a>
        </div>
    @endif

    @if (!empty($facturaAnulada))
        <div class="aviso-anulada">
            <div class="titulo">⚠ Factura anulada</div>
            Esta factura fue anulada el {{ optional($facturaAnulada->anulada_at)->format('d/m/Y H:i') }}
            por {{ $facturaAnulada->anuladaPor->name ?? '—' }}.
            @if ($facturaAnulada->motivo_anulacion)
                Motivo: {{ $facturaAnulada->motivo_anulacion }}.
            @endif
            Los montos y el estado de cuenta de abajo corresponden al momento en que se hizo la venta;
            ya no reflejan la situación actual del cliente.
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
                    <div class="tipo-doc">{{ $titulo }}</div>
                    <div class="num-doc">{{ '#' . $operacion->numero }}</div>
                    @if ($operacion->fecha)
                        <div class="fecha-doc">{{ optional($operacion->fecha)->format('d/m/Y H:i') }}</div>
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
                    <div class="dato">
                        {{ $cliente->codigo ?? 'sin código' }} · {{ $cliente->telefono ?? 'sin teléfono' }}
                        @if ($cliente->direccion)<br>{{ $cliente->direccion }}@endif
                    </div>
                </div>
            </td>
            <td class="b">
                <div class="tarjeta">
                    <div class="rotulo">Emitido por</div>
                    <div class="dato-fuerte">{{ $operacion->user->name ?? '—' }}</div>
                </div>
            </td>
        </tr>
    </table>

       @if ($operacion->no_abono)
        <p class="vacio" style="margin-top:18px;">El cliente no realizó movimientos en esta visita.</p>
        @if ($operacion->no_abono_descripcion)
            <div class="linea-info" style="margin-top:10px;">{{ $operacion->no_abono_descripcion }}</div>
        @endif
    @else

        {{-- ===== Devoluciones ===== --}}
        @if ($operacion->devoluciones->isNotEmpty())
            <div class="seccion">Devoluciones</div>
            <table class="tabla">
                <thead>
                    <tr><th>Producto</th><th class="monto">Cantidad</th><th class="monto">Monto</th></tr>
                </thead>
                <tbody>
                    @foreach ($operacion->devoluciones as $dev)
                        @foreach ($dev->lineas as $linea)
                            <tr>
                                <td>{{ $linea->descripcion }}</td>
                                <td class="monto">{{ (int) $linea->cantidad }}</td>
                                <td class="monto">₡{{ number_format($linea->precio_total, 2) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        @endif

         {{-- ===== Traspaso de cuenta ===== --}}
        @if ($operacion->tipo === 'traspaso')
            <div class="seccion">Traspaso de cuenta</div>
            <div class="linea-info">
                @if ($operacion->facturas->isNotEmpty())
                    Se le trasladó una deuda de <strong>₡{{ number_format($operacion->facturas->first()->total, 2) }}</strong>
                    desde el cliente <strong>{{ optional($operacion->traspasoCliente)->nombre ?? '—' }}</strong>.
                @else
                    Se trasladó una deuda de <strong>₡{{ number_format(abs($operacion->saldo_final - $operacion->saldo_inicial), 2) }}</strong>
                    hacia el cliente <strong>{{ optional($operacion->traspasoCliente)->nombre ?? '—' }}</strong>.
                @endif
            </div>
        @endif

        {{-- ===== Venta ===== --}}
        @if ($operacion->facturas->isNotEmpty() && $operacion->facturas->first()->lineas->isNotEmpty())
            @php $factura = $operacion->facturas->first(); @endphp
            <div class="seccion">
                Venta
                <span class="chip">{{ $factura->estado === 'credito' ? 'Crédito ' . $factura->plazo . ' días' : 'Contado' }}</span>
            </div>
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="monto">Cant.</th>
                        <th class="monto">Precio</th>
                        <th class="monto">Descuento</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($factura->lineas as $linea)
                        <tr>
                            <td>{{ $linea->descripcion }}</td>
                            <td class="monto">{{ (int) $linea->cantidad }}</td>
                            <td class="monto">₡{{ number_format($linea->precio_unit, 2) }}</td>
                            <td class="monto">₡{{ number_format($linea->descuento, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($factura->estado !== 'credito')
                <div class="linea-info">
                    Efectivo: <strong>₡{{ number_format($factura->efectivo, 2) }}</strong> ·
                    Sinpe: <strong>₡{{ number_format($factura->sinpe, 2) }}</strong>
                    @if ($factura->vuelto > 0) · Vuelto: <strong>₡{{ number_format($factura->vuelto, 2) }}</strong> @endif
                    @if ($factura->saldo_favor_aplicado > 0) · Saldo a favor aplicado: <strong>₡{{ number_format($factura->saldo_favor_aplicado, 2) }}</strong> @endif
                    @if ($factura->sinpe > 0 && $factura->comprobante_sinpe && !$paraPdf)
                        · <a href="{{ route('operaciones.foto-venta', $operacion) }}" target="_blank">Ver comprobante de sinpe</a>
                    @endif
                </div>
            @endif
        @endif

        {{-- ===== Abono ===== --}}
        @if ($operacion->abonos->isNotEmpty())
            <div class="seccion">Abono</div>
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Aplicado a</th>
                        <th class="monto">Efectivo</th>
                        <th class="monto">Sinpe</th>
                        <th class="monto">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($operacion->abonos as $ab)
                        <tr>
                            <td>
                                {{ $destinos[$ab->id] ?? 'Factura' }}
                                @if ($ab->sinpe > 0 && $ab->comprobante_sinpe && !$paraPdf)
                                    <br><a href="{{ route('operaciones.foto-abono', $operacion) }}" target="_blank" class="tenue">Ver comprobante de sinpe</a>
                                @endif
                            </td>
                            <td class="monto">₡{{ number_format($ab->efectivo, 2) }}</td>
                            <td class="monto">₡{{ number_format($ab->sinpe, 2) }}</td>
                            <td class="monto"><strong>₡{{ number_format($ab->monto_abono, 2) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    {{-- ===== Saldo ===== --}}
    <table class="resumen">
        <tr>
            <td>Saldo anterior</td>
            <td class="monto">₡{{ number_format($operacion->saldo_inicial, 2) }}</td>
        </tr>
        <tr class="destacada {{ $saldoFavor ? 'favor' : '' }}">
            <td class="ini">Saldo final{{ $saldoFavor ? ' (a favor)' : '' }}</td>
            <td class="monto fin">₡{{ number_format(abs($operacion->saldo_final), 2) }}</td>
        </tr>
    </table>

      {{-- ===== Estado de la cuenta ===== --}}
    @if (!empty($cuentas['filas']))
        <div class="seccion">Estado de la cuenta</div>
        <table class="tabla">
            <thead>
                <tr>
                    <th>Cuenta</th>
                    <th class="monto">Debía antes</th>
                    <th class="monto">Movimiento</th>
                    <th class="monto">Debe ahora</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cuentas['filas'] as $fila)
                    <tr class="{{ $fila['resaltar'] ? 'fila-resaltada' : '' }}">
                        <td>
                            {{ $fila['etiqueta'] }}
                            @if ($fila['fecha']) <span class="tenue">· {{ $fila['fecha'] }}</span> @endif
                            @if ($fila['nota']) <span class="tenue">· {{ $fila['nota'] }}</span> @endif
                        </td>
                        <td class="monto">{{ $dinero($fila['antes']) }}</td>
                        <td class="monto">{{ $movimiento($fila['mov']) }}</td>
                        <td class="monto">{{ $dinero($fila['despues']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fila-total">
                    <td>Total</td>
                    <td class="monto">{{ $dinero($cuentas['totales']['antes']) }}</td>
                    <td class="monto">{{ $movimiento($cuentas['totales']['mov']) }}</td>
                    <td class="monto">
                        {{ $dinero($cuentas['totales']['despues']) }}
                        @if ($cuentas['totales']['despues'] < 0) <span class="tenue">(a favor)</span> @endif
                    </td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="pie">
        <div class="gracias">Gracias por su preferencia</div>
        <div>{{ $marcaNombre }}</div>
    </div>

</div>
</body>
</html>