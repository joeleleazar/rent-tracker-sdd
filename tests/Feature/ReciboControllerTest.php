<?php

use App\Models\Contrato;
use App\Models\ConfiguracionGeneral;
use App\Models\Inquilino;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Models\User;

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
        'costo_agua' => 50,
        'costo_luz' => 80,
        'costo_pasadizo' => 30,
        'costo_seguridad' => 40,
    ]);

    $datosRecibo = fn (array $extra = []) => array_merge([
        'periodo' => now()->format('Y-m-d'),
        'monto_renta' => '1500.00',
        'monto_agua' => '50.00',
        'monto_luz' => '0.00',
        'monto_pasadizo' => '30.00',
        'monto_seguridad' => '40.00',
        'fecha_emision' => now()->format('Y-m-d'),
        'incluye_alquiler' => '1',
        'incluye_luz' => '1',
        'incluye_agua' => '1',
        'incluye_pasadizo' => '1',
        'incluye_seguridad' => '1',
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

test('el monto de luz sugerido se calcula a partir del consumo y la tarifa vigente', function () {
    ConfiguracionGeneral::actual()->update(['tarifa_luz_por_unidad' => 0.5]);
    LecturaMedidor::factory()->create([
        'locacion_id' => $this->locacion->id,
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
        'lectura_actual' => 500,
        'consumo_calculado' => 150,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.recibos.create', $this->locacion));

    $respuesta->assertOk();
    // 150 * 0.5 = 75.00
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
    unset($datos['incluye_seguridad']);

    $respuesta = $this->actingAs($this->admin)->post(
        route('locaciones.recibos.store', $this->locacion),
        $datos,
    );

    $recibo = Recibo::firstWhere('locacion_id', $this->locacion->id);

    $respuesta->assertRedirect(route('recibos.show', $recibo));
    expect($recibo->incluye_seguridad)->toBeFalse();
    expect($recibo->monto_seguridad)->toBe('40.00');
    expect($recibo->total())->toBe(1500.0 + 50.0 + 0.0 + 30.0);
});

test('bloquea la emision de un recibo si no hay contrato activo en el periodo', function () {
    $respuesta = $this->actingAs($this->admin)->post(
        route('locaciones.recibos.store', $this->locacion),
        ($this->datosRecibo)(['periodo' => now()->addYears(5)->format('Y-m-d')]),
    );

    $respuesta->assertSessionHasErrors('periodo');
    expect(Recibo::count())->toBe(0);
});

test('bloquea un segundo recibo para la misma locacion y periodo, ofreciendo editarlo', function () {
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());
    $existente = Recibo::firstWhere('locacion_id', $this->locacion->id);

    $respuesta = $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());

    $respuesta->assertRedirect(route('recibos.edit', $existente));
    expect(Recibo::where('locacion_id', $this->locacion->id)->count())->toBe(1);
});

test('permite editar un recibo ya emitido', function () {
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());
    $recibo = Recibo::firstWhere('locacion_id', $this->locacion->id);

    $datosActualizacion = ($this->datosRecibo)(['monto_seguridad' => '999.00']);
    unset($datosActualizacion['incluye_seguridad']);

    $respuesta = $this->actingAs($this->admin)->put(route('recibos.update', $recibo), $datosActualizacion);

    $respuesta->assertRedirect(route('recibos.show', $recibo));
    $recibo->refresh();
    expect($recibo->monto_seguridad)->toBe('999.00');
    expect($recibo->incluye_seguridad)->toBeFalse();
});

test('editar los costos del contrato despues de emitir un recibo no altera el recibo', function () {
    $this->actingAs($this->admin)->post(route('locaciones.recibos.store', $this->locacion), ($this->datosRecibo)());
    $recibo = Recibo::firstWhere('locacion_id', $this->locacion->id);

    $this->actingAs($this->admin)->patch(route('contratos.costos.update', $this->contrato), [
        'costo_agua' => '999.00',
        'costo_luz' => '80.00',
        'costo_pasadizo' => '30.00',
        'costo_seguridad' => '40.00',
    ]);

    expect($recibo->fresh()->monto_agua)->toBe('50.00');
});

test('el historial de recibos de la locacion muestra los montos efectivamente cobrados', function () {
    Recibo::factory()->create([
        'contrato_id' => $this->contrato->id,
        'locacion_id' => $this->locacion->id,
        'monto_renta' => '1450.00',
        'monto_agua' => 0,
        'monto_luz' => 0,
        'monto_pasadizo' => 0,
        'monto_seguridad' => 0,
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
    ]);

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
    ]));

    $recibo = Recibo::firstWhere('locacion_id', $locacionProrrateo->id);

    expect($recibo->dias_activos_periodo)->toBe(17);
    expect($recibo->dias_totales_periodo)->toBe(31);
});
