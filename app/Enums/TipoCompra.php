<?php


// app/Enums/TipoCompra.php
namespace App\Enums;

enum TipoCompra: string
{
    case Contado = 'contado';
    case Credito = 'credito';

    public function label(): string
    {
        return match ($this) {
            self::Contado => 'Contado',
            self::Credito => 'Crédito',
        };
    }
}