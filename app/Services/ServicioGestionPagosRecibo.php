<?php

namespace App\Services;

use App\Exceptions\MontoPagoExcedeSaldoException;
use App\Exceptions\MontoPagoInvalidoException;
use App\Exceptions\ReciboAnuladoNoAdmitePagosException;
use App\Models\Pago;
use App\Models\Recibo;
use Illuminate\Support\Facades\DB;

/**
 * specs/032: registra, edita y elimina pagos (parciales o totales) contra un
 * recibo, recalculando su estado Pendiente/Pagado a partir de la suma de
 * pagos vigentes en cada operación (research.md Decisión 3) — el estado deja
 * de asignarse a mano salvo la transición hacia/desde "anulado", que sigue
 * viviendo en ServicioCambioEstadoRecibo.
 */
class ServicioGestionPagosRecibo
{
    /**
     * @param  array{monto: float|string, fecha_pago: string, medio_pago?: string|null}  $datos
     */
    public function registrar(Recibo $recibo, array $datos, ?int $registradoPorId): Pago
    {
        return DB::transaction(function () use ($recibo, $datos, $registradoPorId) {
            $recibo->load('pagos');
            $this->asegurarReciboVigente($recibo);

            $monto = (float) $datos['monto'];
            $this->asegurarMontoValido($monto);
            $this->asegurarMontoNoExcedeSaldo($recibo, $monto);

            $pago = $recibo->pagos()->create([
                'monto' => $monto,
                'fecha_pago' => $datos['fecha_pago'],
                // specs/044 (US3): el cobro rápido informa el medio de pago; el resto
                // del flujo de pagos (specs/032) no envía esta clave y queda en null.
                'medio_pago' => $datos['medio_pago'] ?? null,
                'registrado_por_id' => $registradoPorId,
            ]);

            $this->recalcularEstado($recibo->load('pagos'));

            return $pago;
        });
    }

    /**
     * @param  array{monto: float|string, fecha_pago: string}  $datos
     */
    public function actualizar(Pago $pago, array $datos): Pago
    {
        return DB::transaction(function () use ($pago, $datos) {
            $recibo = $pago->recibo()->with('pagos')->first();
            $this->asegurarReciboVigente($recibo);

            $monto = (float) $datos['monto'];
            $this->asegurarMontoValido($monto);

            // El saldo disponible para esta edición no debe contar el propio pago que se está
            // editando (contracts/gestion-pagos.md) — de lo contrario, un pago nunca podría
            // editarse a un monto igual o mayor al que ya tenía.
            $saldoSinEstePago = $recibo->saldoPendiente() + (float) $pago->monto;
            if ($monto > $saldoSinEstePago) {
                throw new MontoPagoExcedeSaldoException($saldoSinEstePago);
            }

            $pago->update([
                'monto' => $monto,
                'fecha_pago' => $datos['fecha_pago'],
            ]);

            $this->recalcularEstado($recibo->load('pagos'));

            return $pago;
        });
    }

    public function eliminar(Pago $pago): void
    {
        DB::transaction(function () use ($pago) {
            $recibo = $pago->recibo;
            $this->asegurarReciboVigente($recibo);

            $pago->delete();

            $this->recalcularEstado($recibo->load('pagos'));
        });
    }

    private function asegurarReciboVigente(Recibo $recibo): void
    {
        if ($recibo->estado === 'anulado') {
            throw new ReciboAnuladoNoAdmitePagosException;
        }
    }

    private function asegurarMontoValido(float $monto): void
    {
        if ($monto <= 0) {
            throw new MontoPagoInvalidoException;
        }
    }

    private function asegurarMontoNoExcedeSaldo(Recibo $recibo, float $monto): void
    {
        $saldo = $recibo->saldoPendiente();

        if ($monto > $saldo) {
            throw new MontoPagoExcedeSaldoException($saldo);
        }
    }

    /**
     * research.md Decisión 3: sin pagos o pago parcial → pendiente (sin fecha
     * de pago); suma de pagos ≥ total → pagado, con fecha_pago fijada al
     * momento en que se cruza el umbral. No toca el estado "anulado".
     * Público porque ServicioCambioEstadoRecibo::reactivar() también lo usa
     * para recalcular Pendiente/Pagado al salir de "anulado" (research.md
     * Decisión 4).
     */
    public function recalcularEstado(Recibo $recibo): void
    {
        if ($recibo->estado === 'anulado') {
            return;
        }

        if ($recibo->estaPagadoPorCompleto()) {
            if ($recibo->estado !== 'pagado') {
                $recibo->update(['estado' => 'pagado', 'fecha_pago' => now()]);
            }

            return;
        }

        if ($recibo->estado !== 'pendiente') {
            $recibo->update(['estado' => 'pendiente', 'fecha_pago' => null]);
        }
    }
}
