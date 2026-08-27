<?php

use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\Locacion;
use App\Models\Recibo;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

function crearReciboParaSeguimiento(Locacion $locacion, array $extra = []): Recibo
{
    $contrato = Contrato::factory()->create([
        'locacion_id' => $locacion->id,
        'inquilino_id' => Inquilino::factory()->create()->id,
        'estado' => 'activo',
    ]);

    return Recibo::factory()->create(array_merge([
        'contrato_id' => $contrato->id,
        'locacion_id' => $locacion->id,
        'periodo' => now()->startOfMonth()->format('Y-m-d'),
        'monto_renta' => '1000.00',
    ], $extra));
}

test('specs/032: la pantalla muestra el avance de pago por locacion segun sus recibos vigentes', function () {
    $sinRecibo = Locacion::factory()->create(['nombre' => 'Local Sin Recibo', 'es_alquilable' => true]);

    $sinPagos = Locacion::factory()->create(['nombre' => 'Local Sin Pagos', 'es_alquilable' => true]);
    crearReciboParaSeguimiento($sinPagos);

    $conPagoParcial = Locacion::factory()->create(['nombre' => 'Local Con Pago Parcial', 'es_alquilable' => true]);
    $reciboParcial = crearReciboParaSeguimiento($conPagoParcial);
    $reciboParcial->pagos()->create(['monto' => 400, 'fecha_pago' => now()->format('Y-m-d')]);

    $pagado = Locacion::factory()->create(['nombre' => 'Local Pagado', 'es_alquilable' => true]);
    $reciboPagado = crearReciboParaSeguimiento($pagado);
    $reciboPagado->pagos()->create(['monto' => 1000, 'fecha_pago' => now()->format('Y-m-d')]);
    $reciboPagado->update(['estado' => 'pagado']);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.seguimiento.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('Local Sin Recibo');
    $respuesta->assertSee('Sin pagos');
    $respuesta->assertSee('S/ 400.00');
    $respuesta->assertSee('S/ 1,000.00');
    $respuesta->assertSee('Pagado');
});

test('specs/032: una locacion sin ningun recibo vigente en el periodo no muestra estado de pago ni accion', function () {
    $locacion = Locacion::factory()->create(['nombre' => 'Local Vacio', 'es_alquilable' => true]);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.seguimiento.index'));

    $respuesta->assertOk();
    $respuesta->assertDontSee(route('recibos.registroMasivo.recibosDelPeriodo', $locacion), false);
});

test('specs/032: un recibo anulado no cuenta en el seguimiento de pagos', function () {
    $locacion = Locacion::factory()->create(['nombre' => 'Local Anulado', 'es_alquilable' => true]);
    $recibo = crearReciboParaSeguimiento($locacion, ['estado' => 'anulado', 'fecha_anulacion' => now()]);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.seguimiento.index'));

    $respuesta->assertOk();
    $respuesta->assertDontSee(route('recibos.registroMasivo.recibosDelPeriodo', $locacion), false);
});

test('specs/032: cambiar de periodo actualiza el avance de pago mostrado', function () {
    $locacion = Locacion::factory()->create(['nombre' => 'Local Periodo Pasado', 'es_alquilable' => true]);
    crearReciboParaSeguimiento($locacion, ['periodo' => now()->subMonth()->startOfMonth()->format('Y-m-d')]);

    $periodoActual = $this->actingAs($this->admin)->get(route('pagos.seguimiento.index'));
    $periodoActual->assertDontSee(route('recibos.registroMasivo.recibosDelPeriodo', $locacion), false);

    $periodoAnterior = $this->actingAs($this->admin)->get(route('pagos.seguimiento.index', ['periodo' => now()->subMonth()->format('Y-m')]));
    $periodoAnterior->assertSee(route('recibos.registroMasivo.recibosDelPeriodo', $locacion), false);
});

test('specs/032: un usuario no autenticado no puede acceder al seguimiento de pagos', function () {
    $respuesta = $this->get(route('pagos.seguimiento.index'));

    $respuesta->assertRedirect(route('login'));
});

