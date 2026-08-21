<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Calcula la fecha límite de pago mensual: el último sábado del mes calendario
 * (FR-001). Ver research.md §1.
 */
class ServicioCalculoFechaLimitePago
{
    public function calcular(Carbon $mes): Carbon
    {
        $finDeMes = $mes->copy()->endOfMonth();

        if ($finDeMes->isSaturday()) {
            return $finDeMes->startOfDay();
        }

        return $finDeMes->previous(Carbon::SATURDAY)->startOfDay();
    }
}
