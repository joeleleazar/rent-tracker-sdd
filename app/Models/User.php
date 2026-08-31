<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\PerfilUsuario;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'perfil', 'activo'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'perfil' => PerfilUsuario::class,
            'activo' => 'boolean',
        ];
    }

    /**
     * Limita la consulta a las cuentas activas (habilitadas para iniciar
     * sesión).
     *
     * @param  Builder<User>  $query
     */
    public function scopeActivos(Builder $query): void
    {
        $query->where('activo', true);
    }

    /**
     * Indica si la cuenta tiene el perfil Master, el único con acceso al CRUD
     * de usuarios (specs/040).
     */
    public function esMaster(): bool
    {
        return $this->perfil === PerfilUsuario::Master;
    }

    /**
     * Indica si la cuenta está habilitada para iniciar sesión y acceder a las
     * secciones protegidas.
     */
    public function estaActivo(): bool
    {
        return $this->activo === true;
    }
}
