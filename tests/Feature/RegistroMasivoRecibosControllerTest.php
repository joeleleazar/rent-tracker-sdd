<?php

use App\Models\ConceptoGastoFijo;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Models\User;
use App\Models\ValorConceptoContrato;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
    $this->luz = ConceptoGastoFijo::firstWhere('clave', 'luz');
    $this->pasadizo = ConceptoGastoFijo::firstWhere('nombre', 'Luz de Pasadizo');
    $this->seguridad = ConceptoGastoFijo::firstWhere('nombre', 'Seguridad');
});

function crearContratoActivo(Locacion $locacion, array $extra = []): Contrato
{
    $valores = $extra['valores'] ?? ['agua' => 50, 'pasadizo' => 30, 'seguridad' => 40];
    unset($extra['valores']);

    $contrato = Contrato::factory()->create(array_merge([
        'locacion_id' => $locacion->id,
        'inquilino_id' => Inquilino::factory()->create()->id,
        'estado' => 'activo',
        'fecha_inicio' => now()->subMonth()->format('Y-m-d'),
        'fecha_fin' => now()->addYear()->format('Y-m-d'),
        'monto_renta' => 1500,
    ], $extra));

    $conceptos = [
        'agua' => ConceptoGastoFijo::firstWhere('nombre', 'Agua'),
        'pasadizo' => ConceptoGastoFijo::firstWhere('nombre', 'Luz de Pasadizo'),
        'seguridad' => ConceptoGastoFijo::firstWhere('nombre', 'Seguridad'),
    ];
    foreach ($valores as $clave => $valor) {
        ValorConceptoContrato::create(['contrato_id' => $contrato->id, 'concepto_gasto_fijo_id' => $conceptos[$clave]->id, 'valor' => $valor]);
    }

    return $contrato;
}

function conceptosCompletosPayload(): array
{
    $agua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
    $luz = ConceptoGastoFijo::firstWhere('clave', 'luz');
    $pasadizo = ConceptoGastoFijo::firstWhere('nombre', 'Luz de Pasadizo');
    $seguridad = ConceptoGastoFijo::firstWhere('nombre', 'Seguridad');

    return [
        $agua->id => ['incluido' => '1', 'monto' => '50.00'],
        $luz->id => ['incluido' => '1', 'monto' => '0.00'],
        $pasadizo->id => ['incluido' => '1', 'monto' => '30.00'],
        $seguridad->id => ['incluido' => '1', 'monto' => '40.00'],
    ];
}

test('la pantalla muestra la situacion de cobro de cada locacion del periodo', function () {
    $sinContrato = Locacion::factory()->create(['nombre' => 'Local Sin Contrato', 'es_alquilable' => true]);
    $sinRecibo = Locacion::factory()->create(['nombre' => 'Local Sin Recibo', 'es_alquilable' => true]);
    crearContratoActivo($sinRecibo);

    $conRecibo = Locacion::factory()->create(['nombre' => 'Local Con Recibo', 'es_alquilable' => true]);
    $contrato = crearContratoActivo($conRecibo);
    Recibo::factory()->create([
        'contrato_id' => $contrato->id,
        'locacion_id' => $conRecibo->id,
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
        'monto_renta' => 1500,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.index'));

    $respuesta->assertOk();
    // ServicioConstruccionArbolLocaciones ordena por nombre — no por orden de creación.
    $respuesta->assertSeeInOrder(['Local Con Recibo', 'Local Sin Contrato', 'Local Sin Recibo']);
    $respuesta->assertSee('Sin contrato activo');
    $respuesta->assertSee(route('recibos.registroMasivo.modal', $sinRecibo), false);
    $respuesta->assertSee(route('recibos.registroMasivo.modal', $conRecibo), false);
});

test('un usuario no autenticado no puede acceder al registro masivo de recibos', function () {
    $respuesta = $this->get(route('recibos.registroMasivo.index'));

    $respuesta->assertRedirect(route('login'));
});

test('el modal muestra los conceptos disponibles con su monto sugerido', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    crearContratoActivo($locacion, ['monto_renta' => 1500]);
    LecturaMedidor::factory()->create([
        'locacion_id' => $locacion->id,
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
        'total' => 75,
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.modal', $locacion));

    $respuesta->assertOk();
    $respuesta->assertSee('1500.00');
    $respuesta->assertSee('50.00');
    $respuesta->assertSee('75.00');
    $respuesta->assertSee('30.00');
    $respuesta->assertSee('40.00');
});

test('el modal sugiere la renta prorrateada cuando el contrato no cubre el mes completo', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    crearContratoActivo($locacion, ['fecha_inicio' => '2026-08-15', 'fecha_fin' => '2027-08-14', 'monto_renta' => 1550]);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.modal', $locacion) . '?periodo=2026-08-01');

    $respuesta->assertOk();
    $respuesta->assertSee('17 días de 31');
    $respuesta->assertSee('850.00');
});

test('confirmar el modal genera el recibo y responde con la fila actualizada', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    crearContratoActivo($locacion, ['monto_renta' => 1500]);

    $respuesta = $this->actingAs($this->admin)->post(route('recibos.registroMasivo.store', $locacion), [
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
        'incluye_alquiler' => '1',
        'monto_renta' => '1500.00',
        'conceptos' => conceptosCompletosPayload(),
    ]);

    $respuesta->assertOk();
    $respuesta->assertSee('fila-recibo-' . $locacion->id, false);
    $respuesta->assertSee('Periodo completo');

    $recibo = Recibo::firstWhere('locacion_id', $locacion->id);
    expect($recibo)->not->toBeNull();
    expect($recibo->monto_renta)->toBe('1500.00');
    expect($recibo->conceptos()->count())->toBe(4);
});

test('rechaza confirmar el modal sin ningun concepto marcado', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    crearContratoActivo($locacion);

    $respuesta = $this->actingAs($this->admin)->post(route('recibos.registroMasivo.store', $locacion), [
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
    ]);

    $respuesta->assertStatus(422);
    expect(Recibo::count())->toBe(0);
});

