<?php
// app/Http/Controllers/ComprobanteController.php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Operacion;
use App\Models\Sucursal;
use App\Services\ComprobantePdfService;
use Illuminate\Support\Facades\Storage;
use App\Models\ReciboEnvio;
use App\Services\ReciboService;
use Illuminate\Support\Facades\Auth;

class ComprobanteController extends Controller
{
    public function __construct(private ComprobantePdfService $comprobantes)
    {
    }

    /** Vista en pantalla (para imprimir desde el navegador o guardar como imagen). */
    public function mostrar(Operacion $operacion)
    {
        return view('admin.comprobante', $this->comprobantes->datos($operacion, paraPdf: false));
    }

    /** Descarga/stream del comprobante como PDF. */
    public function pdf(Operacion $operacion)
    {
                return $this->comprobantes->crearPdf($operacion)->stream($this->comprobantes->nombreArchivo($operacion));
    }

        /**
     * Guarda el PDF y envía el recibo por WhatsApp. Lo llama la pantalla DESPUÉS de
     * guardar la operación, en una petición aparte, para que guardar no espere a Meta.
     * Es idempotente: si la operación ya tiene un envío registrado, no vuelve a enviar.
     */
    public function emitirRecibo(Operacion $operacion, ReciboService $recibos)
    {
        ignore_user_abort(true);
        set_time_limit(120);

        $existente = ReciboEnvio::where('operacion_id', $operacion->id)->latest('id')->first();
        if ($existente) {
            return response()->json(['recibo' => $existente->estado, 'repetido' => true]);
        }

        $recibo = $recibos->emitir($operacion, Auth::id());

        return response()->json(['recibo' => $recibo?->estado]);
    }

    /** Abre exactamente el PDF guardado que se le envió al cliente. */
public function verRecibo(Operacion $operacion)
{
    $envio = ReciboEnvio::where('operacion_id', $operacion->id)
        ->whereNotNull('ruta_pdf')
        ->latest('id')
        ->first();

    if (!$envio || !Storage::disk('comprobantes')->exists($envio->ruta_pdf)) {
        abort(404, 'No hay PDF guardado para esta operación.');
    }

    return Storage::disk('comprobantes')->response(
        $envio->ruta_pdf,
        $this->comprobantes->nombreArchivo($operacion),
        ['Content-Type' => 'application/pdf']
    );
}

/** Reenvío manual por WhatsApp del PDF ya guardado. */
public function reenviarRecibo(Operacion $operacion, ReciboService $recibos)
{
    ignore_user_abort(true);
    set_time_limit(120);

    $recibo = $recibos->reenviar($operacion, Auth::id());

    return response()->json(['recibo' => $recibo?->estado]);
}

    /** Abre el PDF guardado del recibo de un abono migrado. */
    public function verReciboAbono(Abono $abono)
    {
        abort_if(!Cliente::find($abono->cliente_id), 404);

        return $this->responderPdfGuardado('abono_id', $abono->id, $this->comprobantes->nombreArchivoAbonoHistorico($abono));
    }

    /** Reenvío por WhatsApp del recibo de un abono migrado. */
    public function reenviarReciboAbono(Abono $abono, ReciboService $recibos)
    {
        ignore_user_abort(true);
        set_time_limit(120);

        $cliente = Cliente::find($abono->cliente_id);
        abort_if(!$cliente, 404);
        abort_if($abono->operacion_id !== null || (float) $abono->monto_abono <= 0, 422, 'Este abono no se puede reenviar como migrado.');

        $recibo = $recibos->reenviarAbonoHistorico($abono, $cliente, Auth::id());

        return response()->json(['recibo' => $recibo?->estado]);
    }

    /** Abre el PDF guardado de la factura de una compra migrada. */
    public function verReciboFactura(Factura $factura)
    {
        return $this->responderPdfGuardado('factura_id', $factura->id, $this->comprobantes->nombreArchivoFacturaHistorica($factura));
    }

