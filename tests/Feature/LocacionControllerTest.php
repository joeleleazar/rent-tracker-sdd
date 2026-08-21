<?php

use App\Models\Locacion;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

test('el listado solo muestra locaciones alquilables', function () {
    Locacion::factory()->create(['nombre' => 'Local Alquilable', 'es_alquilable' => true]);
    Locacion::factory()->create(['nombre' => 'Galería No Alquilable', 'es_alquilable' => false]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('Local Alquilable');
    $respuesta->assertDontSee('Galería No Alquilable');
});

test('el detalle de una locacion muestra la ruta de jerarquia truncada', function () {
    $galeria = Locacion::factory()->create(['nombre' => 'Galería El Sol']);
    $piso = Locacion::factory()->create(['nombre' => 'Piso 1', 'locacion_padre_id' => $galeria->id]);
    $local = Locacion::factory()->create(['nombre' => 'Local A', 'locacion_padre_id' => $piso->id, 'es_alquilable' => true]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.show', $local));

    $respuesta->assertOk();
    $respuesta->assertSee('Galería El Sol');
    $respuesta->assertSee('Piso 1');
    $respuesta->assertSee('Local A');
});

test('un administrador autenticado puede crear una locacion con padre', function () {
    $padre = Locacion::factory()->create(['nombre' => 'Galería Central']);

    $respuesta = $this->actingAs($this->admin)->post(route('locaciones.store'), [
        'nombre' => 'Piso 1',
        'tamano' => '120.00',
        'ubicacion_fisica' => 'Sector Norte',
        'descripcion' => 'Primer nivel de la galería',
        'locacion_padre_id' => $padre->id,
        'es_alquilable' => false,
    ]);

    $locacion = Locacion::firstWhere('nombre', 'Piso 1');

    $respuesta->assertRedirect(route('locaciones.show', $locacion));
    expect($locacion)->not->toBeNull();
    expect($locacion->locacion_padre_id)->toBe($padre->id);
});

test('rechaza guardar una locacion sin tamano', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('locaciones.store'), [
        'nombre' => 'Piso 1',
        'tamano' => '',
        'ubicacion_fisica' => 'Sector Norte',
        'descripcion' => 'Primer nivel',
        'es_alquilable' => false,
    ]);

    $respuesta->assertSessionHasErrors('tamano');
    expect(Locacion::count())->toBe(0);
});

test('rechaza asignar como padre a una de sus propias locaciones hijas', function () {
    $galeria = Locacion::factory()->create(['nombre' => 'Galería El Sol']);
    $piso = Locacion::factory()->create(['nombre' => 'Piso 1', 'locacion_padre_id' => $galeria->id]);

    $respuesta = $this->actingAs($this->admin)->put(route('locaciones.update', $galeria), [
        'nombre' => $galeria->nombre,
        'tamano' => '999.00',
        'ubicacion_fisica' => $galeria->ubicacion_fisica,
        'descripcion' => $galeria->descripcion,
        'locacion_padre_id' => $piso->id,
        'es_alquilable' => false,
    ]);

    $respuesta->assertStatus(302);
    $respuesta->assertSessionHasErrors('locacion_padre_id');
    expect($galeria->fresh()->locacion_padre_id)->toBeNull();
});

test('bloquea la eliminacion de una locacion con sub-locaciones asociadas', function () {
    $galeria = Locacion::factory()->create(['nombre' => 'Galería El Sol']);
    Locacion::factory()->create(['nombre' => 'Piso 1', 'locacion_padre_id' => $galeria->id]);

    $respuesta = $this->actingAs($this->admin)->delete(route('locaciones.destroy', $galeria));

    $respuesta->assertStatus(302);
    $respuesta->assertSessionHasErrors('eliminar');
    expect(Locacion::find($galeria->id))->not->toBeNull();
});

test('permite eliminar una locacion sin sub-locaciones asociadas', function () {
    $local = Locacion::factory()->create(['nombre' => 'Local A']);

    $respuesta = $this->actingAs($this->admin)->delete(route('locaciones.destroy', $local));

    $respuesta->assertRedirect(route('locaciones.index'));
    expect(Locacion::find($local->id))->toBeNull();
});

test('un usuario no autenticado no puede acceder a las rutas de locacion', function () {
    $respuesta = $this->get(route('locaciones.index'));

    $respuesta->assertRedirect(route('login'));
});
