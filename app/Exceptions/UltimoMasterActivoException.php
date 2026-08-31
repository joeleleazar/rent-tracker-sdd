<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * specs/040 (FR-014): se intentó desactivar, eliminar o degradar de perfil a la
 * última cuenta Master activa. El sistema siempre debe conservar al menos un
 * Master activo para no quedar sin nadie que pueda administrar usuarios.
 */
class UltimoMasterActivoException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Debe existir al menos un usuario Master activo en el sistema.');
    }
}
