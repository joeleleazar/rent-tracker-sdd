<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Rent Tracker') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-50 font-sans antialiased">
        {{-- Navegación plana y lineal, sin menús desplegables (Principio III: Senior-First) --}}
        <nav class="border-b-2 border-gray-300 bg-white" aria-label="Navegación principal">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
                <a href="{{ url('/') }}" class="text-xl font-bold text-gray-900">
                    {{ config('app.name', 'Rent Tracker') }}
                </a>

                @auth
                    <div class="flex flex-wrap items-center gap-4">
                        <a href="{{ route('dashboard') }}" class="btn-senior-secundario">Locaciones</a>
                        <span class="text-lg text-gray-700">{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-senior-secundario">
                                Cerrar Sesión
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </nav>

        @isset($header)
            <header class="border-b-2 border-gray-200 bg-white">
                <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
            {{ $slot }}
        </main>
    </body>
</html>
