<?php

use App\Models\Locacion;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

test('el arbol muestra tanto locaciones alquilables como contenedoras', function () {
    Locacion::factory()->create(['nombre' => 'Local Alquilable', 'es_alquilable' => true]);
    Locacion::factory()->create(['nombre' => 'Galería No Alquilable', 'es_alquilable' => false]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('Local Alquilable');
    $respuesta->assertSee('Galería No Alquilable');
    $respuesta->assertSee('Alquilable');
    $respuesta->assertSee('No Alquilable');
});

test('el arbol muestra multiples locaciones raiz como arboles independientes', function () {
    $galeria = Locacion::factory()->create(['nombre' => 'Galería El Sol']);
    Locacion::factory()->create(['nombre' => 'Piso 1', 'locacion_padre_id' => $galeria->id]);
    Locacion::factory()->create(['nombre' => 'Local Suelto']);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('Galería El Sol');
    $respuesta->assertSee('Piso 1');
    $respuesta->assertSee('Local Suelto');
});

test('cada fila del arbol ofrece editar y agregar hija con el padre preseleccionado', function () {
    $galeria = Locacion::factory()->create(['nombre' => 'Galería El Sol', 'es_alquilable' => false]);
    $local = Locacion::factory()->create(['nombre' => 'Local A', 'locacion_padre_id' => $galeria->id, 'es_alquilable' => true]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.index'));

    $respuesta->assertOk();
    $respuesta->assertSee(route('locaciones.edit', $galeria), false);
    $respuesta->assertSee(route('locaciones.edit', $local), false);
    $respuesta->assertSee(route('locaciones.create', ['locacion_padre_id' => $galeria->id]), false);
    $respuesta->assertSee(route('locaciones.create', ['locacion_padre_id' => $local->id]), false);
});

test('el arbol muestra el icono y la etiqueta del tipo de cada locacion', function () {
    Locacion::factory()->create(['nombre' => 'Galería El Sol', 'tipo' => 'galeria']);
    Locacion::factory()->create(['nombre' => 'Local Suelto', 'tipo' => 'local']);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('Galería');
    $respuesta->assertSee('Local');
    $respuesta->assertSee('bi-building', false);
    $respuesta->assertSee('bi-shop', false);
});

test('una locacion sin tipo asignado se muestra como "Sin tipo" sin error', function () {
    Locacion::factory()->create(['nombre' => 'Locación Antigua', 'tipo' => null]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('Sin tipo');
});

test('la accion rapida agregar preselecciona el padre en el formulario de creacion', function () {
    $padre = Locacion::factory()->create(['nombre' => 'Galería El Sol']);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.create', ['locacion_padre_id' => $padre->id]));

    $respuesta->assertOk();
    $respuesta->assertSee("value=\"{$padre->id}\" selected", false);
});

test('un nodo con locaciones hijas expone un control de colapso y uno sin hijas no', function () {
    $galeria = Locacion::factory()->create(['nombre' => 'Galería El Sol']);
    $local = Locacion::factory()->create(['nombre' => 'Local A', 'locacion_padre_id' => $galeria->id]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('data-bs-target="#hijos-locacion-' . $galeria->id . '"', false);
    $respuesta->assertDontSee('data-bs-target="#hijos-locacion-' . $local->id . '"', false);
});

test('la ruta dashboard redirige al arbol unificado de locaciones', function () {
    $respuesta = $this->actingAs($this->admin)->get(route('dashboard'));

    $respuesta->assertRedirect(route('locaciones.index'));
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
        'tipo' => 'piso',
    ]);

    $locacion = Locacion::firstWhere('nombre', 'Piso 1');

    $respuesta->assertRedirect(route('locaciones.show', $locacion));
    expect($locacion)->not->toBeNull();
    expect($locacion->locacion_padre_id)->toBe($padre->id);
    expect($locacion->tipo)->toBe('piso');
});

test('rechaza guardar una locacion sin tamano', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('locaciones.store'), [
        'nombre' => 'Piso 1',
        'tamano' => '',
        'ubicacion_fisica' => 'Sector Norte',
        'descripcion' => 'Primer nivel',
        'es_alquilable' => false,
        'tipo' => 'piso',
    ]);

    $respuesta->assertSessionHasErrors('tamano');
    expect(Locacion::count())->toBe(0);
});

test('rechaza guardar una locacion sin tipo', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('locaciones.store'), [
        'nombre' => 'Piso 1',
        'tamano' => '120.00',
        'ubicacion_fisica' => 'Sector Norte',
        'descripcion' => 'Primer nivel',
        'es_alquilable' => false,
    ]);

    $respuesta->assertSessionHasErrors('tipo');
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
        'tipo' => 'galeria',
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
