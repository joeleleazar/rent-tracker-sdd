<?php

use App\Models\Contrato;
use App\Services\ServicioCalculoProrrateoContrato;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->servicio = new ServicioCalculoProrrateoContrato();
});

test('sugiere dias activos y monto prorrateado cuando el contrato inicia a mitad de mes', function () {
    $contrato = Contrato::factory()->create([
        'fecha_inicio' => '2026-08-15',
        'fecha_fin' => '2027-08-14',
        'monto_renta' => 1550,
    ]);

    $resultado = $this->servicio->calcular($contrato, Carbon::parse('2026-08-01'));

    expect($resultado)->not->toBeNull();
    expect($resultado['dias_activos'])->toBe(17);
    expect($resultado['dias_totales'])->toBe(31);
    expect($resultado['monto_renta_sugerido'])->toBe(850.0);
});

test('no sugiere prorrateo cuando el contrato inicia el primer dia del mes', function () {
    $contrato = Contrato::factory()->create([
        'fecha_inicio' => '2026-08-01',
        'fecha_fin' => '2027-08-01',
        'monto_renta' => 1550,
    ]);

    $resultado = $this->servicio->calcular($contrato, Carbon::parse('2026-08-01'));

    expect($resultado)->toBeNull();
});

test('sugiere dias activos y monto prorrateado cuando el contrato finaliza a mitad de mes', function () {
    $contrato = Contrato::factory()->create([
        'fecha_inicio' => '2025-01-01',
        'fecha_fin' => '2026-08-10',
        'monto_renta' => 1550,
    ]);

    $resultado = $this->servicio->calcular($contrato, Carbon::parse('2026-08-01'));

    expect($resultado)->not->toBeNull();
    expect($resultado['dias_activos'])->toBe(10);
    expect($resultado['dias_totales'])->toBe(31);
    expect($resultado['monto_renta_sugerido'])->toBe(500.0);
});

test('no sugiere prorrateo cuando el contrato estuvo activo todo el mes', function () {
    $contrato = Contrato::factory()->create([
        'fecha_inicio' => '2025-01-01',
        'fecha_fin' => '2027-01-01',
        'monto_renta' => 1550,
    ]);

    $resultado = $this->servicio->calcular($contrato, Carbon::parse('2026-08-01'));

    expect($resultado)->toBeNull();
});

test('calcula dias activos cuando el contrato inicia y finaliza dentro del mismo mes', function () {
    $contrato = Contrato::factory()->create([
        'fecha_inicio' => '2026-08-05',
        'fecha_fin' => '2026-08-20',
        'monto_renta' => 3100,
    ]);

    $resultado = $this->servicio->calcular($contrato, Carbon::parse('2026-08-01'));

    expect($resultado)->not->toBeNull();
    expect($resultado['dias_activos'])->toBe(16);
    expect($resultado['dias_totales'])->toBe(31);
    expect($resultado['monto_renta_sugerido'])->toBe(round(3100 / 31 * 16, 2));
});
