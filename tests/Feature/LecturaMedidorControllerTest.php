<?php

use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->locacion = Locacion::factory()->create(['es_alquilable' => true]);
});

test('un administrador puede registrar la lectura del medidor de un periodo', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('locaciones.lecturas.store', $this->locacion), [
        'periodo' => '2026-08-01',
        'lectura_actual' => '1250',
    ]);

    $lectura = LecturaMedidor::firstWhere('locacion_id', $this->locacion->id);

    $respuesta->assertRedirect(route('locaciones.lecturas.index', $this->locacion));
    expect($lectura)->not->toBeNull();
    expect($lectura->lectura_actual)->toBe('1250.00');
    expect($lectura->lectura_anterior)->toBeNull();
    // specs/021 Q1:A: sin lectura anterior, el consumo se calcula usando 0 (no queda sin dato).
    expect($lectura->consumo_calculado)->toBe('1250.00');
});

test('el formulario de creacion precarga lectura_anterior con la lectura_actual del periodo previo', function () {
    LecturaMedidor::factory()->create([
        'locacion_id' => $this->locacion->id,
        'periodo' => '2026-07-01',
        'lectura_anterior' => null,
        'lectura_actual' => 1250,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.lecturas.create', $this->locacion) . '?periodo=2026-08');

    $respuesta->assertOk();
    $respuesta->assertSee('value="1250"', false);
});

test('el formulario de creacion muestra sin lectura previa registrada cuando no hay periodo anterior', function () {
    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.lecturas.create', $this->locacion) . '?periodo=2026-08');

    $respuesta->assertOk();
    $respuesta->assertSee('Sin lectura previa registrada');
});

test('permite editar el valor de lectura_anterior autocompletado antes de guardar', function () {
    LecturaMedidor::factory()->create([
        'locacion_id' => $this->locacion->id,
        'periodo' => '2026-07-01',
        'lectura_anterior' => null,
        'lectura_actual' => 1250,
    ]);

    $respuesta = $this->actingAs($this->admin)->post(route('locaciones.lecturas.store', $this->locacion), [
        'periodo' => '2026-08-01',
        'lectura_anterior' => '1245',
        'lectura_actual' => '1400',
    ]);

    $respuesta->assertRedirect(route('locaciones.lecturas.index', $this->locacion));
    $nueva = LecturaMedidor::firstWhere('periodo', '2026-08-01');
    expect($nueva->lectura_anterior)->toBe('1245.00');
    expect($nueva->consumo_calculado)->toBe('155.00');

    // El periodo previo del cual se origino el traslado no se modifica (FR-006).
    $previo = LecturaMedidor::firstWhere('periodo', '2026-07-01');
    expect($previo->lectura_actual)->toBe('1250.00');
});

test('crear redirige a editar si ya existe una lectura para ese periodo', function () {
    $lectura = LecturaMedidor::factory()->create([
        'locacion_id' => $this->locacion->id,
        'periodo' => '2026-08-01',
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.lecturas.create', $this->locacion) . '?periodo=2026-08');

    $respuesta->assertRedirect(route('lecturas.edit', $lectura));
});

test('bloquea guardar una lectura menor a la anterior sin confirmar', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('locaciones.lecturas.store', $this->locacion), [
        'periodo' => '2026-08-01',
        'lectura_anterior' => '1250',
        'lectura_actual' => '1100',
    ]);

    $respuesta->assertSessionHasErrors('lectura_actual');
    expect(LecturaMedidor::where('periodo', '2026-08-01')->count())->toBe(0);
});

test('permite guardar una lectura menor a la anterior si se confirma explicitamente', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('locaciones.lecturas.store', $this->locacion), [
        'periodo' => '2026-08-01',
        'lectura_anterior' => '1250',
        'lectura_actual' => '1100',
        'confirmar_consumo_negativo' => '1',
    ]);

    $respuesta->assertRedirect(route('locaciones.lecturas.index', $this->locacion));
    $lectura = LecturaMedidor::firstWhere('periodo', '2026-08-01');
    expect($lectura->consumo_calculado)->toBe('-150.00');
});

test('el historial de lecturas se muestra en orden cronologico con lectura anterior actual y consumo', function () {
    LecturaMedidor::factory()->create(['locacion_id' => $this->locacion->id, 'periodo' => '2026-06-01', 'lectura_anterior' => null, 'lectura_actual' => 1000]);
    LecturaMedidor::factory()->create(['locacion_id' => $this->locacion->id, 'periodo' => '2026-07-01', 'lectura_anterior' => 1000, 'lectura_actual' => 1100]);
    LecturaMedidor::factory()->create(['locacion_id' => $this->locacion->id, 'periodo' => '2026-08-01', 'lectura_anterior' => 1100, 'lectura_actual' => 1250]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.lecturas.index', $this->locacion));

    $respuesta->assertOk();
    $respuesta->assertSeeInOrder(['agosto 2026', 'julio 2026', 'junio 2026']);
});

test('el historial muestra una advertencia cuando hay discrepancia entre periodos consecutivos', function () {
    LecturaMedidor::factory()->create(['locacion_id' => $this->locacion->id, 'periodo' => '2026-07-01', 'lectura_anterior' => null, 'lectura_actual' => 1250]);
    LecturaMedidor::factory()->create(['locacion_id' => $this->locacion->id, 'periodo' => '2026-08-01', 'lectura_anterior' => 1245, 'lectura_actual' => 1400]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.lecturas.index', $this->locacion));

    $respuesta->assertOk();
    $respuesta->assertSee('Advertencia');
});

test('un usuario no autenticado no puede acceder a las rutas de lecturas', function () {
    $respuesta = $this->get(route('locaciones.lecturas.index', $this->locacion));

    $respuesta->assertRedirect(route('login'));
});
