<?php

use App\Enums\PerfilUsuario;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->master = User::factory()->master()->create();
});

// -------------------- Alta (US1) --------------------

test('el master ve el listado con las cuentas existentes', function () {
    $otro = User::factory()->administrador()->create(['name' => 'Persona Listada']);

    $this->actingAs($this->master)->get(route('usuarios.index'))
        ->assertOk()
        ->assertSee('Persona Listada')
        ->assertSee($otro->email);
});

test('el master abre el formulario de alta y el de edicion sin errores', function () {
    $usuario = User::factory()->administrador()->create();

    $this->actingAs($this->master)->get(route('usuarios.create'))->assertOk()->assertSee('Agregar usuario');
    $this->actingAs($this->master)->get(route('usuarios.edit', $usuario))->assertOk()->assertSee($usuario->email);
});

test('el master crea un usuario nuevo que queda operativo', function () {
    $respuesta = $this->actingAs($this->master)->post(route('usuarios.store'), [
        'name' => 'Colaborador Nuevo',
        'email' => 'colaborador@ejemplo.com',
        'perfil' => PerfilUsuario::Administrador->value,
        'password' => 'clave-inicial-1',
        'password_confirmation' => 'clave-inicial-1',
    ]);

    $respuesta->assertRedirect(route('usuarios.index'));
    $respuesta->assertSessionHas('mensaje');

    $usuario = User::firstWhere('email', 'colaborador@ejemplo.com');
    expect($usuario)->not->toBeNull();
    expect($usuario->perfil)->toBe(PerfilUsuario::Administrador);
    expect($usuario->activo)->toBeTrue();
    expect($usuario->email_verified_at)->not->toBeNull();

    auth()->logout();
    $this->post(route('login'), [
        'email' => 'colaborador@ejemplo.com',
        'password' => 'clave-inicial-1',
    ])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($usuario);
});

test('rechaza un correo ya existente aunque cambie la capitalizacion o los espacios', function () {
    User::factory()->create(['email' => 'existente@ejemplo.com']);

    $this->actingAs($this->master)->post(route('usuarios.store'), [
        'name' => 'Otro',
        'email' => '  Existente@Ejemplo.COM ',
        'perfil' => PerfilUsuario::Administrador->value,
        'password' => 'clave-inicial-1',
        'password_confirmation' => 'clave-inicial-1',
    ])->assertSessionHasErrors('email');

    expect(User::where('email', 'existente@ejemplo.com')->count())->toBe(1);
});

test('rechaza campos obligatorios vacios y correo mal formado', function () {
    $this->actingAs($this->master)->post(route('usuarios.store'), [
        'name' => '',
        'email' => 'no-es-un-correo',
        'perfil' => '',
        'password' => '',
    ])->assertSessionHasErrors(['name', 'email', 'perfil', 'password']);
});

test('rechaza una contrasena de menos de 8 caracteres', function () {
    $this->actingAs($this->master)->post(route('usuarios.store'), [
        'name' => 'Corta Clave',
        'email' => 'corta@ejemplo.com',
        'perfil' => PerfilUsuario::Administrador->value,
        'password' => '1234567',
        'password_confirmation' => '1234567',
    ])->assertSessionHasErrors('password');

    expect(User::where('email', 'corta@ejemplo.com')->exists())->toBeFalse();
});

// -------------------- Mantenimiento (US3) --------------------

test('el master edita el nombre y el correo de una cuenta', function () {
    $usuario = User::factory()->administrador()->create();

    $this->actingAs($this->master)->put(route('usuarios.update', $usuario), [
        'name' => 'Nombre Corregido',
        'email' => 'CORREGIDO@Ejemplo.com',
    ])->assertRedirect(route('usuarios.edit', $usuario));

    $usuario->refresh();
    expect($usuario->name)->toBe('Nombre Corregido');
    expect($usuario->email)->toBe('corregido@ejemplo.com');
});

test('el master restablece la contrasena de una cuenta', function () {
    $usuario = User::factory()->administrador()->create();

    $this->actingAs($this->master)->put(route('usuarios.contrasena.update', $usuario), [
        'password' => 'nueva-clave-segura',
        'password_confirmation' => 'nueva-clave-segura',
    ])->assertRedirect(route('usuarios.edit', $usuario));

    expect(Hash::check('nueva-clave-segura', $usuario->fresh()->password))->toBeTrue();
});

test('el master cambia el perfil de una cuenta', function () {
    $usuario = User::factory()->administrador()->create();

    $this->actingAs($this->master)->put(route('usuarios.perfil.update', $usuario), [
        'perfil' => PerfilUsuario::Master->value,
    ])->assertRedirect(route('usuarios.edit', $usuario));

    expect($usuario->fresh()->perfil)->toBe(PerfilUsuario::Master);
});

test('el master desactiva y luego reactiva una cuenta', function () {
    $usuario = User::factory()->administrador()->create();

    $this->actingAs($this->master)->put(route('usuarios.estado.update', $usuario), ['activo' => '0'])
        ->assertRedirect(route('usuarios.index'));
    expect($usuario->fresh()->activo)->toBeFalse();

    $this->actingAs($this->master)->put(route('usuarios.estado.update', $usuario), ['activo' => '1'])
        ->assertRedirect(route('usuarios.index'));
    expect($usuario->fresh()->activo)->toBeTrue();
});

test('una cuenta desactivada es expulsada al login en su siguiente peticion', function () {
    $usuario = User::factory()->administrador()->create();

    $this->actingAs($usuario)->get(route('locaciones.index'))->assertOk();

    $usuario->update(['activo' => false]);

    $this->actingAs($usuario)->get(route('locaciones.index'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('el master no puede desactivar su propia cuenta', function () {
    User::factory()->master()->create(); // otro master activo: descarta la invariante del último master

    $this->actingAs($this->master)->put(route('usuarios.estado.update', $this->master), ['activo' => '0'])
        ->assertSessionHasErrors('activo');

    expect($this->master->fresh()->activo)->toBeTrue();
});

test('el master no puede quitarse a si mismo el perfil siendo el unico master activo', function () {
    $this->actingAs($this->master)->put(route('usuarios.perfil.update', $this->master), [
        'perfil' => PerfilUsuario::Administrador->value,
    ])->assertSessionHasErrors('perfil');

    expect($this->master->fresh()->perfil)->toBe(PerfilUsuario::Master);
});

test('no se puede eliminar al ultimo master activo desde el controlador', function () {
    $this->actingAs($this->master)->delete(route('usuarios.destroy', $this->master))
        ->assertSessionHasErrors('eliminar');

    expect(User::find($this->master->id))->not->toBeNull();
});

test('el master elimina una cuenta administradora', function () {
    $usuario = User::factory()->administrador()->create();

    $this->actingAs($this->master)->delete(route('usuarios.destroy', $usuario))
        ->assertRedirect(route('usuarios.index'));

    expect(User::find($usuario->id))->toBeNull();
});
