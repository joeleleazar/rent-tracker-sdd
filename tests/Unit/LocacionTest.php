<?php

use App\Models\Locacion;

test('scope alquilables filtra solo las locaciones con es_alquilable en true', function () {
    Locacion::factory()->create(['es_alquilable' => true]);
    Locacion::factory()->create(['es_alquilable' => false]);

    expect(Locacion::alquilables()->count())->toBe(1);
});

test('ancestros devuelve la cadena de padres de mas antiguo a mas cercano invertido', function () {
    $galeria = Locacion::factory()->create(['nombre' => 'Galería El Sol']);
    $piso = Locacion::factory()->create(['nombre' => 'Piso 1', 'locacion_padre_id' => $galeria->id]);
    $local = Locacion::factory()->create(['nombre' => 'Local A', 'locacion_padre_id' => $piso->id]);

    $ancestros = $local->ancestros();

    expect($ancestros)->toHaveCount(2);
    expect($ancestros[0]->id)->toBe($piso->id);
    expect($ancestros[1]->id)->toBe($galeria->id);
});

test('ancestros devuelve un arreglo vacio para una locacion raiz', function () {
    $raiz = Locacion::factory()->create();

    expect($raiz->ancestros())->toBe([]);
});

test('rutaJerarquiaTruncada muestra la cadena completa sin omision cuando tiene 3 niveles o menos', function () {
    $galeria = Locacion::factory()->create(['nombre' => 'Galería El Sol']);
    $piso = Locacion::factory()->create(['nombre' => 'Piso 1', 'locacion_padre_id' => $galeria->id]);
    $local = Locacion::factory()->create(['nombre' => 'Local A', 'locacion_padre_id' => $piso->id]);

    $ruta = $local->rutaJerarquiaTruncada();

    expect($ruta['omitido'])->toBeFalse();
    expect(collect($ruta['niveles'])->pluck('nombre')->all())
        ->toBe(['Galería El Sol', 'Piso 1', 'Local A']);
});

test('rutaJerarquiaTruncada trunca a los ultimos 3 niveles con indicador de omision', function () {
    $nivel1 = Locacion::factory()->create(['nombre' => 'Nivel 1']);
    $nivel2 = Locacion::factory()->create(['nombre' => 'Nivel 2', 'locacion_padre_id' => $nivel1->id]);
    $nivel3 = Locacion::factory()->create(['nombre' => 'Nivel 3', 'locacion_padre_id' => $nivel2->id]);
    $nivel4 = Locacion::factory()->create(['nombre' => 'Nivel 4', 'locacion_padre_id' => $nivel3->id]);
    $nivel5 = Locacion::factory()->create(['nombre' => 'Nivel 5', 'locacion_padre_id' => $nivel4->id]);

    $ruta = $nivel5->rutaJerarquiaTruncada();

    expect($ruta['omitido'])->toBeTrue();
    expect(collect($ruta['niveles'])->pluck('nombre')->all())
        ->toBe(['Nivel 3', 'Nivel 4', 'Nivel 5']);
});

test('tamano se castea como decimal con dos posiciones', function () {
    $locacion = Locacion::factory()->create(['tamano' => 120]);

    expect($locacion->fresh()->tamano)->toBe('120.00');
});
