<?php
// app/Http/Controllers/TraspasoController.php

namespace App\Http\Controllers;

use App\Enums\EstadoFactura;
use App\Exceptions\OperacionException;
use App\Models\Cliente;
use App\Models\Factura;
use App\Services\OperacionService;
use Illuminate\Http\Request;

class TraspasoController extends Controller
{
    public function index()
    {
        return view('admin.traspaso');
    }

    /** Autocompletado de clientes para Traspaso, con saldo y máximo de crédito. */
    public function buscarClientes(Request $request)
    {
        $q = trim((string) $request->query('buscar', ''));

        $clientes = Cliente::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                        ->orWhere('codigo', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->limit(15)
            ->get(['id', 'codigo', 'nombre', 'telefono', 'saldo_actual', 'maximocredito']);

        return response()->json($clientes);
    }

    /** Facturas nuevas de crédito con pendiente, para el selector "facturas específicas". */
    public function facturasPendientes(Cliente $cliente, OperacionService $servicio)
    {
        $facturas = Factura::nuevas()
            ->where('cliente_id', $cliente->id)
            ->where('estado', EstadoFactura::Credito->value)
            ->orderBy('created_at')
            ->with('operacion')
            ->get()
            ->map(fn (Factura $f) => [
                'id' => $f->id,
                'numero' => $f->operacion?->numero,
                'fecha' => optional($f->operacion?->fecha ?? $f->created_at)->format('d/m/Y'),
                'pendiente' => $servicio->pendienteFactura($f),
            ])
            ->filter(fn ($f) => $f['pendiente'] >= 0.01)
            ->values();

        return response()->json($facturas);
    }

    /**
     * Guarda el traspaso. Los recibos NO se envían acá: la pantalla llama a
     * operaciones.recibo por cada operación devuelta, en peticiones aparte.
     */
    public function store(Request $request, OperacionService $servicio)
    {
        $data = $request->validate([
            'cliente_origen_id' => 'required|integer|exists:clientes,id',
            'cliente_destino_id' => 'required|integer|exists:clientes,id',
            'factura_ids' => 'nullable|array',
            'factura_ids.*' => 'integer',
        ]);

        try {
            [$opOrigen, $opDestino] = $servicio->traspasar($data);
        } catch (OperacionException $e) {
            return response()->json(['mensaje' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'mensaje' => 'Traspaso realizado.',
            'operacion_origen_id' => $opOrigen->id,
            'operacion_destino_id' => $opDestino->id,
        ]);
    }
}