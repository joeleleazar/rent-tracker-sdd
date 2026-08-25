<?php

use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->locacion = Locacion::factory()->create(['es_alquilable' => true]);
});

test('rechaza crear un contrato sin ningun inquilino', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1500.00',
        'estado' => 'activo',
        'inquilinos' => [],
    ]);

    $respuesta->assertSessionHasErrors('inquilinos');
    expect(Contrato::where('locacion_id', $this->locacion->id)->count())->toBe(0);
});

test('permite crear un contrato con un inquilino asociado', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1500.00',
        'estado' => 'activo',
        'inquilinos' => [
            ['apellidos' => 'Pérez Gómez', 'nombres' => 'Juan Carlos', 'dni' => '12345678', 'fecha_nacimiento' => '1960-05-15'],
        ],
    ]);

    $contrato = Contrato::firstWhere('locacion_id', $this->locacion->id);

    $respuesta->assertRedirect(route('contratos.show', $contrato));
    expect($contrato->inquilinos)->toHaveCount(1);
    expect($contrato->inquilinos->first()->pivot->es_principal)->toBeTrue();
});

test('rechaza crear un contrato con multiples inquilinos sin exactamente un principal', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1500.00',
        'estado' => 'activo',
        'inquilinos' => [
            ['apellidos' => 'Pérez', 'nombres' => 'Juan', 'dni' => '11111111', 'fecha_nacimiento' => '1960-05-15'],
            ['apellidos' => 'Gómez', 'nombres' => 'Ana', 'dni' => '22222222', 'fecha_nacimiento' => '1965-05-15'],
        ],
        'principal_index' => 5,
    ]);

    $respuesta->assertSessionHasErrors('inquilinos');
    expect(Contrato::where('locacion_id', $this->locacion->id)->count())->toBe(0);
});

test('rechaza un inquilino con dni de formato invalido o menor de edad', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1500.00',
        'estado' => 'activo',
        'inquilinos' => [
            ['apellidos' => 'Pérez', 'nombres' => 'Juan', 'dni' => 'abc', 'fecha_nacimiento' => now()->subYears(10)->format('Y-m-d')],
        ],
    ]);

    $respuesta->assertSessionHasErrors([
        'inquilinos.0.dni',
        'inquilinos.0.fecha_nacimiento',
    ]);
});

test('un administrador puede buscar inquilinos existentes por dni', function () {
    Inquilino::factory()->create(['dni' => '12345678', 'apellidos' => 'Pérez Gómez']);

    $respuesta = $this->actingAs($this->admin)->getJson(route('inquilinos.buscar', ['q' => '12345678']));

    $respuesta->assertOk();
    $respuesta->assertJsonFragment(['dni' => '12345678']);
});

test('registrar un inquilino duplicado en el directorio global es rechazado', function () {
    Inquilino::factory()->create(['dni' => '12345678']);

    $respuesta = $this->actingAs($this->admin)->post(route('inquilinos.store'), [
        'apellidos' => 'Otro',
        'nombres' => 'Distinto',
        'dni' => '12345678',
        'fecha_nacimiento' => '1970-01-01',
    ]);

    $respuesta->assertSessionHasErrors('dni');
});

test('un administrador puede agregar y quitar inquilinos de un contrato existente', function () {
    $contrato = Contrato::factory()->create(['locacion_id' => $this->locacion->id]);

    $respuestaAgregar = $this->actingAs($this->admin)->post(route('contratos.inquilinos.store', $contrato), [
        'apellidos' => 'Gómez',
        'nombres' => 'Ana',
        'dni' => '99998888',
        'fecha_nacimiento' => '1965-05-15',
    ]);

    $respuestaAgregar->assertRedirect(route('contratos.show', $contrato));
    $contrato->refresh();
    expect($contrato->inquilinos)->toHaveCount(2);

    $segundo = Inquilino::firstWhere('dni', '99998888');

    $respuestaQuitar = $this->actingAs($this->admin)->delete(route('contratos.inquilinos.destroy', [$contrato, $segundo]));

    $respuestaQuitar->assertRedirect(route('contratos.show', $contrato));
    expect($contrato->inquilinos()->count())->toBe(1);
});

test('bloquea quitar al unico inquilino de un contrato', function () {
    $contrato = Contrato::factory()->create(['locacion_id' => $this->locacion->id]);
    $unico = $contrato->inquilinos()->first();

    $respuesta = $this->actingAs($this->admin)->delete(route('contratos.inquilinos.destroy', [$contrato, $unico]));

    $respuesta->assertSessionHasErrors('inquilinos');
    expect($contrato->inquilinos()->count())->toBe(1);
});

test('bloquea quitar al inquilino principal sin designar un reemplazo', function () {
    $contrato = Contrato::factory()->create(['locacion_id' => $this->locacion->id]);
    $principal = $contrato->inquilinos()->first();
    $secundario = Inquilino::factory()->create();
    $contrato->inquilinos()->attach($secundario->id, ['es_principal' => false]);

    $respuesta = $this->actingAs($this->admin)->delete(route('contratos.inquilinos.destroy', [$contrato, $principal]));

    $respuesta->assertSessionHasErrors('inquilinos');
    expect($contrato->inquilinos()->count())->toBe(2);
});

test('permite quitar al inquilino principal designando un reemplazo', function () {
    $contrato = Contrato::factory()->create(['locacion_id' => $this->locacion->id]);
    $principal = $contrato->inquilinos()->first();
    $secundario = Inquilino::factory()->create();
    $contrato->inquilinos()->attach($secundario->id, ['es_principal' => false]);

    $respuesta = $this->actingAs($this->admin)->delete(route('contratos.inquilinos.destroy', [$contrato, $principal]), [
        'nuevo_principal_id' => $secundario->id,
    ]);

    $respuesta->assertRedirect(route('contratos.show', $contrato));
    $contrato->refresh();
    expect($contrato->inquilinos)->toHaveCount(1);
    expect($contrato->inquilinos->first()->id)->toBe($secundario->id);
    expect($contrato->inquilinos->first()->pivot->es_principal)->toBeTrue();
});
