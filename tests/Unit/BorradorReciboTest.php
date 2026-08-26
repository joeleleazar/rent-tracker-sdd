<?php

use App\Models\BorradorRecibo;
use App\Models\Locacion;
use App\Models\User;

test('un borrador de recibo pertenece a un usuario y a una locacion', function () {
    $usuario = User::factory()->create();
    $locacion = Locacion::factory()->create();

    $borrador = BorradorRecibo::create([
        'usuario_id' => $usuario->id,
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
        'locacion_id' => $locacion->id,
        'incluye_alquiler' => true,
        'monto_renta' => 1500,
        'fecha_emision' => now()->format('Y-m-d'),
        'conceptos' => [1 => 50, 2 => 30],
    ]);

    expect($borrador->usuario->id)->toBe($usuario->id);
    expect($borrador->locacion->id)->toBe($locacion->id);
});

test('los casts de un borrador de recibo son correctos', function () {
    $borrador = BorradorRecibo::create([
        'usuario_id' => User::factory()->create()->id,
        'periodo' => '2026-08-01',
        'locacion_id' => Locacion::factory()->create()->id,
        'incluye_alquiler' => true,
        'monto_renta' => 1234.5,
        'fecha_emision' => '2026-08-15',
        'conceptos' => ['3' => 40.5, '4' => 12],
    ]);

    $borrador->refresh();

    expect($borrador->periodo->format('Y-m-d'))->toBe('2026-08-01');
    expect($borrador->incluye_alquiler)->toBeTrue();
    expect($borrador->monto_renta)->toBe('1234.50');
    expect($borrador->fecha_emision->format('Y-m-d'))->toBe('2026-08-15');
    expect($borrador->conceptos)->toBe(['3' => 40.5, '4' => 12]);
});

test('el borrador es unico por usuario, periodo y locacion', function () {
    $usuario = User::factory()->create();
    $locacion = Locacion::factory()->create();
    $periodo = now()->startOfMonth()->format('Y-m-d');

    BorradorRecibo::create([
        'usuario_id' => $usuario->id,
        'periodo' => $periodo,
        'locacion_id' => $locacion->id,
        'conceptos' => [],
    ]);

    expect(fn () => BorradorRecibo::create([
        'usuario_id' => $usuario->id,
        'periodo' => $periodo,
        'locacion_id' => $locacion->id,
        'conceptos' => [],
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
