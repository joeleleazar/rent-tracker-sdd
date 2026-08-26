<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Rent Tracker') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo-nicson-plaza.png') }}">

        {{ \Illuminate\Support\Facades\Vite::fonts() }}

        @vite(['resources/css/bootstrap.scss', 'resources/js/bootstrap.js'])
        <style>
            body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        </style>
    </head>
    <body class="d-flex flex-column align-items-center justify-content-center min-vh-100 bg-light py-4">
        <div>
            <a href="/">
                <img src="{{ asset('images/logo-nicson-plaza.png') }}" alt="Nicson Plaza" style="height: 4rem; width: auto;">
            </a>
        </div>

        <div class="w-100 mt-4 card" style="max-width: 28rem;">
            <div class="card-body p-4">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
