<?php

use App\Models\BorradorLecturaMedidor;
use App\Models\ConfiguracionGeneral;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

test('el registro masivo muestra las locaciones alquilables agrupadas jerarquicamente con estado por fila', function () {
    $galeria = Locacion::factory()->create(['nombre' => 'Galería El Sol', 'es_alquilable' => false]);
    $local101 = Locacion::factory()->create(['nombre' => 'Local 101', 'locacion_padre_id' => $galeria->id, 'es_alquilable' => true]);
    $local102 = Locacion::factory()->create(['nombre' => 'Local 102', 'locacion_padre_id' => $galeria->id, 'es_alquilable' => true]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $local101->id,
        'periodo' => '2026-08-01',
        'lectura_actual' => 1250,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('lecturas.registroMasivo.index', ['periodo' => '2026-08']));

    $respuesta->assertOk();
    $respuesta->assertSeeInOrder(['Galería El Sol', 'Local 101', 'Local 102']);
    $respuesta->assertSee('1250.00');
    $respuesta->assertSee(route('lecturas.registroMasivo.editarInline', LecturaMedidor::firstWhere('locacion_id', $local101->id)), false);
    $respuesta->assertSee('name="lecturas[' . $local102->id . '][lectura_actual]"', false);
    $respuesta->assertDontSee('name="lecturas[' . $local101->id . '][lectura_actual]"', false);
    $respuesta->assertDontSee('name="lecturas[' . $galeria->id . '][lectura_actual]"', false);
});

test('el guardado masivo registra varias lecturas en una sola accion e ignora las filas vacias', function () {
    $local1 = Locacion::factory()->create(['es_alquilable' => true]);
    $local2 = Locacion::factory()->create(['es_alquilable' => true]);
    $local3 = Locacion::factory()->create(['es_alquilable' => true]);

    $respuesta = $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.store'), [
        'periodo' => '2026-08-01',
        'lecturas' => [
            $local1->id => ['lectura_actual' => '1000'],
            $local2->id => ['lectura_actual' => '2000'],
            $local3->id => ['lectura_actual' => ''],
        ],
    ]);

    $respuesta->assertRedirect(route('lecturas.registroMasivo.index', ['periodo' => '2026-08']));
    expect(LecturaMedidor::where('locacion_id', $local1->id)->exists())->toBeTrue();
    expect(LecturaMedidor::where('locacion_id', $local2->id)->exists())->toBeTrue();
    expect(LecturaMedidor::where('locacion_id', $local3->id)->exists())->toBeFalse();
});

test('una fila con consumo negativo sin confirmar no se guarda pero no afecta otras filas validas', function () {
    $localOk = Locacion::factory()->create(['es_alquilable' => true]);
    $localNegativo = Locacion::factory()->create(['es_alquilable' => true]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $localNegativo->id,
        'periodo' => '2026-07-01',
        'lectura_actual' => 1250,
    ]);

    $respuesta = $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.store'), [
        'periodo' => '2026-08-01',
        'lecturas' => [
            $localOk->id => ['lectura_actual' => '500'],
            $localNegativo->id => ['lectura_actual' => '1100'],
        ],
    ]);

    $respuesta->assertSessionHasErrors();
    expect(LecturaMedidor::where('locacion_id', $localOk->id)->where('periodo', '2026-08-01')->exists())->toBeTrue();
    expect(LecturaMedidor::where('locacion_id', $localNegativo->id)->where('periodo', '2026-08-01')->exists())->toBeFalse();
});

test('un valor no numerico en una fila no descarta las demas filas validas del lote', function () {
    $localOk = Locacion::factory()->create(['es_alquilable' => true]);
    $localInvalido = Locacion::factory()->create(['es_alquilable' => true]);

    $respuesta = $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.store'), [
        'periodo' => '2026-08-01',
        'lecturas' => [
            $localOk->id => ['lectura_actual' => '900'],
            $localInvalido->id => ['lectura_actual' => 'abc'],
        ],
    ]);

    $respuesta->assertSessionHasErrors();
    expect(LecturaMedidor::where('locacion_id', $localOk->id)->exists())->toBeTrue();
    expect(LecturaMedidor::where('locacion_id', $localInvalido->id)->exists())->toBeFalse();
});

