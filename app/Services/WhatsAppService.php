<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class WhatsAppService
{
    /** Devuelve 506XXXXXXXX o null si el teléfono no sirve. */
       /** Devuelve 506XXXXXXXX o null si el teléfono no sirve o no parece de WhatsApp. */
    public static function normalizarTelefono(?string $telefono): ?string
    {
        $d = preg_replace('/\D/', '', (string) $telefono);

        if (strlen($d) === 11 && str_starts_with($d, '506')) {
            $d = substr($d, 3);
        }

        // 8 dígitos y que empiece con 5, 6, 7 u 8 (móviles); fijos y otros no
        if (strlen($d) === 8 && in_array($d[0], ['5', '6', '7', '8'], true)) {
            return '506' . $d;
        }

        return null;
    }

    public function plantillaPorCanal(string $canal): string
    {
        $nombre = config('services.whatsapp.templates.' . $canal);
        if (!$nombre) {
            throw new RuntimeException("No hay plantilla de WhatsApp configurada para el canal «{$canal}».");
        }

        return $nombre;
    }

    /**
     * Sube el PDF guardado a Meta y lo envía con la plantilla del canal.
     *
     * @param string $telefono      Ya normalizado (506XXXXXXXX)
     * @param string $rutaPdf       Ruta relativa dentro del disco 'comprobantes'
     * @return array{message_id: ?string, destino: string, plantilla: string}
     */
    public function enviarRecibo(string $telefono, string $rutaPdf, string $nombreCliente, string $canal, string $nombreArchivo): array
    {
        $token = (string) config('services.whatsapp.token');
        $phoneId = (string) config('services.whatsapp.phone_number_id');
        if ($token === '' || $phoneId === '') {
            throw new RuntimeException('Falta configurar WHATSAPP_TOKEN o WHATSAPP_PHONE_NUMBER_ID.');
        }

        $base = 'https://graph.facebook.com/' . config('services.whatsapp.api_version', 'v21.0');
        $plantilla = $this->plantillaPorCanal($canal);

        // Modo prueba: todo va al número indicado, nunca al cliente
        $destino = $telefono;
        if ($prueba = config('services.whatsapp.test_to')) {
            $destino = self::normalizarTelefono($prueba)
                ?? throw new RuntimeException('WHATSAPP_TEST_TO no es un número válido.');
        }

        $contenido = Storage::disk('comprobantes')->get($rutaPdf);
        if ($contenido === null || $contenido === '') {
            throw new RuntimeException("No se encontró el PDF guardado: {$rutaPdf}");
        }

        // 1) Subir el PDF a Meta
        $subida = Http::withToken($token)->timeout(30)
            ->attach('file', $contenido, $nombreArchivo, ['Content-Type' => 'application/pdf'])
            ->post("{$base}/{$phoneId}/media", [
                'messaging_product' => 'whatsapp',
                'type' => 'application/pdf',
            ]);

        if ($subida->failed() || !$subida->json('id')) {
            $this->fallo('subida de PDF', $subida);
        }
        $mediaId = $subida->json('id');

        // 2) Enviar la plantilla (header documento + {{1}} = nombre)
        $nombre = trim(preg_replace('/\s+/', ' ', $nombreCliente));
        $nombre = $nombre !== '' ? $nombre : 'cliente';

        $envio = Http::withToken($token)->timeout(30)
            ->post("{$base}/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $destino,
                'type' => 'template',
                'template' => [
                    'name' => $plantilla,
                    'language' => ['code' => config('services.whatsapp.lang', 'es_MX')],
                    'components' => [
                        [
                            'type' => 'header',
                            'parameters' => [[
                                'type' => 'document',
                                'document' => ['id' => $mediaId, 'filename' => $nombreArchivo],
                            ]],
                        ],
                        [
                            'type' => 'body',
                            'parameters' => [['type' => 'text', 'text' => $nombre]],
                        ],
                    ],
                ],
            ]);

        if ($envio->failed()) {
            $this->fallo('envío de plantilla', $envio);
        }

        return [
            'message_id' => $envio->json('messages.0.id'),
            'destino' => $destino,
            'plantilla' => $plantilla,
        ];
    }

    private function fallo(string $etapa, $respuesta): never
    {
        Log::error("WhatsApp (Meta): falló {$etapa}", [
            'status' => $respuesta->status(),
            'body' => $respuesta->body(),
        ]);

        $detalle = $respuesta->json('error.message') ?? ('HTTP ' . $respuesta->status());

        throw new RuntimeException("WhatsApp: falló {$etapa}. {$detalle}");
    }
}