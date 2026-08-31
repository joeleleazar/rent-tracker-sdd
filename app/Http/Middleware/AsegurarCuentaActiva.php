<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * specs/040 (FR-013 / SC-004): una cuenta desactivada deja de tener acceso en
 * su siguiente petición, aunque tenga una sesión abierta. Si el usuario
 * autenticado está inactivo, se cierra su sesión y se le redirige al login con
 * un aviso, en vez de dejarlo en un bucle de 403.
 *
 * Se aplica al grupo de rutas web autenticadas, después de `auth`.
 */
class AsegurarCuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario !== null && ! $usuario->estaActivo()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Tu cuenta fue desactivada. Contacta a un administrador.');
        }

        return $next($request);
    }
}
