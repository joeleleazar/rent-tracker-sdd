<?php

namespace App\Console\Commands;

use App\Services\ServicioAlertaFechaLimitePago;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pagos:alertar-fecha-limite')]
#[Description('Envía la alerta de fecha límite de pago mensual (último sábado del mes) cuando corresponda.')]
class AlertarFechaLimitePago extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ServicioAlertaFechaLimitePago $servicio): int
    {
        $enviada = $servicio->verificarYAlertar();

        $this->info($enviada ? 'Alerta de fecha límite de pago enviada.' : 'No correspondía enviar la alerta en esta ejecución.');

        return self::SUCCESS;
    }
}
