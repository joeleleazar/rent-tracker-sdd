<?php

namespace App\Enums;

/**
 * Perfil de acceso de una cuenta de usuario (specs/040).
 *
 * Conjunto cerrado de dos valores que no se administra desde la interfaz:
 * - Master: acceso operativo completo más acceso EXCLUSIVO a todo el CRUD de
 *   usuarios (crear, listar, editar, restablecer contraseña, cambiar perfil,
 *   desactivar/reactivar y eliminar cuentas).
 * - Administrador: acceso operativo completo al resto del sistema, sin ningún
 *   acceso a la sección de gestión de usuarios (ni siquiera para consultarla).
 */
enum PerfilUsuario: string
{
    case Master = 'master';
    case Administrador = 'administrador';

    /**
     * Texto legible del perfil para mostrar en la interfaz.
     */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Master => 'Master',
            self::Administrador => 'Administrador',
        };
    }

    /**
     * Clase de color de Bootstrap para el `badge` que representa este perfil
     * en los listados (Principio VI: color semántico consistente por concepto).
     */
    public function claseBadge(): string
    {
        return match ($this) {
            self::Master => 'bg-primary',
            self::Administrador => 'bg-secondary',
        };
    }
}
