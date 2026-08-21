<?php

use App\Exceptions\LocacionCicloException;
use App\Exceptions\LocacionConHijasException;
use App\Models\Locacion;
use App\Services\ServicioValidacionJerarquiaLocacion;

beforeEach(function () {
    $this->servicio = new ServicioValidacionJerarquiaLocacion();
});

test('rechaza asignar una locacion como su propio padre', function () {
    $locacion = Locacion::factory()->create();

    expect(fn () => $this->servicio->validarYEjecutar($locacion, $locacion->id, fn () => null))
        ->toThrow(LocacionCicloException::class);
});

test('rechaza un ciclo indirecto de varios niveles', function () {
    $galeria = Locacion::factory()->create(['nombre' => 'Galería El Sol']);
    $piso = Locacion::factory()->create(['nombre' => 'Piso 1', 'locacion_padre_id' => $galeria->id]);
    $local = Locacion::factory()->create(['nombre' => 'Local A', 'locacion_padre_id' => $piso->id]);

    expect(fn () => $this->servicio->validarYEjecutar($galeria, $local->id, fn () => null))
        ->toThrow(LocacionCicloException::class);
});

test('permite asignar un padre valido sin ciclo', function () {
    $galeria = Locacion::factory()->create();
    $piso = Locacion::factory()->create();

    $ejecutado = $this->servicio->validarYEjecutar($piso, $galeria->id, fn () => 'ok');

    expect($ejecutado)->toBe('ok');
});

test('eliminar bloquea si la locacion tiene sub-locaciones asociadas', function () {
    $galeria = Locacion::factory()->create();
    Locacion::factory()->create(['locacion_padre_id' => $galeria->id]);

    expect(fn () => $this->servicio->eliminar($galeria))
        ->toThrow(LocacionConHijasException::class);

    expect(Locacion::find($galeria->id))->not->toBeNull();
});

test('eliminar procede si la locacion no tiene sub-locaciones asociadas', function () {
    $local = Locacion::factory()->create();

    $this->servicio->eliminar($local);

    expect(Locacion::find($local->id))->toBeNull();
});
