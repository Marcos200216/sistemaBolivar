@php
    // El nombre del destinatario es lo más importante de la guía: se achica según su largo
    $nombreMay = mb_strtoupper($nombre);
    $largo = mb_strlen($nombreMay);
    $tamNombre = $largo <= 14 ? 26 : ($largo <= 26 ? 21 : ($largo <= 40 ? 17 : 14));
    $tamDireccion = mb_strlen($direccion) > 150 ? 10.5 : 12.5;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Guía de envío</title>
    <style>
        @page { margin: 0; }
        body {
            margin: 0;
            font-family: Helvetica, Arial, sans-serif;
            color: #101828;
        }

        /* ---------- Cabecera azul ---------- */
        .cabecera {
            background: #0A2E6E;
            padding: 22px 30px 28px;
            text-align: center;
        }
        .cabecera-tag {
            font-size: 7.5pt;
            letter-spacing: 2px;
            color: #9FB4D9;
            text-transform: uppercase;
            margin-bottom: 18px;
        }
        .caja-logo {
            display: inline-block;
            background: #FFFFFF;
            border-radius: 10px;
            padding: 10px 18px;
            margin-bottom: 18px;
        }
        .caja-logo img { height: 54px; }
        .marca-texto { font-size: 15pt; font-weight: bold; color: #0A2E6E; }

        .cabecera-nombre {
            font-size: 21pt;
            letter-spacing: 1.5px;
            color: #FFFFFF;
            text-transform: uppercase;
            line-height: 1.15;
        }
        .franja { height: 6px; background: #2E6BD6; }

        /* ---------- Cuerpo blanco ---------- */
        .cuerpo { padding: 24px 30px 0; }

        table.guia { width: 100%; border-collapse: collapse; }
        table.guia td {
            border: 1px solid #0A2E6E;
            padding: 10px 14px 12px;
            vertical-align: top;
        }
        .rotulo {
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 1.2px;
            color: #0A2E6E;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .valor-de { font-size: 12.5pt; font-weight: bold; color: #101828; }
        .valor-nombre { font-weight: bold; line-height: 1.15; color: #101828; }
        .valor-dir { line-height: 1.4; color: #101828; }
        .valor-tel { font-size: 18pt; font-weight: bold; color: #0A2E6E; letter-spacing: 0.6px; }

        /* ---------- Pie ---------- */
        .pie {
            position: absolute; left: 0; right: 0; bottom: 0;
            background: #0A2E6E; color: #C9D5E8;
            text-align: center;
            font-size: 7.5pt; letter-spacing: 1.5px;
            padding: 10px 30px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <div class="cabecera">
        <div class="cabecera-tag">Guía de envío</div>

        <div class="caja-logo">
            @if ($logo)
                <img src="{{ $logo }}" alt="Distribuidora Guana">
            @else
                <div class="marca-texto">Distribuidora Guana</div>
            @endif
        </div>

        <div class="cabecera-nombre">Distribuidora Guana</div>
    </div>
    <div class="franja"></div>

    <div class="cuerpo">
        <table class="guia">
            <tr>
                <td>
                    <div class="rotulo">De:</div>
                    <div class="valor-de">Distribuidora Guana</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="rotulo">Destinatario:</div>
                    <div class="valor-nombre" style="font-size: {{ $tamNombre }}pt;">{{ $nombreMay }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="rotulo">Dirección:</div>
                    <div class="valor-dir" style="font-size: {{ $tamDireccion }}pt;">{!! nl2br(e($direccion)) !!}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="rotulo">Teléfono:</div>
                    <div class="valor-tel">{{ $telefono !== '' ? $telefono : '—' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="pie">Distribuidora Guana · Guía de envío</div>

</body>
</html>