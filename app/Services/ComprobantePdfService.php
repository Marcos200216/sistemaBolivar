<?php
// app/Services/ComprobantePdfService.php

namespace App\Services;

use App\Enums\EstadoFactura;
use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Devolucion;
use App\Models\Factura;
use App\Models\Operacion;
use Barryvdh\DomPDF\Facade\Pdf;

class ComprobantePdfService
{
    /** Objeto PDF listo para stream() o output(). Todos los tipos de operación pasan por acá. */
    public function crearPdf(Operacion $operacion): \Barryvdh\DomPDF\PDF
    {
        $datos = $this->datos($operacion, paraPdf: true);

        return Pdf::loadView('admin.comprobante', $datos)->setPaper('letter');
    }

    /** Binario del PDF (para guardarlo y enviarlo). */
    public function generarPdf(Operacion $operacion): string
    {
        return $this->crearPdf($operacion)->output();
    }

        /**
     * Título impreso y nombre de archivo del comprobante, según lo que trae la operación.
     * Un solo lugar para pantalla, PDF y WhatsApp.
     */
    public function tipoDocumento(Operacion $operacion): array
    {
        $operacion->loadMissing('facturas.lineas', 'abonos', 'devoluciones');

        if ($operacion->tipo === 'traspaso') {
            return ['titulo' => 'Comprobante de traspaso de cuenta', 'slug' => 'traspaso-cuenta'];
        }

        if ($operacion->no_abono) {
            return ['titulo' => 'Comprobante de visita', 'slug' => 'visita'];
        }

        $compra = $operacion->facturas->isNotEmpty() && $operacion->facturas->first()->lineas->isNotEmpty();
        $abono = $operacion->abonos->isNotEmpty();
        $devolucion = $operacion->devoluciones->isNotEmpty();

        $mapa = [
            '100' => ['Recibo de compra', 'recibo-compra'],
            '010' => ['Recibo de abono', 'recibo-abono'],
            '001' => ['Nota de devolución', 'nota-devolucion'],
            '110' => ['Recibo de compra y abono', 'recibo-compra-abono'],
            '011' => ['Recibo de abono y devolución', 'recibo-abono-devolucion'],
            '101' => ['Recibo de compra y devolución', 'recibo-compra-devolucion'],
            '111' => ['Recibo de movimientos', 'recibo-movimientos'],
        ];

        $clave = ($compra ? '1' : '0') . ($abono ? '1' : '0') . ($devolucion ? '1' : '0');
        [$titulo, $slug] = $mapa[$clave] ?? ['Comprobante', 'comprobante'];

        return ['titulo' => $titulo, 'slug' => $slug];
    }

    /** Nombre del archivo PDF (sin tildes ni espacios, seguro para el header de WhatsApp). */
    public function nombreArchivo(Operacion $operacion): string
    {
        return $this->tipoDocumento($operacion)['slug'] . "-{$operacion->numero}.pdf";
    }

    /**
     * Arma los datos del comprobante. El desglose de saldo NO se recalcula:
     * sale directo de operaciones.saldo_inicial / saldo_final, que
     * OperacionService ya guarda al momento de la visita.
     */
    public function datos(Operacion $operacion, bool $paraPdf): array
    {
        $operacion->load('cliente', 'user', 'sucursal', 'facturas.lineas', 'abonos', 'devoluciones.lineas');

        $canal = $operacion->sucursal->canal ?? 'normal';
        $nombreCanal = $canal === 'mayorista' ? 'Distribuidora Guana' : 'Distribuidora Azur';
        $logo = $canal === 'mayorista' ? 'fondo_guana.png' : 'fondo_azur.png';

        $cliente = $operacion->cliente;

        // Ítem 7 — si la factura de esta operación fue anulada después, se avisa
        // en el comprobante para que una reimpresión no engañe a nadie.
        $facturaAnulada = $operacion->facturas->firstWhere('estado', EstadoFactura::Anulada->value);
        if ($facturaAnulada) {
            $facturaAnulada->loadMissing('anuladaPor');
        }

        return [
            'operacion' => $operacion,
            'cliente' => $cliente,
            'canal' => $canal,
            'nombreCanal' => $nombreCanal,
            'logo' => $logo,
            'cuentas' => $this->cuentasAlEmitir($operacion, $cliente),
            'destinos' => $this->destinosAbono($operacion),
            'facturaAnulada' => $facturaAnulada,
            'paraPdf' => $paraPdf,
            'titulo' => $this->tipoDocumento($operacion)['titulo'],
        ];
    }

