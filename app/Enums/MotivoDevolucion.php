<?php

// app/Enums/MotivoDevolucion.php
namespace App\Enums;

enum MotivoDevolucion: string
{
    case NotaCredito = 'nota_credito';
    case BajaRotacion = 'baja_rotacion';
    case ProductoMalEstado = 'producto_mal_estado';
    case NoPedido = 'no_pedido';

    public static function desdeLegacy(string $codigo): self
    {
        return match ($codigo) {
            'NC' => self::NotaCredito,
            'BR' => self::BajaRotacion,
            'PM' => self::ProductoMalEstado,
            'NP' => self::NoPedido,
            default => self::NotaCredito, // fallback defensivo, no debería pasar
        };
    }

    /** Texto para mostrar en pantalla. */
    public function label(): string
    {
        return match ($this) {
            self::NotaCredito => 'Nota de crédito',
            self::BajaRotacion => 'Baja rotación',
            self::ProductoMalEstado => 'Producto en mal estado',
            self::NoPedido => 'No pedido',
        };
    }
}