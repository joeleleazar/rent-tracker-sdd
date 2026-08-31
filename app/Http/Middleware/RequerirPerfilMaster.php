<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * specs/040 (FR-003 / FR-005): restringe una ruta al perfil Master. El CRUD de
 * usuarios es exclusivo de ese perfil; una cuenta con perfil Administrador que
 * conozca la URL recibe 403 y no obtiene ninguna información del recurso.
 *
 * Se aplica siempre después de `auth`, por lo que puede asumir que hay un
 * usuario autenticado.
 */
class RequerirPerfilMaster
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario === null || ! $usuario->esMaster()) {
            abort(Response::HTTP_FORBIDDEN, 'No tienes permiso para acceder a la gestión de usuarios.');
        }

        return $next($request);
    }
}
