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
