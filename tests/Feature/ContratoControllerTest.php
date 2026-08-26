<?php

use App\Models\ConceptoGastoFijo;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Models\User;
use App\Models\ValorConceptoContrato;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $this->inquilino = Inquilino::factory()->create();
    $this->agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
    $this->pasadizo = ConceptoGastoFijo::firstWhere('nombre', 'Luz de Pasadizo');
    $this->seguridad = ConceptoGastoFijo::firstWhere('nombre', 'Seguridad');
});

test('un administrador autenticado puede crear un contrato sin solapamiento', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1500.00',
        'estado' => 'activo',
        'inquilinos' => [
            ['apellidos' => 'Pérez Gómez', 'nombres' => 'Juan Carlos', 'dni' => '12345678', 'fecha_nacimiento' => '1960-05-15'],
        ],
    ]);

    $contrato = Contrato::firstWhere('locacion_id', $this->locacion->id);

    $respuesta->assertRedirect(route('contratos.show', $contrato));
    expect($contrato)->not->toBeNull();
    expect($contrato->monto_renta)->toBe('1500.00');
});

test('permite registrar los valores de referencia del contrato y usa 0.00 por defecto si se omiten', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1500.00',
        'estado' => 'activo',
        'valores' => [
            $this->agua->id => '50.00',
            $this->seguridad->id => '40.00',
        ],
        'inquilinos' => [
            ['apellidos' => 'Pérez Gómez', 'nombres' => 'Juan Carlos', 'dni' => '13245678', 'fecha_nacimiento' => '1960-05-15'],
        ],
    ]);

    $contrato = Contrato::firstWhere('locacion_id', $this->locacion->id);

    $respuesta->assertRedirect(route('contratos.show', $contrato));
    expect($contrato->valorDeConcepto($this->agua))->toBe(50.0);
    expect($contrato->valorDeConcepto($this->pasadizo))->toBeNull();
    expect($contrato->valorDeConcepto($this->seguridad))->toBe(40.0);
});

test('un administrador puede editar rapidamente solo los valores de referencia del contrato', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
    ]);

    $respuesta = $this->actingAs($this->admin)->patch(route('contratos.costos.update', $contrato), [
        'valores' => [
            $this->agua->id => '55.00',
            $this->pasadizo->id => '35.00',
            $this->seguridad->id => '45.00',
        ],
    ]);

    $respuesta->assertRedirect(route('contratos.show', $contrato));
    $contrato->refresh();
    expect($contrato->valorDeConcepto($this->agua))->toBe(55.0);
    expect($contrato->valorDeConcepto($this->pasadizo))->toBe(35.0);
    expect($contrato->valorDeConcepto($this->seguridad))->toBe(45.0);
});

test('rechaza valores de referencia no numericos en la edicion rapida', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
    ]);

    $respuesta = $this->actingAs($this->admin)->patch(route('contratos.costos.update', $contrato), [
        'valores' => [
            $this->agua->id => 'no-es-un-numero',
            $this->pasadizo->id => '35.00',
        ],
    ]);

    $respuesta->assertSessionHasErrors('valores.' . $this->agua->id);
});

test('la edicion rapida de valores nunca ofrece ni acepta un valor para renta o luz', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
    ]);
    $renta = ConceptoGastoFijo::firstWhere('clave', 'renta');
    $luz = ConceptoGastoFijo::firstWhere('clave', 'luz');

    $respuesta = $this->actingAs($this->admin)->get(route('contratos.show', $contrato));
    $respuesta->assertDontSee('name="valores[' . $renta->id . ']"', false);
    $respuesta->assertDontSee('name="valores[' . $luz->id . ']"', false);

    $this->actingAs($this->admin)->patch(route('contratos.costos.update', $contrato), [
        'valores' => [$renta->id => '999.00', $luz->id => '999.00'],
    ]);

    expect($contrato->valorDeConcepto($renta))->toBeNull();
    expect($contrato->valorDeConcepto($luz))->toBeNull();
});

test('editar la fecha_fin del contrato reinicia los hitos de notificacion ya enviados', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'notificado_30_dias_en' => now(),
        'notificado_15_dias_en' => now(),
    ]);

    $this->actingAs($this->admin)->put(route('contratos.update', $contrato), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2027-03-31',
        'monto_renta' => (string) $contrato->monto_renta,
        'estado' => 'activo',
    ]);

    $contrato->refresh();
    expect($contrato->notificado_30_dias_en)->toBeNull();
    expect($contrato->notificado_15_dias_en)->toBeNull();
    expect($contrato->notificado_7_dias_en)->toBeNull();
});

