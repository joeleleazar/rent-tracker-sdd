<?php

namespace App\Exceptions;

use RuntimeException;

class MontoPagoExcedeSaldoException extends RuntimeException
{
    public function __construct(public readonly float $saldoDisponible)
    {
        parent::__construct(sprintf(
            'El monto del pago no puede superar el saldo pendiente del recibo (S/ %s).',
            number_format($saldoDisponible, 2)
        ));
    }
}