    /** Reenvío por WhatsApp de la factura de una compra migrada. */
    public function reenviarReciboFactura(Factura $factura, ReciboService $recibos)
    {
        ignore_user_abort(true);
        set_time_limit(120);

        abort_if($factura->operacion_id !== null, 422, 'Esta factura tiene comprobante propio.');

        $recibo = $recibos->reenviarFacturaHistorica($factura, Auth::id());

        return response()->json(['recibo' => $recibo?->estado]);
    }

    private function responderPdfGuardado(string $columna, int $id, string $nombre)
    {
        $envio = ReciboEnvio::where($columna, $id)->whereNotNull('ruta_pdf')->latest('id')->first();

        if (!$envio || !Storage::disk('comprobantes')->exists($envio->ruta_pdf)) {
            abort(404, 'No hay PDF guardado.');
        }

        return Storage::disk('comprobantes')->response($envio->ruta_pdf, $nombre, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Sirve la foto del comprobante de sinpe de la VENTA de esta operación.
     * El control de acceso es gratis: Operacion tiene BelongsToSucursal, así
     * que el route model binding ya filtra por la sucursal activa en la
     * sesión — si la operación es de otra sucursal, esto da 404 antes de
     * siquiera llegar acá.
     */
    public function fotoVenta(Operacion $operacion)
    {
        $operacion->loadMissing('facturas');
        $ruta = $operacion->facturas->first()?->comprobante_sinpe;

        return $this->responderFoto($ruta);
    }

    /** Igual que fotoVenta(), pero para el comprobante de sinpe del ABONO. */
    public function fotoAbono(Operacion $operacion)
    {
        $operacion->loadMissing('abonos');
        $ruta = $operacion->abonos->whereNotNull('comprobante_sinpe')->first()?->comprobante_sinpe;

        return $this->responderFoto($ruta);
    }

    /**
     * Recibo de un abono MIGRADO del sistema viejo (operacion_id NULL).
     * No se modifica ningún dato guardado: en lo migrado saldo_final sí encadena
     * pero saldo_inicial a veces repite el total original de la factura, así que
     * el "antes" se recalcula solo para mostrarlo (después + abono).
     *
     * Abono no tiene el trait BelongsToSucursal: el control de acceso se hace a
     * mano con Cliente::find() (que sí está scoped por sucursal).
     */
    public function reciboHistorico(Abono $abono)
    {
        $cliente = Cliente::find($abono->cliente_id);
        abort_if(!$cliente, 404);

        // Factura a la que se aplicó el abono (solo para mostrar su número).
        // Se exige que sea del mismo cliente.
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
        $inicialGuardado = round((float) $abono->saldo_inicial, 2);
        $recalculado = abs($antes - $inicialGuardado) > 0.005;

        $sinDesglose = $monto > 0
            && round((float) $abono->efectivo + (float) $abono->sinpe, 2) < $monto;

        $canal = Sucursal::find(session('sucursal_id'))?->canal ?? 'normal';
        $nombreCanal = $canal === 'mayorista' ? 'Distribuidora Guana' : 'Distribuidora Azur';
        $logo = $canal === 'mayorista' ? 'fondo_guana.png' : 'fondo_azur.png';

        return view('admin.recibo-historico', [
            'abono' => $abono,
            'cliente' => $cliente,
            'factura' => $factura,
            'numeroFactura' => $numeroFactura,
            'facturaAnulada' => $factura && (string) $factura->estado === 'anulada',
            'antes' => $antes,
            'despues' => $despues,
            'recalculado' => $recalculado,
            'sinDesglose' => $sinDesglose,
            'nombreCanal' => $nombreCanal,
            'logo' => $logo,
        ]);
    }

    private function responderFoto(?string $ruta)
    {
        if (!$ruta || !Storage::disk('comprobantes')->exists($ruta)) {
            abort(404, 'No hay foto de comprobante para esta operación.');
        }

        return Storage::disk('comprobantes')->response($ruta);
    }
}