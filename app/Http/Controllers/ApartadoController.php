<?php
// app/Http/Controllers/ApartadoController.php

namespace App\Http\Controllers;

use App\Models\Apartado;
use Illuminate\Http\Request;

class ApartadoController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $apartados = Apartado::with('cliente', 'lineas')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(15)
                ->through(fn (Apartado $a) => [
                    'id' => $a->id,
                    'cliente_id' => $a->cliente_id,
                    'nota' => $a->nota, 
                    'cliente_nombre' => $a->cliente->nombre,
                    'fecha' => $a->created_at->format('d/m/Y H:i'),
                    'lineas' => $a->lineas->map(fn ($l) => [
                        'producto_id' => $l->producto_id,
                        'producto_variante_id' => $l->producto_variante_id,
                        'nombre' => $l->nombre,
                        'cantidad' => $l->cantidad,
                    ]),
                ]);

            return response()->json($apartados);
        }

        return view('admin.apartados');
    }

  public function store(Request $request)
{
    $data = $request->validate([
        'cliente_id' => 'required|exists:clientes,id',
        'nota' => 'nullable|string|max:500',
        'lineas' => 'nullable|array',
        'lineas.*.producto_id' => 'required|integer',
        'lineas.*.producto_variante_id' => 'nullable|integer',
        'lineas.*.nombre' => 'required|string|max:255',
        'lineas.*.cantidad' => 'required|integer|min:1',
    ]);

    $nota = trim($data['nota'] ?? '');
    $lineas = $data['lineas'] ?? [];

    if ($nota === '' && count($lineas) === 0) {
        return response()->json(['mensaje' => 'Escribí un texto o agregá al menos un producto.'], 422);
    }

    $apartado = Apartado::create([
        'cliente_id' => $data['cliente_id'],
        'nota' => $nota !== '' ? $nota : null,
    ]);
    foreach ($lineas as $linea) {
        $apartado->lineas()->create($linea);
    }

    return response()->json(['ok' => true, 'mensaje' => 'Apartado guardado.']);
}

    public function destroy(Apartado $apartado)
    {
        $apartado->delete();

        return response()->json(['ok' => true, 'mensaje' => 'Apartado eliminado.']);
    }

    /** Para el ícono con contador en el layout (badge). */
    public function contador()
    {
        return response()->json(['total' => Apartado::count()]);
    }

    public function show(Apartado $apartado)
{
    $apartado->load('cliente', 'lineas');

    return response()->json([
        'id' => $apartado->id,
        'cliente_id' => $apartado->cliente_id,
        'nota' => $apartado->nota,
        'cliente_nombre' => $apartado->cliente->nombre,
        'lineas' => $apartado->lineas->map(fn ($l) => [
            'producto_id' => $l->producto_id,
            'producto_variante_id' => $l->producto_variante_id,
            'nombre' => $l->nombre,
            'cantidad' => $l->cantidad,
        ]),
    ]);
}
}