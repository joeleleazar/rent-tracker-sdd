<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Rent Tracker') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo-nicson-plaza.png') }}">

        {{ \Illuminate\Support\Facades\Vite::fonts() }}

        @vite(['resources/css/bootstrap.scss', 'resources/js/bootstrap.js'])
        <style>
            body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        </style>
    </head>
    <body class="d-flex flex-column align-items-center justify-content-center min-vh-100 bg-light px-3 py-5">
        <main class="w-100" style="max-width: 27rem;">
            <div class="text-center mb-4">
                <a href="/" aria-label="{{ config('app.name', 'Rent Tracker') }} — ir al inicio">
                    <img src="{{ asset('images/logo-nicson-plaza.png') }}" alt="Nicson Plaza" style="height: 3.25rem; width: auto;">
                </a>
            </div>

            <div class="card">
                <div class="card-body p-4 p-sm-4 d-flex flex-column gap-3">
                    @isset($title)
                        <div>
                            <h1 class="fs-3 fw-bold mb-1">{{ $title }}</h1>
                            @isset($subtitle)
                                <p class="text-secondary mb-0">{{ $subtitle }}</p>
                            @endisset
                        </div>
                    @endisset

                    {{ $slot }}
                </div>
            </div>
        </main>
    </body>
</html>
