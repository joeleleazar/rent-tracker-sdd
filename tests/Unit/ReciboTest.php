<?php

use App\Models\Contrato;
use App\Models\Locacion;
use App\Models\Recibo;

test('un recibo pertenece a un contrato y a una locacion', function () {
    $locacion = Locacion::factory()->create();
    $contrato = Contrato::factory()->create(['locacion_id' => $locacion->id]);

    $recibo = Recibo::factory()->create([
        'contrato_id' => $contrato->id,
        'locacion_id' => $locacion->id,
    ]);

    expect($recibo->contrato->id)->toBe($contrato->id);
    expect($recibo->locacion->id)->toBe($locacion->id);
});

test('total suma unicamente los conceptos incluidos', function () {
    $recibo = Recibo::factory()->make([
        'monto_renta' => 1000,
        'monto_agua' => 50,
        'monto_luz' => 80,
        'monto_pasadizo' => 30,
        'monto_seguridad' => 40,
        'incluye_alquiler' => true,
        'incluye_agua' => true,
        'incluye_luz' => true,
        'incluye_pasadizo' => true,
        'incluye_seguridad' => true,
    ]);

    expect($recibo->total())->toBe(1200.0);
});

test('total excluye los conceptos marcados como no incluidos', function () {
    $recibo = Recibo::factory()->make([
        'monto_renta' => 1000,
        'monto_agua' => 50,
        'monto_luz' => 80,
        'monto_pasadizo' => 30,
        'monto_seguridad' => 40,
        'incluye_alquiler' => true,
        'incluye_agua' => true,
        'incluye_luz' => false,
        'incluye_pasadizo' => true,
        'incluye_seguridad' => false,
    ]);

    expect($recibo->total())->toBe(1080.0);
});

test('editar los costos del contrato despues de emitir un recibo no altera el recibo ya emitido', function () {
    $contrato = Contrato::factory()->create(['costo_agua' => 50]);

    $recibo = Recibo::factory()->create([
        'contrato_id' => $contrato->id,
        'locacion_id' => $contrato->locacion_id,
        'monto_agua' => 50,
    ]);

    $contrato->update(['costo_agua' => 999]);

    expect($recibo->fresh()->monto_agua)->toBe('50.00');
    expect($contrato->fresh()->costo_agua)->toBe('999.00');
});
