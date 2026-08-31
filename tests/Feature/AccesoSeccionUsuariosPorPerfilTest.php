<?php

use App\Models\User;

/**
 * specs/040 US2: el perfil Administrador conserva todo el acceso operativo pero
 * no ve ni puede invocar ninguna función de la sección de gestión de usuarios;
 * un invitado tampoco. Sólo el perfil Master accede.
 */

dataset('rutas de la seccion de usuarios', function () {
    return [
        'index' => ['get', fn (User $u) => route('usuarios.index')],
        'create' => ['get', fn (User $u) => route('usuarios.create')],
        'store' => ['post', fn (User $u) => route('usuarios.store')],
        'edit' => ['get', fn (User $u) => route('usuarios.edit', $u)],
        'update' => ['put', fn (User $u) => route('usuarios.update', $u)],
        'contrasena.update' => ['put', fn (User $u) => route('usuarios.contrasena.update', $u)],
        'perfil.update' => ['put', fn (User $u) => route('usuarios.perfil.update', $u)],
        'estado.update' => ['put', fn (User $u) => route('usuarios.estado.update', $u)],
        'destroy' => ['delete', fn (User $u) => route('usuarios.destroy', $u)],
    ];
});

test('un administrador recibe 403 en toda ruta de la seccion de usuarios', function (string $metodo, Closure $url) {
    $admin = User::factory()->administrador()->create();
    $objetivo = User::factory()->create();

    $this->actingAs($admin)->$metodo($url($objetivo))->assertForbidden();
})->with('rutas de la seccion de usuarios');

test('un invitado es redirigido al login en toda ruta de la seccion de usuarios', function (string $metodo, Closure $url) {
    $objetivo = User::factory()->create();

    $this->$metodo($url($objetivo))->assertRedirect(route('login'));
})->with('rutas de la seccion de usuarios');

test('un master con cuenta desactivada es expulsado al login', function () {
    $master = User::factory()->master()->inactivo()->create();

    $this->actingAs($master)->get(route('usuarios.index'))->assertRedirect(route('login'));
});

test('el enlace de navegacion a usuarios no se muestra para un administrador', function () {
    $admin = User::factory()->administrador()->create();

    $this->actingAs($admin)->get(route('locaciones.index'))
        ->assertOk()
        ->assertDontSee(route('usuarios.index'));
});

test('el enlace de navegacion a usuarios se muestra para un master', function () {
    $master = User::factory()->master()->create();

    $this->actingAs($master)->get(route('locaciones.index'))
        ->assertOk()
        ->assertSee(route('usuarios.index'));
});

test('un administrador conserva el acceso a las secciones operativas del negocio', function () {
    $admin = User::factory()->administrador()->create();

    $this->actingAs($admin)->get(route('locaciones.index'))->assertOk();
    $this->actingAs($admin)->get(route('recibos.registroMasivo.index'))->assertOk();
    $this->actingAs($admin)->get(route('pagos.seguimiento.index'))->assertOk();
});
