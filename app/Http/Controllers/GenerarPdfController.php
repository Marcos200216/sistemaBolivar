<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Sucursal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GenerarPdfController extends Controller
{
    public function index()
    {
        $this->exigirMayorista();

        // Cliente ya viene filtrado por la sucursal activa (Global Scope)
        $clientes = Cliente::orderBy('nombre')->get(['id', 'nombre', 'telefono', 'direccion']);

        return view('admin.generarpdf', compact('clientes'));
    }

    public function generar(Request $request)
    {
        $this->exigirMayorista();

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'direccion' => ['required', 'string', 'max:300'],
            'telefono' => ['nullable', 'string', 'max:30'],
        ]);

        $pdf = Pdf::loadView('admin.generarpdf-pdf', [
            'nombre' => trim($datos['nombre']),
            'direccion' => trim($datos['direccion']),
            'telefono' => trim((string) ($datos['telefono'] ?? '')),
            'logo' => public_path('images/fondo_guana.png'),
        ])->setPaper('a5', 'portrait');   // antes: 'landscape'

        return $pdf->stream('guia-envio-' . Str::slug($datos['nombre']) . '.pdf');
    }

    /** Solo la sucursal mayorista (Guana) usa esta pantalla. */
    private function exigirMayorista(): void
    {
        $sucursal = Sucursal::findOrFail(session('sucursal_id'));
        $canal = $sucursal->canal instanceof \BackedEnum ? $sucursal->canal->value : $sucursal->canal;

        abort_unless($canal === 'mayorista', 404);
    }
}