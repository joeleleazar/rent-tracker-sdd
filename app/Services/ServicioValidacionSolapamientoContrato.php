<?php

namespace App\Services;

use App\Exceptions\ContratoSolapadoException;
use App\Models\Contrato;
use Closure;
use Illuminate\Support\Facades\DB;

class ServicioValidacionSolapamientoContrato
{
    /**
     * Bloquea (lockForUpdate) los contratos existentes de la locación dentro de una
     * transacción, verifica que el rango de fechas propuesto no se solape con ninguno
     * de ellos (excluyendo los rescindidos) y, solo si no hay conflicto, ejecuta $accion
     * dentro de la misma transacción para persistir el contrato.
     */
    public function validarYEjecutar(
        int $locacionId,
        string $fechaInicio,
        string $fechaFin,
        ?int $excluirContratoId,
        Closure $accion,
    ): mixed {
        return DB::transaction(function () use ($locacionId, $fechaInicio, $fechaFin, $excluirContratoId, $accion) {
            $conflicto = Contrato::where('locacion_id', $locacionId)
                ->where('estado', '!=', 'rescindido')
                ->when($excluirContratoId, fn ($query) => $query->where('id', '!=', $excluirContratoId))
                ->where('fecha_inicio', '<=', $fechaFin)
                ->where('fecha_fin', '>=', $fechaInicio)
                ->lockForUpdate()
                ->first();

            if ($conflicto) {
                throw new ContratoSolapadoException($conflicto);
            }

            return $accion();
        });
    }
}
