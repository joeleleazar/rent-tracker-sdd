<?php

namespace App\Exceptions;

use RuntimeException;

class MontoPagoInvalidoException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('El monto del pago debe ser mayor a cero.');
    }
}
