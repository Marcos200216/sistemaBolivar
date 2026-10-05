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
use App\Models\Sucursal;

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
                        'traspaso' => $this->infoTraspaso($operacion),
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

        // Facturas migradas: solo entran como fila si ESTA operación les movió algo.
        $idsMigradasMovidas = Abono::where('operacion_id', $operacion->id)->whereNotNull('factura_id')->pluck('factura_id')
            ->merge(Devolucion::where('operacion_id', $operacion->id)->pluck('factura_id'))
            ->unique()->values();

        $facturas = Factura::query()
            ->where('cliente_id', $cliente->id)
            ->whereIn('estado', [EstadoFactura::Credito->value, EstadoFactura::Saldada->value])
            ->where(function ($q) use ($operacion, $idsMigradasMovidas) {
                $q->where(function ($n) use ($operacion) {
                    $n->whereNotNull('operacion_id')->where('operacion_id', '<=', $operacion->id);
                })->orWhere(function ($mg) use ($idsMigradasMovidas) {
                    $mg->whereNull('operacion_id')->whereIn('id', $idsMigradasMovidas);
                });
            })
            ->with('operacion')
            ->orderBy('facturas.operacion_id')
            ->orderByRaw('COALESCE(facturas.fecha, facturas.created_at)')
            ->orderBy('facturas.id')
            ->get();

        $filas = [];
        $sumAntes = 0;
        $sumDespues = 0;

        foreach ($facturas as $f) {
            // Migrada: parte de saldo_migrado (ya refleja lo abonado en el sistema anterior)
            $total = $f->operacion_id === null ? $c($f->saldo_migrado) : $c($f->total);

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

            $fecha = $f->fecha ?? $f->operacion?->fecha ?? $f->operacion?->created_at ?? $f->created_at;
            $numero = $f->operacion_id ? $f->operacion?->numero : $f->factura_id_legacy;

            $notaCreada = $f->operacion?->tipo === 'traspaso' ? 'traspaso de cuenta' : 'compra de esta visita';
            $filas[] = [
                'etiqueta' => 'Factura #' . ($numero ?? '—'),
                'fecha' => optional($fecha)->format('d/m/Y'),
                'nota' => $creada ? $notaCreada : null,
                'antes' => $m($antes),
                'mov' => $m($despues - $antes),
                'despues' => $m($despues),
                'resaltar' => $abonadoEsta > 0,
            ];
        }

        // Todo lo que no es una fila de arriba: deuda del sistema anterior sin movimiento o saldo a favor
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

        /** Datos del traspaso para el comprobante: dirección (origen/destino), tipo (deuda o saldo a favor) y monto. */
    private function infoTraspaso(Operacion $operacion): ?array
    {
        if ($operacion->tipo !== 'traspaso') {
            return null;
        }

        // El origen siempre se crea primero: su pareja tiene el número siguiente
        $esOrigen = Operacion::withoutGlobalScopes()
            ->where('sucursal_id', $operacion->sucursal_id)
            ->where('cliente_id', $operacion->traspaso_cliente_id)
            ->where('traspaso_cliente_id', $operacion->cliente_id)
            ->where('tipo', 'traspaso')
            ->where('numero', $operacion->numero + 1)
            ->exists();

        $efecto = round((float) $operacion->saldo_final - (float) $operacion->saldo_inicial, 2);
        $aFavor = $esOrigen ? ((float) $operacion->saldo_inicial < 0) : ($efecto < 0);
        $otro = Cliente::withoutGlobalScopes()->find($operacion->traspaso_cliente_id)?->nombre ?? '—';

        return [
            'esOrigen' => $esOrigen,
            'aFavor' => $aFavor,
            'monto' => abs($efecto),
            'otro' => $otro,
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


        // ------------------------------------------------------------------
    // Recibos de lo migrado (operacion_id NULL), para PDF y WhatsApp.
    // Misma lógica que ComprobanteController::reciboHistorico() y
    // AbonoController::facturaHistorica(), pero el canal sale de la
    // sucursal del cliente (no de la sesión).
    // ------------------------------------------------------------------

    private function canalDeCliente(?Cliente $cliente): string
    {
        $canal = ($cliente ? Sucursal::find($cliente->sucursal_id)?->canal : null) ?? 'normal';

        return $canal instanceof \BackedEnum ? $canal->value : (string) $canal;
    }

    public function datosReciboHistorico(Abono $abono, Cliente $cliente, bool $paraPdf): array
    {
        $factura = $abono->factura_id
            ? Factura::withoutGlobalScopes()->where('cliente_id', $cliente->id)->find($abono->factura_id)
            : null;

        $numeroFactura = null;
        if ($factura) {
            $numeroFactura = $factura->operacion_id
                ? optional(Operacion::withoutGlobalScopes()->find($factura->operacion_id))->numero
                : $factura->factura_id_legacy;
        }

        $monto = round((float) $abono->monto_abono, 2);
        $despues = round((float) $abono->saldo_final, 2);
        $antes = round($despues + $monto, 2);
        $recalculado = abs($antes - round((float) $abono->saldo_inicial, 2)) > 0.005;
        $sinDesglose = $monto > 0
            && round((float) $abono->efectivo + (float) $abono->sinpe, 2) < $monto;

        $canal = $this->canalDeCliente($cliente);

        return [
            'abono' => $abono,
            'cliente' => $cliente,
            'factura' => $factura,
            'numeroFactura' => $numeroFactura,
            'facturaAnulada' => $factura && (string) $factura->estado === 'anulada',
            'antes' => $antes,
            'despues' => $despues,
            'recalculado' => $recalculado,
            'sinDesglose' => $sinDesglose,
            'nombreCanal' => $canal === 'mayorista' ? 'Distribuidora Guana' : 'Distribuidora Azur',
            'logo' => $canal === 'mayorista' ? 'fondo_guana.png' : 'fondo_azur.png',
            'paraPdf' => $paraPdf,
        ];
    }

    public function datosFacturaHistorica(Factura $factura, bool $paraPdf): array
    {
        $factura->loadMissing('lineas', 'cliente');

        $canal = $this->canalDeCliente($factura->cliente);

        $etiqueta = match ((string) $factura->estado) {
            'contado' => 'Contado',
            'saldada' => 'Crédito saldado',
            'anulada' => 'Anulada',
            default => 'Crédito',
        };

        $f = $factura->fecha ?? $factura->created_at;

        return [
            'factura' => $factura,
            'nombreCanal' => $canal === 'mayorista' ? 'Distribuidora Guana' : 'Distribuidora Azur',
            'logo' => $canal === 'mayorista' ? 'fondo_guana.png' : 'fondo_azur.png',
            'etiqueta' => $etiqueta,
            'fecha' => $f ? \Illuminate\Support\Carbon::parse($f) : null,
            'paraPdf' => $paraPdf,
        ];
    }

    public function generarPdfReciboHistorico(Abono $abono, Cliente $cliente): string
    {
        return Pdf::loadView('admin.recibo-historico', $this->datosReciboHistorico($abono, $cliente, true))
            ->setPaper('letter')
            ->output();
    }

    public function generarPdfFacturaHistorica(Factura $factura): string
    {
        return Pdf::loadView('admin.factura-historica', $this->datosFacturaHistorica($factura, true))
            ->setPaper('letter')
            ->output();
    }

    public function nombreArchivoAbonoHistorico(Abono $abono): string
    {
        return "recibo-abono-historico-{$abono->id}.pdf";
    }

    public function nombreArchivoFacturaHistorica(Factura $factura): string
    {
        return 'factura-' . ($factura->factura_id_legacy ?? $factura->id) . '.pdf';
    }
}