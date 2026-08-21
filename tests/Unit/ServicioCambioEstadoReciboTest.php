<?php

use App\Exceptions\CambioEstadoReciboRequiereConfirmacionException;
use App\Models\Recibo;
use App\Services\ServicioCambioEstadoRecibo;

beforeEach(function () {
    $this->servicio = new ServicioCambioEstadoRecibo();
});

test('un recibo nuevo inicia en estado pendiente', function () {
    $recibo = Recibo::factory()->create();

    expect($recibo->estado)->toBe('pendiente');
});

test('cambiar a pagado asigna fecha_pago y limpia fecha_anulacion', function () {
    $recibo = Recibo::factory()->create();

    $this->servicio->cambiar($recibo, 'pagado', false);

    $recibo->refresh();
    expect($recibo->estado)->toBe('pagado');
    expect($recibo->fecha_pago)->not->toBeNull();
    expect($recibo->fecha_anulacion)->toBeNull();
});

test('cambiar a pendiente limpia ambas fechas', function () {
    $recibo = Recibo::factory()->create(['estado' => 'pagado', 'fecha_pago' => now()]);

    $this->servicio->cambiar($recibo, 'pendiente', false);

    $recibo->refresh();
    expect($recibo->estado)->toBe('pendiente');
    expect($recibo->fecha_pago)->toBeNull();
    expect($recibo->fecha_anulacion)->toBeNull();
});

test('anular sin confirmar lanza excepcion y no cambia el estado', function () {
    $recibo = Recibo::factory()->create();

    expect(fn () => $this->servicio->cambiar($recibo, 'anulado', false))
        ->toThrow(CambioEstadoReciboRequiereConfirmacionException::class);

    expect($recibo->fresh()->estado)->toBe('pendiente');
});

test('anular confirmando asigna fecha_anulacion y limpia fecha_pago', function () {
    $recibo = Recibo::factory()->create(['estado' => 'pagado', 'fecha_pago' => now()]);

    $this->servicio->cambiar($recibo, 'anulado', true);

    $recibo->refresh();
    expect($recibo->estado)->toBe('anulado');
    expect($recibo->fecha_anulacion)->not->toBeNull();
    expect($recibo->fecha_pago)->toBeNull();
});

test('revertir un recibo anulado sin confirmar lanza excepcion', function () {
    $recibo = Recibo::factory()->create(['estado' => 'anulado', 'fecha_anulacion' => now()]);

    expect(fn () => $this->servicio->cambiar($recibo, 'pendiente', false))
        ->toThrow(CambioEstadoReciboRequiereConfirmacionException::class);

    expect($recibo->fresh()->estado)->toBe('anulado');
});

test('revertir un recibo anulado confirmando aplica el nuevo estado', function () {
    $recibo = Recibo::factory()->create(['estado' => 'anulado', 'fecha_anulacion' => now()]);

    $this->servicio->cambiar($recibo, 'pagado', true);

    $recibo->refresh();
    expect($recibo->estado)->toBe('pagado');
    expect($recibo->fecha_pago)->not->toBeNull();
    expect($recibo->fecha_anulacion)->toBeNull();
});

test('las transiciones pendiente a pagado son libres y no requieren confirmacion', function () {
    $recibo = Recibo::factory()->create(['estado' => 'pendiente']);

    $this->servicio->cambiar($recibo, 'pagado', false);
    $this->servicio->cambiar($recibo->fresh(), 'pendiente', false);

    expect($recibo->fresh()->estado)->toBe('pendiente');
});
