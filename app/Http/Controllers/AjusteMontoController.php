<?php
// app/Http/Controllers/AjusteMontoController.php

namespace App\Http\Controllers;

use App\Exceptions\OperacionException;
use App\Models\Cliente;
use App\Models\Sucursal;
use App\Services\AjusteSaldoService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Pantalla "Modificar monto": solo superadmin (las rutas van dentro del grupo 'superadmin').
 * Trabaja con clientes de CUALQUIER sucursal sin cambiar la sucursal activa en sesión.
 * Los id llegan como número (sin route model binding) para que el Global Scope de
 * sucursal no dé 404 con clientes o facturas de otra sucursal.
 */
class AjusteMontoController extends Controller
{
    public function index()
    {
        $sucursales = Sucursal::orderBy('id')->get(['id', 'nombre']);

        return view('admin.modificar-monto', compact('sucursales'));
    }

    /** Buscador de clientes: filtra por sucursal (opcional) y por nombre, código o teléfono. */
    public function clientes(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $sucursal = (string) $request->query('sucursal', '');

        $clientes = Cliente::withoutGlobalScopes()
            ->when(ctype_digit($sucursal), fn ($query) => $query->where('sucursal_id', (int) $sucursal))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                        ->orWhere('codigo', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->limit(20)
            ->get(['id', 'sucursal_id', 'codigo', 'nombre', 'telefono', 'saldo_actual']);

        $nombres = Sucursal::pluck('nombre', 'id');

        return response()->json($clientes->map(fn ($c) => [
            'id' => $c->id,
            'codigo' => $c->codigo,
            'nombre' => $c->nombre,
            'telefono' => $c->telefono,
            'sucursal' => $nombres->get($c->sucursal_id) ?? ('Sucursal #' . $c->sucursal_id),
            'saldo' => (float) $c->saldo_actual,
        ]));
    }

    /** Cliente + sus facturas de crédito pendientes (sin anuladas ni saldadas). */
    public function cliente(AjusteSaldoService $servicio, int $cliente): JsonResponse
    {
        return response()->json($servicio->detalleCliente($cliente));
    }

    /** Vista previa del reparto de una baja. No escribe nada. */
    public function simular(Request $request, AjusteSaldoService $servicio, int $cliente): JsonResponse
    {
        $datos = $request->validate([
            'monto' => 'required|numeric|min:0.01',
        ]);

        return $this->intentar(fn () => $servicio->simularBaja($cliente, (float) $datos['monto']));
    }

    public function bajar(Request $request, AjusteSaldoService $servicio, int $cliente): JsonResponse
    {
        $datos = $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'motivo' => 'required|string|min:3|max:500',
        ]);

        return $this->intentar(fn () => $servicio->bajarTotal(
            $cliente, (float) $datos['monto'], $datos['motivo'], (int) Auth::id()
        ));
    }

    public function anularTodas(Request $request, AjusteSaldoService $servicio, int $cliente): JsonResponse
    {
        $datos = $request->validate([
            'motivo' => 'required|string|min:3|max:500',
        ]);

        return $this->intentar(fn () => $servicio->anularTodas($cliente, $datos['motivo'], (int) Auth::id()));
    }

    public function editarFactura(Request $request, AjusteSaldoService $servicio, int $factura): JsonResponse
    {
        $datos = $request->validate([
            'pendiente' => 'required|numeric|min:0',
            'motivo' => 'required|string|min:3|max:500',
        ]);

        return $this->intentar(fn () => $servicio->editarFactura(
            $factura, (float) $datos['pendiente'], $datos['motivo'], (int) Auth::id()
        ));
    }

    public function anularFactura(Request $request, AjusteSaldoService $servicio, int $factura): JsonResponse
    {
        $datos = $request->validate([
            'motivo' => 'required|string|min:3|max:500',
        ]);

        return $this->intentar(fn () => $servicio->anularFactura($factura, $datos['motivo'], (int) Auth::id()));
    }

    /** Las reglas del servicio (OperacionException) se devuelven como 422 con su mensaje. */
    private function intentar(Closure $accion): JsonResponse
    {
        try {
            return response()->json($accion());
        } catch (OperacionException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }
    }
}