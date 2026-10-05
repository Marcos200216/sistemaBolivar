<?php
// app/Services/ReciboService.php

namespace App\Services;

use App\Models\Abono;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Operacion;
use App\Models\ReciboEnvio;
use App\Models\Sucursal;
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

    // ------------------------------------------------------------------
    // Lo migrado del sistema viejo (operacion_id NULL)
    // ------------------------------------------------------------------

    /** Reenvía el recibo de un abono migrado (genera el PDF la primera vez y lo guarda). */
    public function reenviarAbonoHistorico(Abono $abono, Cliente $cliente, ?int $usuarioId = null): ?ReciboEnvio
    {
        try {
            return $this->reenviarHistorico(
                ['abono_id' => $abono->id],
                $cliente,
                fn () => $this->pdfs->generarPdfReciboHistorico($abono, $cliente),
                $this->pdfs->nombreArchivoAbonoHistorico($abono),
                "s{$cliente->sucursal_id}-abono-{$abono->id}",
                $usuarioId
            );
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Reenvía la factura de una compra migrada (genera el PDF la primera vez y lo guarda). */
    public function reenviarFacturaHistorica(Factura $factura, ?int $usuarioId = null): ?ReciboEnvio
    {
        try {
            $cliente = $factura->cliente;
            if (!$cliente) {
                return null;
            }

            return $this->reenviarHistorico(
                ['factura_id' => $factura->id],
                $cliente,
                fn () => $this->pdfs->generarPdfFacturaHistorica($factura),
                $this->pdfs->nombreArchivoFacturaHistorica($factura),
                "s{$cliente->sucursal_id}-factura-{$factura->id}",
                $usuarioId
            );
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    private function reenviarHistorico(array $ref, Cliente $cliente, \Closure $generar, string $nombreArchivo, string $prefijo, ?int $usuarioId): ReciboEnvio
    {
        $columna = array_key_first($ref);

        $previo = ReciboEnvio::where($columna, $ref[$columna])
            ->whereNotNull('ruta_pdf')
            ->latest('id')
            ->first();

        if ($previo && Storage::disk('comprobantes')->exists($previo->ruta_pdf)) {
            $ruta = $previo->ruta_pdf;
        } else {
            try {
                $ruta = 'recibos/' . now()->format('Y-m') . "/{$prefijo}-" . Str::lower(Str::random(8)) . '.pdf';

                if (!Storage::disk('comprobantes')->put($ruta, $generar())) {
                    throw new RuntimeException("No se pudo escribir el PDF en el disco 'comprobantes': {$ruta}");
                }
            } catch (Throwable $e) {
                report($e);

                return $this->registrarHistorico($ref, $cliente, [
                    'estado' => ReciboEnvio::FALLIDO,
                    'error' => $this->recortar('No se pudo generar o guardar el PDF: ' . $e->getMessage()),
                    'reenviado_por' => $usuarioId,
                ]);
            }
        }

        return $this->enviarHistorico($ref, $cliente, $ruta, $nombreArchivo, $usuarioId);
    }

    private function enviarHistorico(array $ref, Cliente $cliente, string $ruta, string $nombreArchivo, ?int $usuarioId): ReciboEnvio
    {
        $canal = Sucursal::find($cliente->sucursal_id)?->canal ?? 'normal';
        $canal = $canal instanceof \BackedEnum ? $canal->value : (string) $canal;

        $base = [
            'ruta_pdf' => $ruta,
            'telefono' => $cliente->telefono,
            'reenviado_por' => $usuarioId,
        ];

        $telefono = WhatsAppService::normalizarTelefono($cliente->telefono);

        if ($telefono === null) {
            $sinDigitos = preg_replace('/\D/', '', (string) $cliente->telefono) === '';

            return $this->registrarHistorico($ref, $cliente, $base + [
                'estado' => $sinDigitos ? ReciboEnvio::SIN_TELEFONO : ReciboEnvio::INVALIDO,
            ]);
        }

        try {
            $r = $this->whatsapp->enviarRecibo($telefono, $ruta, (string) $cliente->nombre, $canal, $nombreArchivo);

            return $this->registrarHistorico($ref, $cliente, $base + [
                'estado' => ReciboEnvio::ENVIADO,
                'destino' => $r['destino'],
                'plantilla' => $r['plantilla'],
                'whatsapp_message_id' => $r['message_id'],
                'enviado_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->registrarHistorico($ref, $cliente, $base + [
                'estado' => ReciboEnvio::FALLIDO,
                'error' => $this->recortar($e->getMessage()),
            ]);
        }
    }

    private function registrarHistorico(array $ref, Cliente $cliente, array $datos): ReciboEnvio
    {
        return ReciboEnvio::create($datos + $ref + [
            'operacion_id' => null,
            'cliente_id' => $cliente->id,
            'sucursal_id' => $cliente->sucursal_id,
        ]);
    }

    // ------------------------------------------------------------------
    // Operaciones nuevas (sin cambios)
    // ------------------------------------------------------------------

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