<?php

use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Models\Representante;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $this->inquilino = Inquilino::factory()->create();
});

test('rechaza crear un contrato sin ningun representante', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1500.00',
        'estado' => 'activo',
        'representantes' => [],
    ]);

    $respuesta->assertSessionHasErrors('representantes');
    expect(Contrato::where('locacion_id', $this->locacion->id)->count())->toBe(0);
});

test('permite crear un contrato con un representante asociado', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1500.00',
        'estado' => 'activo',
        'representantes' => [
            ['apellidos' => 'Pérez Gómez', 'nombres' => 'Juan Carlos', 'dni' => '12345678', 'fecha_nacimiento' => '1960-05-15'],
        ],
    ]);

    $contrato = Contrato::firstWhere('locacion_id', $this->locacion->id);

    $respuesta->assertRedirect(route('contratos.show', $contrato));
    expect($contrato->representantes)->toHaveCount(1);
    expect($contrato->representantes->first()->pivot->es_principal)->toBeTrue();
});

test('rechaza crear un contrato con multiples representantes sin exactamente un principal', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1500.00',
        'estado' => 'activo',
        'representantes' => [
            ['apellidos' => 'Pérez', 'nombres' => 'Juan', 'dni' => '11111111', 'fecha_nacimiento' => '1960-05-15'],
            ['apellidos' => 'Gómez', 'nombres' => 'Ana', 'dni' => '22222222', 'fecha_nacimiento' => '1965-05-15'],
        ],
        'principal_index' => 5,
    ]);

    $respuesta->assertSessionHasErrors('representantes');
    expect(Contrato::where('locacion_id', $this->locacion->id)->count())->toBe(0);
});

test('rechaza un representante con dni de formato invalido o menor de edad', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1500.00',
        'estado' => 'activo',
        'representantes' => [
            ['apellidos' => 'Pérez', 'nombres' => 'Juan', 'dni' => 'abc', 'fecha_nacimiento' => now()->subYears(10)->format('Y-m-d')],
        ],
    ]);

    $respuesta->assertSessionHasErrors([
        'representantes.0.dni',
        'representantes.0.fecha_nacimiento',
    ]);
});

test('un administrador puede buscar representantes existentes por dni', function () {
    Representante::factory()->create(['dni' => '12345678', 'apellidos' => 'Pérez Gómez']);

    $respuesta = $this->actingAs($this->admin)->getJson(route('representantes.buscar', ['q' => '12345678']));

    $respuesta->assertOk();
    $respuesta->assertJsonFragment(['dni' => '12345678']);
});

test('registrar un representante duplicado en el directorio global es rechazado', function () {
    Representante::factory()->create(['dni' => '12345678']);

    $respuesta = $this->actingAs($this->admin)->post(route('representantes.store'), [
        'apellidos' => 'Otro',
        'nombres' => 'Distinto',
        'dni' => '12345678',
        'fecha_nacimiento' => '1970-01-01',
    ]);

    $respuesta->assertSessionHasErrors('dni');
});

test('un administrador puede agregar y quitar representantes de un contrato existente', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
    ]);
    $primero = Representante::factory()->create();
    $contrato->representantes()->attach($primero->id, ['es_principal' => true]);

    $respuestaAgregar = $this->actingAs($this->admin)->post(route('contratos.representantes.store', $contrato), [
        'apellidos' => 'Gómez',
        'nombres' => 'Ana',
        'dni' => '99998888',
        'fecha_nacimiento' => '1965-05-15',
    ]);

    $respuestaAgregar->assertRedirect(route('contratos.show', $contrato));
    $contrato->refresh();
    expect($contrato->representantes)->toHaveCount(2);

    $segundo = Representante::firstWhere('dni', '99998888');

    $respuestaQuitar = $this->actingAs($this->admin)->delete(route('contratos.representantes.destroy', [$contrato, $segundo]));

    $respuestaQuitar->assertRedirect(route('contratos.show', $contrato));
    expect($contrato->representantes()->count())->toBe(1);
});

test('bloquea quitar al unico representante de un contrato', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
    ]);
    $unico = Representante::factory()->create();
    $contrato->representantes()->attach($unico->id, ['es_principal' => true]);

    $respuesta = $this->actingAs($this->admin)->delete(route('contratos.representantes.destroy', [$contrato, $unico]));

    $respuesta->assertSessionHasErrors('representantes');
    expect($contrato->representantes()->count())->toBe(1);
});