test('rechaza un concepto ya cubierto por otro recibo del mismo periodo y locacion', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $contrato = crearContratoActivo($locacion);
    $periodo = now()->startOfMonth()->format('Y-m-d');

    $this->actingAs($this->admin)->post(route('recibos.registroMasivo.store', $locacion), [
        'periodo' => $periodo,
        'incluye_alquiler' => '1',
        'monto_renta' => '1500.00',
    ]);

    $respuesta = $this->actingAs($this->admin)->post(route('recibos.registroMasivo.store', $locacion), [
        'periodo' => $periodo,
        'incluye_alquiler' => '1',
        'monto_renta' => '1500.00',
    ]);

    $respuesta->assertStatus(422);
    $respuesta->assertSee('Renta');
    expect(Recibo::where('locacion_id', $locacion->id)->count())->toBe(1);
});

test('reabrir el modal tras un recibo parcial solo ofrece los conceptos restantes', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    crearContratoActivo($locacion);

    $this->actingAs($this->admin)->post(route('recibos.registroMasivo.store', $locacion), [
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
        'incluye_alquiler' => '1',
        'monto_renta' => '1500.00',
    ]);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.modal', $locacion));

    $respuesta->assertOk();
    $respuesta->assertDontSee('name="incluye_alquiler"', false);
    $respuesta->assertSee('name="conceptos[' . $this->agua->id . '][incluido]"', false);
});

test('genera un segundo recibo independiente cubriendo los conceptos restantes', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    crearContratoActivo($locacion, ['monto_renta' => 1500]);
    $periodo = now()->startOfMonth()->format('Y-m-d');

    $this->actingAs($this->admin)->post(route('recibos.registroMasivo.store', $locacion), [
        'periodo' => $periodo,
        'incluye_alquiler' => '1',
        'monto_renta' => '1500.00',
    ]);

    $respuesta = $this->actingAs($this->admin)->post(route('recibos.registroMasivo.store', $locacion), [
        'periodo' => $periodo,
        'conceptos' => conceptosCompletosPayload(),
    ]);

    $respuesta->assertOk();
    expect(Recibo::where('locacion_id', $locacion->id)->count())->toBe(2);

    $recibos = Recibo::where('locacion_id', $locacion->id)->with('conceptos')->get();
    $conceptosCubiertos = $recibos->flatMap(fn (Recibo $r) => $r->conceptos->pluck('concepto_gasto_fijo_id'));
    $rentaCubierta = $recibos->filter(fn (Recibo $r) => $r->monto_renta !== null)->count();

    expect($rentaCubierta)->toBe(1);
    expect($conceptosCubiertos->duplicates())->toBeEmpty();
    expect($conceptosCubiertos->sort()->values()->all())->toBe(
        collect([$this->agua->id, $this->luz->id, $this->pasadizo->id, $this->seguridad->id])->sort()->values()->all()
    );
});

test('el periodo agil expone flechas hx-get al mes anterior y siguiente sin boton de recarga completa', function () {
    $respuesta = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.index', ['periodo' => '2026-08']));

    $respuesta->assertOk();
    $respuesta->assertSee('id="contenido-periodo-recibos"', false);
    $respuesta->assertSee('hx-get="' . route('recibos.registroMasivo.index', ['periodo' => '2026-07']) . '"', false);
    $respuesta->assertSee('hx-get="' . route('recibos.registroMasivo.index', ['periodo' => '2026-09']) . '"', false);
    $respuesta->assertSee('hx-select="#contenido-periodo-recibos"', false);
    $respuesta->assertSee('hx-trigger="change"', false);
    $respuesta->assertDontSee('Cambiar Periodo');
});

test('muestra la cantidad de recibos y el total facturado por locacion, excluyendo anulados', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $contrato = crearContratoActivo($locacion, ['monto_renta' => 1500]);
    $periodo = now()->startOfMonth()->format('Y-m-d');

    $recibo1 = Recibo::factory()->create(['contrato_id' => $contrato->id, 'locacion_id' => $locacion->id, 'periodo' => $periodo, 'monto_renta' => 1500]);
    $recibo2 = Recibo::factory()->create(['contrato_id' => $contrato->id, 'locacion_id' => $locacion->id, 'periodo' => $periodo, 'monto_renta' => null]);
    $recibo2->conceptos()->create(['concepto_gasto_fijo_id' => $this->agua->id, 'monto' => 50]);
    $reciboAnulado = Recibo::factory()->create(['contrato_id' => $contrato->id, 'locacion_id' => $locacion->id, 'periodo' => $periodo, 'monto_renta' => 999, 'estado' => 'anulado']);

    $sinRecibos = Locacion::factory()->create(['es_alquilable' => true]);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.index', ['periodo' => now()->format('Y-m')]));

    $respuesta->assertOk();
    $respuesta->assertSee('2 recibos');
    $respuesta->assertSee('S/ 1,550.00');
    $respuesta->assertSee('0 recibos');
    $respuesta->assertDontSee('999.00');
});
