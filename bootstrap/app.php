<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render termina el TLS en su borde y reenvía la petición al contenedor
        // por HTTP; sin esto Laravel ve "http" y genera URLs de assets con
        // http:// (mixed content). El contenedor nunca se expone directo, así
        // que confiar en todos los proxies es seguro aquí.
        $middleware->trustProxies(at: '*');

        // specs/040: gestión de usuarios por perfiles.
        $middleware->alias([
            'perfil.master' => \App\Http\Middleware\RequerirPerfilMaster::class,
            'cuenta.activa' => \App\Http\Middleware\AsegurarCuentaActiva::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
