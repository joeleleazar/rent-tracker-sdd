<?php

namespace App\Exceptions;

use RuntimeException;

class ReciboAnuladoNoAdmitePagosException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Un recibo anulado no admite registrar, editar ni eliminar pagos.');
    }
}
