<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCompra;
use App\Enums\TipoCompra;
use App\Models\Compra;
use App\Models\Proveedor;
use Illuminate\Http\Request;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->wantsJson()) {
            return view('admin.compras');
        }

        $q = $request->query('q');

        $compras = Compra::with('proveedor')
            ->when($q, function ($query) use ($q) {
                $query->where('descripcion', 'like', "%{$q}%")
                    ->orWhereHas('proveedor', fn ($p) => $p->where('nombre', 'like', "%{$q}%"));
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15);

        $compras->getCollection()->transform(function ($compra) {
            return [
                'id' => $compra->id,
                'proveedor' => $compra->proveedor->nombre,
                'descripcion' => $compra->descripcion,
                'monto_total' => $compra->monto_total,
                'tipo' => $compra->tipo->value,
                'tipo_label' => $compra->tipo->label(),
                'estado' => $compra->estado->value,
                'estado_label' => $compra->estado->label(),
                'saldo_pendiente' => $compra->saldo_pendiente,
                'fecha' => $compra->fecha->format('Y-m-d'),
            ];
        });

        return response()->json($compras);
    }

    public function proveedores(Request $request)
{
    $proveedores = Proveedor::withCount('compras')
        ->orderBy('nombre')
        ->get(['id', 'nombre', 'telefono']);

    return response()->json($proveedores);
}

    public function store(Request $request)
    {
        $datos = $request->validate([
            'proveedor_nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:255',
            'monto_total' => 'required|numeric|min:0.01',
            'tipo' => 'required|in:contado,credito',
            'fecha' => 'required|date',
        ]);

        $proveedor = Proveedor::firstOrCreate(['nombre' => trim($datos['proveedor_nombre'])]);

        $tipo = TipoCompra::from($datos['tipo']);
        $esContado = $tipo === TipoCompra::Contado;

        $compra = Compra::create([
            'proveedor_id' => $proveedor->id,
            'descripcion' => $datos['descripcion'],
            'monto_total' => $datos['monto_total'],
            'tipo' => $tipo,
            'estado' => $esContado ? EstadoCompra::Pagada : EstadoCompra::Pendiente,
            'saldo_pendiente' => $esContado ? 0 : $datos['monto_total'],
            'fecha' => $datos['fecha'],
        ]);

        return response()->json(['mensaje' => 'Compra registrada.', 'id' => $compra->id]);
    }

    public function update(Request $request, Compra $compra)
{
    $datos = $request->validate([
        'proveedor_nombre' => 'required|string|max:255',
        'descripcion' => 'required|string|max:255',
        'monto_total' => 'required|numeric|min:0.01',
        'fecha' => 'required|date',
    ]);

    $proveedor = Proveedor::firstOrCreate(['nombre' => trim($datos['proveedor_nombre'])]);

    if ($compra->tipo === TipoCompra::Contado) {
        $saldoPendiente = 0;
        $estado = EstadoCompra::Pagada;
    } else {
        $totalPagado = $compra->pagos()->sum('monto');
        $saldoPendiente = max($datos['monto_total'] - $totalPagado, 0);
        $estado = $saldoPendiente <= 0 ? EstadoCompra::Pagada : EstadoCompra::Pendiente;
    }

    $compra->update([
        'proveedor_id' => $proveedor->id,
        'descripcion' => $datos['descripcion'],
        'monto_total' => $datos['monto_total'],
        'saldo_pendiente' => $saldoPendiente,
        'estado' => $estado,
        'fecha' => $datos['fecha'],
    ]);

    return response()->json(['mensaje' => 'Compra actualizada.']);
}

    public function destroy(Compra $compra)
    {
        $compra->delete();

        return response()->json(['mensaje' => 'Compra eliminada.']);
    }

    public function registrarPago(Request $request, Compra $compra)
{
    $datos = $request->validate([
        'monto' => 'required|numeric|min:0.01|max:' . $compra->saldo_pendiente,
        'fecha' => 'required|date',
    ], [
        'monto.max' => 'El abono no puede ser mayor al saldo pendiente (₡' . number_format($compra->saldo_pendiente, 2) . ').',
    ]);

    $compra->pagos()->create($datos);

    $saldoPendiente = max($compra->saldo_pendiente - $datos['monto'], 0);

    $compra->update([
        'saldo_pendiente' => $saldoPendiente,
        'estado' => $saldoPendiente <= 0 ? EstadoCompra::Pagada : EstadoCompra::Pendiente,
    ]);

    return response()->json(['mensaje' => 'Abono registrado.', 'saldo_pendiente' => $saldoPendiente]);
}

public function actualizarProveedor(Request $request, Proveedor $proveedor)
{
    $datos = $request->validate([
        'nombre' => 'required|string|max:255',
        'telefono' => 'nullable|string|max:50',
    ]);

    $proveedor->update($datos);

    return response()->json(['mensaje' => 'Proveedor actualizado.']);
}

public function eliminarProveedor(Proveedor $proveedor)
{
    if ($proveedor->compras()->exists()) {
        return response()->json([
            'mensaje' => 'No se puede eliminar: este proveedor tiene compras registradas.'
        ], 422);
    }

    $proveedor->delete();

    return response()->json(['mensaje' => 'Proveedor eliminado.']);
}
}