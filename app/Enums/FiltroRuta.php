<?php
// app/Enums/FiltroRuta.php

namespace App\Enums;

enum FiltroRuta: string
{
    case Activos = 'activos';
    case Cancelados = 'cancelados';

    public function label(): string
    {
        return match ($this) {
            self::Activos => 'Activos',
            self::Cancelados => 'Cancelados',
        };
    }
}