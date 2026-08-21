<?php

use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;

test('un contrato pertenece a una locacion y a un inquilino', function () {
    $locacion = Locacion::factory()->create();
    $inquilino = Inquilino::factory()->create();

    $contrato = Contrato::factory()->create([
        'locacion_id' => $locacion->id,
        'inquilino_id' => $inquilino->id,
    ]);

    expect($contrato->locacion)->toBeInstanceOf(Locacion::class);
    expect($contrato->locacion->id)->toBe($locacion->id);
    expect($contrato->inquilino)->toBeInstanceOf(Inquilino::class);
    expect($contrato->inquilino->id)->toBe($inquilino->id);
});

test('monto_renta se castea como decimal con dos posiciones', function () {
    $contrato = Contrato::factory()->create(['monto_renta' => 1500]);

    expect($contrato->fresh()->monto_renta)->toBe('1500.00');
});

test('estado por defecto es borrador', function () {
    $contrato = Contrato::factory()->create();
    $contrato->estado = null;

    $nuevo = new Contrato($contrato->only(['locacion_id', 'inquilino_id', 'fecha_inicio', 'fecha_fin', 'monto_renta']));

    expect($nuevo->estado)->toBe('borrador');
});

test('acepta unicamente los valores de estado definidos', function (string $estado) {
    $contrato = Contrato::factory()->create(['estado' => $estado]);

    expect($contrato->fresh()->estado)->toBe($estado);
})->with(['borrador', 'activo', 'vencido', 'rescindido']);

test('rechaza un valor de estado invalido a nivel de base de datos', function () {
    $contrato = Contrato::factory()->create();

    $contrato->estado = 'invalido';

    expect(fn () => $contrato->saveQuietly())->toThrow(\Illuminate\Database\QueryException::class);
});

test('los costos fijos tienen por defecto 0.00 y se castean como decimal', function () {
    $locacion = Locacion::factory()->create();
    $inquilino = Inquilino::factory()->create();

    $contrato = new Contrato([
        'locacion_id' => $locacion->id,
        'inquilino_id' => $inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => 1000,
    ]);
    $contrato->save();

    $contrato->refresh();
    expect($contrato->costo_agua)->toBe('0.00');
    expect($contrato->costo_luz)->toBe('0.00');
    expect($contrato->costo_pasadizo)->toBe('0.00');
    expect($contrato->costo_seguridad)->toBe('0.00');
});

test('los hitos de notificacion de vencimiento se castean como datetime nulo por defecto', function () {
    $contrato = Contrato::factory()->create();

    expect($contrato->notificado_30_dias_en)->toBeNull();
    expect($contrato->notificado_15_dias_en)->toBeNull();
    expect($contrato->notificado_7_dias_en)->toBeNull();
});

test('un contrato sin garantia registrada indica tieneGarantia false', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => null]);

    expect($contrato->tieneGarantia())->toBeFalse();
});

test('un monto de garantia igual a cero se trata como sin garantia', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => 0]);

    expect($contrato->tieneGarantia())->toBeFalse();
});

test('un monto de garantia mayor a cero indica tieneGarantia true', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => 1500]);

    expect($contrato->tieneGarantia())->toBeTrue();
    expect($contrato->fresh()->monto_garantia)->toBe('1500.00');
});

test('garantiaResuelta refleja el estado_garantia del contrato', function () {
    $contrato = Contrato::factory()->create(['monto_garantia' => 1500, 'estado_garantia' => 'entregada']);
    expect($contrato->garantiaResuelta())->toBeFalse();

    $contrato->update(['estado_garantia' => 'resuelta']);
    expect($contrato->fresh()->garantiaResuelta())->toBeTrue();
});