test('la referencia de la lectura anterior se muestra junto al campo o indica que no hay lectura previa', function () {
    $conAnterior = Locacion::factory()->create(['es_alquilable' => true]);
    $sinAnterior = Locacion::factory()->create(['es_alquilable' => true]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $conAnterior->id,
        'periodo' => '2026-07-01',
        'lectura_actual' => 1250,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('lecturas.registroMasivo.index', ['periodo' => '2026-08']));

    $respuesta->assertOk();
    $respuesta->assertSee('1250.00');
    $respuesta->assertSee('Sin lectura previa registrada');
});

test('el encabezado de la tabla incluye la columna consumo entre lectura actual y total', function () {
    Locacion::factory()->create(['es_alquilable' => true]);

    $respuesta = $this->actingAs($this->admin)->get(route('lecturas.registroMasivo.index', ['periodo' => '2026-08']));

    $respuesta->assertOk();
    $respuesta->assertSeeInOrder(['Lectura Actual', 'Consumo', 'Total']);
});

test('la celda de consumo de una fila completada arranca en el marcador inicial y expone el consumo calculado en su data-consumo', function () {
    $local = Locacion::factory()->create(['es_alquilable' => true]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $local->id,
        'periodo' => '2026-07-01',
        'lectura_actual' => 1000,
    ]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $local->id,
        'periodo' => '2026-08-01',
        'lectura_anterior' => 1000,
        'lectura_actual' => 1250,
        'consumo_calculado' => 250,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('lecturas.registroMasivo.index', ['periodo' => '2026-08']));

    $respuesta->assertOk();
    $respuesta->assertSee('data-consumo="250.00"', false);
    expect($respuesta->getContent())->toMatch('/<div[^>]*id="consumo-fila-' . $local->id . '"[^>]*>\s*—\s*<\/div>/u');
});

test('la celda de consumo de una fila pendiente con lectura anterior arranca en el marcador inicial y expone la lectura anterior en data-lectura-anterior', function () {
    $local = Locacion::factory()->create(['es_alquilable' => true]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $local->id,
        'periodo' => '2026-07-01',
        'lectura_actual' => 1000,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('lecturas.registroMasivo.index', ['periodo' => '2026-08']));

    $respuesta->assertOk();
    $respuesta->assertSee('data-lectura-anterior="1000.00"', false);
    expect($respuesta->getContent())->toMatch('/<div[^>]*id="consumo-fila-' . $local->id . '"[^>]*>\s*—\s*<\/div>/u');
});

test('las filas no alquilables y la fila de total general agregan la celda vacia adicional de la columna consumo', function () {
    $galeria = Locacion::factory()->create(['nombre' => 'Galería Consumo Test', 'es_alquilable' => false]);
    Locacion::factory()->create(['nombre' => 'Local Consumo Test', 'locacion_padre_id' => $galeria->id, 'es_alquilable' => true]);

    $respuesta = $this->actingAs($this->admin)->get(route('lecturas.registroMasivo.index', ['periodo' => '2026-08']));

    $respuesta->assertOk();

    $contarCeldas = function (DOMElement $nodo): int {
        $contador = 0;
        foreach ($nodo->childNodes as $hijo) {
            if ($hijo->nodeType === XML_ELEMENT_NODE) {
                $contador++;
            }
        }

        return $contador;
    };

    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $respuesta->getContent());
    $xpath = new DOMXPath($dom);

    $filaGaleria = $xpath->query("//div[contains(@class,'fila-registro-masivo')][.//span[contains(text(),'Galería Consumo Test')]]")->item(0);
    expect($filaGaleria)->not->toBeNull();
    expect($contarCeldas($filaGaleria))->toBe(5);

    $filaTotalGeneral = $xpath->query("//div[contains(@class,'tabla-registro-masivo__total-general')]")->item(0);
    expect($filaTotalGeneral)->not->toBeNull();
    expect($contarCeldas($filaTotalGeneral))->toBe(5);
});

