<?php

use App\Models\Locacion;
use App\Services\ServicioConstruccionArbolLocaciones;

beforeEach(function () {
    $this->servicio = new ServicioConstruccionArbolLocaciones();
});

test('construir agrupa correctamente las locaciones por locacion_padre_id', function () {
    $galeria = Locacion::factory()->create(['nombre' => 'Galería El Sol']);
    $piso = Locacion::factory()->create(['nombre' => 'Piso 1', 'locacion_padre_id' => $galeria->id]);
    $local = Locacion::factory()->create(['nombre' => 'Local A', 'locacion_padre_id' => $piso->id]);

    $raices = $this->servicio->construir();

    expect($raices)->toHaveCount(1);
    expect($raices[0]['locacion']->id)->toBe($galeria->id);
    expect($raices[0]['hijos'])->toHaveCount(1);
    expect($raices[0]['hijos'][0]['locacion']->id)->toBe($piso->id);
    expect($raices[0]['hijos'][0]['hijos'])->toHaveCount(1);
    expect($raices[0]['hijos'][0]['hijos'][0]['locacion']->id)->toBe($local->id);
});

test('construir admite multiples locaciones raiz independientes', function () {
    $raiz1 = Locacion::factory()->create(['nombre' => 'Galería El Sol']);
    $raiz2 = Locacion::factory()->create(['nombre' => 'Local Suelto']);

    $raices = $this->servicio->construir();

    expect($raices)->toHaveCount(2);
    $idsRaices = collect($raices)->map(fn (array $nodo) => $nodo['locacion']->id)->all();
    expect($idsRaices)->toContain($raiz1->id, $raiz2->id);
});

test('construir devuelve un arreglo de hijos vacio para una locacion sin hijas', function () {
    Locacion::factory()->create(['nombre' => 'Local Suelto']);

    $raices = $this->servicio->construir();

    expect($raices[0]['hijos'])->toBe([]);
});
