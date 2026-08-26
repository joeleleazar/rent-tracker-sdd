<?php

use App\Models\ConceptoGastoFijo;
use App\Models\Contrato;
use App\Models\ConfiguracionGeneral;
use App\Models\Inquilino;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Models\User;
use App\Models\ValorConceptoContrato;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $this->inquilino = Inquilino::factory()->create();
    $this->contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'estado' => 'activo',
        'fecha_inicio' => now()->subMonth()->format('Y-m-d'),
        'fecha_fin' => now()->addYear()->format('Y-m-d'),
        'monto_renta' => 1500,
    ]);

    $this->agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
    $this->luz = ConceptoGastoFijo::firstWhere('clave', 'luz');
    $this->pasadizo = ConceptoGastoFijo::firstWhere('nombre', 'Luz de Pasadizo');
    $this->seguridad = ConceptoGastoFijo::firstWhere('nombre', 'Seguridad');

    ValorConceptoContrato::create(['contrato_id' => $this->contrato->id, 'concepto_gasto_fijo_id' => $this->agua->id, 'valor' => 50]);
    ValorConceptoContrato::create(['contrato_id' => $this->contrato->id, 'concepto_gasto_fijo_id' => $this->pasadizo->id, 'valor' => 30]);
    ValorConceptoContrato::create(['contrato_id' => $this->contrato->id, 'concepto_gasto_fijo_id' => $this->seguridad->id, 'valor' => 40]);

    $datosRecibo = fn (array $extra = []) => array_merge([
        'periodo' => now()->format('Y-m-d'),
        'monto_renta' => '1500.00',
        'fecha_emision' => now()->format('Y-m-d'),
        'incluye_alquiler' => '1',
        'conceptos' => [
            $this->luz->id => ['incluido' => '1', 'monto' => '0.00'],
            $this->agua->id => ['incluido' => '1', 'monto' => '50.00'],
            $this->pasadizo->id => ['incluido' => '1', 'monto' => '30.00'],
            $this->seguridad->id => ['incluido' => '1', 'monto' => '40.00'],
        ],
    ], $extra);

    $this->datosRecibo = $datosRecibo;
});

test('el formulario de creacion precarga los montos fijos del contrato activo del periodo', function () {
    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.recibos.create', $this->locacion));

    $respuesta->assertOk();
    $respuesta->assertSee('1500');
    $respuesta->assertSee('50');
    $respuesta->assertSee('30');
    $respuesta->assertSee('40');
});

