<?php

use App\Exceptions\MontoPagoExcedeSaldoException;
use App\Exceptions\MontoPagoInvalidoException;
use App\Exceptions\ReciboAnuladoNoAdmitePagosException;
use App\Models\Recibo;
use App\Models\User;
use App\Services\ServicioGestionPagosRecibo;

beforeEach(function () {
    $this->servicio = new ServicioGestionPagosRecibo();
    $this->admin = User::factory()->create();
});

test('registrar un pago parcial deja el recibo pendiente con el avance correcto', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => '960.75']);

    $this->servicio->registrar($recibo, ['monto' => 500, 'fecha_pago' => now()->format('Y-m-d')], $this->admin->id);

    $recibo->refresh();
    expect($recibo->estado)->toBe('pendiente');
    expect($recibo->fecha_pago)->toBeNull();
    expect($recibo->montoPagado())->toBe(500.0);
    expect($recibo->saldoPendiente())->toBe(460.75);
});

test('registrar un pago que completa el total pasa el recibo a pagado', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => '960.75']);

    $this->servicio->registrar($recibo, ['monto' => 460.75, 'fecha_pago' => now()->format('Y-m-d')], $this->admin->id);
    $this->servicio->registrar($recibo->fresh(), ['monto' => 500, 'fecha_pago' => now()->format('Y-m-d')], $this->admin->id);

    $recibo->refresh();
    expect($recibo->estado)->toBe('pagado');
    expect($recibo->fecha_pago)->not->toBeNull();
    expect($recibo->estaPagadoPorCompleto())->toBeTrue();
});

test('registrar un pago que excede el saldo pendiente se rechaza', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => '960.75']);
    $this->servicio->registrar($recibo, ['monto' => 500, 'fecha_pago' => now()->format('Y-m-d')], $this->admin->id);

    expect(fn () => $this->servicio->registrar($recibo->fresh(), ['monto' => 600, 'fecha_pago' => now()->format('Y-m-d')], $this->admin->id))
        ->toThrow(MontoPagoExcedeSaldoException::class);

    expect($recibo->fresh()->montoPagado())->toBe(500.0);
});

test('registrar un pago con monto cero o negativo se rechaza', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => '960.75']);

    expect(fn () => $this->servicio->registrar($recibo, ['monto' => 0, 'fecha_pago' => now()->format('Y-m-d')], $this->admin->id))
        ->toThrow(MontoPagoInvalidoException::class);
    expect(fn () => $this->servicio->registrar($recibo, ['monto' => -10, 'fecha_pago' => now()->format('Y-m-d')], $this->admin->id))
        ->toThrow(MontoPagoInvalidoException::class);

    expect($recibo->fresh()->pagos)->toHaveCount(0);
});

test('registrar un pago sobre un recibo anulado se rechaza', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => '960.75', 'estado' => 'anulado', 'fecha_anulacion' => now()]);

    expect(fn () => $this->servicio->registrar($recibo, ['monto' => 100, 'fecha_pago' => now()->format('Y-m-d')], $this->admin->id))
        ->toThrow(ReciboAnuladoNoAdmitePagosException::class);
});

test('actualizar un pago recalcula el saldo excluyendo el propio pago editado', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => '960.75']);
    $pago = $this->servicio->registrar($recibo, ['monto' => 500, 'fecha_pago' => now()->format('Y-m-d')], $this->admin->id);

    // Editar al mismo monto que ya tenía debe ser válido (no debería contarse dos veces).
    $this->servicio->actualizar($pago, ['monto' => 500, 'fecha_pago' => now()->format('Y-m-d')]);
    expect($recibo->fresh()->montoPagado())->toBe(500.0);

    // Editar a un monto mayor, dentro del saldo real (960.75), también debe ser válido.
    $this->servicio->actualizar($pago->fresh(), ['monto' => 800, 'fecha_pago' => now()->format('Y-m-d')]);
    expect($recibo->fresh()->montoPagado())->toBe(800.0);
});

test('editar un pago a un monto que excede el saldo real se rechaza', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => '960.75']);
    $pago = $this->servicio->registrar($recibo, ['monto' => 500, 'fecha_pago' => now()->format('Y-m-d')], $this->admin->id);

    expect(fn () => $this->servicio->actualizar($pago, ['monto' => 1000, 'fecha_pago' => now()->format('Y-m-d')]))
        ->toThrow(MontoPagoExcedeSaldoException::class);
});

test('eliminar un pago recalcula el estado del recibo, que puede volver a pendiente', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => '960.75']);
    $pago = $this->servicio->registrar($recibo, ['monto' => 960.75, 'fecha_pago' => now()->format('Y-m-d')], $this->admin->id);
    expect($recibo->fresh()->estado)->toBe('pagado');

    $this->servicio->eliminar($pago);

    $recibo->refresh();
    expect($recibo->estado)->toBe('pendiente');
    expect($recibo->fecha_pago)->toBeNull();
    expect($recibo->montoPagado())->toBe(0.0);
});

test('editar o eliminar un pago de un recibo anulado se rechaza', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => '960.75']);
    $pago = $this->servicio->registrar($recibo, ['monto' => 500, 'fecha_pago' => now()->format('Y-m-d')], $this->admin->id);

    $recibo->update(['estado' => 'anulado', 'fecha_anulacion' => now()]);

    expect(fn () => $this->servicio->actualizar($pago->fresh(), ['monto' => 600, 'fecha_pago' => now()->format('Y-m-d')]))
        ->toThrow(ReciboAnuladoNoAdmitePagosException::class);
    expect(fn () => $this->servicio->eliminar($pago->fresh()))
        ->toThrow(ReciboAnuladoNoAdmitePagosException::class);
});
