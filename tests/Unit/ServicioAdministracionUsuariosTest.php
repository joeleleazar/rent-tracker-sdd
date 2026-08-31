<?php

use App\Enums\PerfilUsuario;
use App\Exceptions\AutoproteccionUsuarioException;
use App\Exceptions\UltimoMasterActivoException;
use App\Models\User;
use App\Services\ServicioAdministracionUsuarios;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->servicio = app(ServicioAdministracionUsuarios::class);
});

test('crear da de alta una cuenta activa con el correo normalizado y verificado y la contrasena hasheada', function () {
    $master = User::factory()->master()->create();

    $usuario = $this->servicio->crear([
        'name' => 'Nueva Persona',
        'email' => '  NUEVA@Ejemplo.com ',
        'perfil' => PerfilUsuario::Administrador,
        'password' => 'clave-super-segura',
    ], $master);

    expect($usuario->email)->toBe('nueva@ejemplo.com');
    expect($usuario->perfil)->toBe(PerfilUsuario::Administrador);
    expect($usuario->activo)->toBeTrue();
    expect($usuario->email_verified_at)->not->toBeNull();
    expect(Hash::check('clave-super-segura', $usuario->password))->toBeTrue();
});

test('con dos masters activos se puede desactivar uno', function () {
    $ejecutor = User::factory()->master()->create();
    $otro = User::factory()->master()->create();

    $this->servicio->cambiarEstado($otro, false, $ejecutor);

    expect($otro->fresh()->activo)->toBeFalse();
});

test('no se puede desactivar al ultimo master activo', function () {
    $ultimoMaster = User::factory()->master()->create();
    $ejecutor = User::factory()->administrador()->create();

    expect(fn () => $this->servicio->cambiarEstado($ultimoMaster, false, $ejecutor))
        ->toThrow(UltimoMasterActivoException::class);
    expect($ultimoMaster->fresh()->activo)->toBeTrue();
});

test('no se puede degradar de perfil al ultimo master activo', function () {
    $ultimoMaster = User::factory()->master()->create();
    $ejecutor = User::factory()->administrador()->create();

    expect(fn () => $this->servicio->cambiarPerfil($ultimoMaster, PerfilUsuario::Administrador, $ejecutor))
        ->toThrow(UltimoMasterActivoException::class);
    expect($ultimoMaster->fresh()->perfil)->toBe(PerfilUsuario::Master);
});

test('un usuario no puede desactivar su propia cuenta', function () {
    $master = User::factory()->master()->create();
    User::factory()->master()->create(); // otro master activo: descarta la invariante del último master

    expect(fn () => $this->servicio->cambiarEstado($master, false, $master))
        ->toThrow(AutoproteccionUsuarioException::class);
});

test('un master no puede quitarse a si mismo el perfil siendo el unico master activo', function () {
    $master = User::factory()->master()->create();

    expect(fn () => $this->servicio->cambiarPerfil($master, PerfilUsuario::Administrador, $master))
        ->toThrow(AutoproteccionUsuarioException::class);
});

test('un master puede degradarse a si mismo si hay otro master activo', function () {
    $master = User::factory()->master()->create();
    User::factory()->master()->create();

    $this->servicio->cambiarPerfil($master, PerfilUsuario::Administrador, $master);

    expect($master->fresh()->perfil)->toBe(PerfilUsuario::Administrador);
});

test('no se puede eliminar al ultimo master activo', function () {
    $ultimoMaster = User::factory()->master()->create();
    $ejecutor = User::factory()->administrador()->create();

    expect(fn () => $this->servicio->eliminar($ultimoMaster, $ejecutor))
        ->toThrow(UltimoMasterActivoException::class);
    expect(User::find($ultimoMaster->id))->not->toBeNull();
});

test('cada operacion deja un evento en el canal de seguridad con actor y cuenta afectada', function () {
    $canal = Mockery::mock();
    $canal->shouldReceive('info')
        ->once()
        ->withArgs(function (string $accion, array $contexto) {
            return $accion === 'usuario.creado'
                && array_key_exists('actor_id', $contexto)
                && array_key_exists('usuario_afectado_id', $contexto);
        });

    Log::shouldReceive('channel')->with('seguridad')->andReturn($canal);

    $master = User::factory()->master()->create();

    $this->servicio->crear([
        'name' => 'Con Log',
        'email' => 'con.log@ejemplo.com',
        'perfil' => PerfilUsuario::Administrador,
        'password' => 'clave-de-prueba',
    ], $master);
});