test('el monto de luz sugerido usa el total ya persistido de la lectura del periodo', function () {
    // specs/019 FR-006: el total ya se fijó al registrar la lectura; cambiar la tarifa
    // después no debe alterar el monto sugerido del formulario de recibo.
    LecturaMedidor::factory()->create([
        'locacion_id' => $this->locacion->id,
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
        'lectura_actual' => 500,
        'total' => 75,
    ]);
    ConfiguracionGeneral::actual()->update(['tarifa_luz_por_unidad' => 5]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.recibos.create', $this->locacion));

    $respuesta->assertOk();
    $respuesta->assertSee('75');
});

test('permite emitir un recibo con montos editados sin alterar el contrato', function () {
    $respuesta = $this->actingAs($this->admin)->post(
        route('locaciones.recibos.store', $this->locacion),
        ($this->datosRecibo)(['monto_renta' => '1450.00']),
    );

    $recibo = Recibo::firstWhere('locacion_id', $this->locacion->id);

    $respuesta->assertRedirect(route('recibos.show', $recibo));
    expect($recibo->monto_renta)->toBe('1450.00');
    expect($this->contrato->fresh()->monto_renta)->toBe('1500.00');
    expect($recibo->contrato_id)->toBe($this->contrato->id);
});

test('los conceptos desmarcados se excluyen del total sin afectar el contrato', function () {
    $datos = ($this->datosRecibo)();
    $datos['conceptos'][$this->seguridad->id]['incluido'] = '0';

    $respuesta = $this->actingAs($this->admin)->post(
        route('locaciones.recibos.store', $this->locacion),
        $datos,
    );

    $recibo = Recibo::firstWhere('locacion_id', $this->locacion->id);

    $respuesta->assertRedirect(route('recibos.show', $recibo));
    expect($recibo->conceptos->contains('concepto_gasto_fijo_id', $this->seguridad->id))->toBeFalse();
    expect($recibo->total())->toBe(1500.0 + 50.0 + 0.0 + 30.0);
});

test('guardar borrador crea o actualiza (upsert) el borrador del usuario autenticado', function () {
    $periodo = now()->startOfMonth()->format('Y-m-d');

    $respuesta = $this->actingAs($this->admin)->post(route('locaciones.recibos.borrador', $this->locacion), [
        'periodo' => $periodo,
        'incluye_alquiler' => '1',
        'monto_renta' => '1500.00',
        'fecha_emision' => now()->format('Y-m-d'),
        'conceptos' => [
            $this->agua->id => ['incluido' => '1', 'monto' => '50.00'],
        ],
    ]);

    $respuesta->assertOk();
    $borrador = \App\Models\BorradorRecibo::where('usuario_id', $this->admin->id)->where('locacion_id', $this->locacion->id)->first();
    expect($borrador)->not->toBeNull();
    expect($borrador->incluye_alquiler)->toBeTrue();
    expect($borrador->conceptos)->toBe([(string) $this->agua->id => 50]);

    // segundo guardado sobre la misma locacion/periodo/usuario: upsert, no duplica fila.
    $this->actingAs($this->admin)->post(route('locaciones.recibos.borrador', $this->locacion), [
        'periodo' => $periodo,
        'incluye_alquiler' => '0',
        'conceptos' => [],
    ]);

    expect(\App\Models\BorradorRecibo::where('usuario_id', $this->admin->id)->where('locacion_id', $this->locacion->id)->count())->toBe(1);
    expect($borrador->fresh()->incluye_alquiler)->toBeFalse();
});

test('el formulario de creacion prellena los conceptos y montos desde un borrador existente', function () {
    \App\Models\BorradorRecibo::create([
        'usuario_id' => $this->admin->id,
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
        'locacion_id' => $this->locacion->id,
        'incluye_alquiler' => true,
        'monto_renta' => 1499,
        'fecha_emision' => now()->format('Y-m-d'),
        'conceptos' => [$this->agua->id => 999],
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.recibos.create', $this->locacion));

    $respuesta->assertOk();
    $respuesta->assertSee('1499');
    $respuesta->assertSee('999');
});

test('emitir el recibo exitosamente elimina el borrador correspondiente', function () {
    \App\Models\BorradorRecibo::create([
        'usuario_id' => $this->admin->id,
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
        'locacion_id' => $this->locacion->id,
        'conceptos' => [],
    ]);

    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());

    expect(\App\Models\BorradorRecibo::where('usuario_id', $this->admin->id)->where('locacion_id', $this->locacion->id)->exists())->toBeFalse();
});

test('si la emision falla por conceptos ya cubiertos el borrador no se elimina', function () {
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());

    \App\Models\BorradorRecibo::create([
        'usuario_id' => $this->admin->id,
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
        'locacion_id' => $this->locacion->id,
        'conceptos' => [],
    ]);

    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());

    expect(\App\Models\BorradorRecibo::where('usuario_id', $this->admin->id)->where('locacion_id', $this->locacion->id)->exists())->toBeTrue();
});

