<?php

namespace App\Exceptions;

use RuntimeException;

class MotivoRetencionRequeridoException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Debe ingresar un motivo de retención cuando el monto retenido es mayor a cero.');
    }
}
