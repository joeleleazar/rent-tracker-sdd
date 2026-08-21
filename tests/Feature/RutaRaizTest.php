<?php

use App\Models\User;

test('un visitante sin sesion es redirigido al login desde la raiz', function () {
    $respuesta = $this->get('/');

    $respuesta->assertRedirect(route('login'));
});

test('un usuario autenticado es redirigido al panel principal desde la raiz', function () {
    $usuario = User::factory()->create();

    $respuesta = $this->actingAs($usuario)->get('/');

    $respuesta->assertRedirect(route('dashboard'));
});
