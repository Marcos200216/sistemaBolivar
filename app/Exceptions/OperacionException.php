<?php
// app/Exceptions/OperacionException.php

namespace App\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;

/**
 * Error de negocio al guardar una operación (stock, límite de crédito, abono
 * excedido, precio faltante, foto faltante...). El mensaje es para el usuario.
 */
class OperacionException extends RuntimeException
{
    public function render(Request $request)
    {
        return response()->json(['mensaje' => $this->getMessage()], 422);
    }
}