test('rechaza la creacion de un contrato que se solapa con uno existente', function () {
    Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'estado' => 'activo',
    ]);

    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-06-01',
        'fecha_fin' => '2027-05-31',
        'monto_renta' => '1200.00',
        'estado' => 'activo',
        'inquilinos' => [
            ['apellidos' => 'Pérez Gómez', 'nombres' => 'Juan Carlos', 'dni' => '87654321', 'fecha_nacimiento' => '1960-05-15'],
        ],
    ]);

    $respuesta->assertStatus(302);
    $respuesta->assertSessionHasErrors('solapamiento');
    expect(Contrato::where('locacion_id', $this->locacion->id)->count())->toBe(1);
});

test('un administrador autenticado puede editar un contrato existente', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1000.00',
        'estado' => 'activo',
    ]);

    $respuesta = $this->actingAs($this->admin)->put(route('contratos.update', $contrato), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1750.50',
        'estado' => 'activo',
    ]);

    $respuesta->assertRedirect(route('contratos.show', $contrato));
    expect($contrato->fresh()->monto_renta)->toBe('1750.50');
});

test('un usuario no autenticado no puede acceder a las rutas de contrato', function () {
    $respuesta = $this->get(route('contratos.create', $this->locacion));

    $respuesta->assertRedirect(route('login'));
});

test('permite registrar la garantia entregada al crear un contrato', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1500.00',
        'estado' => 'activo',
        'monto_garantia' => '1500.00',
        'fecha_entrega_garantia' => '2026-08-19',
        'medio_entrega_garantia' => 'efectivo',
        'inquilinos' => [
            ['apellidos' => 'Pérez Gómez', 'nombres' => 'Juan Carlos', 'dni' => '11223344', 'fecha_nacimiento' => '1960-05-15'],
        ],
    ]);

    $contrato = Contrato::firstWhere('locacion_id', $this->locacion->id);

    $respuesta->assertRedirect(route('contratos.show', $contrato));
    expect($contrato->monto_garantia)->toBe('1500.00');
    expect($contrato->fecha_entrega_garantia->format('Y-m-d'))->toBe('2026-08-19');
    expect($contrato->medio_entrega_garantia)->toBe('efectivo');
    expect($contrato->estado_garantia)->toBe('entregada');
});

test('permite crear un contrato sin garantia sin bloquear el guardado', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1500.00',
        'estado' => 'activo',
        'inquilinos' => [
            ['apellidos' => 'Pérez Gómez', 'nombres' => 'Juan Carlos', 'dni' => '99112233', 'fecha_nacimiento' => '1960-05-15'],
        ],
    ]);

    $contrato = Contrato::firstWhere('locacion_id', $this->locacion->id);

    $respuesta->assertRedirect(route('contratos.show', $contrato));
    expect($contrato->tieneGarantia())->toBeFalse();
});

test('exige fecha de entrega de garantia cuando se registra un monto mayor a cero', function () {
    $respuesta = $this->actingAs($this->admin)->post(route('contratos.store', $this->locacion), [
        'inquilino_id' => $this->inquilino->id,
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'monto_renta' => '1500.00',
        'estado' => 'activo',
        'monto_garantia' => '1500.00',
        'inquilinos' => [
            ['apellidos' => 'Pérez Gómez', 'nombres' => 'Juan Carlos', 'dni' => '55667788', 'fecha_nacimiento' => '1960-05-15'],
        ],
    ]);

    $respuesta->assertSessionHasErrors('fecha_entrega_garantia');
});

test('el detalle del contrato muestra sin garantia registrada cuando no aplica', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'monto_garantia' => null,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('contratos.show', $contrato));

    $respuesta->assertOk();
    $respuesta->assertSee('Sin garantía registrada');
});

test('el detalle del contrato muestra el monto y fecha de garantia registrada', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'monto_garantia' => 1500,
        'fecha_entrega_garantia' => '2026-08-19',
        'medio_entrega_garantia' => 'efectivo',
        'estado_garantia' => 'entregada',
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('contratos.show', $contrato));

    $respuesta->assertOk();
    $respuesta->assertSee('1,500.00');
    $respuesta->assertSee('19/08/2026');
    $respuesta->assertSee('Efectivo');
});

test('permite registrar la resolucion de garantia con devolucion total', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'monto_garantia' => 1500,
        'estado_garantia' => 'entregada',
    ]);

    $respuesta = $this->actingAs($this->admin)->post(route('contratos.garantia.resolucion', $contrato), [
        'monto_devuelto_garantia' => '1500.00',
        'monto_retenido_garantia' => '0.00',
    ]);

    $respuesta->assertRedirect(route('contratos.show', $contrato));
    $contrato->refresh();
    expect($contrato->estado_garantia)->toBe('resuelta');
    expect($contrato->monto_devuelto_garantia)->toBe('1500.00');
});

