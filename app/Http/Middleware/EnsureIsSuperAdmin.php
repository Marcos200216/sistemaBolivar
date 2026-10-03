<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->es_superadmin) {
            return redirect()->route('dashboard')
                ->withErrors(['acceso' => 'No tienes permiso para acceder a esa sección.']);
        }

        return $next($request);
    }
}