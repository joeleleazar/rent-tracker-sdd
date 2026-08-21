<?php

namespace App\Exceptions;

use RuntimeException;

class ConsumoNegativoSinConfirmarException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('La lectura ingresada es menor a la del periodo anterior, lo que resultaría en un consumo negativo. Confirme explícitamente para continuar o corrija el valor.');
    }
}