test('el borrador es propio de cada usuario: no se pisan entre si', function () {
    $otroAdmin = User::factory()->create();
    $periodo = now()->startOfMonth()->format('Y-m-d');

    $this->actingAs($this->admin)->post(route('locaciones.recibos.borrador', $this->locacion), [
        'periodo' => $periodo,
        'conceptos' => [$this->agua->id => ['incluido' => '1', 'monto' => '11.00']],
    ]);
    $this->actingAs($otroAdmin)->post(route('locaciones.recibos.borrador', $this->locacion), [
        'periodo' => $periodo,
        'conceptos' => [$this->agua->id => ['incluido' => '1', 'monto' => '22.00']],
    ]);

    $borradorAdmin = \App\Models\BorradorRecibo::where('usuario_id', $this->admin->id)->where('locacion_id', $this->locacion->id)->first();
    $borradorOtro = \App\Models\BorradorRecibo::where('usuario_id', $otroAdmin->id)->where('locacion_id', $this->locacion->id)->first();

    expect($borradorAdmin->conceptos)->toBe([(string) $this->agua->id => 11]);
    expect($borradorOtro->conceptos)->toBe([(string) $this->agua->id => 22]);
});

test('bloquea la emision de un recibo si no hay contrato activo en el periodo', function () {
    $respuesta = $this->actingAs($this->admin)->post(
        route('locaciones.recibos.store', $this->locacion),
        ($this->datosRecibo)(['periodo' => now()->addYears(5)->format('Y-m-d')]),
    );

    $respuesta->assertSessionHasErrors('periodo');
    expect(Recibo::count())->toBe(0);
});

test('bloquea un segundo recibo que repite conceptos ya cubiertos', function () {
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());

    $respuesta = $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());

    $respuesta->assertSessionHasErrors('periodo');
    expect(Recibo::where('locacion_id', $this->locacion->id)->count())->toBe(1);
});

test('permite un segundo recibo para la misma locacion y periodo con conceptos distintos', function () {
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)([
        'conceptos' => [],
    ]));

    $respuesta = $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)([
        'incluye_alquiler' => '0',
    ]));

    $respuesta->assertSessionDoesntHaveErrors();
    expect(Recibo::where('locacion_id', $this->locacion->id)->count())->toBe(2);
});

test('el formulario de creacion oculta los conceptos ya cubiertos por un recibo previo', function () {
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)([
        'conceptos' => [],
    ]));

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.recibos.create', $this->locacion));

    $respuesta->assertOk();
    $respuesta->assertSee('ya está cubierto');
    $respuesta->assertDontSee('name="incluye_alquiler"', false);
});

test('permite editar un recibo ya emitido', function () {
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());
    $recibo = Recibo::firstWhere('locacion_id', $this->locacion->id);

    $datosActualizacion = ($this->datosRecibo)();
    $datosActualizacion['conceptos'][$this->seguridad->id] = ['incluido' => '1', 'monto' => '999.00'];

    $respuesta = $this->actingAs($this->admin)->put(route('recibos.update', $recibo), $datosActualizacion);

    $respuesta->assertRedirect(route('recibos.show', $recibo));
    $recibo->refresh();
    expect($recibo->conceptos->firstWhere('concepto_gasto_fijo_id', $this->seguridad->id)->monto)->toBe('999.00');
});

test('specs/029: el formulario de edicion ofrece el campo de Renta cuando el recibo ya la incluye', function () {
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());
    $recibo = Recibo::firstWhere('locacion_id', $this->locacion->id);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.edit', $recibo));

    $respuesta->assertOk();
    $respuesta->assertSee('name="incluye_alquiler"', false);
    $respuesta->assertSee('name="monto_renta"', false);
    $respuesta->assertSee('value="1500.00"', false);
});

test('specs/029: editar el monto de Renta de un recibo ya emitido lo actualiza', function () {
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());
    $recibo = Recibo::firstWhere('locacion_id', $this->locacion->id);

    $datosActualizacion = ($this->datosRecibo)(['monto_renta' => '1650.00']);

    $respuesta = $this->actingAs($this->admin)->put(route('recibos.update', $recibo), $datosActualizacion);

    $respuesta->assertRedirect(route('recibos.show', $recibo));
    expect($recibo->fresh()->monto_renta)->toBe('1650.00');
});

