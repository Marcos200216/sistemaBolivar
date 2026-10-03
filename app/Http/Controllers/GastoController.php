<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use Illuminate\Http\Request;

class GastoController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->wantsJson()) {
            return view('admin.gastos');
        }

        $q = $request->query('q');

        $gastos = Gasto::when($q, function ($query) use ($q) {
                $query->where('categoria', 'like', "%{$q}%")
                    ->orWhere('descripcion', 'like', "%{$q}%");
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15);

        $gastos->getCollection()->transform(function ($gasto) {
            return [
                'id' => $gasto->id,
                'categoria' => $gasto->categoria,
                'descripcion' => $gasto->descripcion,
                'monto' => $gasto->monto,
                'fecha' => $gasto->fecha->format('Y-m-d'),
            ];
        });

        return response()->json($gastos);
    }

    public function categorias()
    {
        $categorias = Gasto::select('categoria')->distinct()->orderBy('categoria')->pluck('categoria');

        return response()->json($categorias);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'categoria' => 'required|string|max:255',
            'descripcion' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'required|date',
        ]);

        Gasto::create($datos);

        return response()->json(['mensaje' => 'Gasto registrado.']);
    }

    public function update(Request $request, Gasto $gasto)
    {
        $datos = $request->validate([
            'categoria' => 'required|string|max:255',
            'descripcion' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'required|date',
        ]);

        $gasto->update($datos);

        return response()->json(['mensaje' => 'Gasto actualizado.']);
    }

    public function destroy(Gasto $gasto)
    {
        $gasto->delete();

        return response()->json(['mensaje' => 'Gasto eliminado.']);
    }
}