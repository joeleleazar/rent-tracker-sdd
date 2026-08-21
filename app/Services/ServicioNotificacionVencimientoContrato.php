<?php

namespace App\Services;

use App\Mail\ContratoProximoAVencer;
use App\Models\ConfiguracionGeneral;
use App\Models\Contrato;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Determina los hitos de anticipación (30/15/7 días) pendientes de notificar para
 * cada contrato activo y envía el correo correspondiente, evitando duplicados por
 * hito (FR-003, FR-004; ver research.md §3-4).
 */
class ServicioNotificacionVencimientoContrato
{
    /**
     * Hitos de anticipación en días, junto con la columna que registra su envío.
     *
     * @var array<int, string>
     */
    private const HITOS = [
        30 => 'notificado_30_dias_en',
        15 => 'notificado_15_dias_en',
        7 => 'notificado_7_dias_en',
    ];

    /**
     * Revisa todos los contratos no rescindidos y envía las notificaciones de los
     * hitos ya alcanzados y aún no notificados. Devuelve la cantidad de correos
     * enviados (para reporte del comando artisan).
     */
    public function verificarYNotificar(): int
    {
        $correoDestino = ConfiguracionGeneral::actual()->correo_notificaciones_vencimiento;
        $hoy = now()->startOfDay();
        $totalEnviados = 0;

        $contratos = Contrato::where('estado', '!=', 'rescindido')
            ->where('fecha_fin', '>=', $hoy)
            ->get();

        foreach ($contratos as $contrato) {
            // fecha_fin >= hoy garantizado por la consulta anterior, por lo que la
            // diferencia siempre es no-negativa (evita ambigüedad de signo de Carbon).
            $diasParaVencer = $hoy->diffInDays($contrato->fecha_fin->copy()->startOfDay(), true);

            foreach (self::HITOS as $dias => $columna) {
                if ($diasParaVencer > $dias) {
                    continue;
                }

                if ($contrato->{$columna} !== null) {
                    continue;
                }

                DB::transaction(function () use ($contrato, $columna, $dias, $correoDestino) {
                    Mail::to($correoDestino)->send(new ContratoProximoAVencer($contrato, $dias));
                    $contrato->update([$columna => now()]);
                });

                $totalEnviados++;
            }
        }

        return $totalEnviados;
    }
}
