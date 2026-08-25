<?php

namespace App\Exceptions;

use RuntimeException;

class ContratoSinInquilinosException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Debe asociar por lo menos un inquilino al contrato antes de guardar.');
    }
}
