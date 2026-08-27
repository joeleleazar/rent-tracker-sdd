<?php

use App\Models\ConfiguracionGeneral;
use App\Models\Contrato;
use App\Models\Inquilino;
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
    ]);
    $this->recibo = Recibo::factory()->create([
        'contrato_id' => $this->contrato->id,
        'locacion_id' => $this->locacion->id,
        'monto_renta' => '1000.00',
    ]);
});

test('specs/035: el comprobante de un pago parcial muestra el monto de ese pago, el acumulado y el saldo pendiente', function () {
    $primerPago = $this->recibo->pagos()->create(['monto' => 300, 'fecha_pago' => now()->format('Y-m-d'), 'registrado_por_id' => $this->admin->id]);
    $segundoPago = $this->recibo->pagos()->create(['monto' => 200, 'fecha_pago' => now()->format('Y-m-d'), 'registrado_por_id' => $this->admin->id]);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.comprobante', $segundoPago));

    $respuesta->assertOk();
    $respuesta->assertSee('Comprobante de Pago');
    $respuesta->assertSee((string) $this->recibo->id);
    $respuesta->assertSee((string) $segundoPago->id);
    $respuesta->assertSee($this->inquilino->nombreCompleto());
    $respuesta->assertSee($this->locacion->nombre);
    // Monto de este pago (segundo), acumulado (300+200=500) y saldo pendiente (1000-500=500).
    $respuesta->assertSee('200.00');
    $respuesta->assertSee('500.00');
    $respuesta->assertSee('1,000.00');
});

test('specs/035: el comprobante de un pago que completa el total muestra saldo pendiente en cero', function () {
    $pago = $this->recibo->pagos()->create(['monto' => 1000, 'fecha_pago' => now()->format('Y-m-d')]);
    $this->recibo->update(['estado' => 'pagado']);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.comprobante', $pago));

    $respuesta->assertOk();
    $respuesta->assertSee('0.00');
});

test('specs/035: el comprobante muestra a quien recibe el pago cuando esta configurado', function () {
    ConfiguracionGeneral::actual()->update(['nombre_propietario' => 'Carlos Alberto Mendoza Ibáñez']);
    $pago = $this->recibo->pagos()->create(['monto' => 300, 'fecha_pago' => now()->format('Y-m-d')]);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.comprobante', $pago));

    $respuesta->assertOk();
    $respuesta->assertSee('Carlos Alberto Mendoza Ibáñez');
});

test('specs/035: el comprobante de un pago sigue disponible aunque el recibo ya este anulado', function () {
    $pago = $this->recibo->pagos()->create(['monto' => 300, 'fecha_pago' => now()->format('Y-m-d')]);
    $this->recibo->update(['estado' => 'anulado', 'fecha_anulacion' => now()]);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.comprobante', $pago));

    $respuesta->assertOk();
    $respuesta->assertSee('300.00');
});

test('specs/035: el comprobante refleja el monto actualizado si el pago se edita despues de exportarlo', function () {
    $pago = $this->recibo->pagos()->create(['monto' => 300, 'fecha_pago' => now()->format('Y-m-d')]);

    $primeraExportacion = $this->actingAs($this->admin)->get(route('pagos.comprobante', $pago));
    $primeraExportacion->assertSee('300.00');

    $pago->update(['monto' => 450]);

    $segundaExportacion = $this->actingAs($this->admin)->get(route('pagos.comprobante', $pago));
    $segundaExportacion->assertSee('450.00');
});

test('specs/035: un usuario no autenticado no puede ver el comprobante de un pago', function () {
    $pago = $this->recibo->pagos()->create(['monto' => 300, 'fecha_pago' => now()->format('Y-m-d')]);

    $respuesta = $this->get(route('pagos.comprobante', $pago));

    $respuesta->assertRedirect(route('login'));
});

