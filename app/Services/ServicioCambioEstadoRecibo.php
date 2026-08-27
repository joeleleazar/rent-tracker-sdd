<?php

namespace App\Services;

use App\Exceptions\CambioEstadoReciboRequiereConfirmacionException;
use App\Models\Recibo;
use Illuminate\Support\Facades\DB;

/**
 * specs/032: tras retirar el toggle manual Pendiente/Pagado (FR-006), las
 * únicas transiciones de estado que un administrador sigue eligiendo a mano
 * son anular un recibo y reactivarlo (salir de "anulado"). Ambas exigen
 * confirmación explícita, igual que ya exigía el `cambiar()` genérico
 * anterior para cualquier transición que involucrara "anulado" — la única
 * diferencia es que ahora TODA transición de este servicio involucra
 * "anulado" por definición, así que la confirmación ya no es condicional.
 */
class ServicioCambioEstadoRecibo
{
    public function __construct(private readonly ServicioGestionPagosRecibo $servicioPagos)
    {
    }

    public function anular(Recibo $recibo, bool $confirmado): void
    {
        if (! $confirmado) {
            throw new CambioEstadoReciboRequiereConfirmacionException();
        }

        DB::transaction(function () use ($recibo) {
            $recibo->update(['estado' => 'anulado', 'fecha_anulacion' => now(), 'fecha_pago' => null]);
        });
    }

    /**
     * research.md Decisión 4/5: no recibe a qué estado volver — los pagos que
     * el recibo ya tenía nunca se tocaron mientras estuvo anulado, así que
     * Pendiente/Pagado se recalcula solo a partir de ellos.
     */
    public function reactivar(Recibo $recibo, bool $confirmado): void
    {
        if (! $confirmado) {
            throw new CambioEstadoReciboRequiereConfirmacionException();
        }

        DB::transaction(function () use ($recibo) {
            $recibo->update(['estado' => 'pendiente', 'fecha_anulacion' => null]);
            $this->servicioPagos->recalcularEstado($recibo->load('pagos'));
        });
    }
}
