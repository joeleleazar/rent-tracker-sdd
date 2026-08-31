<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * specs/040 (FR-015): un usuario intentó desactivar su propia cuenta o quitarse
 * a sí mismo el perfil Master siendo el único Master activo. Ambas acciones se
 * bloquean para que nadie se deje a sí mismo (o al sistema) sin acceso.
 */
class AutoproteccionUsuarioException extends RuntimeException
{
    public static function noPuedeDesactivarseASiMismo(): self
    {
        return new self('No puedes desactivar tu propia cuenta.');
    }

    public static function noPuedeQuitarseElPerfilMaster(): self
    {
        return new self('No puedes quitarte a ti mismo el perfil Master.');
    }
}