test('permite registrar la resolucion de garantia con retencion parcial y motivo', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'monto_garantia' => 1500,
        'estado_garantia' => 'entregada',
    ]);

    $respuesta = $this->actingAs($this->admin)->post(route('contratos.garantia.resolucion', $contrato), [
        'monto_devuelto_garantia' => '1200.00',
        'monto_retenido_garantia' => '300.00',
        'motivo_retencion_garantia' => 'Reparación de puerta dañada',
    ]);

    $respuesta->assertRedirect(route('contratos.show', $contrato));
    $contrato->refresh();
    expect($contrato->monto_devuelto_garantia)->toBe('1200.00');
    expect($contrato->monto_retenido_garantia)->toBe('300.00');
    expect($contrato->motivo_retencion_garantia)->toBe('Reparación de puerta dañada');
});

test('bloquea la resolucion de garantia si la suma no coincide con el monto entregado', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'monto_garantia' => 1500,
        'estado_garantia' => 'entregada',
    ]);

    $respuesta = $this->actingAs($this->admin)->post(route('contratos.garantia.resolucion', $contrato), [
        'monto_devuelto_garantia' => '1200.00',
        'monto_retenido_garantia' => '200.00',
    ]);

    $respuesta->assertSessionHasErrors('garantia');
    expect($contrato->fresh()->estado_garantia)->toBe('entregada');
});

test('bloquea la resolucion de garantia con retencion sin motivo', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'monto_garantia' => 1500,
        'estado_garantia' => 'entregada',
    ]);

    $respuesta = $this->actingAs($this->admin)->post(route('contratos.garantia.resolucion', $contrato), [
        'monto_devuelto_garantia' => '1200.00',
        'monto_retenido_garantia' => '300.00',
    ]);

    $respuesta->assertSessionHasErrors();
    expect($contrato->fresh()->estado_garantia)->toBe('entregada');
});

test('bloquea corregir una resolucion ya registrada sin confirmar', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'monto_garantia' => 1500,
        'estado_garantia' => 'resuelta',
        'monto_devuelto_garantia' => 1500,
        'monto_retenido_garantia' => 0,
        'fecha_resolucion_garantia' => now(),
    ]);

    $respuesta = $this->actingAs($this->admin)->post(route('contratos.garantia.resolucion', $contrato), [
        'monto_devuelto_garantia' => '1400.00',
        'monto_retenido_garantia' => '100.00',
        'motivo_retencion_garantia' => 'Corrección',
    ]);

    $respuesta->assertSessionHasErrors('garantia');
    expect($contrato->fresh()->monto_devuelto_garantia)->toBe('1500.00');
});

test('permite corregir una resolucion ya registrada confirmando explicitamente', function () {
    $contrato = Contrato::factory()->create([
        'locacion_id' => $this->locacion->id,
        'inquilino_id' => $this->inquilino->id,
        'monto_garantia' => 1500,
        'estado_garantia' => 'resuelta',
        'monto_devuelto_garantia' => 1500,
        'monto_retenido_garantia' => 0,
        'fecha_resolucion_garantia' => now(),
    ]);

    $respuesta = $this->actingAs($this->admin)->post(route('contratos.garantia.resolucion', $contrato), [
        'monto_devuelto_garantia' => '1400.00',
        'monto_retenido_garantia' => '100.00',
        'motivo_retencion_garantia' => 'Corrección',
        'confirmado' => '1',
    ]);

    $respuesta->assertRedirect(route('contratos.show', $contrato));
    expect($contrato->fresh()->monto_devuelto_garantia)->toBe('1400.00');
});

test('el formulario de creacion de contrato ofrece los conceptos configurables, nunca renta ni luz', function () {
    $renta = ConceptoGastoFijo::firstWhere('clave', 'renta');
    $luz = ConceptoGastoFijo::firstWhere('clave', 'luz');

    $respuesta = $this->actingAs($this->admin)->get(route('contratos.create', $this->locacion));

    $respuesta->assertOk();
    $respuesta->assertSee('name="valores[' . $this->agua->id . ']"', false);
    $respuesta->assertDontSee('name="valores[' . $renta->id . ']"', false);
    $respuesta->assertDontSee('name="valores[' . $luz->id . ']"', false);
});

test('el formulario de edicion de contrato precarga los valores de referencia ya configurados', function () {
    $contrato = Contrato::factory()->create(['locacion_id' => $this->locacion->id, 'inquilino_id' => $this->inquilino->id]);
    ValorConceptoContrato::create(['contrato_id' => $contrato->id, 'concepto_gasto_fijo_id' => $this->agua->id, 'valor' => 33]);

    $respuesta = $this->actingAs($this->admin)->get(route('contratos.edit', $contrato));

    $respuesta->assertOk();
    $respuesta->assertSee('value="33"', false);
});
