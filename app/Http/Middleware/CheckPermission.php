<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $modulo, string $nivel = 'lectura'): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        if (!auth()->user()->hasPermission($modulo, $nivel)) {
            abort(403, 'No tienes permisos para realizar esta acción en el módulo ' . $modulo . '.');
        }

        return $next($request);
    }
}
