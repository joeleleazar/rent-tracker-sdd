<?php

use App\Models\User;

/**
 * specs/040 (FR-017): se retiró el auto-registro público de cuentas. La única
 * vía de alta es un Master desde la sección de gestión de usuarios.
 */

test('la ruta GET de registro ya no existe', function () {
    $this->get('/register')->assertNotFound();
});

test('la ruta POST de registro ya no existe y no crea cuentas', function () {
    $antes = User::count();

    $this->post('/register', [
        'name' => 'Intruso',
        'email' => 'intruso@ejemplo.com',
        'password' => 'clave-que-no-deberia-funcionar',
        'password_confirmation' => 'clave-que-no-deberia-funcionar',
    ])->assertNotFound();

    expect(User::count())->toBe($antes);
    expect(User::where('email', 'intruso@ejemplo.com')->exists())->toBeFalse();
});

test('el nombre de ruta register ya no esta registrado', function () {
    expect(\Illuminate\Support\Facades\Route::has('register'))->toBeFalse();
});
