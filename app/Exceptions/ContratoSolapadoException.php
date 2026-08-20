<?php

namespace App\Exceptions;

use App\Models\Contrato;
use RuntimeException;

class ContratoSolapadoException extends RuntimeException
{
    public function __construct(public readonly Contrato $contratoEnConflicto)
    {
        parent::__construct(sprintf(
            'El rango de fechas se solapa con el contrato #%d (%s a %s).',
            $contratoEnConflicto->id,
            $contratoEnConflicto->fecha_inicio->format('d/m/Y'),
            $contratoEnConflicto->fecha_fin->format('d/m/Y'),
        ));
    }
}
