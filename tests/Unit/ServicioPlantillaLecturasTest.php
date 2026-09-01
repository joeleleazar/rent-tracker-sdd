<?php

use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Services\ServicioPlantillaLecturas;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->servicio = app(ServicioPlantillaLecturas::class);
});

test('genera una fila por locacion alquilable en orden de arbol, sin columna de tarifa', function () {
    $galeria = Locacion::factory()->create(['nombre' => 'Galería', 'es_alquilable' => false]);
    $l101 = Locacion::factory()->create(['nombre' => 'Local 101', 'locacion_padre_id' => $galeria->id, 'es_alquilable' => true]);
    $l102 = Locacion::factory()->create(['nombre' => 'Local 102', 'locacion_padre_id' => $galeria->id, 'es_alquilable' => true]);

    $filas = $this->servicio->filas(Carbon::parse('2026-08-01'));

    expect($filas)->toHaveCount(2)
        ->and($filas[0]['local_id'])->toBe($l101->id)
        ->and($filas[1]['local_id'])->toBe($l102->id)
        ->and($filas[0]['periodo'])->toBe('2026-08')
        ->and(ServicioPlantillaLecturas::ENCABEZADOS)->not->toContain('tarifa')
        ->and(ServicioPlantillaLecturas::ENCABEZADOS)->not->toContain('Tarifa por kWh');
});

test('precarga la lectura actual si ya existe registro para el periodo y muestra la anterior real', function () {
    $local = Locacion::factory()->create(['es_alquilable' => true]);
    LecturaMedidor::factory()->create(['locacion_id' => $local->id, 'periodo' => '2026-07-01', 'lectura_actual' => 900]);
    LecturaMedidor::factory()->create(['locacion_id' => $local->id, 'periodo' => '2026-08-01', 'lectura_actual' => 1000]);

    $fila = $this->servicio->filas(Carbon::parse('2026-08-01'))[0];

    expect($fila['Lectura Periodo Anterior'])->toBe('900.00')
        ->and($fila['Lectura Actual'])->toBe('1000.00');
});

test('deja la lectura actual vacia cuando no hay registro del periodo', function () {
    $local = Locacion::factory()->create(['es_alquilable' => true]);
    LecturaMedidor::factory()->create(['locacion_id' => $local->id, 'periodo' => '2026-07-01', 'lectura_actual' => 900]);

    $fila = $this->servicio->filas(Carbon::parse('2026-08-01'))[0];

    expect($fila['Lectura Actual'])->toBeNull()
        ->and($fila['Lectura Periodo Anterior'])->toBe('900.00');
});
