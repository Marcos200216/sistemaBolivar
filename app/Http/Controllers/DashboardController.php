<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Operacion;
use App\Services\AtrasadosService;

class DashboardController extends Controller
{
    public function index(AtrasadosService $atrasadosService)
    {
        // Cliente y Operacion ya vienen filtrados por la sucursal activa (Global Scope)
        $totalClientes = Cliente::count();

        $conSaldo = Cliente::activos()
    ->get(['id', 'nombre', 'telefono', 'saldo_actual', 'cliente_id_legacy', 'created_at']);
        $totalConSaldo = $conSaldo->count();

        $topDeudores = $conSaldo->sortByDesc('saldo_actual')->take(5)->values();

        $referencias = $atrasadosService->referencias($conSaldo);
        $totalAtrasados = $referencias->count();
        $clientesPorId = $conSaldo->keyBy('id');
        $topAtrasados = $referencias->take(5)->map(fn ($fecha, $id) => [
            'cliente' => $clientesPorId[$id],
            'dias' => abs((int) $fecha->diffInDays(now())),
        ])->values();

        $operacionesHoy = Operacion::whereDate('fecha', today())->count();

        $ultimasOperaciones = Operacion::orderByDesc('id')->limit(5)->get();
        $nombres = Cliente::whereIn('id', $ultimasOperaciones->pluck('cliente_id'))->pluck('nombre', 'id');

        return view('admin.dashboard', compact(
            'totalClientes', 'totalConSaldo', 'totalAtrasados', 'operacionesHoy',
            'topAtrasados', 'topDeudores', 'ultimasOperaciones', 'nombres'
        ));
    }
}