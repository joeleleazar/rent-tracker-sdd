<?php

namespace App\Http\Controllers;

use App\Enums\PerfilUsuario;
use App\Exceptions\AutoproteccionUsuarioException;
use App\Exceptions\UltimoMasterActivoException;
use App\Http\Requests\SolicitudCambiarPerfilUsuario;
use App\Http\Requests\SolicitudGuardarUsuario;
use App\Http\Requests\SolicitudRestablecerContrasenaUsuario;
use App\Models\User;
use App\Services\ServicioAdministracionUsuarios;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * specs/040: CRUD de usuarios por perfiles. Todas estas rutas viven bajo la
 * pila de middleware `['auth', 'cuenta.activa', 'perfil.master']`, por lo que
 * el controlador puede asumir que quien llega es un Master con cuenta activa.
 *
 * Las violaciones de invariante del dominio (último Master activo,
 * auto-protección) se traducen a errores de sesión con `back()->withErrors()`,
 * siguiendo la convención del resto del proyecto (p. ej. PagoReciboController).
 */
class ControladorUsuario extends Controller
{
    public function __construct(private readonly ServicioAdministracionUsuarios $servicio)
    {
    }

    public function index(): View
    {
        return view('usuarios.index', [
            'usuarios' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('usuarios.create', [
            'perfiles' => PerfilUsuario::cases(),
        ]);
    }

    public function store(SolicitudGuardarUsuario $solicitud): RedirectResponse
    {
        $this->servicio->crear([
            'name' => $solicitud->validated('name'),
            'email' => $solicitud->validated('email'),
            'perfil' => $solicitud->validated('perfil'),
            'password' => $solicitud->validated('password'),
        ], $solicitud->user());

        return redirect()->route('usuarios.index')
            ->with('mensaje', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.edit', [
            'usuario' => $usuario,
            'perfiles' => PerfilUsuario::cases(),
        ]);
    }

    public function update(SolicitudGuardarUsuario $solicitud, User $usuario): RedirectResponse
    {
        $this->servicio->editarDatos($usuario, [
            'name' => $solicitud->validated('name'),
            'email' => $solicitud->validated('email'),
        ], $solicitud->user());

        return redirect()->route('usuarios.edit', $usuario)
            ->with('mensaje', 'Datos del usuario actualizados correctamente.');
    }

    public function restablecerContrasena(SolicitudRestablecerContrasenaUsuario $solicitud, User $usuario): RedirectResponse
    {
        $this->servicio->restablecerContrasena($usuario, $solicitud->validated('password'), $solicitud->user());

        return redirect()->route('usuarios.edit', $usuario)
            ->with('mensaje', 'Contraseña restablecida correctamente.');
    }

    public function cambiarPerfil(SolicitudCambiarPerfilUsuario $solicitud, User $usuario): RedirectResponse
    {
        try {
            $this->servicio->cambiarPerfil(
                $usuario,
                PerfilUsuario::from($solicitud->validated('perfil')),
                $solicitud->user(),
            );
        } catch (UltimoMasterActivoException|AutoproteccionUsuarioException $excepcion) {
            return back()->withErrors(['perfil' => $excepcion->getMessage()]);
        }

        return redirect()->route('usuarios.edit', $usuario)
            ->with('mensaje', 'Perfil actualizado correctamente.');
    }

    public function cambiarEstado(Request $solicitud, User $usuario): RedirectResponse
    {
        $datos = $solicitud->validate([
            'activo' => ['required', 'boolean'],
        ]);

        try {
            $this->servicio->cambiarEstado($usuario, (bool) $datos['activo'], $solicitud->user());
        } catch (UltimoMasterActivoException|AutoproteccionUsuarioException $excepcion) {
            return back()->withErrors(['activo' => $excepcion->getMessage()]);
        }

        $mensaje = $datos['activo']
            ? 'Usuario reactivado correctamente.'
            : 'Usuario desactivado correctamente.';

        return redirect()->route('usuarios.index')->with('mensaje', $mensaje);
    }

    public function destroy(User $usuario): RedirectResponse
    {
        try {
            $this->servicio->eliminar($usuario, request()->user());
        } catch (UltimoMasterActivoException $excepcion) {
            return back()->withErrors(['eliminar' => $excepcion->getMessage()]);
        }

        return redirect()->route('usuarios.index')
            ->with('mensaje', 'Usuario eliminado correctamente.');
    }
}
