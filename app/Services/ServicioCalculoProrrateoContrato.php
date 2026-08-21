<?php

namespace App\Services;

use App\Models\Contrato;
use Illuminate\Support\Carbon;

/**
 * Calcula los días activos de un contrato dentro de un periodo (mes) y el monto
 * de renta prorrateado sugerido, cuando la fecha de inicio o fin del contrato no
 * coincide con el primer/último día de ese mes (FR-005 a FR-008). Devuelve null
 * si el contrato estuvo activo el mes completo (A-003/A-004, no se prorratean
 * costos fijos). Ver research.md §4.
 */
class ServicioCalculoProrrateoContrato
{
    /**
     * @return array{dias_activos: int, dias_totales: int, monto_renta_sugerido: float}|null
     */
    public function calcular(Contrato $contrato, Carbon $periodo): ?array
    {
        $inicioDeMes = $periodo->copy()->startOfMonth();
        $finDeMes = $periodo->copy()->endOfMonth();

        $activoTodoElMes = $contrato->fecha_inicio->lte($inicioDeMes) && $contrato->fecha_fin->gte($finDeMes);

        if ($activoTodoElMes) {
            return null;
        }

        $inicioActivo = $contrato->fecha_inicio->gt($inicioDeMes) ? $contrato->fecha_inicio : $inicioDeMes;
        $finActivo = $contrato->fecha_fin->lt($finDeMes) ? $contrato->fecha_fin : $finDeMes;

        $diasActivos = (int) $inicioActivo->diffInDays($finActivo) + 1;
        $diasTotales = (int) $finDeMes->day;

        $montoSugerido = round(((float) $contrato->monto_renta / $diasTotales) * $diasActivos, 2);

        return [
            'dias_activos' => $diasActivos,
            'dias_totales' => $diasTotales,
            'monto_renta_sugerido' => $montoSugerido,
        ];
    }
}
