<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CuentaController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.mi-cuenta', ['admin' => $request->user()]);
    }

    public function update(Request $request)
    {
        $admin = $request->user();

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', 'unique:users,email,' . $admin->id],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        if (! empty($datos['password'])) {
            $datos['password'] = Hash::make($datos['password']);
        } else {
            unset($datos['password']);
        }

        $admin->update($datos);

        return redirect()->route('cuenta.edit')->with('exito', 'Tu cuenta se actualizó correctamente.');
    }
}