test('specs/029: desmarcar Renta al editar la quita del recibo', function () {
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());
    $recibo = Recibo::firstWhere('locacion_id', $this->locacion->id);

    $datosActualizacion = ($this->datosRecibo)();
    $datosActualizacion['incluye_alquiler'] = '0';

    $respuesta = $this->actingAs($this->admin)->put(route('recibos.update', $recibo), $datosActualizacion);

    $respuesta->assertRedirect(route('recibos.show', $recibo));
    expect($recibo->fresh()->monto_renta)->toBeNull();
});

test('specs/029: editar un recibo sin Renta la sigue ofreciendo disponible cuando nadie mas la cubre', function () {
    $sinRenta = ($this->datosRecibo)(['incluye_alquiler' => '0']);
    unset($sinRenta['monto_renta']);
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), $sinRenta);
    $recibo = Recibo::firstWhere('locacion_id', $this->locacion->id);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.edit', $recibo));

    $respuesta->assertOk();
    $respuesta->assertSee('name="incluye_alquiler"', false);
});

test('specs/029: editar un recibo sin Renta no la ofrece si otro recibo del mismo periodo ya la cubre', function () {
    $sinRenta = ($this->datosRecibo)(['incluye_alquiler' => '0']);
    unset($sinRenta['monto_renta']);
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), $sinRenta);
    $reciboSinRenta = Recibo::firstWhere('locacion_id', $this->locacion->id);

    Recibo::factory()->create([
        'contrato_id' => $this->contrato->id,
        'locacion_id' => $this->locacion->id,
        'periodo' => $reciboSinRenta->periodo,
        'monto_renta' => 1500,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.edit', $reciboSinRenta));

    $respuesta->assertOk();
    $respuesta->assertDontSee('name="incluye_alquiler"', false);
});

test('editar los valores de referencia del contrato despues de emitir un recibo no altera el recibo', function () {
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());
    $recibo = Recibo::firstWhere('locacion_id', $this->locacion->id);

    $this->actingAs($this->admin)->patch(route('contratos.costos.update', $this->contrato), [
        'valores' => [$this->agua->id => '999.00'],
    ]);

    expect($recibo->fresh()->conceptos->firstWhere('concepto_gasto_fijo_id', $this->agua->id)->monto)->toBe('50.00');
});

test('el historial de recibos de la locacion muestra los montos efectivamente cobrados', function () {
    $recibo = Recibo::factory()->create([
        'contrato_id' => $this->contrato->id,
        'locacion_id' => $this->locacion->id,
        'monto_renta' => '1450.00',
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
    ]);
    $recibo->conceptos()->create(['concepto_gasto_fijo_id' => $this->agua->id, 'monto' => 0]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.recibos.index', $this->locacion));

    $respuesta->assertOk();
    $respuesta->assertSee('1,450.00');
});

test('un usuario no autenticado no puede acceder a las rutas de recibos', function () {
    $respuesta = $this->get(route('locaciones.recibos.index', $this->locacion));

    $respuesta->assertRedirect(route('login'));
});

test('un recibo recien emitido inicia en estado pendiente', function () {
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());
    $recibo = Recibo::firstWhere('locacion_id', $this->locacion->id);

    expect($recibo->estado)->toBe('pendiente');
});

test('un administrador puede marcar un recibo como pagado', function () {
    $recibo = Recibo::factory()->create(['contrato_id' => $this->contrato->id, 'locacion_id' => $this->locacion->id]);

    $respuesta = $this->actingAs($this->admin)->patch(route('recibos.estado.update', $recibo), [
        'nuevo_estado' => 'pagado',
        'confirmado' => '1',
    ]);

    $respuesta->assertRedirect(route('recibos.show', $recibo));
    $recibo->refresh();
    expect($recibo->estado)->toBe('pagado');
    expect($recibo->fecha_pago)->not->toBeNull();
});

test('anular un recibo sin confirmar es rechazado', function () {
    $recibo = Recibo::factory()->create(['contrato_id' => $this->contrato->id, 'locacion_id' => $this->locacion->id]);

    $respuesta = $this->actingAs($this->admin)->patch(route('recibos.estado.update', $recibo), [
        'nuevo_estado' => 'anulado',
    ]);

    $respuesta->assertSessionHasErrors('estado');
    expect($recibo->fresh()->estado)->toBe('pendiente');
});

