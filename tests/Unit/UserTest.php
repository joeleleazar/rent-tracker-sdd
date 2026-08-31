<?php

use App\Enums\PerfilUsuario;
use App\Models\User;

test('el perfil se castea al enum PerfilUsuario', function () {
    $usuario = User::factory()->master()->create();

    expect($usuario->perfil)->toBe(PerfilUsuario::Master);
    expect($usuario->fresh()->perfil)->toBeInstanceOf(PerfilUsuario::class);
});

test('activos excluye las cuentas desactivadas', function () {
    User::factory()->count(2)->create();
    User::factory()->inactivo()->create();

    expect(User::activos()->count())->toBe(2);
});

test('esMaster distingue el perfil Master del Administrador', function () {
    expect(User::factory()->master()->create()->esMaster())->toBeTrue();
    expect(User::factory()->administrador()->create()->esMaster())->toBeFalse();
});

test('estaActivo refleja el estado de la cuenta', function () {
    expect(User::factory()->create()->estaActivo())->toBeTrue();
    expect(User::factory()->inactivo()->create()->estaActivo())->toBeFalse();
});
