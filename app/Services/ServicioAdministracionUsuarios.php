<?php

namespace App\Services;

use App\Enums\PerfilUsuario;
use App\Exceptions\AutoproteccionUsuarioException;
use App\Exceptions\UltimoMasterActivoException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * specs/040: lógica de negocio de la gestión de usuarios por perfiles. Todas
 * las operaciones que pueden romper la invariante "siempre existe al menos un
 * Master activo" (desactivar, eliminar, degradar de perfil) se ejecutan dentro
 * de una transacción con bloqueo de fila sobre los Master activos, para
 * serializar dos degradaciones simultáneas.
 *
 * Cada operación deja constancia en el canal de log `seguridad` (FR-018),
 * indicando quién la ejecutó (siempre un Master) y sobre qué cuenta.
 */
class ServicioAdministracionUsuarios
{
    /**
     * Da de alta una cuenta nueva. La contraseña la fija el Master; la cuenta
     * nace activa y con el correo ya verificado (la verificación de correo no
     * es requisito para iniciar sesión, specs/040 Assumptions).
     *
     * @param  array{name: string, email: string, perfil: PerfilUsuario|string, password: string}  $datos
     */
    public function crear(array $datos, User $actor): User
    {
        return DB::transaction(function () use ($datos, $actor) {
            $usuario = User::create([
                'name' => $datos['name'],
                'email' => $this->normalizarCorreo($datos['email']),
                'perfil' => $datos['perfil'] instanceof PerfilUsuario
                    ? $datos['perfil']
                    : PerfilUsuario::from($datos['perfil']),
                'activo' => true,
                'password' => Hash::make($datos['password']),
            ]);

            // `email_verified_at` no es asignable en masa; la verificación de
            // correo no es requisito para iniciar sesión (specs/040
            // Assumptions), así que la cuenta nace ya verificada.
            $usuario->forceFill(['email_verified_at' => now()])->save();

            $this->registrarEvento('usuario.creado', $actor, $usuario, [
                'perfil' => $usuario->perfil->value,
            ]);

            return $usuario;
        });
    }

    /**
     * Actualiza nombre y correo de una cuenta existente.
     *
     * @param  array{name: string, email: string}  $datos
     */
    public function editarDatos(User $usuario, array $datos, User $actor): void
    {
        DB::transaction(function () use ($usuario, $datos, $actor) {
            $usuario->update([
                'name' => $datos['name'],
                'email' => $this->normalizarCorreo($datos['email']),
            ]);

            $this->registrarEvento('usuario.datos_actualizados', $actor, $usuario);
        });
    }

    /**
     * Fija una contraseña nueva para la cuenta indicada.
     */
    public function restablecerContrasena(User $usuario, string $contrasena, User $actor): void
    {
        DB::transaction(function () use ($usuario, $contrasena, $actor) {
            $usuario->update(['password' => Hash::make($contrasena)]);

            $this->registrarEvento('usuario.contrasena_restablecida', $actor, $usuario);
        });
    }

    /**
     * Cambia el perfil de una cuenta entre Master y Administrador. Bloquea la
     * degradación del único Master activo (FR-014) y la auto-degradación del
     * único Master (FR-015).
     */
    public function cambiarPerfil(User $usuario, PerfilUsuario $perfil, User $actor): void
    {
        DB::transaction(function () use ($usuario, $perfil, $actor) {
            $perfilAnterior = $usuario->perfil;

            if ($perfilAnterior === $perfil) {
                return;
            }

            $degradaAUnMaster = $perfilAnterior === PerfilUsuario::Master
                && $perfil === PerfilUsuario::Administrador;

            if ($degradaAUnMaster) {
                if ($usuario->is($actor) && $this->contarOtrosMastersActivos($usuario) === 0) {
                    throw AutoproteccionUsuarioException::noPuedeQuitarseElPerfilMaster();
                }

                $this->asegurarQueNoEsElUltimoMaster($usuario);
            }

            $usuario->update(['perfil' => $perfil]);

            $this->registrarEvento('usuario.perfil_cambiado', $actor, $usuario, [
                'perfil_anterior' => $perfilAnterior->value,
                'perfil_nuevo' => $perfil->value,
            ]);
        });
    }

    /**
     * Activa o desactiva una cuenta. Una cuenta desactivada no puede iniciar
     * sesión ni acceder a secciones protegidas, pero conserva su información.
     * Bloquea la desactivación del último Master activo (FR-014) y la
     * auto-desactivación (FR-015).
     */
    public function cambiarEstado(User $usuario, bool $activo, User $actor): void
    {
        DB::transaction(function () use ($usuario, $activo, $actor) {
            if ($usuario->activo === $activo) {
                return;
            }

            if (! $activo) {
                if ($usuario->is($actor)) {
                    throw AutoproteccionUsuarioException::noPuedeDesactivarseASiMismo();
                }

                if ($usuario->esMaster()) {
                    $this->asegurarQueNoEsElUltimoMaster($usuario);
                }
            }

            $usuario->update(['activo' => $activo]);

            $this->registrarEvento(
                $activo ? 'usuario.reactivado' : 'usuario.desactivado',
                $actor,
                $usuario,
            );
        });
    }

    /**
     * Elimina definitivamente una cuenta. Sujeto a la misma salvaguarda del
     * último Master activo (specs/040 contracts/gestion-usuarios.md).
     */
    public function eliminar(User $usuario, User $actor): void
    {
        DB::transaction(function () use ($usuario, $actor) {
            if ($usuario->esMaster() && $usuario->activo) {
                $this->asegurarQueNoEsElUltimoMaster($usuario);
            }

            $this->registrarEvento('usuario.eliminado', $actor, $usuario, [
                'email' => $usuario->email,
                'perfil' => $usuario->perfil->value,
            ]);

            $usuario->delete();
        });
    }

    /**
     * Lanza si `$usuario` es el único Master activo que quedaría, tomando un
     * bloqueo sobre las filas Master activas para serializar operaciones
     * concurrentes.
     */
    private function asegurarQueNoEsElUltimoMaster(User $usuario): void
    {
        if ($this->contarOtrosMastersActivos($usuario) === 0) {
            throw new UltimoMasterActivoException();
        }
    }

    private function contarOtrosMastersActivos(User $usuario): int
    {
        // PostgreSQL no admite `FOR UPDATE` junto con funciones de agregación,
        // así que se bloquean las filas Master activas y se cuentan en PHP.
        return User::query()
            ->where('perfil', PerfilUsuario::Master)
            ->where('activo', true)
            ->whereKeyNot($usuario->getKey())
            ->lockForUpdate()
            ->get(['id'])
            ->count();
    }

    private function normalizarCorreo(string $correo): string
    {
        return mb_strtolower(trim($correo));
    }

    /**
     * @param  array<string, scalar>  $detalle
     */
    private function registrarEvento(string $accion, User $actor, User $afectado, array $detalle = []): void
    {
        Log::channel('seguridad')->info($accion, array_merge([
            'accion' => $accion,
            'actor_id' => $actor->getKey(),
            'actor_email' => $actor->email,
            'usuario_afectado_id' => $afectado->getKey(),
        ], $detalle));
    }
}
