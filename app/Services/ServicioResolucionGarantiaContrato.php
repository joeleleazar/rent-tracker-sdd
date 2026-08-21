<?php

namespace App\Services;

use App\Exceptions\GarantiaDescuadreException;
use App\Exceptions\MotivoRetencionRequeridoException;
use App\Exceptions\ResolucionGarantiaRequiereConfirmacionException;
use App\Models\Contrato;
use Illuminate\Support\Facades\DB;

/**
 * Registra la resolución de la garantía de un contrato (devuelto/retenido,
 * FR-006 a FR-010): exige cuadre exacto contra el monto entregado, motivo
 * obligatorio si hay retención, y confirmación explícita para corregir una
 * resolución ya registrada. Ver research.md §2-3.
 */
class ServicioResolucionGarantiaContrato
{
    public function registrar(
        Contrato $contrato,
        float $montoDevuelto,
        float $montoRetenido,
        ?string $motivo,
        bool $confirmado,
    ): void {
        DB::transaction(function () use ($contrato, $montoDevuelto, $montoRetenido, $motivo, $confirmado) {
            if ($contrato->garantiaResuelta() && ! $confirmado) {
                throw new ResolucionGarantiaRequiereConfirmacionException();
            }

            if ($montoRetenido > 0 && empty($motivo)) {
                throw new MotivoRetencionRequeridoException();
            }

            $montoGarantia = (float) $contrato->monto_garantia;
            $suma = $montoDevuelto + $montoRetenido;

            if (bccomp((string) $suma, (string) $montoGarantia, 2) !== 0) {
                throw new GarantiaDescuadreException($montoGarantia, $suma);
            }

            $contrato->update([
                'monto_devuelto_garantia' => $montoDevuelto,
                'monto_retenido_garantia' => $montoRetenido,
                'motivo_retencion_garantia' => $montoRetenido > 0 ? $motivo : null,
                'estado_garantia' => 'resuelta',
                'fecha_resolucion_garantia' => now(),
            ]);
        });
    }
}
