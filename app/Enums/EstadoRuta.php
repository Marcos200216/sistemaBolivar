<?php

namespace App\Enums;

enum EstadoRuta: string
{
    case EnProgreso = 'en_progreso';
    case Finalizada = 'finalizada';

    public function label(): string
    {
        return match ($this) {
            self::EnProgreso => 'En progreso',
            self::Finalizada => 'Finalizada',
        };
    }
}