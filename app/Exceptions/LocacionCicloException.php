<?php

namespace App\Exceptions;

use RuntimeException;

class LocacionCicloException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No se puede asignar una locación hija como padre.');
    }
}
