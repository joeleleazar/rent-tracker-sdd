<?php

namespace App\Exceptions;

use RuntimeException;

class InquilinoPrincipalInvalidoException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Debe designar exactamente un inquilino como Principal.');
    }
}
