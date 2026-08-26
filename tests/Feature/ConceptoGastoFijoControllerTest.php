<?php

use App\Models\ConceptoGastoFijo;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Models\User;
use App\Models\ValorConceptoContrato;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

test('el listado muestra los conceptos existentes indicando cuales estan protegidos', function () {
    $respuesta = $this->actingAs($this->admin)->get(route('conceptosGastoFijo.index'));

    $respuesta->assertOk();
    $respuesta->assertSeeInOrder(['Renta', 'Agua', 'Luz', 'Luz de Pasadizo', 'Seguridad']);
});

test('un administrador puede crear un concepto nuevo sin clave protegida', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('conceptosGastoFijo.store'), [
        'nombre' => 'Internet',
        'orden' => 6,
    ]);

    $respuesta->assertRedirect(route('conceptosGastoFijo.index'));
    $concepto = ConceptoGastoFijo::firstWhere('nombre', 'Internet');
    expect($concepto)->not->toBeNull();
    expect($concepto->clave)->toBeNull();
    expect($concepto->esProtegido())->toBeFalse();
});

test('un administrador puede renombrar reordenar y desactivar un concepto regular', function () {
    $concepto = ConceptoGastoFijo::firstWhere('nombre', 'Agua');

    $respuesta = $this->actingAs($this->admin)->put(route('conceptosGastoFijo.update', $concepto), [
        'nombre' => 'Agua Potable',
        'orden' => 10,
        'activo' => '0',
    ]);

    $respuesta->assertRedirect(route('conceptosGastoFijo.index'));
    $concepto->refresh();
    expect($concepto->nombre)->toBe('Agua Potable');
    expect($concepto->orden)->toBe(10);
    expect($concepto->activo)->toBeFalse();
});

test('no se puede desactivar el concepto renta', function () {
    $renta = ConceptoGastoFijo::firstWhere('clave', 'renta');

    $respuesta = $this->actingAs($this->admin)->put(route('conceptosGastoFijo.update', $renta), [
        'nombre' => 'Renta',
        'orden' => 1,
        'activo' => '0',
    ]);

    $respuesta->assertSessionHasErrors();
    expect($renta->fresh()->activo)->toBeTrue();
});

test('no se puede eliminar el concepto renta', function () {
    $renta = ConceptoGastoFijo::firstWhere('clave', 'renta');

    $respuesta = $this->actingAs($this->admin)->delete(route('conceptosGastoFijo.destroy', $renta));

    $respuesta->assertSessionHasErrors();
    expect(ConceptoGastoFijo::find($renta->id))->not->toBeNull();
});

test('no se puede eliminar un concepto ya usado en un contrato o recibo', function () {
    $agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $inquilino = Inquilino::factory()->create();
    $contrato = Contrato::factory()->create([
        'locacion_id' => $locacion->id,
        'inquilino_id' => $inquilino->id,
        'estado' => 'activo',
    ]);
    ValorConceptoContrato::create(['contrato_id' => $contrato->id, 'concepto_gasto_fijo_id' => $agua->id, 'valor' => 50]);

    $respuesta = $this->actingAs($this->admin)->delete(route('conceptosGastoFijo.destroy', $agua));

    $respuesta->assertSessionHasErrors();
    expect(ConceptoGastoFijo::find($agua->id))->not->toBeNull();
});

test('specs/026: un concepto cuyo unico uso esta en recibos anulados ya no cuenta como en uso y puede eliminarse', function () {
    $agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $inquilino = Inquilino::factory()->create();
    $contrato = Contrato::factory()->create([
        'locacion_id' => $locacion->id,
        'inquilino_id' => $inquilino->id,
        'estado' => 'activo',
    ]);
    $recibo = Recibo::factory()->create(['contrato_id' => $contrato->id, 'locacion_id' => $locacion->id, 'estado' => 'anulado']);
    $recibo->conceptos()->create(['concepto_gasto_fijo_id' => $agua->id, 'monto' => 50]);

    $indice = $this->actingAs($this->admin)->get(route('conceptosGastoFijo.index'));
    $indice->assertDontSee('disabled title="No se puede eliminar', false);

    $respuesta = $this->actingAs($this->admin)->delete(route('conceptosGastoFijo.destroy', $agua));

    $respuesta->assertRedirect(route('conceptosGastoFijo.index'));
    expect(ConceptoGastoFijo::find($agua->id))->toBeNull();
});

test('se puede eliminar un concepto sin ningun uso', function () {
    $concepto = ConceptoGastoFijo::factory()->create(['nombre' => 'Internet']);

    $respuesta = $this->actingAs($this->admin)->delete(route('conceptosGastoFijo.destroy', $concepto));

    $respuesta->assertRedirect(route('conceptosGastoFijo.index'));
    expect(ConceptoGastoFijo::find($concepto->id))->toBeNull();
});

test('un usuario no autenticado no puede acceder al catalogo de conceptos', function () {
    $respuesta = $this->get(route('conceptosGastoFijo.index'));

    $respuesta->assertRedirect(route('login'));
});
