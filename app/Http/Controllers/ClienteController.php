<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $clientes = Cliente::when($request->query('q'), function ($query) use ($request) {
                $busqueda = $request->query('q');
                $query->where(function ($q) use ($busqueda) {
                    $q->where('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('codigo', 'like', "%{$busqueda}%");
                });
            })
                ->orderBy('nombre')
                ->paginate(15);

            return response()->json([
                'data' => $clientes->items(),
                'current_page' => $clientes->currentPage(),
                'last_page' => $clientes->lastPage(),
                'total' => $clientes->total(),
                'from' => $clientes->firstItem(),
                'to' => $clientes->lastItem(),
            ]);
        }

        $sucursales = Sucursal::orderBy('nombre')->get();

        return view('admin.clientes', compact('sucursales'));
    }

    public function store(Request $request)
{
    $datos = $this->validarDatos($request);
    $datos['maximocredito'] = $datos['maximocredito'] ?? 0;

    Cliente::create($datos);

    return response()->json(['ok' => true, 'mensaje' => 'Cliente creado correctamente.']);
}

public function update(Request $request, Cliente $cliente)
{
    $sucursalAnterior = $cliente->sucursal_id;

    $datos = $this->validarDatos($request, $cliente);
    $datos['maximocredito'] = $datos['maximocredito'] ?? 0;

    $cambioDeSucursal = (int) $datos['sucursal_id'] !== (int) $sucursalAnterior;

    $cliente->update($datos);

    if ($cambioDeSucursal) {
        // Limpia las filas huérfanas: el cliente ya no pertenece a ninguna
        // ruta de la sucursal de la que se fue (Ordenada o Aleatoria,
        // Activos o Cancelados — todas las rutas de esa sucursal vieja).
        \App\Models\RutaCliente::where('cliente_id', $cliente->id)
            ->whereIn('ruta_id', \App\Models\Ruta::withoutGlobalScopes()
                ->where('sucursal_id', $sucursalAnterior)
                ->pluck('id'))
            ->delete();
    }

    return response()->json(['ok' => true, 'mensaje' => 'Cliente actualizado correctamente.']);
}

    public function destroy(Cliente $cliente)
{
    try {
        $cliente->delete();
    } catch (QueryException $e) {
        // 23000 = violación de integridad (llave foránea): el cliente tiene historial
        if ($e->getCode() === '23000') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se puede eliminar a este cliente porque tiene historial (facturas, abonos u otros movimientos). Para no descuadrar la contabilidad, se conserva.',
            ], 409);
        }

        throw $e;
    }

    return response()->json(['ok' => true, 'mensaje' => 'Cliente eliminado.']);
}

       private function validarDatos(Request $request, ?Cliente $cliente = null): array
{
    $clienteId = $cliente?->id;

    $validador = Validator::make($request->all(), [
        'codigo' => [
            'nullable', 'string', 'max:50',
            Rule::unique('clientes', 'codigo')
                ->where('sucursal_id', $request->input('sucursal_id'))
                ->ignore($clienteId),
        ],
        'nombre' => 'required|string|max:255',
        'telefono' => 'nullable|string|max:30',
        'correo' => 'nullable|email|max:255',
        'direccion' => 'nullable|string|max:500',
        'maximocredito' => 'nullable|numeric|min:0',
        'genero' => 'nullable|in:F,M',
        'sucursal_id' => 'required|exists:sucursales,id',
        'latitud' => 'nullable|numeric|between:-90,90|required_with:longitud',
        'longitud' => 'nullable|numeric|between:-180,180|required_with:latitud',
    ], [
        'codigo.unique' => 'Ya hay un cliente con ese código en esta sucursal.',
    ]);

    // Ítem 5 (reunión) — si se está editando un cliente EXISTENTE y se le cambia
    // la sucursal, hay que exigir una dirección nueva (distinta a la que ya
    // tenía) antes de guardar. Se compara contra lo que hay en la base, no
    // contra el request, para que no se pueda "engañar" mandando el mismo
    // texto que ya tenía o dejándolo vacío.
    $validador->after(function ($validador) use ($request, $cliente) {
        if (!$cliente) {
            return; // cliente nuevo: no hay sucursal "anterior" con la que comparar
        }
        if ((int) $request->input('sucursal_id') === (int) $cliente->sucursal_id) {
            return; // no cambió de sucursal
        }

        $direccionNueva = trim((string) $request->input('direccion'));
        $direccionActual = trim((string) $cliente->direccion);

        if ($direccionNueva === '') {
            $validador->errors()->add('direccion', 'Al cambiar de sucursal, tenés que indicar una dirección.');
        } elseif ($direccionNueva === $direccionActual) {
            $validador->errors()->add('direccion', 'Al cambiar de sucursal, la dirección debe ser distinta a la anterior.');
        }
    });

    return $validador->validate();
}

public function guardarUbicacion(Request $request, Cliente $cliente)
{
    $datos = $request->validate([
        'latitud' => 'required|numeric|between:-90,90',
        'longitud' => 'required|numeric|between:-180,180',
    ]);

    $cliente->update($datos);

    return response()->json(['ok' => true, 'mensaje' => 'Ubicación guardada.']);
}
}