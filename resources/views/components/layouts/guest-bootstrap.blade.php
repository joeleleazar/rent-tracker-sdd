<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Rent Tracker') }}</title>

        {{ \Illuminate\Support\Facades\Vite::fonts() }}

        @vite(['resources/css/bootstrap.scss', 'resources/js/bootstrap.js'])
        <style>
            body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        </style>
    </head>
    <body class="d-flex flex-column align-items-center justify-content-center min-vh-100 bg-light py-4">
        <div>
            <a href="/">
                <x-application-logo style="width: 5rem; height: 5rem; fill: currentColor; color: #374151;" />
            </a>
        </div>

        <div class="w-100 mt-4 card" style="max-width: 28rem;">
            <div class="card-body p-4">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
