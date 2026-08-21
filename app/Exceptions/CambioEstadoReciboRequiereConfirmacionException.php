<?php

namespace App\Exceptions;

use RuntimeException;

class CambioEstadoReciboRequiereConfirmacionException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Este cambio de estado involucra un recibo anulado y requiere confirmación explícita antes de aplicarse.');
    }
}
