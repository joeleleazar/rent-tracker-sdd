<?php

use App\Exceptions\CambioEstadoReciboRequiereConfirmacionException;
use App\Models\Recibo;
use App\Services\ServicioCambioEstadoRecibo;
use App\Services\ServicioGestionPagosRecibo;

beforeEach(function () {
    $this->servicio = new ServicioCambioEstadoRecibo(new ServicioGestionPagosRecibo());
});

test('un recibo nuevo inicia en estado pendiente', function () {
    $recibo = Recibo::factory()->create();

    expect($recibo->estado)->toBe('pendiente');
});

test('anular sin confirmar lanza excepcion y no cambia el estado', function () {
    $recibo = Recibo::factory()->create();

    expect(fn () => $this->servicio->anular($recibo, false))
        ->toThrow(CambioEstadoReciboRequiereConfirmacionException::class);

    expect($recibo->fresh()->estado)->toBe('pendiente');
});

test('anular confirmando asigna fecha_anulacion y limpia fecha_pago', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => '1000.00', 'estado' => 'pagado', 'fecha_pago' => now()]);

    $this->servicio->anular($recibo, true);

    $recibo->refresh();
    expect($recibo->estado)->toBe('anulado');
    expect($recibo->fecha_anulacion)->not->toBeNull();
    expect($recibo->fecha_pago)->toBeNull();
});

test('reactivar sin confirmar lanza excepcion', function () {
    $recibo = Recibo::factory()->create(['estado' => 'anulado', 'fecha_anulacion' => now()]);

    expect(fn () => $this->servicio->reactivar($recibo, false))
        ->toThrow(CambioEstadoReciboRequiereConfirmacionException::class);

    expect($recibo->fresh()->estado)->toBe('anulado');
});

test('reactivar confirmando recalcula el estado a partir de los pagos que ya tenia, sin pagos queda pendiente', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => '1000.00', 'estado' => 'anulado', 'fecha_anulacion' => now()]);

    $this->servicio->reactivar($recibo, true);

    $recibo->refresh();
    expect($recibo->estado)->toBe('pendiente');
    expect($recibo->fecha_anulacion)->toBeNull();
    expect($recibo->fecha_pago)->toBeNull();
});

test('reactivar confirmando recalcula el estado a partir de los pagos que ya tenia, con pagos completos queda pagado', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => '1000.00', 'estado' => 'anulado', 'fecha_anulacion' => now()]);
    $recibo->pagos()->create(['monto' => 1000, 'fecha_pago' => now()->format('Y-m-d')]);

    $this->servicio->reactivar($recibo->fresh(), true);

    $recibo->refresh();
    expect($recibo->estado)->toBe('pagado');
    expect($recibo->fecha_anulacion)->toBeNull();
    expect($recibo->fecha_pago)->not->toBeNull();
});

test('anular conserva los pagos ya registrados', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => '1000.00']);
    $recibo->pagos()->create(['monto' => 400, 'fecha_pago' => now()->format('Y-m-d')]);

    $this->servicio->anular($recibo->fresh(), true);

    expect($recibo->fresh()->pagos)->toHaveCount(1);
    expect($recibo->fresh()->montoPagado())->toBe(400.0);
});
