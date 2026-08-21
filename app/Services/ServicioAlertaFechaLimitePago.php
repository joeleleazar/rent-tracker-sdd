<?php

namespace App\Services;

use App\Mail\AlertaFechaLimitePago;
use App\Models\ConfiguracionGeneral;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Envía la alerta de fecha límite de pago mensual (último sábado del mes) cuando
 * faltan los días de anticipación configurados, sin duplicar el envío dentro del
 * mismo mes (FR-002 a FR-004; ver research.md §2-3).
 */
class ServicioAlertaFechaLimitePago
{
    public function __construct(
        private readonly ServicioCalculoFechaLimitePago $servicioFechaLimite,
    ) {
    }

    /**
     * Devuelve true si se envió la alerta en esta ejecución.
     */
    public function verificarYAlertar(): bool
    {
        $configuracion = ConfiguracionGeneral::actual();
        $hoy = now()->startOfDay();
        $fechaLimite = $this->servicioFechaLimite->calcular($hoy);

        $yaEnviadaEsteMes = $configuracion->alerta_pago_mes_enviada_en !== null
            && $configuracion->alerta_pago_mes_enviada_en->isSameMonth($hoy);

        if ($yaEnviadaEsteMes) {
            return false;
        }

        $inicioVentana = $fechaLimite->copy()->subDays($configuracion->dias_anticipacion_alerta_pago);

        if ($hoy->lt($inicioVentana)) {
            return false;
        }

        DB::transaction(function () use ($configuracion, $fechaLimite) {
            Mail::to($configuracion->correo_notificaciones_vencimiento)
                ->send(new AlertaFechaLimitePago($fechaLimite));

            $configuracion->update(['alerta_pago_mes_enviada_en' => now()]);
        });

        return true;
    }
}
