<?php

use App\Exceptions\ContratoSolapadoException;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Services\ServicioValidacionSolapamientoContrato;

beforeEach(function () {
    $this->locacion = Locacion::factory()->create();
    $this->inquilino = Inquilino::factory()->create();
    $this->servicio = new ServicioValidacionSolapamientoContrato();
});

test('rechaza un rango de fechas que se solapa con un contrato existente', function () {
    Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'estado' => 'activo',
    ]);

    $ejecutado = false;

    expect(fn () => $this->servicio->validarYEjecutar(
        $this->locacion->id,
        '2026-06-01',
        '2027-05-31',
        null,
        function () use (&$ejecutado) {
            $ejecutado = true;
        }
    ))->toThrow(ContratoSolapadoException::class);

    expect($ejecutado)->toBeFalse();
});

test('permite un rango de fechas secuencial sin solapamiento', function () {
    Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'estado' => 'activo',
    ]);

    $resultado = $this->servicio->validarYEjecutar(
        $this->locacion->id,
        '2027-01-01',
        '2027-12-31',
        null,
        fn () => 'creado'
    );

    expect($resultado)->toBe('creado');
});

test('ignora contratos rescindidos al evaluar el solapamiento', function () {
    Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'estado' => 'rescindido',
    ]);

    $resultado = $this->servicio->validarYEjecutar(
        $this->locacion->id,
        '2026-06-01',
        '2026-08-31',
        null,
        fn () => 'creado'
    );

    expect($resultado)->toBe('creado');
});

test('excluye el propio contrato al validar una actualizacion', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'estado' => 'activo',
    ]);

    $resultado = $this->servicio->validarYEjecutar(
        $this->locacion->id,
        '2026-01-01',
        '2026-12-31',
        $contrato->id,
        fn () => 'actualizado'
    );

    expect($resultado)->toBe('actualizado');
});

test('rechaza cuando el nuevo contrato envuelve completamente a uno existente', function () {
    Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-03-01',
        'fecha_fin' => '2026-06-30',
        'estado' => 'activo',
    ]);

    expect(fn () => $this->servicio->validarYEjecutar(
        $this->locacion->id,
        '2026-01-01',
        '2026-12-31',
        null,
        fn () => 'creado'
    ))->toThrow(ContratoSolapadoException::class);
});
