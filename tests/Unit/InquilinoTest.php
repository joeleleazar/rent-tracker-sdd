<?php

use App\Models\Inquilino;
use Illuminate\Database\QueryException;

test('el dni de un inquilino es unico en el directorio global', function () {
    Inquilino::factory()->create(['dni' => '12345678']);

    expect(fn () => Inquilino::factory()->create(['dni' => '12345678']))
        ->toThrow(QueryException::class);
});

test('nombreCompleto concatena apellidos y nombres', function () {
    $inquilino = Inquilino::factory()->make(['apellidos' => 'Pérez Gómez', 'nombres' => 'Juan Carlos']);

    expect($inquilino->nombreCompleto())->toBe('Pérez Gómez, Juan Carlos');
});

test('fecha_nacimiento se castea a fecha', function () {
    $inquilino = Inquilino::factory()->create(['fecha_nacimiento' => '1960-05-15']);

    expect($inquilino->fecha_nacimiento)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
