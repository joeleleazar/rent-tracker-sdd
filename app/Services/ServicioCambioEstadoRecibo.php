<?php

namespace App\Services;

use App\Exceptions\CambioEstadoReciboRequiereConfirmacionException;
use App\Models\Recibo;
use Illuminate\Support\Facades\DB;

/**
 * Cambia el estado de pago de un recibo (pendiente/pagado/anulado), exigiendo
 * confirmación explícita para cualquier transición hacia o desde "anulado"
 * (FR-004), y manteniendo fecha_pago/fecha_anulacion consistentes con el estado
 * resultante (FR-003). Las transiciones son libres (FR-005).
 */
class ServicioCambioEstadoRecibo
{
    public function cambiar(Recibo $recibo, string $nuevoEstado, bool $confirmado): void
    {
        DB::transaction(function () use ($recibo, $nuevoEstado, $confirmado) {
            $involucraAnulado = $nuevoEstado === 'anulado' || $recibo->estado === 'anulado';

            if ($involucraAnulado && ! $confirmado) {
                throw new CambioEstadoReciboRequiereConfirmacionException();
            }

            $datos = ['estado' => $nuevoEstado];

            $datos['fecha_pago'] = $nuevoEstado === 'pagado' ? now() : null;
            $datos['fecha_anulacion'] = $nuevoEstado === 'anulado' ? now() : null;

            $recibo->update($datos);
        });
    }
}
