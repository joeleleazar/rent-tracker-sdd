<?php

use App\Models\ConceptoGastoFijo;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Services\ServicioImportacionRecibos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->servicio = app(ServicioImportacionRecibos::class);
    $this->luz = ConceptoGastoFijo::firstWhere('clave', 'luz');
    $this->agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
});

function contratoRecibos(Locacion $locacion): Contrato
{
    return Contrato::factory()->create([
        'locacion_id' => $locacion->id,
        'inquilino_id' => Inquilino::factory()->create()->id,
        'estado' => 'activo',
        'fecha_inicio' => Carbon::parse('2026-07-01'),
        'fecha_fin' => Carbon::parse('2027-01-01'),
        'monto_renta' => 1500,
    ]);
}

/** CSV con los encabezados de la plantilla de recibos (columnas de concepto Agua/Luz de Pasadizo/Seguridad). */
function csvRecibos(array $filas, string $periodo = '2026-08'): UploadedFile
{
    $lineas = ['periodo,local_id,Locación,Contrato,Renta,Luz,Agua,Luz de Pasadizo,Seguridad,Total'];
    foreach ($filas as $f) {
        $lineas[] = implode(',', [
            $f['periodo'] ?? $periodo,
            $f['local_id'] ?? '',
            'Local',
            'C1',
            $f['renta'] ?? '0',
            $f['luz'] ?? '0',
            $f['agua'] ?? '0',
            $f['pasadizo'] ?? '0',
            $f['seguridad'] ?? '0',
            $f['total'] ?? '',
        ]);
    }

    return UploadedFile::fake()->createWithContent('recibos.csv', implode("\n", $lineas));
}

test('previsualizar calcula la accion crear cuando no hay recibo y actualizar cuando hay uno', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    $l2 = Locacion::factory()->create(['es_alquilable' => true]);
    $c1 = contratoRecibos($l1);
    $c2 = contratoRecibos($l2);
    Recibo::factory()->create(['locacion_id' => $l2->id, 'contrato_id' => $c2->id, 'periodo' => '2026-08-01']);

    $r = $this->servicio->previsualizar(csvRecibos([
        ['local_id' => $l1->id, 'renta' => '1500', 'luz' => '50'],
        ['local_id' => $l2->id, 'renta' => '1500', 'luz' => '60'],
    ]), Carbon::parse('2026-08-01'));

    expect($r['ok'])->toBeTrue();
    expect($r['filas'][0]->accion)->toBe('crear');
    expect($r['filas'][1]->accion)->toBe('actualizar');
});

test('previsualizar invalida una locacion sin contrato activo y una con varios recibos', function () {
    $sinContrato = Locacion::factory()->create(['es_alquilable' => true]);
    $conVarios = Locacion::factory()->create(['es_alquilable' => true]);
    $c = contratoRecibos($conVarios);
    Recibo::factory()->count(2)->create(['locacion_id' => $conVarios->id, 'contrato_id' => $c->id, 'periodo' => '2026-08-01']);

    $r = $this->servicio->previsualizar(csvRecibos([
        ['local_id' => $sinContrato->id, 'renta' => '100'],
        ['local_id' => $conVarios->id, 'renta' => '100'],
    ]), Carbon::parse('2026-08-01'));

    expect($r['filas'][0]->valida)->toBeFalse()
        ->and($r['filas'][0]->errorNoRecuperable)->toBeTrue()
        ->and($r['filas'][1]->valida)->toBeFalse();
});

test('previsualizar invalida montos negativos o no numericos', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    contratoRecibos($l1);

    $r = $this->servicio->previsualizar(csvRecibos([
        ['local_id' => $l1->id, 'renta' => '-5'],
    ]), Carbon::parse('2026-08-01'));

    expect($r['filas'][0]->valida)->toBeFalse();
});

test('previsualizar avisa de una columna de concepto que ya no existe en el catalogo', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    contratoRecibos($l1);

    $csv = UploadedFile::fake()->createWithContent(
        'recibos.csv',
        "periodo,local_id,Locación,Contrato,Renta,Luz,Gas,Total\n2026-08,{$l1->id},Local,C1,1500,50,20,1570",
    );

    $r = $this->servicio->previsualizar($csv, Carbon::parse('2026-08-01'));

    expect($r['ok'])->toBeTrue()
        ->and(collect($r['avisos'])->implode(' '))->toContain('gas');
});

