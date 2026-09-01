<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fuerza https en todas las URLs generadas cuando APP_URL ya es https
        // (producción tras el proxy de Render). Complementa a trustProxies por
        // si algún request llega sin el header X-Forwarded-Proto.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // specs/040: única fuente de verdad del permiso "puede administrar
        // usuarios". La usan tanto el middleware `perfil.master` (a través de
        // User::esMaster()) como las vistas (`@can('gestionar-usuarios')`).
        Gate::define('gestionar-usuarios', fn (User $usuario) => $usuario->esMaster());
    }
}
