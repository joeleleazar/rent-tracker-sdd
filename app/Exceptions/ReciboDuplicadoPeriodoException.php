<?php

namespace App\Exceptions;

use App\Models\Recibo;
use RuntimeException;

class ReciboDuplicadoPeriodoException extends RuntimeException
{
    public function __construct(public readonly Recibo $reciboExistente)
    {
        parent::__construct('Ya existe un recibo emitido para esta locación en ese periodo. Edite el recibo existente en vez de crear uno duplicado.');
    }
}
