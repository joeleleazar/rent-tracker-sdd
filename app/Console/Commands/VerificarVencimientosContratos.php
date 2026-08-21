<?php

namespace App\Console\Commands;

use App\Services\ServicioNotificacionVencimientoContrato;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('contratos:verificar-vencimientos')]
#[Description('Envía notificaciones por correo de contratos próximos a vencer (hitos de 30/15/7 días).')]
class VerificarVencimientosContratos extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ServicioNotificacionVencimientoContrato $servicio): int
    {
        $totalEnviados = $servicio->verificarYNotificar();

        $this->info("Notificaciones de vencimiento enviadas: {$totalEnviados}.");

        return self::SUCCESS;
    }
}
