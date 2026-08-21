<?php

use App\Models\Representante;
use Illuminate\Database\QueryException;

test('el dni de un representante es unico en el directorio global', function () {
    Representante::factory()->create(['dni' => '12345678']);

    expect(fn () => Representante::factory()->create(['dni' => '12345678']))
        ->toThrow(QueryException::class);
});

test('nombreCompleto concatena apellidos y nombres', function () {
    $representante = Representante::factory()->make(['apellidos' => 'Pérez Gómez', 'nombres' => 'Juan Carlos']);

    expect($representante->nombreCompleto())->toBe('Pérez Gómez, Juan Carlos');
});

test('fecha_nacimiento se castea a fecha', function () {
    $representante = Representante::factory()->create(['fecha_nacimiento' => '1960-05-15']);

    expect($representante->fecha_nacimiento)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
