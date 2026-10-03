<?php

// app/Enums/EstadoCompra.php
namespace App\Enums;

enum EstadoCompra: string
{
    case Pendiente = 'pendiente';
    case Pagada = 'pagada';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Pagada => 'Pagada',
        };
    }
}