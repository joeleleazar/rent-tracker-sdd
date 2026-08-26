<?php

use App\Models\ConceptoGastoFijo;
use App\Models\Contrato;
use App\Models\Inquilino;
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
    $respuesta->assertSee(route('locaciones.recibos.create', $sinRecibo), false);
    $respuesta->assertSee(route('locaciones.recibos.create', $conRecibo), false);
});

test('un recibo anulado deja sus conceptos disponibles otra vez en la pantalla (specs/026)', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $contrato = crearContratoActivo($locacion);
    $periodo = now()->startOfMonth();

    $recibo = Recibo::factory()->create([
        'contrato_id' => $contrato->id,
        'locacion_id' => $locacion->id,
        'periodo' => $periodo->format('Y-m-d'),
        'monto_renta' => 1500,
    ]);
    foreach ([$this->agua->id => 50, $this->luz->id => 0, $this->pasadizo->id => 30, $this->seguridad->id => 40] as $conceptoId => $monto) {
        $recibo->conceptos()->create(['concepto_gasto_fijo_id' => $conceptoId, 'monto' => $monto]);
    }

    $antesDeAnular = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.index'));
    $antesDeAnular->assertSee('Periodo completo');
    $antesDeAnular->assertDontSee(route('locaciones.recibos.create', $locacion), false);

    $recibo->update(['estado' => 'anulado']);

    $despuesDeAnular = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.index'));
    $despuesDeAnular->assertDontSee('Periodo completo');
    $despuesDeAnular->assertSee(route('locaciones.recibos.create', $locacion), false);
    // specs/024: el conteo/total por locacion ya excluye anulados; sigue siendo asi (regresion).
    $despuesDeAnular->assertSee('0 recibos');
});

test('un usuario no autenticado no puede acceder al registro masivo de recibos', function () {
    $respuesta = $this->get(route('recibos.registroMasivo.index'));

    $respuesta->assertRedirect(route('login'));
});

test('specs/026: las rutas del modal de generacion masiva ya no existen', function () {
    // La generacion de recibo desde el registro masivo pasa a reutilizar la pagina
    // individual (locaciones.recibos.create/store, ya probada en ReciboControllerTest) en
    // vez de un modal propio — research.md Decision 3. Este test confirma que el modal y
    // su endpoint de guardado quedaron retirados, no solo que dejaron de usarse.
    expect(fn () => route('recibos.registroMasivo.modal', 1))->toThrow(\Symfony\Component\Routing\Exception\RouteNotFoundException::class);
    expect(fn () => route('recibos.registroMasivo.store', 1))->toThrow(\Symfony\Component\Routing\Exception\RouteNotFoundException::class);
});

test('specs/026: "Generar Recibo" es un enlace normal a la pagina individual con el periodo visible', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    crearContratoActivo($locacion, ['monto_renta' => 1500]);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.index', ['periodo' => '2026-08']));

    $respuesta->assertOk();
    $respuesta->assertSee(route('locaciones.recibos.create', ['locacion' => $locacion, 'periodo' => '2026-08']), false);
    $respuesta->assertDontSee('id="contenido-modal-recibo"', false);
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

test('el selector de periodo no muestra ningun boton de confirmacion junto a las flechas', function () {
    // specs/028: réplica de specs/027 (lecturas/registro-masivo) — la navegación oficial queda
    // limitada a las flechas y al autoenvío del campo de fecha, ya cubiertos por el test anterior.
    $respuesta = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.index'));

    $respuesta->assertOk();
    preg_match('/<button[^>]*>\s*Ir\s*<\/button>/s', $respuesta->getContent(), $coincidencia);
    expect($coincidencia)->toBeEmpty();
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

test('specs/026 US3: recibosDelPeriodo redirige directo con un solo recibo', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $contrato = crearContratoActivo($locacion);
    $periodo = now()->startOfMonth()->format('Y-m-d');
    $recibo = Recibo::factory()->create(['contrato_id' => $contrato->id, 'locacion_id' => $locacion->id, 'periodo' => $periodo, 'monto_renta' => 1500]);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.recibosDelPeriodo', ['locacion' => $locacion, 'periodo' => now()->format('Y-m')]));

    $respuesta->assertRedirect(route('recibos.show', $recibo));
});

test('specs/026 US3: recibosDelPeriodo lista cuando hay varios recibos, incluyendo anulados', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $contrato = crearContratoActivo($locacion);
    $periodo = now()->startOfMonth()->format('Y-m-d');
    $recibo1 = Recibo::factory()->create(['contrato_id' => $contrato->id, 'locacion_id' => $locacion->id, 'periodo' => $periodo, 'monto_renta' => 1500]);
    $recibo2 = Recibo::factory()->create(['contrato_id' => $contrato->id, 'locacion_id' => $locacion->id, 'periodo' => $periodo, 'monto_renta' => null, 'estado' => 'anulado']);
    $recibo2->conceptos()->create(['concepto_gasto_fijo_id' => $this->agua->id, 'monto' => 50]);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.recibosDelPeriodo', ['locacion' => $locacion, 'periodo' => now()->format('Y-m')]));

    $respuesta->assertOk();
    $respuesta->assertSee(route('recibos.show', $recibo1), false);
    $respuesta->assertSee(route('recibos.show', $recibo2), false);
    $respuesta->assertSee('Anulado');
});

test('specs/026 US3: recibosDelPeriodo sin ningun recibo redirige de vuelta al indice', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.recibosDelPeriodo', ['locacion' => $locacion, 'periodo' => now()->format('Y-m')]));

    $respuesta->assertRedirect(route('recibos.registroMasivo.index', ['periodo' => now()->format('Y-m')]));
});

