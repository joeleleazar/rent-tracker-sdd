<?php

use App\Models\LecturaMedidor;
use App\Models\Locacion;
use Illuminate\Database\QueryException;

test('una lectura de medidor pertenece a una locacion', function () {
    $locacion = Locacion::factory()->create();

    $lectura = LecturaMedidor::factory()->create(['locacion_id' => $locacion->id]);

    expect($lectura->locacion->id)->toBe($locacion->id);
});

test('no permite dos lecturas para la misma locacion y periodo', function () {
    $locacion = Locacion::factory()->create();
    $periodo = now()->startOfMonth()->format('Y-m-d');

    LecturaMedidor::factory()->create(['locacion_id' => $locacion->id, 'periodo' => $periodo]);

    expect(fn () => LecturaMedidor::factory()->create(['locacion_id' => $locacion->id, 'periodo' => $periodo]))
        ->toThrow(QueryException::class);
});

test('lectura_anterior, lectura_actual y consumo_calculado se castean como decimal', function () {
    $lectura = LecturaMedidor::factory()->create(['lectura_anterior' => 1100, 'lectura_actual' => 1250, 'consumo_calculado' => 150]);

    expect($lectura->fresh()->lectura_anterior)->toBe('1100.00');
    expect($lectura->fresh()->lectura_actual)->toBe('1250.00');
    expect($lectura->fresh()->consumo_calculado)->toBe('150.00');
});

test('editar la lectura anterior autocompletada no modifica el periodo previo del cual se traslado', function () {
    $locacion = Locacion::factory()->create();
    $periodoPrevio = LecturaMedidor::factory()->create([
        'locacion_id' => $locacion->id,
        'periodo' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'lectura_anterior' => null,
        'lectura_actual' => 1250,
        'consumo_calculado' => null,
    ]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $locacion->id,
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
        'lectura_anterior' => 1245, // editado manualmente, no coincide con 1250
        'lectura_actual' => 1400,
        'consumo_calculado' => 155,
    ]);

    expect($periodoPrevio->fresh()->lectura_actual)->toBe('1250.00');
});

test('discrepanciaConSiguiente detecta cuando la lectura anterior del siguiente periodo no coincide', function () {
    $locacion = Locacion::factory()->create();
    $actual = LecturaMedidor::factory()->create([
        'locacion_id' => $locacion->id,
        'periodo' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'lectura_actual' => 1250,
    ]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $locacion->id,
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
        'lectura_anterior' => 1245,
        'lectura_actual' => 1400,
    ]);

    expect($actual->discrepanciaConSiguiente())->toBeTrue();
});

test('discrepanciaConSiguiente es falso cuando los valores coinciden', function () {
    $locacion = Locacion::factory()->create();
    $actual = LecturaMedidor::factory()->create([
        'locacion_id' => $locacion->id,
        'periodo' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'lectura_actual' => 1250,
    ]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $locacion->id,
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
        'lectura_anterior' => 1250,
        'lectura_actual' => 1400,
    ]);

    expect($actual->discrepanciaConSiguiente())->toBeFalse();
});
