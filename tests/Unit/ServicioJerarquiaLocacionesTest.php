<?php

use App\Models\Locacion;
use App\Services\ServicioJerarquiaLocaciones;

/**
 * specs/043: resolución de una rama de la jerarquía de locaciones (id + todos
 * sus descendientes) para el filtro del listado de morosos.
 */

function servicioJerarquia(): ServicioJerarquiaLocaciones
{
    return app(ServicioJerarquiaLocaciones::class);
}

test('idsDeRama devuelve el id y todos sus descendientes en varios niveles', function () {
    $galeria = Locacion::factory()->create();
    $piso = Locacion::factory()->create(['locacion_padre_id' => $galeria->id]);
    $localA = Locacion::factory()->create(['locacion_padre_id' => $piso->id]);
    $localB = Locacion::factory()->create(['locacion_padre_id' => $piso->id]);
    $otraRama = Locacion::factory()->create();

    $ids = servicioJerarquia()->idsDeRama($galeria->id);

    expect($ids)->toEqualCanonicalizing([$galeria->id, $piso->id, $localA->id, $localB->id]);
    expect($ids)->not->toContain($otraRama->id);
});

test('idsDeRama devuelve solo el propio id para una hoja', function () {
    $galeria = Locacion::factory()->create();
    $local = Locacion::factory()->create(['locacion_padre_id' => $galeria->id]);

    expect(servicioJerarquia()->idsDeRama($local->id))->toBe([$local->id]);
});
