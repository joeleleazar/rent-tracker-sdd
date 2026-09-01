<?php

use App\Models\BorradorLecturaMedidor;
use App\Models\ConfiguracionGeneral;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->admin = User::factory()->create();
    ConfiguracionGeneral::actual()->update(['tarifa_luz_por_unidad' => 2]);
});

function subirCsvLecturas(array $filas, string $periodo = '2026-08'): UploadedFile
{
    $lineas = ['periodo,local_id,Locación,Lectura Periodo Anterior,Lectura Actual'];
    foreach ($filas as $f) {
        $lineas[] = implode(',', [$periodo, $f['local_id'] ?? '', 'Local', $f['anterior'] ?? '', $f['actual'] ?? '']);
    }

    return UploadedFile::fake()->createWithContent('plantilla.csv', implode("\n", $lineas));
}

test('descarga la plantilla con los encabezados esperados y una fila por locacion alquilable', function () {
    Excel::fake();
    Locacion::factory()->count(2)->create(['es_alquilable' => true]);
    Locacion::factory()->create(['es_alquilable' => false]);

    $this->actingAs($this->admin)
        ->get(route('lecturas.registroMasivo.plantilla', ['periodo' => '2026-08']))
        ->assertOk();

    Excel::assertDownloaded('lecturas-plantilla-2026-08.xlsx');
});

test('previsualizar devuelve la tabla editable con badges y sin persistir nada', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    $l2 = Locacion::factory()->create(['es_alquilable' => true]);

    $respuesta = $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.importar.previsualizar'), [
        'periodo' => '2026-08-01',
        'archivo' => subirCsvLecturas([
            ['local_id' => $l1->id, 'actual' => '1200'],
            ['local_id' => $l2->id, 'actual' => ''],
        ]),
    ]);

    $respuesta->assertOk();
    $respuesta->assertSee('tabla-vista-previa-lecturas', false);
    $respuesta->assertSee('Confirmar importación');
    expect(LecturaMedidor::count())->toBe(0);
    expect(BorradorLecturaMedidor::count())->toBe(0);
});

test('previsualizar rechaza con 422 un archivo con columnas de la plantilla de recibos', function () {
    $csv = UploadedFile::fake()->createWithContent(
        'recibos.csv',
        "periodo,local_id,Locación,Contrato,Renta (S/),Luz (S/),Total (S/)\n2026-08,1,Local,C1,100,50,150",
    );

    $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.importar.previsualizar'), [
        'periodo' => '2026-08-01',
        'archivo' => $csv,
    ])->assertStatus(422)->assertSee('recibos', false);
});

test('previsualizar rechaza con 422 un archivo generado para otro periodo', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);

    $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.importar.previsualizar'), [
        'periodo' => '2026-09-01',
        'archivo' => subirCsvLecturas([['local_id' => $l1->id, 'actual' => '10']], '2026-08'),
    ])->assertStatus(422);
});

test('confirmar crea y actualiza las filas validas, omite las invalidas y redirige con el resumen', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    $l2 = Locacion::factory()->create(['es_alquilable' => true]);
    LecturaMedidor::factory()->create(['locacion_id' => $l2->id, 'periodo' => '2026-08-01', 'lectura_actual' => 500, 'total' => 0]);

    $respuesta = $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.importar.confirmar'), [
        'periodo' => '2026-08-01',
        'filas' => [
            ['local_id' => $l1->id, 'lectura_actual' => '1200'],
            ['local_id' => $l2->id, 'lectura_actual' => '1300'],
            ['local_id' => 999999, 'lectura_actual' => '1'],
        ],
    ]);

    $respuesta->assertRedirect(route('lecturas.registroMasivo.index', ['periodo' => '2026-08']));
    $respuesta->assertSessionHas('mensaje', 'Importación: 1 creadas, 1 actualizadas, 1 omitidas.');
    expect((float) LecturaMedidor::firstWhere('locacion_id', $l1->id)->lectura_actual)->toBe(1200.0);
    expect((float) LecturaMedidor::firstWhere('locacion_id', $l2->id)->lectura_actual)->toBe(1300.0);
});

test('confirmar sin ninguna fila valida vuelve con error y no persiste nada', function () {
    $noAlquilable = Locacion::factory()->create(['es_alquilable' => false]);

    $respuesta = $this->actingAs($this->admin)->from(route('lecturas.registroMasivo.index'))->post(route('lecturas.registroMasivo.importar.confirmar'), [
        'periodo' => '2026-08-01',
        'filas' => [
            ['local_id' => $noAlquilable->id, 'lectura_actual' => '10'],
        ],
    ]);

    $respuesta->assertRedirect(route('lecturas.registroMasivo.index'));
    $respuesta->assertSessionHasErrors('archivo');
    expect(LecturaMedidor::count())->toBe(0);
});

test('confirmar dos veces el mismo lote es idempotente', function () {
    $l1 = Locacion::factory()->create(['es_alquilable' => true]);
    $lote = ['periodo' => '2026-08-01', 'filas' => [['local_id' => $l1->id, 'lectura_actual' => '1200']]];

    $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.importar.confirmar'), $lote);
    $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.importar.confirmar'), $lote)
        ->assertSessionHas('mensaje', 'Importación: 0 creadas, 1 actualizadas, 0 omitidas.');

    expect(LecturaMedidor::where('locacion_id', $l1->id)->count())->toBe(1);
});

test('la grilla manual y su autoguardado siguen respondiendo igual', function () {
    $local = Locacion::factory()->create(['es_alquilable' => true]);

    $this->actingAs($this->admin)->get(route('lecturas.registroMasivo.index', ['periodo' => '2026-08']))
        ->assertOk()
        ->assertSee('name="lecturas[' . $local->id . '][lectura_actual]"', false);

    $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.borrador'), [
        'periodo' => '2026-08-01',
        'lecturas' => [$local->id => ['lectura_actual' => '123']],
    ])->assertOk();

    expect(BorradorLecturaMedidor::where('locacion_id', $local->id)->exists())->toBeTrue();
});
