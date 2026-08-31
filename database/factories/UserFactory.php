<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'perfil' => \App\Enums\PerfilUsuario::Administrador,
            'activo' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Cuenta con perfil Master (acceso exclusivo al CRUD de usuarios, specs/040).
     */
    public function master(): static
    {
        return $this->state(fn (array $attributes) => [
            'perfil' => \App\Enums\PerfilUsuario::Master,
        ]);
    }

    /**
     * Cuenta con perfil Administrador (sin acceso a la gestión de usuarios).
     */
    public function administrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'perfil' => \App\Enums\PerfilUsuario::Administrador,
        ]);
    }

    /**
     * Cuenta desactivada: no puede iniciar sesión ni acceder a secciones
     * protegidas.
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
