<?php

use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $this->inquilino = Inquilino::factory()->create();
});

test('un administrador autenticado puede crear un contrato sin solapamiento', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1500.00',
        'estado' => 'activo',
    ]);

    $contrato = Contrato::firstWhere('locacion_id', $this->locacion->id);

    $respuesta->assertRedirect(route('contratos.show', $contrato));
    expect($contrato)->not->toBeNull();
    expect($contrato->monto_renta)->toBe('1500.00');
});

test('rechaza la creacion de un contrato que se solapa con uno existente', function () {
    Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'estado' => 'activo',
    ]);

    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-06-01',
        'fecha_fin' => '2027-05-31',
        'monto_renta' => '1200.00',
        'estado' => 'activo',
    ]);

    $respuesta->assertStatus(302);
    $respuesta->assertSessionHasErrors('solapamiento');
    expect(Contrato::where('locacion_id', $this->locacion->id)->count())->toBe(1);
});

test('un administrador autenticado puede editar un contrato existente', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1000.00',
        'estado' => 'activo',
    ]);

    $respuesta = $this->actingAs($this->admin)->put(route('contratos.update', $contrato), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1750.50',
        'estado' => 'activo',
    ]);

    $respuesta->assertRedirect(route('contratos.show', $contrato));
    expect($contrato->fresh()->monto_renta)->toBe('1750.50');
});

test('un usuario no autenticado no puede acceder a las rutas de contrato', function () {
    $respuesta = $this->get(route('contratos.create', $this->locacion));

    $respuesta->assertRedirect(route('login'));
});