test('anular un recibo confirmando lo marca como anulado', function () {
    $recibo = Recibo::factory()->create(['contrato_id' => $this->contrato->id, 'locacion_id' => $this->locacion->id]);

    $respuesta = $this->actingAs($this->admin)->patch(route('recibos.estado.update', $recibo), [
        'nuevo_estado' => 'anulado',
        'confirmado' => '1',
    ]);

    $respuesta->assertRedirect(route('recibos.show', $recibo));
    expect($recibo->fresh()->estado)->toBe('anulado');
    expect($recibo->fresh()->fecha_anulacion)->not->toBeNull();
});

test('el comprobante incluye los conceptos montos periodo y estado del recibo', function () {
    $recibo = Recibo::factory()->create([
        'contrato_id' => $this->contrato->id,
        'locacion_id' => $this->locacion->id,
        'monto_renta' => '1500.00',
        'periodo' => '2026-08-01',
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.comprobante', $recibo));

    $respuesta->assertOk();
    $respuesta->assertSee('1,500.00');
    $respuesta->assertSee('agosto 2026');
    $respuesta->assertSee('Pendiente');
    $respuesta->assertSee('Imprimir Recibo');
    $respuesta->assertSee('Enviar por WhatsApp');
});

test('el comprobante de un recibo anulado muestra la marca anulado', function () {
    $recibo = Recibo::factory()->create([
        'contrato_id' => $this->contrato->id,
        'locacion_id' => $this->locacion->id,
        'estado' => 'anulado',
        'fecha_anulacion' => now(),
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.comprobante', $recibo));

    $respuesta->assertOk();
    $respuesta->assertSeeInOrder(['Anulado']);
});

test('el comprobante incluye la hoja de estilos de impresion', function () {
    $recibo = Recibo::factory()->create(['contrato_id' => $this->contrato->id, 'locacion_id' => $this->locacion->id]);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.comprobante', $recibo));

    $respuesta->assertOk();
    $respuesta->assertSee('@media print');
    $respuesta->assertSee('no-imprimir', false);
});

test('el formulario de creacion sugiere dias activos y monto prorrateado si el contrato inicia a mitad de mes', function () {
    $locacionProrrateo = Locacion::factory()->create(['es_alquilable' => true]);
    Contrato::factory()->create([
        'locacion_id' => $locacionProrrateo->id,
        'inquilino_id' => $this->inquilino->id,
        'estado' => 'activo',
        'fecha_inicio' => '2026-08-15',
        'fecha_fin' => '2027-08-14',
        'monto_renta' => 1550,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.recibos.create', $locacionProrrateo) . '?periodo=2026-08');

    $respuesta->assertOk();
    $respuesta->assertSee('17 días de 31');
    $respuesta->assertSee('850.00');
});

test('no muestra sugerencia de prorrateo cuando el contrato esta activo todo el mes', function () {
    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.recibos.create', $this->locacion));

    $respuesta->assertOk();
    $respuesta->assertDontSee('días activo');
});

test('el recibo emitido persiste los dias activos y totales del prorrateo', function () {
    $locacionProrrateo = Locacion::factory()->create(['es_alquilable' => true]);
    Contrato::factory()->create([
        'locacion_id' => $locacionProrrateo->id,
        'inquilino_id' => $this->inquilino->id,
        'estado' => 'activo',
        'fecha_inicio' => '2026-08-15',
        'fecha_fin' => '2027-08-14',
        'monto_renta' => 1550,
    ]);

    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $locacionProrrateo), ($this->datosRecibo)([
        'periodo' => '2026-08-01',
        'monto_renta' => '850.00',
        'conceptos' => [],
    ]));

    $recibo = Recibo::firstWhere('locacion_id', $locacionProrrateo->id);

    expect($recibo->dias_activos_periodo)->toBe(17);
    expect($recibo->dias_totales_periodo)->toBe(31);
});
