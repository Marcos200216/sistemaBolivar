<?php
// app/Http/Controllers/CuentaPorCobrarController.php

namespace App\Http\Controllers;

use App\Models\Cliente;

class CuentaPorCobrarController extends Controller
{
    public function index()
    {
        $clientes = Cliente::where('saldo_actual', '>', 0)
            ->orderByDesc('saldo_actual')
            ->paginate(20);

        $totalPendiente = Cliente::where('saldo_actual', '>', 0)->sum('saldo_actual');

        return view('admin.cuentas-por-cobrar', compact('clientes', 'totalPendiente'));
    }
}