<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdministradorController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $query = User::query()->with('sucursal');

            if ($busqueda = $request->get('q')) {
                $query->where(function ($q) use ($busqueda) {
                    $q->where('name', 'like', "%{$busqueda}%")
                      ->orWhere('email', 'like', "%{$busqueda}%");
                });
            }

            return response()->json(
                $query->orderBy('name')->paginate(10)->withQueryString()
            );
        }

        return view('admin.administradores', [
            'sucursales' => Sucursal::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
{
    $datos = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:1'],
    ]);

    $datos['password'] = Hash::make($datos['password']);
    $datos['es_superadmin'] = false;

    User::create($datos);

    return response()->json(['mensaje' => 'Administrador creado correctamente.'], 201);
}

    public function update(Request $request, User $administrador)
{
    $datos = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'max:255', 'unique:users,email,' . $administrador->id],
        'password' => ['nullable', 'string', 'min:1'],
    ]);

    if (! empty($datos['password'])) {
        $datos['password'] = Hash::make($datos['password']);
    } else {
        unset($datos['password']);
    }

    $administrador->update($datos);

    return response()->json(['mensaje' => 'Administrador actualizado correctamente.']);
}

    public function destroy(Request $request, User $administrador)
    {
        if ($administrador->id === $request->user()->id) {
            return response()->json(['mensaje' => 'No puedes eliminar tu propia cuenta.'], 422);
        }

        if (User::count() <= 1) {
            return response()->json(['mensaje' => 'No se puede eliminar al último administrador del sistema.'], 422);
        }

        $administrador->delete();

        return response()->json(['mensaje' => 'Administrador eliminado correctamente.']);
    }

    public function actualizarMiCuenta(Request $request)
    {
        $admin = $request->user();

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', 'unique:users,email,' . $admin->id],
            'password' => ['nullable', 'string', 'min:1', 'confirmed'],
        ]);

        if (! empty($datos['password'])) {
            $datos['password'] = Hash::make($datos['password']);
        } else {
            unset($datos['password']);
        }

        $admin->update($datos);

        return response()->json(['mensaje' => 'Tu cuenta se actualizó correctamente.']);
    }
}