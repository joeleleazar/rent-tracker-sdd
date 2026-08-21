<?php

namespace App\Exceptions;

use RuntimeException;

class RepresentantePrincipalInvalidoException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Debe designar exactamente un representante como Principal.');
    }
}
