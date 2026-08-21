<?php

namespace App\Exceptions;

use RuntimeException;

class GarantiaDescuadreException extends RuntimeException
{
    public function __construct(float $montoGarantia, float $suma)
    {
        parent::__construct(sprintf(
            'La suma del monto devuelto y el monto retenido (S/ %s) debe ser exactamente igual al monto de garantía entregada (S/ %s).',
            number_format($suma, 2),
            number_format($montoGarantia, 2),
        ));
    }
}
