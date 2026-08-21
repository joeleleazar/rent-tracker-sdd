<?php

namespace App\Exceptions;

use App\Models\Locacion;
use RuntimeException;

class LocacionConHijasException extends RuntimeException
{
    public function __construct(Locacion $locacion)
    {
        parent::__construct(sprintf(
            'No se puede eliminar "%s" porque tiene locaciones asociadas. Elimine o reasigne primero las sub-locaciones.',
            $locacion->nombre,
        ));
    }
}
