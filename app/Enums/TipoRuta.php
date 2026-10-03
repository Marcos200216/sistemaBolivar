<?php
// app/Enums/TipoRuta.php

namespace App\Enums;

enum TipoRuta: string
{
    case Ordenada = 'ordenada';
    case Aleatoria = 'aleatoria';

    public function label(): string
    {
        return match ($this) {
            self::Ordenada => 'Ordenada',
            self::Aleatoria => 'Aleatoria',
        };
    }
}