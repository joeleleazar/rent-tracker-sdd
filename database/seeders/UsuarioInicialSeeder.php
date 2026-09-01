<?php

namespace Database\Seeders;

use App\Enums\PerfilUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea la cuenta de acceso inicial (perfil Master) para un despliegue nuevo.
 *
 * Es necesario porque el auto-registro público se retiró en specs/040: sin esta
 * cuenta no habría forma de iniciar sesión la primera vez. Es idempotente
 * (`firstOrCreate` por correo), así que puede ejecutarse en cada deploy sin
 * duplicar ni pisar la contraseña de una cuenta ya existente.
 *
 * Toma las credenciales de las variables de entorno `ADMIN_EMAIL`,
 * `ADMIN_PASSWORD` y `ADMIN_NAME` (con valores por defecto para un arranque
 * sin configuración). Cambiá la contraseña desde la app apenas ingreses.
 */
class UsuarioInicialSeeder extends Seeder
{
    public function run(): void
    {
        $correo = env('ADMIN_EMAIL', 'admin@rent-tracker.test');

        $usuario = User::firstOrCreate(
            ['email' => $correo],
            [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'demo1234')),
                'perfil' => PerfilUsuario::Master,
                'activo' => true,
                'email_verified_at' => now(),
            ],
        );

        $this->command?->info(
            $usuario->wasRecentlyCreated
                ? "Cuenta inicial creada: {$correo}"
                : "La cuenta inicial {$correo} ya existía; sin cambios.",
        );
    }
}