test('el autoguardado persiste un borrador por usuario periodo y locacion sin aplicar validaciones de negocio', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);

    $respuesta = $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.borrador'), [
        'periodo' => '2026-08-01',
        'lecturas' => [
            $locacion->id => ['lectura_actual' => '999'],
        ],
    ]);

    $respuesta->assertOk();
    $borrador = BorradorLecturaMedidor::where('usuario_id', $this->admin->id)
        ->where('periodo', '2026-08-01')
        ->where('locacion_id', $locacion->id)
        ->first();

    expect($borrador)->not->toBeNull();
    expect($borrador->lectura_actual)->toBe('999.00');
});

test('el indice restaura automaticamente los valores del borrador existente', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);

    BorradorLecturaMedidor::create([
        'usuario_id' => $this->admin->id,
        'periodo' => '2026-08-01',
        'locacion_id' => $locacion->id,
        'lectura_actual' => '777',
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('lecturas.registroMasivo.index', ['periodo' => '2026-08']));

    $respuesta->assertOk();
    $respuesta->assertSee('value="777.00"', false);
});

test('el borrador se descarta al completar el guardado final exitoso', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);

    BorradorLecturaMedidor::create([
        'usuario_id' => $this->admin->id,
        'periodo' => '2026-08-01',
        'locacion_id' => $locacion->id,
        'lectura_actual' => '777',
    ]);

    $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.store'), [
        'periodo' => '2026-08-01',
        'lecturas' => [
            $locacion->id => ['lectura_actual' => '777'],
        ],
    ]);

    expect(BorradorLecturaMedidor::where('usuario_id', $this->admin->id)->where('periodo', '2026-08-01')->count())->toBe(0);
});

// --- specs/018: eliminación del patrón N+1 (FR-001/FR-002, contrato "registro-masivo-optimizado") ---

test('el guardado masivo no incrementa las consultas de base de datos de forma lineal con el tamaño del lote', function () {
    $construirLote = fn (int $cantidad) => Locacion::factory()->count($cantidad)->create(['es_alquilable' => true]);

    $enviarLote = function ($locaciones, string $periodo) {
        return $locaciones->mapWithKeys(
            fn ($locacion, $indice) => [$locacion->id => ['lectura_actual' => (string) (100 + $indice)]]
        )->all();
    };

    // Los lotes se crean ANTES de habilitar el query log: solo interesa medir las
    // consultas que dispara store(), no las de los factories de preparación.
    $loteChico = $construirLote(5);
    $loteGrande = $construirLote(50);

    DB::enableQueryLog();
    $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.store'), [
        'periodo' => '2026-08-01',
        'lecturas' => $enviarLote($loteChico, '2026-08-01'),
    ]);
    $consultasLoteChico = count(DB::getQueryLog());
    DB::flushQueryLog();

    $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.store'), [
        'periodo' => '2026-09-01',
        'lecturas' => $enviarLote($loteGrande, '2026-09-01'),
    ]);
    $consultasLoteGrande = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Antes del fix, cada fila adicional agrega ~4 consultas (lookup de locación,
    // lectura anterior, chequeo de duplicado, insert). Después del fix, el costo
    // marginal por fila adicional debe acercarse a 1 (solo el INSERT).
    $filasAdicionales = $loteGrande->count() - $loteChico->count();
    expect($consultasLoteGrande - $consultasLoteChico)->toBeLessThan($filasAdicionales * 2);
});