test('specs/033: la pantalla se titula Registro de Pagos', function () {
    $respuesta = $this->actingAs($this->admin)->get(route('pagos.seguimiento.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('Registro de Pagos');
});

test('specs/033: el menu principal ofrece un enlace a Registro de Pagos', function () {
    $respuesta = $this->actingAs($this->admin)->get(route('locaciones.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('Registro de Pagos');
    $respuesta->assertSee(route('pagos.seguimiento.index'), false);
});

test('specs/033: el enlace de Registro de Pagos queda marcado como activo en esa pantalla', function () {
    $respuesta = $this->actingAs($this->admin)->get(route('pagos.seguimiento.index'));

    $respuesta->assertOk();
    $respuesta->assertSee(
        'href="'.route('pagos.seguimiento.index').'" class="nav-link text-white d-flex align-items-center gap-2 py-2 active"',
        false
    );
});

test('specs/033: una locacion con saldo pendiente ofrece Registrar Pago hacia el recibo del periodo', function () {
    $locacion = Locacion::factory()->create(['nombre' => 'Local Con Saldo', 'es_alquilable' => true]);
    crearReciboParaSeguimiento($locacion);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.seguimiento.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('Registrar Pago');
    $respuesta->assertSee(route('recibos.registroMasivo.recibosDelPeriodo', $locacion), false);
});

test('specs/033: una locacion ya pagada por completo no ofrece Registrar Pago pero si Ver Pagos', function () {
    $locacion = Locacion::factory()->create(['nombre' => 'Local Ya Pagado', 'es_alquilable' => true]);
    $recibo = crearReciboParaSeguimiento($locacion);
    $recibo->pagos()->create(['monto' => 1000, 'fecha_pago' => now()->format('Y-m-d')]);
    $recibo->update(['estado' => 'pagado']);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.seguimiento.index'));

    $respuesta->assertOk();
    $respuesta->assertDontSee('Registrar Pago');
    $respuesta->assertSee('Ver Pagos');
    $respuesta->assertSee(route('recibos.registroMasivo.recibosDelPeriodo', $locacion), false);
});

test('specs/033: una locacion sin recibos vigentes en el periodo no ofrece Registrar Pago ni Ver Pagos', function () {
    $locacion = Locacion::factory()->create(['nombre' => 'Local Sin Nada', 'es_alquilable' => true]);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.seguimiento.index'));

    $respuesta->assertOk();
    $respuesta->assertDontSee(route('recibos.registroMasivo.recibosDelPeriodo', $locacion), false);
});

test('specs/034: una locacion sin pagos muestra la barra de progreso vacia', function () {
    $locacion = Locacion::factory()->create(['nombre' => 'Local Barra Vacia', 'es_alquilable' => true]);
    crearReciboParaSeguimiento($locacion);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.seguimiento.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('aria-valuenow="0"', false);
    $respuesta->assertSee('progress-bar bg-secondary', false);
});

test('specs/034: una locacion con pago parcial muestra la barra proporcional al avance', function () {
    $locacion = Locacion::factory()->create(['nombre' => 'Local Barra Parcial', 'es_alquilable' => true]);
    $recibo = crearReciboParaSeguimiento($locacion);
    $recibo->pagos()->create(['monto' => 400, 'fecha_pago' => now()->format('Y-m-d')]);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.seguimiento.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('aria-valuenow="40"', false);
    $respuesta->assertSee('progress-bar bg-warning', false);
});

test('specs/034: una locacion completamente pagada muestra la barra completa', function () {
    $locacion = Locacion::factory()->create(['nombre' => 'Local Barra Completa', 'es_alquilable' => true]);
    $recibo = crearReciboParaSeguimiento($locacion);
    $recibo->pagos()->create(['monto' => 1000, 'fecha_pago' => now()->format('Y-m-d')]);
    $recibo->update(['estado' => 'pagado']);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.seguimiento.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('aria-valuenow="100"', false);
    $respuesta->assertSee('progress-bar bg-success', false);
});

test('specs/034: una locacion sin recibos vigentes en el periodo no muestra ninguna barra de progreso', function () {
    Locacion::factory()->create(['nombre' => 'Local Sin Barra', 'es_alquilable' => true]);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.seguimiento.index'));

    $respuesta->assertOk();
    $respuesta->assertDontSee('role="progressbar"', false);
});
