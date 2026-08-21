<?php

use App\Services\ServicioCalculoFechaLimitePago;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->servicio = new ServicioCalculoFechaLimitePago();
});

test('devuelve el mismo dia cuando el mes termina en sabado', function () {
    // Agosto 2026 termina el sábado 29.
    $fechaLimite = $this->servicio->calcular(Carbon::parse('2026-08-01'));

    expect($fechaLimite->format('Y-m-d'))->toBe('2026-08-29');
    expect($fechaLimite->isSaturday())->toBeTrue();
});

test('retrocede al sabado anterior cuando el mes no termina en sabado', function () {
    // Setiembre 2026 termina el miércoles 30; el último sábado es el 26.
    $fechaLimite = $this->servicio->calcular(Carbon::parse('2026-09-01'));

    expect($fechaLimite->format('Y-m-d'))->toBe('2026-09-26');
    expect($fechaLimite->isSaturday())->toBeTrue();
});

test('calcula correctamente para los 7 posibles dias de fin de mes', function (string $mes, string $esperado) {
    $fechaLimite = $this->servicio->calcular(Carbon::parse($mes));

    expect($fechaLimite->format('Y-m-d'))->toBe($esperado);
    expect($fechaLimite->isSaturday())->toBeTrue();
})->with([
    // Verificado manualmente contra un calendario de 2026.
    ['2026-08-01', '2026-08-29'], // agosto termina sábado 29
    ['2026-09-01', '2026-09-26'], // setiembre termina miércoles 30
    ['2026-10-01', '2026-10-31'], // octubre termina sábado 31
    ['2026-11-01', '2026-11-28'], // noviembre termina lunes 30
    ['2026-12-01', '2026-12-26'], // diciembre termina jueves 31
    ['2027-01-01', '2027-01-30'], // enero termina domingo 31
    ['2027-02-01', '2027-02-27'], // febrero termina domingo 28
]);
