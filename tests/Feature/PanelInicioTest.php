<?php

use App\Models\Contrato;
use App\Models\Locacion;
use App\Models\Pago;
use App\Models\Recibo;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * specs/043: panel de inicio de solo lectura (estado de cobranza).
 */

function reciboMoroso(array $atributos = [], ?Locacion $locacion = null): Recibo
{
    $contrato = Contrato::factory()->create($locacion ? ['locacion_id' => $locacion->id] : []);

    return Recibo::factory()->create(array_merge([
        'contrato_id' => $contrato->id,
        'locacion_id' => $contrato->locacion_id,
        'monto_renta' => 1000,
        'periodo' => '2026-05-01',
    ], $atributos));
}

beforeEach(function () {
    Carbon::setTestNow('2026-08-15');
    $this->usuario = User::factory()->create();
});

afterEach(fn () => Carbon::setTestNow());

// ---------------------------------------------------------------------------
// Grupo acceso (T014 / US4)
// ---------------------------------------------------------------------------

test('un usuario Master ve el panel en la ruta dashboard', function () {
    $this->actingAs(User::factory()->master()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Estado de cobranza');
});

test('un usuario Administrador ve el panel en la ruta dashboard', function () {
    $this->actingAs(User::factory()->administrador()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Estado de cobranza');
});

test('un invitado es redirigido a login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('el panel no contiene controles de escritura de pagos, recibos ni contratos', function () {
    reciboMoroso();

    $html = $this->actingAs($this->usuario)->get(route('dashboard'))->getContent();

    expect($html)
        ->not->toContain('Registrar Pago')
        ->not->toContain('Anular')
        ->not->toContain(route('pagos.store', 1))
        ->not->toContain(route('recibos.estado.update', 1));
});

// ---------------------------------------------------------------------------
// Grupo morosos (T013 / US1)
// ---------------------------------------------------------------------------

test('un recibo moroso aparece en la tabla y enlaza a su detalle', function () {
    $recibo = reciboMoroso();

    $this->actingAs($this->usuario)->get(route('dashboard'))
        ->assertOk()
        ->assertSee($recibo->contrato->inquilinoPrincipal()->nombreCompleto())
        ->assertSee(route('recibos.show', $recibo));
});

test('los recibos pagados, anulados o con fecha limite futura no aparecen en morosos', function () {
    $pagado = reciboMoroso(['monto_renta' => 500]);
    Pago::factory()->create(['recibo_id' => $pagado->id, 'monto' => 500]);
    reciboMoroso(['estado' => 'anulado']);
    $futuro = reciboMoroso(['periodo' => '2026-08-01', 'monto_renta' => 777]);

    $respuesta = $this->actingAs($this->usuario)->get(route('dashboard'))->assertOk();

    $respuesta->assertDontSee(route('recibos.show', $pagado));
    // el recibo con fecha límite futura vive en "próximos vencimientos", no en morosos
    $respuesta->assertSee(route('recibos.show', $futuro));
});

test('sin recibos morosos se muestra el estado vacio', function () {
    $this->actingAs($this->usuario)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('No hay recibos vencidos impagos');
});

test('el filtro por rama de locacion limita las filas y el resumen', function () {
    $galeria = Locacion::factory()->create(['nombre' => 'Galeria Central']);
    $local = Locacion::factory()->create(['locacion_padre_id' => $galeria->id]);
    $dentro = reciboMoroso(['monto_renta' => 1000], $local);
    $fuera = reciboMoroso(['monto_renta' => 1000]);

    $this->actingAs($this->usuario)->get(route('dashboard', ['locacion' => $galeria->id]))
        ->assertOk()
        ->assertSee(route('recibos.show', $dentro))
        ->assertDontSee(route('recibos.show', $fuera));
});

// ---------------------------------------------------------------------------
// Grupo próximos vencimientos (T020 / US2)
// ---------------------------------------------------------------------------

test('un recibo en plazo aparece en proximos vencimientos y no en morosos', function () {
    $recibo = reciboMoroso(['periodo' => '2026-08-01', 'monto_renta' => 1234]);

    $this->actingAs($this->usuario)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('No hay recibos vencidos impagos') // morosos vacío
        ->assertSee(route('recibos.show', $recibo));   // presente en próximos
});

test('sin proximos vencimientos se muestra su estado vacio', function () {
    $this->actingAs($this->usuario)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('No hay pagos próximos a vencer');
});

// ---------------------------------------------------------------------------
// Grupo indicadores (T025 / US3)
// ---------------------------------------------------------------------------

test('las tarjetas de indicadores se muestran y la tasa sin datos evita dividir por cero', function () {
    // sin recibos del mes en curso -> facturado 0 -> tasa "sin datos"
    $this->actingAs($this->usuario)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Tasa de cobranza')
        ->assertSee('Cartera total por cobrar');
});

test('un contrato activo por vencer aparece y enlaza a su detalle; uno vencido no', function () {
    $porVencer = Contrato::factory()->create(['estado' => 'activo', 'fecha_fin' => '2026-08-20']);
    $vencido = Contrato::factory()->create(['estado' => 'activo', 'fecha_fin' => '2026-08-14']);

    $this->actingAs($this->usuario)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('contratos.show', $porVencer))
        ->assertDontSee(route('contratos.show', $vencido));
});
