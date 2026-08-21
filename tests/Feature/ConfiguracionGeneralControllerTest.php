<?php

use App\Models\ConfiguracionGeneral;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();

    $this->datosValidos = fn (array $extra = []) => array_merge([
        'correo_notificaciones_vencimiento' => 'nuevo-admin@ejemplo.com',
        'tarifa_luz_por_unidad' => '0.75',
        'dias_anticipacion_alerta_pago' => '5',
    ], $extra);
});

test('un administrador autenticado puede ver la configuracion general', function () {
    $respuesta = $this->actingAs($this->admin)->get(route('configuracion.edit'));

    $respuesta->assertOk();
});

test('un administrador autenticado puede actualizar el correo la tarifa de luz y la anticipacion de pago', function () {
    $respuesta = $this->actingAs($this->admin)->put(route('configuracion.update'), ($this->datosValidos)([
        'dias_anticipacion_alerta_pago' => '10',
    ]));

    $respuesta->assertRedirect(route('configuracion.edit'));
    $configuracion = ConfiguracionGeneral::actual();
    expect($configuracion->correo_notificaciones_vencimiento)->toBe('nuevo-admin@ejemplo.com');
    expect($configuracion->tarifa_luz_por_unidad)->toBe('0.7500');
    expect($configuracion->dias_anticipacion_alerta_pago)->toBe(10);
});

test('rechaza un correo invalido', function () {
    $respuesta = $this->actingAs($this->admin)->put(route('configuracion.update'), ($this->datosValidos)([
        'correo_notificaciones_vencimiento' => 'no-es-un-correo',
    ]));

    $respuesta->assertSessionHasErrors('correo_notificaciones_vencimiento');
});

test('rechaza una tarifa de luz negativa', function () {
    $respuesta = $this->actingAs($this->admin)->put(route('configuracion.update'), ($this->datosValidos)([
        'tarifa_luz_por_unidad' => '-1',
    ]));

    $respuesta->assertSessionHasErrors('tarifa_luz_por_unidad');
});

test('rechaza dias de anticipacion de pago invalidos', function () {
    $respuesta = $this->actingAs($this->admin)->put(route('configuracion.update'), ($this->datosValidos)([
        'dias_anticipacion_alerta_pago' => '0',
    ]));

    $respuesta->assertSessionHasErrors('dias_anticipacion_alerta_pago');
});

test('un usuario no autenticado no puede acceder a la configuracion', function () {
    $respuesta = $this->get(route('configuracion.edit'));

    $respuesta->assertRedirect(route('login'));
});