test('previsualizar rechaza un archivo de la plantilla de lecturas', function () {
    $csv = UploadedFile::fake()->createWithContent(
        'lecturas.csv',
        "periodo,local_id,Locación,Lectura Periodo Anterior,Lectura Actual\n2026-08,1,Local,100,150",
    );

    $r = $this->servicio->previsualizar($csv, Carbon::parse('2026-08-01'));

    expect($r['ok'])->toBeFalse()
        ->and($r['motivoRechazo'])->toContain('lecturas');
});

test('confirmar crea el recibo con renta y conceptos, y su total() cuadra', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    contratoRecibos($l1);

    $this->servicio->confirmar([
        ['local_id' => $l1->id, 'renta' => '1500', 'luz' => '80', 'conceptos' => [$this->agua->id => '40'], 'total' => ''],
    ], Carbon::parse('2026-08-01'));

    $recibo = Recibo::where('locacion_id', $l1->id)->where('periodo', '2026-08-01')->with('conceptos')->first();
    expect($recibo)->not->toBeNull()
        ->and((float) $recibo->monto_renta)->toBe(1500.0)
        ->and($recibo->total())->toBe(1620.0);
});

test('confirmar respeta un total tecleado a mano ajustando el componente de luz', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    contratoRecibos($l1);

    // renta 1500 + luz 80 + agua 40 = 1620; el usuario teclea 1700 → luz pasa a 160
    $this->servicio->confirmar([
        ['local_id' => $l1->id, 'renta' => '1500', 'luz' => '80', 'conceptos' => [$this->agua->id => '40'], 'total' => '1700'],
    ], Carbon::parse('2026-08-01'));

    $recibo = Recibo::where('locacion_id', $l1->id)->where('periodo', '2026-08-01')->with('conceptos')->first();
    expect($recibo->total())->toBe(1700.0)
        ->and((float) $recibo->conceptos->firstWhere('concepto_gasto_fijo_id', $this->luz->id)->monto)->toBe(160.0);
});

test('confirmar actualiza el recibo existente y es idempotente', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    $c1 = contratoRecibos($l1);
    Recibo::factory()->create(['locacion_id' => $l1->id, 'contrato_id' => $c1->id, 'periodo' => '2026-08-01', 'monto_renta' => 999]);

    $lote = [['local_id' => $l1->id, 'renta' => '1500', 'luz' => '80', 'conceptos' => [$this->agua->id => '40'], 'total' => '']];

    $primero = $this->servicio->confirmar($lote, Carbon::parse('2026-08-01'));
    $totalTrasPrimero = Recibo::where('locacion_id', $l1->id)->first()->total();

    $segundo = $this->servicio->confirmar($lote, Carbon::parse('2026-08-01'));

    expect($primero->actualizadas)->toBe(1)
        ->and($segundo->actualizadas)->toBe(1)
        ->and($segundo->creadas)->toBe(0)
        ->and(Recibo::where('locacion_id', $l1->id)->count())->toBe(1)
        ->and(Recibo::where('locacion_id', $l1->id)->first()->total())->toBe($totalTrasPrimero);
});

test('confirmar omite filas invalidas sin abortar el lote', function () {
    $valida = Locacion::factory()->create(['es_alquilable' => true]);
    contratoRecibos($valida);
    $sinContrato = Locacion::factory()->create(['es_alquilable' => true]);

    $r = $this->servicio->confirmar([
        ['local_id' => $valida->id, 'renta' => '1500', 'luz' => '0', 'conceptos' => [], 'total' => ''],
        ['local_id' => $sinContrato->id, 'renta' => '100', 'luz' => '0', 'conceptos' => [], 'total' => ''],
    ], Carbon::parse('2026-08-01'));

    expect($r->creadas)->toBe(1)->and($r->omitidas)->toBe(1);
});