test('un lote mixto con fila valida duplicada consumo negativo y no numerica preserva el exito parcial', function () {
    $localValido = Locacion::factory()->create(['es_alquilable' => true]);
    $localDuplicado = Locacion::factory()->create(['es_alquilable' => true]);
    $localConsumoNegativo = Locacion::factory()->create(['es_alquilable' => true]);
    $localNoNumerico = Locacion::factory()->create(['es_alquilable' => true]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $localDuplicado->id,
        'periodo' => '2026-08-01',
        'lectura_actual' => 1000,
    ]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $localConsumoNegativo->id,
        'periodo' => '2026-07-01',
        'lectura_actual' => 1000,
    ]);

    $respuesta = $this->actingAs($this->admin)->post(route('lecturas.registroMasivo.store'), [
        'periodo' => '2026-08-01',
        'lecturas' => [
            $localValido->id => ['lectura_actual' => '500'],
            $localDuplicado->id => ['lectura_actual' => '1500'],
            $localConsumoNegativo->id => ['lectura_actual' => '100'],
            $localNoNumerico->id => ['lectura_actual' => 'abc'],
        ],
    ]);

    $respuesta->assertSessionHasErrors([
        "lecturas.{$localDuplicado->id}.lectura_actual" => 'Ya existe una lectura registrada para ese periodo en esta locación. Edite la lectura existente en vez de crear un duplicado.',
        "lecturas.{$localConsumoNegativo->id}.lectura_actual" => 'La lectura ingresada es menor a la del periodo anterior, lo que resultaría en un consumo negativo. Confirme explícitamente para continuar o corrija el valor.',
        "lecturas.{$localNoNumerico->id}.lectura_actual" => 'La lectura debe ser un número mayor o igual a 0.',
    ]);

    expect(LecturaMedidor::where('locacion_id', $localValido->id)->where('periodo', '2026-08-01')->exists())->toBeTrue();
    expect(LecturaMedidor::where('locacion_id', $localDuplicado->id)->where('periodo', '2026-08-01')->count())->toBe(1);
    expect(LecturaMedidor::where('locacion_id', $localConsumoNegativo->id)->where('periodo', '2026-08-01')->exists())->toBeFalse();
    expect(LecturaMedidor::where('locacion_id', $localNoNumerico->id)->exists())->toBeFalse();
});

test('un usuario no autenticado no puede acceder al registro masivo', function () {
    $respuesta = $this->get(route('lecturas.registroMasivo.index'));

    $respuesta->assertRedirect(route('login'));
});

// --- US4: Totalizado por consumo con tarifa editable (FR-013/FR-014/FR-015) ---

