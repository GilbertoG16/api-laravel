<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Verifica si el usuario tiene al menos uno de los roles especificados
        if ($request->user()->hasAnyRole($roles)) {
            return $next($request);
        }
        
        return response()->json(['message' => 'Acceso no autorizado'], 403);
    }
}
