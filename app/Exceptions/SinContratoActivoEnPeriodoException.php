<?php

namespace App\Exceptions;

use RuntimeException;

class SinContratoActivoEnPeriodoException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No hay un contrato activo vigente para el periodo seleccionado en esta locación.');
    }
}
