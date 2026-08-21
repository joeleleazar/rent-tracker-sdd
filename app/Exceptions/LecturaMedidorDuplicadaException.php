<?php

namespace App\Exceptions;

use App\Models\LecturaMedidor;
use RuntimeException;

class LecturaMedidorDuplicadaException extends RuntimeException
{
    public function __construct(public readonly LecturaMedidor $lecturaExistente)
    {
        parent::__construct('Ya existe una lectura registrada para ese periodo en esta locación. Edite la lectura existente en vez de crear un duplicado.');
    }
}
