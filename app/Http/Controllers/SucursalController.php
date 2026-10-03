<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    public function selector(Request $request)
    {
        // Guana (canal mayorista) solo la ve el superadmin.
        $sucursales = Sucursal::activas()
            ->when(! $request->user()->es_superadmin, fn ($q) => $q->where('canal', '!=', 'mayorista'))
            ->orderBy('canal', 'desc')
            ->orderBy('id')
            ->get();

        return view('sucursales.selector', compact('sucursales'));
    }

    public function elegir(Request $request)
    {
        $request->validate([
            'sucursal_id' => 'required|exists:sucursales,id',
        ]);

        $sucursal = Sucursal::activas()->findOrFail($request->sucursal_id);

        // Aunque no aparezca en el selector, se rechaza si mandan el POST a mano.
        abort_if(
            ! $request->user()->es_superadmin && $sucursal->canal === 'mayorista',
            403,
            'No tenés permiso para ingresar a esa sucursal.'
        );

        session(['sucursal_id' => $sucursal->id]);

        return redirect()->intended(route('dashboard'));
    }
}