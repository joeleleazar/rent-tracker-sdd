<?php

namespace App\Exceptions;

use RuntimeException;

class ContratoSinRepresentantesException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Debe asociar por lo menos un representante al contrato antes de guardar.');
    }
}
