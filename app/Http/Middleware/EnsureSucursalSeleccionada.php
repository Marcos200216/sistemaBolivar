<?php

namespace App\Http\Middleware;

use App\Models\Sucursal;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSucursalSeleccionada
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Admin restringido a una sola sucursal: se fija automático, sin pantalla.
        if ($user->sucursal_id !== null) {
            // Admin normal con Guana fija: no puede trabajar, se cierra la sesión.
            if (! $user->es_superadmin && $this->esGuana($user->sucursal_id)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => 'Tu usuario no tiene permiso para esa sucursal. Consultá con el administrador principal.']);
            }

            session(['sucursal_id' => $user->sucursal_id]);
            return $next($request);
        }

        // Admin libre: si ya eligió sucursal en esta sesión, sigue de largo.
        if (session()->has('sucursal_id')) {
            // Un admin normal no puede estar en Guana (ej. sesión abierta antes del cambio).
            if (! $user->es_superadmin && $this->esGuana(session('sucursal_id'))) {
                session()->forget('sucursal_id');

                return redirect()->route('sucursales.selector')
                    ->withErrors(['acceso' => 'No tenés permiso para ingresar a esa sucursal.']);
            }

            return $next($request);
        }

        return redirect()->route('sucursales.selector');
    }

    private function esGuana($sucursalId): bool
    {
        return Sucursal::whereKey($sucursalId)->where('canal', 'mayorista')->exists();
    }
}