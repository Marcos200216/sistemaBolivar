<?php

namespace App\Enums;

enum EstadoFactura: string
{
    case Contado = 'contado';
    case Credito = 'credito';
    case Saldada = 'saldada';
    case Anulada = 'anulada';

    public static function desdeLegacy(string $letra): self
    {
        return match ($letra) {
            'C' => self::Contado,
            'F' => self::Credito,
            'R' => self::Saldada,
            'E' => self::Anulada,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Contado => 'Contado',
            self::Credito => 'Crédito activo',
            self::Saldada => 'Saldada',
            self::Anulada => 'Anulada',
        };
    }
}