<?php

use App\Models\Recibo;
use App\Services\ServicioCodigoQrRecibo;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->servicio = app(ServicioCodigoQrRecibo::class);
});

test('el enlace es la ruta firmada de cobro del recibo', function () {
    $recibo = Recibo::factory()->create();

    $enlace = $this->servicio->enlace($recibo);

    expect($enlace)->toBe(URL::signedRoute('cobro.recibo', $recibo))
        ->and($enlace)->toContain('/cobro/recibo/'.$recibo->id)
        ->and($enlace)->toContain('signature=');
});

test('dataUri devuelve un PNG en base64 no vacio', function () {
    $recibo = Recibo::factory()->create();

    $dataUri = $this->servicio->dataUri($recibo);

    expect($dataUri)->toStartWith('data:image/png;base64,')
        ->and(strlen($dataUri))->toBeGreaterThan(200);
});

test('numeroEsValido acepta enteros positivos con o sin # y rechaza el resto', function () {
    expect($this->servicio->numeroEsValido('42'))->toBeTrue()
        ->and($this->servicio->numeroEsValido('#42'))->toBeTrue()
        ->and($this->servicio->numeroEsValido('abc'))->toBeFalse()
        ->and($this->servicio->numeroEsValido(''))->toBeFalse()
        ->and($this->servicio->numeroEsValido(null))->toBeFalse()
        ->and($this->servicio->idDesdeNumero('#42'))->toBe(42);
});