    /**
     * Estado de la cuenta del cliente AL MOMENTO de esta operación (no el de hoy),
     * así un comprobante reimpreso sale siempre igual.
     *
     * - Cada factura nueva de crédito se lista con lo que debía antes, el movimiento
     *   de esta visita y lo que debe después. Solo cuenta lo aplicado hasta esta
     *   operación (operacion_id <= la actual).
     * - Todo lo que no sea factura nueva (deuda del sistema anterior, saldo a favor)
     *   va en UNA línea calculada como saldo − suma de facturas nuevas, para que el
     *   total siempre coincida con operaciones.saldo_inicial / saldo_final.
     * - Todo en céntimos (enteros) para evitar errores de redondeo.
     */
    private function cuentasAlEmitir(Operacion $operacion, Cliente $cliente): array
    {
        $c = fn ($v) => (int) round(((float) $v) * 100);
        $m = fn (int $centimos) => round($centimos / 100, 2);

        $facturas = Factura::nuevas()
            ->where('cliente_id', $cliente->id)
            ->whereIn('estado', [EstadoFactura::Credito->value, EstadoFactura::Saldada->value])
            ->where('operacion_id', '<=', $operacion->id)
            ->with('operacion')
            ->orderBy('operacion_id')
            ->get();

        $filas = [];
        $sumAntes = 0;
        $sumDespues = 0;

        foreach ($facturas as $f) {
            $total = $c($f->total);

            $abonadoHasta = $c(Abono::where('factura_id', $f->id)->where('operacion_id', '<=', $operacion->id)->sum('monto_abono'));
            $abonadoEsta = $c(Abono::where('factura_id', $f->id)->where('operacion_id', $operacion->id)->sum('monto_abono'));
            $devueltoHasta = $c(Devolucion::where('factura_id', $f->id)->where('operacion_id', '<=', $operacion->id)->sum('total'));
            $devueltoEsta = $c(Devolucion::where('factura_id', $f->id)->where('operacion_id', $operacion->id)->sum('total'));

            $creada = (int) $f->operacion_id === (int) $operacion->id;

            $despues = max(0, $total - $abonadoHasta - $devueltoHasta);
            $antes = $creada
                ? 0
                : max(0, $total - ($abonadoHasta - $abonadoEsta) - ($devueltoHasta - $devueltoEsta));

            if (!$creada && $antes === 0 && $despues === 0) {
                continue; // ya estaba saldada antes de esta visita
            }

            $sumAntes += $antes;
            $sumDespues += $despues;

            $fecha = $f->operacion?->fecha ?? $f->operacion?->created_at ?? $f->created_at;

            $notaCreada = $f->operacion?->tipo === 'traspaso' ? 'traspaso de cuenta' : 'compra de esta visita';
            $filas[] = [
                'etiqueta' => 'Factura #' . ($f->operacion?->numero ?? '—'),
                'fecha' => optional($fecha)->format('d/m/Y'),
                'nota' => $creada ? $notaCreada : null,
                'antes' => $m($antes),
                'mov' => $m($despues - $antes),
                'despues' => $m($despues),
                'resaltar' => $abonadoEsta > 0,
            ];
        }

        // Todo lo que no es factura nueva: deuda del sistema anterior o saldo a favor
        $resAntes = $c($operacion->saldo_inicial) - $sumAntes;
        $resDespues = $c($operacion->saldo_final) - $sumDespues;

        if ($resAntes !== 0 || $resDespues !== 0) {
            $idsNuevas = $facturas->pluck('id');
            $abonoAlAnterior = $operacion->abonos->contains(fn ($a) => !$idsNuevas->contains($a->factura_id));

            $filas[] = [
                'etiqueta' => ($resAntes < 0 || $resDespues < 0) ? 'Saldo a favor / ajustes' : 'Deuda del sistema anterior',
                'fecha' => null,
                'nota' => null,
                'antes' => $m($resAntes),
                'mov' => $m($resDespues - $resAntes),
                'despues' => $m($resDespues),
                'resaltar' => $abonoAlAnterior,
            ];
        }

        $inicial = $c($operacion->saldo_inicial);
        $final = $c($operacion->saldo_final);

        return [
            'filas' => $filas,
            'totales' => [
                'antes' => $m($inicial),
                'mov' => $m($final - $inicial),
                'despues' => $m($final),
            ],
        ];
    }

    /**
     * Texto de a qué se aplicó cada abono de la operación, con el número que ve
     * el cliente (el de la operación en facturas nuevas, el del sistema anterior
     * en las migradas), no el id interno.
     */
    private function destinosAbono(Operacion $operacion): array
    {
        $destinos = [];

        foreach ($operacion->abonos as $ab) {
            if (!$ab->factura_id) {
                $destinos[$ab->id] = 'Deuda del sistema anterior';
                continue;
            }

            $f = Factura::withoutGlobalScopes()->with('operacion')->find($ab->factura_id);
            if (!$f) {
                $destinos[$ab->id] = 'Factura';
                continue;
            }

            $numero = $f->operacion_id ? $f->operacion?->numero : $f->factura_id_legacy;
            $destinos[$ab->id] = 'Factura #' . ($numero ?? '—')
                . ((string) $f->estado === 'anulada' ? ' (anulada)' : '');
        }

        return $destinos;
    }
}