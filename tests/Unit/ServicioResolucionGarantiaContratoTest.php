<?php

use App\Exceptions\GarantiaDescuadreException;
use App\Exceptions\MotivoRetencionRequeridoException;
use App\Exceptions\ResolucionGarantiaRequiereConfirmacionException;
use App\Models\Contrato;
use App\Services\ServicioResolucionGarantiaContrato;

beforeEach(function () {
    $this->servicio = new ServicioResolucionGarantiaContrato();
});

test('registra una devolucion total sin retencion', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => 1500]);

    $this->servicio->registrar($contrato, 1500, 0, null, false);

    $contrato->refresh();
    expect($contrato->estado_garantia)->toBe('resuelta');
    expect($contrato->monto_devuelto_garantia)->toBe('1500.00');
    expect($contrato->monto_retenido_garantia)->toBe('0.00');
    expect($contrato->fecha_resolucion_garantia)->not->toBeNull();
});

test('registra una devolucion parcial con retencion y motivo', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => 1500]);

    $this->servicio->registrar($contrato, 1200, 300, 'Reparación de puerta dañada', false);

    $contrato->refresh();
    expect($contrato->monto_devuelto_garantia)->toBe('1200.00');
    expect($contrato->monto_retenido_garantia)->toBe('300.00');
    expect($contrato->motivo_retencion_garantia)->toBe('Reparación de puerta dañada');
    expect($contrato->estado_garantia)->toBe('resuelta');
});

test('rechaza la resolucion si la suma no coincide con el monto de garantia', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => 1500]);

    expect(fn () => $this->servicio->registrar($contrato, 1200, 200, 'Motivo', false))
        ->toThrow(GarantiaDescuadreException::class);

    expect($contrato->fresh()->estado_garantia)->toBeNull();
});

test('rechaza la resolucion con retencion sin motivo', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => 1500]);

    expect(fn () => $this->servicio->registrar($contrato, 1200, 300, null, false))
        ->toThrow(MotivoRetencionRequeridoException::class);

    expect($contrato->fresh()->estado_garantia)->toBeNull();
});

test('exige un motivo tambien en la retencion total de la garantia', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => 1500]);

    expect(fn () => $this->servicio->registrar($contrato, 0, 1500, '', false))
        ->toThrow(MotivoRetencionRequeridoException::class);
});

test('exige confirmacion para corregir una resolucion ya registrada', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => 1500]);
    $this->servicio->registrar($contrato, 1500, 0, null, false);

    expect(fn () => $this->servicio->registrar($contrato->fresh(), 1400, 100, 'Motivo nuevo', false))
        ->toThrow(ResolucionGarantiaRequiereConfirmacionException::class);
});

test('permite corregir una resolucion ya registrada si se confirma', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => 1500]);
    $this->servicio->registrar($contrato, 1500, 0, null, false);

    $this->servicio->registrar($contrato->fresh(), 1400, 100, 'Motivo corregido', true);

    $contrato->refresh();
    expect($contrato->monto_devuelto_garantia)->toBe('1400.00');
    expect($contrato->monto_retenido_garantia)->toBe('100.00');
    expect($contrato->motivo_retencion_garantia)->toBe('Motivo corregido');
});

test('el cuadre usa comparacion exacta y no falsos positivos por coma flotante', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => 0.30]);

    // 0.1 + 0.2 !== 0.3 en aritmética de punto flotante de PHP; bccomp debe
    // considerarlos iguales con 2 decimales.
    $this->servicio->registrar($contrato, 0.1, 0.2, 'Retención mínima', false);

    expect($contrato->fresh()->estado_garantia)->toBe('resuelta');
});