test('el indice incluye el valor vigente de la tarifa por kwh como valor por defecto', function () {
    ConfiguracionGeneral::actual()->update(['tarifa_luz_por_unidad' => '0.7500']);

    $respuesta = $this->actingAs($this->admin)->get(route('lecturas.registroMasivo.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('value="0.7500"', false);
});

test('actualizar la tarifa desde el registro masivo persiste el nuevo valor en configuracion general', function () {
    $respuesta = $this->actingAs($this->admin)->patch(route('lecturas.registroMasivo.actualizarTarifa'), [
        'tarifa_luz_por_unidad' => '0.85',
    ]);

    $respuesta->assertNoContent();
    expect(ConfiguracionGeneral::actual()->fresh()->tarifa_luz_por_unidad)->toBe('0.8500');
});

test('actualizar la tarifa con un valor invalido no modifica la configuracion general', function () {
    ConfiguracionGeneral::actual()->update(['tarifa_luz_por_unidad' => '0.5000']);
    $tarifaOriginal = ConfiguracionGeneral::actual()->fresh()->tarifa_luz_por_unidad;

    $respuesta = $this->actingAs($this->admin)->patch(route('lecturas.registroMasivo.actualizarTarifa'), [
        'tarifa_luz_por_unidad' => '-5',
    ]);

    $respuesta->assertSessionHasErrors('tarifa_luz_por_unidad');
    expect(ConfiguracionGeneral::actual()->fresh()->tarifa_luz_por_unidad)->toBe($tarifaOriginal);
});

// --- US5: Exportación a Excel y PDF (FR-016) ---

test('la exportacion a excel responde con un archivo xlsx con las locaciones del periodo', function () {
    $local = Locacion::factory()->create(['nombre' => 'Local Exportable', 'es_alquilable' => true]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $local->id,
        'periodo' => '2026-08-01',
        'lectura_actual' => 500,
        'consumo_calculado' => 100,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('lecturas.registroMasivo.exportarExcel', ['periodo' => '2026-08']));

    $respuesta->assertOk();
    $respuesta->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('la exportacion a pdf responde con un archivo pdf con el mismo contenido', function () {
    $local = Locacion::factory()->create(['nombre' => 'Local Exportable PDF', 'es_alquilable' => true]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $local->id,
        'periodo' => '2026-08-01',
        'lectura_actual' => 500,
        'consumo_calculado' => 100,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('lecturas.registroMasivo.exportarPdf', ['periodo' => '2026-08']));

    $respuesta->assertOk();
    $respuesta->assertHeader('content-type', 'application/pdf');
});

// --- US6: Edición en línea de una lectura ya registrada (FR-005/FR-017) ---

test('una fila completada muestra un icono accesible en vez del badge de texto completada', function () {
    $local = Locacion::factory()->create(['es_alquilable' => true]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $local->id,
        'periodo' => '2026-08-01',
        'lectura_actual' => 1250,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('lecturas.registroMasivo.index', ['periodo' => '2026-08']));

    $respuesta->assertOk();
    $respuesta->assertDontSee('badge text-bg-success', false);
    $respuesta->assertDontSee('>Completada<', false);
    $respuesta->assertSee('bi-check-circle-fill', false);
});

test('el icono de lectura completada aparece antes del valor de la lectura en el marcado', function () {
    $local = Locacion::factory()->create(['es_alquilable' => true]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $local->id,
        'periodo' => '2026-08-01',
        'lectura_actual' => 1250,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('lecturas.registroMasivo.index', ['periodo' => '2026-08']));

    $respuesta->assertOk();
    $respuesta->assertSeeInOrder(['bi-check-circle-fill', '1250.00']);
});

test('editar inline responde con la parcial de la fila en modo edicion con el valor prellenado', function () {
    $local = Locacion::factory()->create(['es_alquilable' => true]);

    $lectura = LecturaMedidor::factory()->create([
        'locacion_id' => $local->id,
        'periodo' => '2026-08-01',
        'lectura_actual' => 1250,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('lecturas.registroMasivo.editarInline', $lectura));

    $respuesta->assertOk();
    $respuesta->assertSee('value="1250.00"', false);
    $respuesta->assertSee('name="lectura_actual"', false);
});

test('actualizar inline con un valor valido actualiza la lectura y responde con la fila en modo lectura', function () {
    $local = Locacion::factory()->create(['es_alquilable' => true]);

    $lectura = LecturaMedidor::factory()->create([
        'locacion_id' => $local->id,
        'periodo' => '2026-08-01',
        'lectura_actual' => 1250,
    ]);

    $respuesta = $this->actingAs($this->admin)->patch(route('lecturas.registroMasivo.actualizarInline', $lectura), [
        'periodo' => '2026-08-01',
        'lectura_actual' => '1300',
    ]);

    $respuesta->assertOk();
    $respuesta->assertSee('1300.00');
    $respuesta->assertDontSee('name="lectura_actual"', false);
    expect($lectura->fresh()->lectura_actual)->toBe('1300.00');
});

test('actualizar inline con consumo negativo sin confirmar exige confirmacion en la misma fila', function () {
    $local = Locacion::factory()->create(['es_alquilable' => true]);

    LecturaMedidor::factory()->create([
        'locacion_id' => $local->id,
        'periodo' => '2026-07-01',
        'lectura_actual' => 1000,
    ]);

    $lectura = LecturaMedidor::factory()->create([
        'locacion_id' => $local->id,
        'periodo' => '2026-08-01',
        'lectura_anterior' => 1000,
        'lectura_actual' => 1250,
        'consumo_calculado' => 250,
    ]);

    $respuesta = $this->actingAs($this->admin)->patch(route('lecturas.registroMasivo.actualizarInline', $lectura), [
        'periodo' => '2026-08-01',
        'lectura_actual' => '900',
    ]);

    $respuesta->assertOk();
    $respuesta->assertSee('confirmar_consumo_negativo', false);
    $respuesta->assertSee('name="lectura_actual"', false);
    $respuesta->assertSee('value="900"', false);
    expect($lectura->fresh()->lectura_actual)->toBe('1250.00');
});
