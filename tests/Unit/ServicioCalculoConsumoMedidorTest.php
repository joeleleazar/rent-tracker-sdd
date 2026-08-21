<?php

use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Services\ServicioCalculoConsumoMedidor;

beforeEach(function () {
    $this->servicio = new ServicioCalculoConsumoMedidor();
    $this->locacion = Locacion::factory()->create();
});

test('sugerirLecturaAnterior devuelve null sin periodo previo', function () {
    $sugerida = $this->servicio->sugerirLecturaAnterior($this->locacion, now()->startOfMonth()->format('Y-m-d'));

    expect($sugerida)->toBeNull();
});

test('sugerirLecturaAnterior devuelve la lectura_actual del periodo previo mas reciente', function () {
    LecturaMedidor::factory()->create([
        'locacion_id' => $this->locacion->id,
        'periodo' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
        'lectura_actual' => 1250,
    ]);

    $sugerida = $this->servicio->sugerirLecturaAnterior($this->locacion, now()->startOfMonth()->format('Y-m-d'));

    expect($sugerida)->toBe(1250.0);
});

test('sugerirLecturaAnterior soporta periodos salteados usando el mas reciente disponible', function () {
    LecturaMedidor::factory()->create([
        'locacion_id' => $this->locacion->id,
        'periodo' => now()->subMonths(3)->startOfMonth()->format('Y-m-d'),
        'lectura_actual' => 1000,
    ]);
    LecturaMedidor::factory()->create([
        'locacion_id' => $this->locacion->id,
        'periodo' => now()->subMonths(2)->startOfMonth()->format('Y-m-d'),
        'lectura_actual' => 1100,
    ]);

    $sugerida = $this->servicio->sugerirLecturaAnterior($this->locacion, now()->startOfMonth()->format('Y-m-d'));

    expect($sugerida)->toBe(1100.0);
});

test('calcularConsumo devuelve null sin lectura anterior', function () {
    expect($this->servicio->calcularConsumo(null, 1250))->toBeNull();
});

test('calcularConsumo resta la lectura anterior de la actual', function () {
    expect($this->servicio->calcularConsumo(1245, 1400))->toBe(155.0);
});

test('calcularConsumo puede devolver un valor negativo', function () {
    expect($this->servicio->calcularConsumo(1250, 1100))->toBe(-150.0);
});
