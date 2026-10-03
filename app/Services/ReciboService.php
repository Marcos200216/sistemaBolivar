<?php
// app/Services/ReciboService.php

namespace App\Services;

use App\Models\Operacion;
use App\Models\ReciboEnvio;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ReciboService
{
    public function __construct(
        private ComprobantePdfService $pdfs,
        private WhatsAppService $whatsapp,
    ) {}

    /**
     * Guarda el PDF del comprobante en el disco 'comprobantes' (carpeta recibos),
     * intenta enviarlo por WhatsApp y deja el resultado en recibo_envios.
     * Nunca debe romper la operación ya guardada: cualquier fallo se reporta y se registra.
     */
    public function emitir(Operacion $operacion, ?int $usuarioId = null): ?ReciboEnvio
{
    try {
        try {
            $ruta = $this->guardarPdf($operacion);
        } catch (Throwable $e) {
            report($e);

            return $this->registrar($operacion, [
                'estado' => ReciboEnvio::FALLIDO,
                'error' => $this->recortar('No se pudo generar o guardar el PDF: ' . $e->getMessage()),
                'reenviado_por' => $usuarioId,
            ]);
        }

        return $this->enviar($operacion, $ruta, $usuarioId);
    } catch (Throwable $e) {
        report($e);

        return null;
    }
}

/**
 * Reenvía el MISMO PDF ya guardado (no genera uno nuevo). Si la operación nunca
 * llegó a guardar PDF (falló al generarlo) o el archivo ya no existe, lo emite de cero.
 */
public function reenviar(Operacion $operacion, ?int $usuarioId = null): ?ReciboEnvio
{
    try {
        $previo = ReciboEnvio::where('operacion_id', $operacion->id)
            ->whereNotNull('ruta_pdf')
            ->latest('id')
            ->first();

        if (!$previo || !Storage::disk('comprobantes')->exists($previo->ruta_pdf)) {
            return $this->emitir($operacion, $usuarioId);
        }

        return $this->enviar($operacion, $previo->ruta_pdf, $usuarioId);
    } catch (Throwable $e) {
        report($e);

        return null;
    }
}

    private function guardarPdf(Operacion $operacion): string
    {
        $binario = $this->pdfs->generarPdf($operacion);

        $ruta = 'recibos/' . now()->format('Y-m')
            . "/s{$operacion->sucursal_id}-n{$operacion->numero}-" . Str::lower(Str::random(8)) . '.pdf';

        if (!Storage::disk('comprobantes')->put($ruta, $binario)) {
            throw new RuntimeException("No se pudo escribir el PDF en el disco 'comprobantes': {$ruta}");
        }

        return $ruta;
    }

    private function enviar(Operacion $operacion, string $ruta, ?int $usuarioId): ReciboEnvio
    {
        $cliente = $operacion->cliente;
        $canal = $operacion->sucursal->canal ?? 'normal';

        $base = [
            'ruta_pdf' => $ruta,
            'telefono' => $cliente?->telefono,
            'reenviado_por' => $usuarioId,
        ];

        $telefono = WhatsAppService::normalizarTelefono($cliente?->telefono);

        if ($telefono === null) {
            $sinDigitos = preg_replace('/\D/', '', (string) $cliente?->telefono) === '';

            return $this->registrar($operacion, $base + [
                'estado' => $sinDigitos ? ReciboEnvio::SIN_TELEFONO : ReciboEnvio::INVALIDO,
            ]);
        }

        try {
            $r = $this->whatsapp->enviarRecibo(
                $telefono,
                $ruta,
                (string) $cliente->nombre,
                $canal,
                $this->pdfs->nombreArchivo($operacion),
            );

            return $this->registrar($operacion, $base + [
                'estado' => ReciboEnvio::ENVIADO,
                'destino' => $r['destino'],
                'plantilla' => $r['plantilla'],
                'whatsapp_message_id' => $r['message_id'],
                'enviado_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->registrar($operacion, $base + [
                'estado' => ReciboEnvio::FALLIDO,
                'error' => $this->recortar($e->getMessage()),
            ]);
        }
    }

    private function registrar(Operacion $operacion, array $datos): ReciboEnvio
    {
        return ReciboEnvio::create($datos + [
            'operacion_id' => $operacion->id,
            'cliente_id' => $operacion->cliente_id,
            'sucursal_id' => $operacion->sucursal_id,
        ]);
    }

    private function recortar(string $texto): string
    {
        return mb_substr($texto, 0, 500);
    }
}