<?php

use App\Models\ConceptoGastoFijo;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
});

function contratoFeatureRecibos(Locacion $locacion): Contrato
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

function subirCsvRecibos(array $filas, string $periodo = '2026-08'): UploadedFile
{
    $lineas = ['periodo,local_id,Locación,Contrato,Renta,Luz,Agua,Luz de Pasadizo,Seguridad,Total'];
    foreach ($filas as $f) {
        $lineas[] = implode(',', [
            $periodo, $f['local_id'] ?? '', 'Local', 'C1',
            $f['renta'] ?? '0', $f['luz'] ?? '0', $f['agua'] ?? '0', '0', '0', $f['total'] ?? '',
        ]);
    }

    return UploadedFile::fake()->createWithContent('recibos.csv', implode("\n", $lineas));
}

test('descarga la plantilla de recibos con encabezados dinamicos', function () {
    Excel::fake();
    $local = Locacion::factory()->create(['es_alquilable' => true]);
    contratoFeatureRecibos($local);

    $this->actingAs($this->admin)
        ->get(route('recibos.registroMasivo.plantilla', ['periodo' => '2026-08']))
        ->assertOk();

    Excel::assertDownloaded('recibos-plantilla-2026-08.xlsx');
});

test('previsualizar devuelve la tabla editable sin persistir nada', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    contratoFeatureRecibos($l1);

    $respuesta = $this->actingAs($this->admin)->post(route('recibos.registroMasivo.importar.previsualizar'), [
        'periodo' => '2026-08-01',
        'archivo' => subirCsvRecibos([['local_id' => $l1->id, 'renta' => '1500', 'luz' => '50']]),
    ]);

    $respuesta->assertOk();
    $respuesta->assertSee('tabla-vista-previa-recibos', false);
    expect(Recibo::count())->toBe(0);
});

test('previsualizar rechaza con 422 un archivo de la plantilla de lecturas', function () {
    $csv = UploadedFile::fake()->createWithContent(
        'lecturas.csv',
        "periodo,local_id,Locación,Lectura Periodo Anterior,Lectura Actual\n2026-08,1,Local,100,150",
    );

    $this->actingAs($this->admin)->post(route('recibos.registroMasivo.importar.previsualizar'), [
        'periodo' => '2026-08-01',
        'archivo' => $csv,
    ])->assertStatus(422);
});

test('previsualizar rechaza con 422 un archivo de otro periodo', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    contratoFeatureRecibos($l1);

    $this->actingAs($this->admin)->post(route('recibos.registroMasivo.importar.previsualizar'), [
        'periodo' => '2026-09-01',
        'archivo' => subirCsvRecibos([['local_id' => $l1->id, 'renta' => '1500']], '2026-08'),
    ])->assertStatus(422);
});

test('confirmar crea y actualiza recibos y redirige con el resumen', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    $l2 = Locacion::factory()->create(['es_alquilable' => true]);
    contratoFeatureRecibos($l1);
    $c2 = contratoFeatureRecibos($l2);
    Recibo::factory()->create(['locacion_id' => $l2->id, 'contrato_id' => $c2->id, 'periodo' => '2026-08-01', 'monto_renta' => 1]);

    $respuesta = $this->actingAs($this->admin)->post(route('recibos.registroMasivo.importar.confirmar'), [
        'periodo' => '2026-08-01',
        'filas' => [
            ['local_id' => $l1->id, 'renta' => '1500', 'luz' => '80', 'conceptos' => [$this->agua->id => '40'], 'total' => ''],
            ['local_id' => $l2->id, 'renta' => '1600', 'luz' => '0', 'conceptos' => [], 'total' => ''],
            ['local_id' => 999999, 'renta' => '1', 'luz' => '0', 'conceptos' => [], 'total' => ''],
        ],
    ]);

    $respuesta->assertRedirect(route('recibos.registroMasivo.index', ['periodo' => '2026-08']));
    $respuesta->assertSessionHas('mensaje', 'Importación: 1 creados, 1 actualizados, 1 omitidos.');
    expect(Recibo::where('locacion_id', $l1->id)->where('periodo', '2026-08-01')->first()->total())->toBe(1620.0);
    expect((float) Recibo::where('locacion_id', $l2->id)->where('periodo', '2026-08-01')->first()->monto_renta)->toBe(1600.0);
});

test('confirmar sin filas validas vuelve con error y no persiste', function () {
    $sinContrato = Locacion::factory()->create(['es_alquilable' => true]);

    $this->actingAs($this->admin)->from(route('recibos.registroMasivo.index'))
        ->post(route('recibos.registroMasivo.importar.confirmar'), [
            'periodo' => '2026-08-01',
            'filas' => [['local_id' => $sinContrato->id, 'renta' => '100', 'conceptos' => []]],
        ])
        ->assertRedirect(route('recibos.registroMasivo.index'))
        ->assertSessionHasErrors('archivo');

    expect(Recibo::count())->toBe(0);
});

test('la tabla y acciones existentes de la pantalla de registro masivo de recibos siguen respondiendo', function () {
    $local = Locacion::factory()->create(['es_alquilable' => true]);
    contratoFeatureRecibos($local);

    $this->actingAs($this->admin)->get(route('recibos.registroMasivo.index', ['periodo' => '2026-08']))
        ->assertOk()
        ->assertSee('Registro Masivo de Recibos');
});