test('specs/036: el comprobante del primer pago no muestra el estado actual del recibo ya completo', function () {
    $primerPago = $this->recibo->pagos()->create(['monto' => 300, 'fecha_pago' => now()->format('Y-m-d')]);
    $this->recibo->pagos()->create(['monto' => 700, 'fecha_pago' => now()->format('Y-m-d')]);
    $this->recibo->update(['estado' => 'pagado']);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.comprobante', $primerPago));

    $respuesta->assertOk();
    // El total del recibo (1,000.00) sigue mostrandose sin cambios (no se historiza,
    // research.md Decision 3); lo que NO debe aparecer es el acumulado/saldo ACTUALES del
    // recibo ya completo (1,000.00 pagado / 0.00 pendiente) en el lugar de "Pagado hasta
    // ahora"/"Saldo pendiente" — deben mostrar el historico de este pago (300.00/700.00).
    $respuesta->assertSeeInOrder(['Pagado hasta ahora', 'S/ 300.00', 'Saldo pendiente', 'S/ 700.00'], false);
});

test('specs/036: el comprobante del segundo pago que completa el total si muestra saldo en cero', function () {
    $this->recibo->pagos()->create(['monto' => 300, 'fecha_pago' => now()->format('Y-m-d')]);
    $segundoPago = $this->recibo->pagos()->create(['monto' => 700, 'fecha_pago' => now()->format('Y-m-d')]);
    $this->recibo->update(['estado' => 'pagado']);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.comprobante', $segundoPago));

    $respuesta->assertOk();
    $respuesta->assertSee('1,000.00');
    $respuesta->assertSee('0.00');
});

test('specs/036: editar el monto de un pago anterior recalcula el acumulado historico de un pago intermedio', function () {
    $primerPago = $this->recibo->pagos()->create(['monto' => 200, 'fecha_pago' => now()->format('Y-m-d')]);
    $segundoPago = $this->recibo->pagos()->create(['monto' => 300, 'fecha_pago' => now()->format('Y-m-d')]);
    $this->recibo->pagos()->create(['monto' => 200, 'fecha_pago' => now()->format('Y-m-d')]);

    $primerPago->update(['monto' => 400]);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.comprobante', $segundoPago));

    $respuesta->assertOk();
    // Acumulado hasta el segundo pago tras la edicion: 400 (primero editado) + 300 (segundo) = 700.
    // El total de pagos actuales del recibo es 900 (400+300+200) — si el calculo fuera en vivo en vez
    // de historico, aparecería 900.00 en vez de 700.00.
    $respuesta->assertSee('700.00');
    $respuesta->assertDontSee('900.00');
});

test('specs/039: el comprobante reserva un area en blanco ampliada antes de la linea de firma', function () {
    $pago = $this->recibo->pagos()->create(['monto' => 300, 'fecha_pago' => now()->format('Y-m-d')]);

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.comprobante', $pago));

    $respuesta->assertOk();
    $respuesta->assertSee('height: 5rem', false);
    $respuesta->assertSee('Firma de quien recibe el pago');
});

test('specs/036: eliminar un pago anterior recalcula el acumulado historico de un pago intermedio', function () {
    $primerPago = $this->recibo->pagos()->create(['monto' => 200, 'fecha_pago' => now()->format('Y-m-d')]);
    $segundoPago = $this->recibo->pagos()->create(['monto' => 300, 'fecha_pago' => now()->format('Y-m-d')]);
    $this->recibo->pagos()->create(['monto' => 200, 'fecha_pago' => now()->format('Y-m-d')]);

    $primerPago->delete();

    $respuesta = $this->actingAs($this->admin)->get(route('pagos.comprobante', $segundoPago));

    $respuesta->assertOk();
    // Acumulado hasta el segundo pago tras eliminar el primero: solo 300 (el segundo por si solo).
    // El total de pagos actuales del recibo es 500 (300+200) — si el calculo fuera en vivo en vez de
    // historico, aparecería 500.00 en vez de 300.00.
    $respuesta->assertSee('300.00');
    $respuesta->assertDontSee('500.00');
});
