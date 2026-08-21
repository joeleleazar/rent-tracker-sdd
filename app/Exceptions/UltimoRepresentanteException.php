<?php

namespace App\Exceptions;

use RuntimeException;

class UltimoRepresentanteException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No se puede quitar a este representante porque es el único asociado al contrato. El contrato debe mantener al menos un representante.');
    }
}
