<?php

use App\Models\Pago;
use App\Models\Recibo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

function enlaceCobro(Recibo $recibo): string
{
    return URL::signedRoute('cobro.recibo', $recibo);
}

test('la vista de escaneo responde 200 para un usuario autenticado', function () {
    $this->actingAs($this->admin)->get(route('cobro.index'))
        ->assertOk()
        ->assertSee('Cobro por QR')
        ->assertSee('Número de recibo');
});

test('buscar con un numero inexistente vuelve al escaner con el error', function () {
    $this->actingAs($this->admin)->from(route('cobro.index'))
        ->get(route('cobro.buscar', ['numero' => '999999']))
        ->assertRedirect(route('cobro.index'))
        ->assertSessionHasErrors('numero');
});

test('buscar con un numero no numerico vuelve con el error', function () {
    $this->actingAs($this->admin)->from(route('cobro.index'))
        ->get(route('cobro.buscar', ['numero' => 'abc']))
        ->assertRedirect(route('cobro.index'))
        ->assertSessionHasErrors('numero');
});

test('buscar con un numero valido redirige a la vista de cobro firmada', function () {
    $recibo = Recibo::factory()->create();

    $respuesta = $this->actingAs($this->admin)->get(route('cobro.buscar', ['numero' => (string) $recibo->id]));

    $respuesta->assertRedirect();
    expect($respuesta->headers->get('Location'))
        ->toContain('/cobro/recibo/'.$recibo->id)
        ->toContain('signature=');
});

test('abrir la vista de cobro sin firma valida devuelve 403', function () {
    $recibo = Recibo::factory()->create();

    $this->actingAs($this->admin)->get(route('cobro.recibo', $recibo))->assertForbidden();
});

test('con firma valida y saldo pendiente se muestra el formulario rapido', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => 500]);

    $this->actingAs($this->admin)->get(enlaceCobro($recibo))
        ->assertOk()
        ->assertSee('Registrar pago')
        ->assertSee('Saldo pendiente')
        ->assertSee('name="medio_pago"', false);
});

test('un recibo anulado muestra el aviso y no el formulario', function () {
    $recibo = Recibo::factory()->create(['estado' => 'anulado']);

    $this->actingAs($this->admin)->get(enlaceCobro($recibo))
        ->assertOk()
        ->assertSee('anulado')
        ->assertDontSee('Registrar pago');
});

test('un recibo ya saldado muestra el aviso y no el formulario', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => 300]);
    Pago::factory()->create(['recibo_id' => $recibo->id, 'monto' => 300]);

    $this->actingAs($this->admin)->get(enlaceCobro($recibo))
        ->assertOk()
        ->assertSee('saldado')
        ->assertDontSee('Registrar pago');
});

test('registrar el pago desde el formulario rapido baja el saldo y redirige con el mensaje', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => 500]);

    $respuesta = $this->actingAs($this->admin)->post(route('cobro.pago.store', $recibo), [
        'monto' => '200',
        'fecha_pago' => now()->format('Y-m-d'),
        'medio_pago' => 'Efectivo',
    ]);

    $respuesta->assertRedirect();
    expect($respuesta->headers->get('Location'))->toContain('/cobro/recibo/'.$recibo->id);
    $respuesta->assertSessionHas('mensaje', 'Pago registrado correctamente.');

    $pago = $recibo->pagos()->sole();
    expect((float) $pago->monto)->toBe(200.0)
        ->and($pago->medio_pago)->toBe('Efectivo')
        ->and($recibo->fresh()->load('pagos')->saldoPendiente())->toBe(300.0);
});

test('registrar el pago con evidencia guarda el archivo sobre el pago', function () {
    Storage::fake('local');
    $recibo = Recibo::factory()->create(['monto_renta' => 500]);

    $this->actingAs($this->admin)->post(route('cobro.pago.store', $recibo), [
        'monto' => '500',
        'fecha_pago' => now()->format('Y-m-d'),
        'evidencia' => UploadedFile::fake()->image('comprobante.jpg'),
    ])->assertRedirect();

    $pago = $recibo->pagos()->sole();
    expect($pago->tieneEvidencia())->toBeTrue();
    Storage::disk('local')->assertExists($pago->evidencia_ruta);
});

test('un monto mayor al saldo pendiente es rechazado sin registrar pago', function () {
    $recibo = Recibo::factory()->create(['monto_renta' => 100]);

    $this->actingAs($this->admin)->from(enlaceCobro($recibo))->post(route('cobro.pago.store', $recibo), [
        'monto' => '999',
        'fecha_pago' => now()->format('Y-m-d'),
    ])->assertSessionHasErrors('monto');

    expect($recibo->pagos()->count())->toBe(0);
});

test('el comprobante del recibo incluye el codigo QR de cobro', function () {
    $recibo = Recibo::factory()->create();

    $this->actingAs($this->admin)->get(route('recibos.comprobante', $recibo))
        ->assertOk()
        ->assertSee('data:image/png;base64,', false)
        ->assertSee('Cobro por QR');
});
