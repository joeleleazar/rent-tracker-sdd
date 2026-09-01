<?php

use App\Models\ConfiguracionGeneral;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Services\ServicioImportacionLecturas;
use App\Support\Importacion\FilaImportada;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->servicio = app(ServicioImportacionLecturas::class);
    ConfiguracionGeneral::actual()->update(['tarifa_luz_por_unidad' => 2]);
});

/** Arma un archivo CSV con los encabezados de la plantilla de lecturas. */
function csvLecturas(array $filas, string $periodo = '2026-08'): UploadedFile
{
    $lineas = ['periodo,local_id,Locación,Lectura Periodo Anterior,Lectura Actual'];
    foreach ($filas as $f) {
        $lineas[] = implode(',', [
            $f['periodo'] ?? $periodo,
            $f['local_id'] ?? '',
            $f['nombre'] ?? 'Local',
            $f['anterior'] ?? '',
            $f['actual'] ?? '',
        ]);
    }

    return UploadedFile::fake()->createWithContent('plantilla.csv', implode("\n", $lineas));
}

test('previsualizar marca validas las filas correctas y calcula su accion crear/actualizar', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    $l2 = Locacion::factory()->create(['es_alquilable' => true]);
    LecturaMedidor::factory()->create(['locacion_id' => $l2->id, 'periodo' => '2026-08-01', 'lectura_actual' => 500]);

    $resultado = $this->servicio->previsualizar(csvLecturas([
        ['local_id' => $l1->id, 'actual' => '1200'],
        ['local_id' => $l2->id, 'actual' => '1300'],
    ]), Carbon::parse('2026-08-01'));

    expect($resultado['ok'])->toBeTrue();
    expect($resultado['filas'][0]->valida)->toBeTrue();
    expect($resultado['filas'][0]->accion)->toBe(FilaImportada::ACCION_CREAR);
    expect($resultado['filas'][1]->accion)->toBe(FilaImportada::ACCION_ACTUALIZAR);
});

test('previsualizar invalida lectura vacia, no numerica, negativa y menor que la anterior', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    $l2 = Locacion::factory()->create(['es_alquilable' => true]);
    $l3 = Locacion::factory()->create(['es_alquilable' => true]);
    $l4 = Locacion::factory()->create(['es_alquilable' => true]);
    LecturaMedidor::factory()->create(['locacion_id' => $l4->id, 'periodo' => '2026-07-01', 'lectura_actual' => 1000]);

    $resultado = $this->servicio->previsualizar(csvLecturas([
        ['local_id' => $l1->id, 'actual' => ''],
        ['local_id' => $l2->id, 'actual' => 'abc'],
        ['local_id' => $l3->id, 'actual' => '-5'],
        ['local_id' => $l4->id, 'actual' => '900'],
    ]), Carbon::parse('2026-08-01'));

    expect(collect($resultado['filas'])->pluck('valida')->all())->toBe([false, false, false, false]);
});

test('previsualizar invalida un local_id inexistente o no alquilable como error no recuperable', function () {
    $noAlquilable = Locacion::factory()->create(['es_alquilable' => false]);

    $resultado = $this->servicio->previsualizar(csvLecturas([
        ['local_id' => 999999, 'actual' => '10'],
        ['local_id' => $noAlquilable->id, 'actual' => '10'],
    ]), Carbon::parse('2026-08-01'));

    expect($resultado['filas'][0]->valida)->toBeFalse()
        ->and($resultado['filas'][0]->errorNoRecuperable)->toBeTrue()
        ->and($resultado['filas'][1]->errorNoRecuperable)->toBeTrue();
});

test('previsualizar rechaza un archivo de otro periodo', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);

    $resultado = $this->servicio->previsualizar(
        csvLecturas([['local_id' => $l1->id, 'actual' => '10', 'periodo' => '2026-07']]),
        Carbon::parse('2026-08-01'),
    );

    expect($resultado['ok'])->toBeFalse()
        ->and($resultado['motivoRechazo'])->toContain('otro periodo');
});

test('previsualizar rechaza un archivo al que le faltan columnas de la plantilla', function () {
    $csv = UploadedFile::fake()->createWithContent('malo.csv', "local_id,Lectura Actual\n1,100");

    $resultado = $this->servicio->previsualizar($csv, Carbon::parse('2026-08-01'));

    expect($resultado['ok'])->toBeFalse();
});

test('confirmar hace upsert de las filas validas en una transaccion y cuenta el resultado', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    $l2 = Locacion::factory()->create(['es_alquilable' => true]);
    LecturaMedidor::factory()->create(['locacion_id' => $l2->id, 'periodo' => '2026-08-01', 'lectura_actual' => 500, 'total' => 0]);

    $resultado = $this->servicio->confirmar([
        ['local_id' => $l1->id, 'lectura_actual' => '1200'],
        ['local_id' => $l2->id, 'lectura_actual' => '1300'],
        ['local_id' => 999999, 'lectura_actual' => '1'],
    ], Carbon::parse('2026-08-01'));

    expect($resultado->creadas)->toBe(1)
        ->and($resultado->actualizadas)->toBe(1)
        ->and($resultado->omitidas)->toBe(1);
    expect((float) LecturaMedidor::firstWhere('locacion_id', $l2->id)->lectura_actual)->toBe(1300.0);
});

test('confirmar es idempotente: repetir el mismo lote no cambia nada', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    $lote = [['local_id' => $l1->id, 'lectura_actual' => '1200']];

    $this->servicio->confirmar($lote, Carbon::parse('2026-08-01'));
    $primera = LecturaMedidor::firstWhere('locacion_id', $l1->id)->only(['lectura_actual', 'total']);

    $segundo = $this->servicio->confirmar($lote, Carbon::parse('2026-08-01'));

    expect(LecturaMedidor::where('locacion_id', $l1->id)->count())->toBe(1)
        ->and($segundo->creadas)->toBe(0)
        ->and($segundo->actualizadas)->toBe(1)
        ->and(LecturaMedidor::firstWhere('locacion_id', $l1->id)->only(['lectura_actual', 'total']))->toEqual($primera);
});

test('confirmar usa la tarifa global para el total salvo que la fila traiga un total explicito', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    $l2 = Locacion::factory()->create(['es_alquilable' => true]);
    LecturaMedidor::factory()->create(['locacion_id' => $l1->id, 'periodo' => '2026-07-01', 'lectura_actual' => 100]);
    LecturaMedidor::factory()->create(['locacion_id' => $l2->id, 'periodo' => '2026-07-01', 'lectura_actual' => 100]);

    $this->servicio->confirmar([
        ['local_id' => $l1->id, 'lectura_actual' => '150'],
        ['local_id' => $l2->id, 'lectura_actual' => '150', 'total' => '999'],
    ], Carbon::parse('2026-08-01'));

    // consumo 50 * tarifa 2 = 100
    $del1 = LecturaMedidor::where('locacion_id', $l1->id)->where('periodo', '2026-08-01')->first();
    $del2 = LecturaMedidor::where('locacion_id', $l2->id)->where('periodo', '2026-08-01')->first();
    expect((float) $del1->total)->toBe(100.0)
        ->and((float) $del2->total)->toBe(999.0);
});
