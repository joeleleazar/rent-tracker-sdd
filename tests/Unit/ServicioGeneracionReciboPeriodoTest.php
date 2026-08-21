<?php

use App\Exceptions\ReciboDuplicadoPeriodoException;
use App\Exceptions\SinContratoActivoEnPeriodoException;
use App\Models\ConfiguracionGeneral;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Services\ServicioCalculoProrrateoContrato;
use App\Services\ServicioGeneracionReciboPeriodo;

beforeEach(function () {
    $this->servicio = new ServicioGeneracionReciboPeriodo(new ServicioCalculoProrrateoContrato());
    $this->locacion = Locacion::factory()->create();
    $this->inquilino = Inquilino::factory()->create();
});

function datosBaseRecibo(): array
{
    return [
        'monto_renta' => 1500,
        'monto_agua' => 50,
        'monto_luz' => 0,
        'monto_pasadizo' => 30,
        'monto_seguridad' => 40,
        'incluye_alquiler' => true,
        'incluye_luz' => true,
        'incluye_agua' => true,
        'incluye_pasadizo' => true,
        'incluye_seguridad' => true,
        'fecha_emision' => now()->format('Y-m-d'),
    ];
}

test('bloquea la generacion si no hay contrato activo en el periodo', function () {
    expect(fn () => $this->servicio->generar($this->locacion, now()->startOfMonth(), datosBaseRecibo()))
        ->toThrow(SinContratoActivoEnPeriodoException::class);
});

test('bloquea un segundo recibo para la misma locacion y periodo', function () {
    Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'estado' => 'activo',
        'fecha_inicio' => now()->subMonth()->format('Y-m-d'),
        'fecha_fin' => now()->addYear()->format('Y-m-d'),
    ]);

    $periodo = now()->startOfMonth();
    $this->servicio->generar($this->locacion, $periodo, datosBaseRecibo());

    expect(fn () => $this->servicio->generar($this->locacion, $periodo, datosBaseRecibo()))
        ->toThrow(ReciboDuplicadoPeriodoException::class);
    expect(Recibo::where('locacion_id', $this->locacion->id)->count())->toBe(1);
});

test('calcula el monto de luz sugerido como consumo por tarifa vigente', function () {
    ConfiguracionGeneral::actual()->update(['tarifa_luz_por_unidad' => 0.75]);
    $lectura = LecturaMedidor::factory()->create([
        'locacion_id' => $this->locacion->id,
        'consumo_calculado' => 100,
    ]);

    expect($this->servicio->calcularMontoLuzSugerido($lectura))->toBe(75.0);
});

test('el monto de luz sugerido es 0 sin lectura o sin dato de consumo anterior', function () {
    expect($this->servicio->calcularMontoLuzSugerido(null))->toBe(0.0);

    $lectura = LecturaMedidor::factory()->create([
        'locacion_id' => $this->locacion->id,
        'consumo_calculado' => null,
    ]);
    expect($this->servicio->calcularMontoLuzSugerido($lectura))->toBe(0.0);
});
