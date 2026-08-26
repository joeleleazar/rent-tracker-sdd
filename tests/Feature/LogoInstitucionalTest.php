<?php

use App\Models\Recibo;
use App\Models\User;

test('specs/030 US1: el login muestra el logo de Nicson Plaza', function () {
    $respuesta = $this->get(route('login'));

    $respuesta->assertOk();
    $respuesta->assertSee('src="' . asset('images/logo-nicson-plaza.png') . '"', false);
});

test('specs/030 US2: una pantalla autenticada muestra el logo enlazando al inicio', function () {
    $admin = User::factory()->create();

    $respuesta = $this->actingAs($admin)->get(route('locaciones.index'));

    $respuesta->assertOk();
    $respuesta->assertSee('src="' . asset('images/logo-nicson-plaza.png') . '"', false);
    $respuesta->assertSee('href="' . url('/') . '"', false);
});

test('specs/030 US3: el comprobante de un recibo muestra el logo', function () {
    $admin = User::factory()->create();
    $recibo = Recibo::factory()->create();

    $respuesta = $this->actingAs($admin)->get(route('recibos.comprobante', $recibo));

    $respuesta->assertOk();
    $respuesta->assertSee('src="' . asset('images/logo-nicson-plaza.png') . '"', false);
});

test('specs/030 US4: el icono de pestaña del navegador es el logo, autenticado y sin autenticar', function () {
    $sinAutenticar = $this->get(route('login'));
    $sinAutenticar->assertOk();
    $sinAutenticar->assertSee('<link rel="icon" type="image/png" href="' . asset('images/logo-nicson-plaza.png') . '">', false);

    $admin = User::factory()->create();
    $autenticado = $this->actingAs($admin)->get(route('locaciones.index'));
    $autenticado->assertOk();
    $autenticado->assertSee('<link rel="icon" type="image/png" href="' . asset('images/logo-nicson-plaza.png') . '">', false);
});
