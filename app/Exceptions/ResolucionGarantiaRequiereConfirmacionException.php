<?php

namespace App\Exceptions;

use RuntimeException;

class ResolucionGarantiaRequiereConfirmacionException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Este contrato ya tiene una resolución de garantía registrada. Confirme explícitamente para corregirla.');
    }
}
