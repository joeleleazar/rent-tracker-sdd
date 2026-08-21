<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Rent Tracker') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/bootstrap.scss', 'resources/js/bootstrap.js'])
        <style>
            body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        </style>
    </head>
    <body>
        {{-- Navegación plana y lineal, sin menús desplegables (Principio III: Senior-First) --}}
        <nav class="navbar navbar-expand-lg bg-white border-bottom border-2" aria-label="Navegación principal">
            <div class="container-xl flex-wrap gap-3 py-2">
                <a href="{{ url('/') }}" class="navbar-brand fw-bold fs-4">
                    {{ config('app.name', 'Rent Tracker') }}
                </a>

                @auth
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-lg">Locaciones</a>
                        <a href="{{ route('locaciones.index') }}" class="btn btn-outline-secondary btn-lg">Gestionar Locaciones</a>
                        <a href="{{ route('configuracion.edit') }}" class="btn btn-outline-secondary btn-lg">Configuración</a>
                        <span class="fs-5">{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-lg">
                                Cerrar Sesión
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </nav>

        @isset($header)
            <header class="bg-white border-bottom">
                <div class="container-xl py-4">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="container-xl py-4">
            {{ $slot }}
        </main>

        {{-- Scripts adicionales específicos de una vista (ej. el editor de
             representantes o el gráfico de consumo), agregados vía @push('scripts'). --}}
        @stack('scripts')
    </body>
</html>
