<?php
// app/Enums/EstadoRutaCliente.php

namespace App\Enums;

enum EstadoRutaCliente: string
{
    case Pendiente = 'pendiente';
    case Finalizado = 'finalizado';
    case NoAbono = 'no_abono';
    case Recobro = 'recobro';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Finalizado => 'Finalizado',
            self::NoAbono => 'No abonó',
            self::Recobro => 'Recobro',
        };
    }
}