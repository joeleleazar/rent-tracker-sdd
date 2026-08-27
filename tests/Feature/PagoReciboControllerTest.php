<?php

use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $this->inquilino = Inquilino::factory()->create();
    $this->contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'estado' => 'activo',
    ]);
    $this->recibo = Recibo::factory()->create([
        'contrato_id' => $this->contrato->id,
        'locacion_id' => $this->locacion->id,
        'monto_renta' => '960.75',
    ]);
});

test('specs/032: registrar un pago valido crea el pago y actualiza el estado del recibo', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('pagos.store', $this->recibo), [
        'monto' => '500.00',
        'fecha_pago' => now()->format('Y-m-d'),
    ]);

    $respuesta->assertRedirect();
    expect($this->recibo->fresh()->montoPagado())->toBe(500.0);
    expect($this->recibo->fresh()->estado)->toBe('pendiente');
});

test('specs/032: registrar un pago que completa el total marca el recibo como pagado', function () {
    $this->actingAs($this->admin)->post(route('pagos.store', $this->recibo), [
        'monto' => '960.75',
        'fecha_pago' => now()->format('Y-m-d'),
    ]);

    expect($this->recibo->fresh()->estado)->toBe('pagado');
});

test('specs/032: rechaza un pago que excede el saldo pendiente', function () {
    $this->actingAs($this->admin)->post(route('pagos.store', $this->recibo), [
        'monto' => '500.00',
        'fecha_pago' => now()->format('Y-m-d'),
    ]);

    $respuesta = $this->actingAs($this->admin)->post(route('pagos.store', $this->recibo), [
        'monto' => '600.00',
        'fecha_pago' => now()->format('Y-m-d'),
    ]);

    $respuesta->assertSessionHasErrors('monto');
    expect($this->recibo->fresh()->montoPagado())->toBe(500.0);
});

test('specs/032: rechaza un pago con monto cero', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('pagos.store', $this->recibo), [
        'monto' => '0',
        'fecha_pago' => now()->format('Y-m-d'),
    ]);

    $respuesta->assertSessionHasErrors('monto');
});

test('specs/032: rechaza registrar un pago sobre un recibo anulado', function () {
    $this->recibo->update(['estado' => 'anulado', 'fecha_anulacion' => now()]);

    $respuesta = $this->actingAs($this->admin)->post(route('pagos.store', $this->recibo), [
        'monto' => '100.00',
        'fecha_pago' => now()->format('Y-m-d'),
    ]);

    $respuesta->assertSessionHasErrors();
    expect($this->recibo->fresh()->pagos)->toHaveCount(0);
});

test('specs/032: un usuario no autenticado no puede registrar un pago', function () {
    $respuesta = $this->post(route('pagos.store', $this->recibo), [
        'monto' => '100.00',
        'fecha_pago' => now()->format('Y-m-d'),
    ]);

    $respuesta->assertRedirect(route('login'));
});

test('specs/032: editar un pago valido actualiza su monto', function () {
    $this->actingAs($this->admin)->post(route('pagos.store', $this->recibo), [
        'monto' => '500.00',
        'fecha_pago' => now()->format('Y-m-d'),
    ]);
    $pago = $this->recibo->fresh()->pagos->first();

    $respuesta = $this->actingAs($this->admin)->put(route('pagos.update', $pago), [
        'monto' => '300.00',
        'fecha_pago' => now()->format('Y-m-d'),
    ]);

    $respuesta->assertRedirect(route('recibos.show', $this->recibo));
    expect($this->recibo->fresh()->montoPagado())->toBe(300.0);
});

test('specs/032: editar un pago a un monto que excede el saldo real se rechaza', function () {
    $this->actingAs($this->admin)->post(route('pagos.store', $this->recibo), [
        'monto' => '500.00',
        'fecha_pago' => now()->format('Y-m-d'),
    ]);
    $pago = $this->recibo->fresh()->pagos->first();

    $respuesta = $this->actingAs($this->admin)->put(route('pagos.update', $pago), [
        'monto' => '1000.00',
        'fecha_pago' => now()->format('Y-m-d'),
    ]);

    $respuesta->assertSessionHasErrors('monto');
    expect($this->recibo->fresh()->montoPagado())->toBe(500.0);
});

test('specs/032: eliminar un pago recalcula el avance del recibo', function () {
    $this->actingAs($this->admin)->post(route('pagos.store', $this->recibo), [
        'monto' => '960.75',
        'fecha_pago' => now()->format('Y-m-d'),
    ]);
    $pago = $this->recibo->fresh()->pagos->first();
    expect($this->recibo->fresh()->estado)->toBe('pagado');

    $respuesta = $this->actingAs($this->admin)->delete(route('pagos.destroy', $pago));

    $respuesta->assertRedirect(route('recibos.show', $this->recibo));
    expect($this->recibo->fresh()->estado)->toBe('pendiente');
    expect($this->recibo->fresh()->pagos)->toHaveCount(0);
});

test('specs/032: rechaza editar o eliminar un pago de un recibo anulado', function () {
    $this->actingAs($this->admin)->post(route('pagos.store', $this->recibo), [
        'monto' => '500.00',
        'fecha_pago' => now()->format('Y-m-d'),
    ]);
    $pago = $this->recibo->fresh()->pagos->first();
    $this->recibo->update(['estado' => 'anulado', 'fecha_anulacion' => now()]);

    $respuestaEditar = $this->actingAs($this->admin)->put(route('pagos.update', $pago), [
        'monto' => '300.00',
        'fecha_pago' => now()->format('Y-m-d'),
    ]);
    $respuestaEditar->assertSessionHasErrors();

    $respuestaEliminar = $this->actingAs($this->admin)->delete(route('pagos.destroy', $pago));
    $respuestaEliminar->assertSessionHasErrors();

    expect($this->recibo->fresh()->pagos)->toHaveCount(1);
});