test('specs/026 US3: el indice muestra "Ver Recibos" solo cuando hay al menos un recibo del periodo, de cualquier estado', function () {
    $conRecibo = Locacion::factory()->create(['es_alquilable' => true]);
    $contrato = crearContratoActivo($conRecibo);
    $periodo = now()->startOfMonth()->format('Y-m-d');
    Recibo::factory()->create(['contrato_id' => $contrato->id, 'locacion_id' => $conRecibo->id, 'periodo' => $periodo, 'estado' => 'anulado', 'monto_renta' => 1500]);

    $sinRecibo = Locacion::factory()->create(['es_alquilable' => true]);
    crearContratoActivo($sinRecibo);

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.index', ['periodo' => now()->format('Y-m')]));

    $respuesta->assertOk();
    $respuesta->assertSee(route('recibos.registroMasivo.recibosDelPeriodo', ['locacion' => $conRecibo, 'periodo' => now()->format('Y-m')]), false);
    $respuesta->assertDontSee(route('recibos.registroMasivo.recibosDelPeriodo', ['locacion' => $sinRecibo, 'periodo' => now()->format('Y-m')]), false);
});

test('specs/029: un concepto con valor de referencia configurado pero sin recibo se muestra disponible, no cubierto', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    $contrato = crearContratoActivo($locacion, ['monto_renta' => 1500]);
    $internet = ConceptoGastoFijo::factory()->create(['nombre' => 'Internet Prueba']);
    ValorConceptoContrato::create(['contrato_id' => $contrato->id, 'concepto_gasto_fijo_id' => $internet->id, 'valor' => 50]);
    $periodo = now()->startOfMonth();

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.index', ['periodo' => $periodo->format('Y-m')]));

    $respuesta->assertOk();
    $respuesta->assertSee('<span class="badge bg-light text-dark border">Internet Prueba</span>', false);
    $respuesta->assertDontSee('<i class="bi bi-check-lg" aria-hidden="true"></i> Internet Prueba', false);

    $recibo = Recibo::factory()->create(['contrato_id' => $contrato->id, 'locacion_id' => $locacion->id, 'periodo' => $periodo->format('Y-m-d'), 'monto_renta' => null]);
    $recibo->conceptos()->create(['concepto_gasto_fijo_id' => $internet->id, 'monto' => 50]);

    $conRecibo = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.index', ['periodo' => $periodo->format('Y-m')]));
    $conRecibo->assertSee($internet->nombre);
    $conRecibo->assertSee(route('recibos.show', $recibo), false);
    $conRecibo->assertDontSee('<span class="badge bg-light text-dark border">' . $internet->nombre . '</span>', false);

    $recibo->update(['estado' => 'anulado']);

    $anulado = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.index', ['periodo' => $periodo->format('Y-m')]));
    $anulado->assertSee('<span class="badge bg-light text-dark border">Internet Prueba</span>', false);
});

test('specs/029: Renta sin ningun recibo vigente que la cubra se muestra disponible', function () {
    $locacion = Locacion::factory()->create(['es_alquilable' => true]);
    crearContratoActivo($locacion, ['monto_renta' => 1500]);
    $periodo = now()->startOfMonth()->format('Y-m');

    $respuesta = $this->actingAs($this->admin)->get(route('recibos.registroMasivo.index', ['periodo' => $periodo]));

    $respuesta->assertOk();
    $respuesta->assertSee('<span class="badge bg-light text-dark border">Renta</span>', false);
});
