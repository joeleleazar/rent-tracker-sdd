<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * FR-009 (specs/003-representantes-contrato, corrección 2026-08-23): no se
 * permite quitar al inquilino Principal de un contrato con otros inquilinos
 * asociados sin designar simultáneamente un nuevo Principal entre ellos.
 */
class InquilinoPrincipalSinReemplazoException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Debe designar un nuevo inquilino Principal antes de quitar al actual.');
    }
}
