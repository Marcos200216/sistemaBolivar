<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class InventarioController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $canal = $this->canalActual();

            $query = Producto::with(['variantes', 'subcategoria'])
                ->whereHas('subcategoria.categoria', fn($q) => $q->where('canal', $canal));

            if ($busqueda = $request->query('busqueda')) {
                $query->where(function ($q) use ($busqueda) {
                    $q->where('nombre', 'like', "%{$busqueda}%")
                      ->orWhere('codigo', 'like', "%{$busqueda}%");
                });
            }

            if ($subcategoriaId = $request->query('subcategoria_id')) {
                $query->where('subcategoria_id', $subcategoriaId);
            }

            return response()->json([
    'canal' => $canal,
    'productos' => $query->orderBy('nombre')->paginate(20)->withQueryString(),
    'categorias' => Categoria::where('canal', $canal)->with('subcategorias')->orderBy('orden')->get(),
]);
        }

        return view('admin.inventario');
    }

    public function update(Request $request, Producto $producto)
{
    $data = $request->validate([
        'nombre' => 'required|string|max:255',
        'codigo' => 'nullable|string|max:50|unique:catalogo.productos,codigo,' . $producto->id,
        'precio' => 'nullable|numeric|min:0',
        'variantes' => 'array',
        'variantes.*.id' => 'required|integer|exists:catalogo.producto_variantes,id',
        'variantes.*.stock' => 'required|integer|min:0',
    ]);

    $producto->update([
    'nombre' => $data['nombre'],
    'codigo' => $data['codigo'] ?? null,
    'precio' => $data['precio'] ?? null,
]);

// Guana tiene stock infinito: nunca se toca stock desde acá
if ($this->canalActual() === 'normal') {
    foreach ($data['variantes'] ?? [] as $v) {
        ProductoVariante::where('id', $v['id'])
            ->where('producto_id', $producto->id)
            ->update(['stock' => $v['stock']]);
    }
}

    return response()->json($producto->fresh('variantes'));
}

public function alternarActivo(Producto $producto)
{
    $producto->update(['activo' => !$producto->activo]);
    return response()->json($producto->fresh());
}

public function destroy(Producto $producto)
{
    $tieneStock = $producto->variantes()->where('stock', '>', 0)->exists();

    if ($tieneStock) {
        return response()->json([
            'mensaje' => 'No se puede eliminar: el producto todavía tiene stock en alguna variante. Dejalo en 0 primero.',
        ], 422);
    }

    $producto->variantes()->delete();
    $producto->delete();

    return response()->json(['ok' => true]);
}

    protected function canalActual(): string
    {
        return Sucursal::find(session('sucursal_id'))->canal ?? 'normal';
    }
}