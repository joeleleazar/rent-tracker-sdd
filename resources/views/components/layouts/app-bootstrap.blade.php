<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Rent Tracker') }}</title>

        {{-- Instrument Sans se auto-hospeda vía el plugin de fuentes de Vite
             (ver vite.config.js); el enlace bunny.net a Figtree que había antes
             quedó mudo (la hoja `body` nunca pidió esa familia), descargando
             una fuente que ninguna regla usaba mientras Instrument Sans caía a
             la fuente del sistema por falta de @font-face. --}}
        {{ \Illuminate\Support\Facades\Vite::fonts() }}

        @vite(['resources/css/bootstrap.scss', 'resources/js/bootstrap.js', 'resources/js/htmx.js'])
        <style>
            body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }

            .sidebar-principal {
                background-color: #111827;
                width: 100%;
            }

            @media (min-width: 768px) {
                .sidebar-principal {
                    width: 280px;
                    min-height: 100vh;
                }
            }
        </style>
    </head>
    <body>
        {{--
            hx-boost (specs/011): convierte todos los enlaces y formularios internos
            en peticiones asíncronas sin recarga completa de página, sin requerir
            ningún cambio en controladores/rutas — ver contracts/convenciones-htmx.md.
            Si htmx no carga, este atributo se ignora y todo navega/envía de forma
            clásica (degradación elegante, FR-007).
        --}}
        <div class="d-flex flex-column flex-md-row min-vh-100" hx-boost="true">
            {{--
                Sidebar de navegación (Principio III) — vertical y siempre visible
                en escritorio; en pantallas angostas se reordena a una franja
                horizontal con los mismos enlaces, sin ocultar ninguno.
            --}}
            <nav
                class="sidebar-principal d-flex flex-column flex-shrink-0 p-3 text-white"
                aria-label="Navegación principal"
            >
                <div class="d-flex flex-md-column flex-row flex-wrap align-items-center align-items-md-stretch justify-content-between gap-3">
                    <a href="{{ url('/') }}" class="d-flex align-items-center text-white text-decoration-none fs-4 fw-bold mb-md-3">
                        {{ config('app.name', 'Rent Tracker') }}
                    </a>

                    @auth
                        <ul class="nav nav-pills flex-md-column flex-row flex-wrap gap-2 mb-md-3" style="min-width: 0;">
                            <li class="nav-item">
                                <a href="{{ route('dashboard') }}" class="nav-link text-white d-flex align-items-center gap-2 py-2 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                    <i class="bi bi-buildings" aria-hidden="true"></i> Locaciones
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('locaciones.index') }}" class="nav-link text-white d-flex align-items-center gap-2 py-2 {{ request()->routeIs('locaciones.*') ? 'active' : '' }}">
                                    <i class="bi bi-diagram-3" aria-hidden="true"></i> Gestionar Locaciones
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('configuracion.edit') }}" class="nav-link text-white d-flex align-items-center gap-2 py-2 {{ request()->routeIs('configuracion.*') ? 'active' : '' }}">
                                    <i class="bi bi-gear" aria-hidden="true"></i> Configuración
                                </a>
                            </li>
                        </ul>

                        <div class="d-flex flex-md-column flex-row align-items-center align-items-md-stretch gap-3 mt-md-auto pt-md-3 border-top border-secondary">
                            <span class="text-white-50">{{ Auth::user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-light w-100">
                                    Cerrar Sesión
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </nav>

            <div class="d-flex flex-column flex-grow-1 min-vw-0">
                @isset($header)
                    <header class="bg-white border-bottom">
                        <div class="container-xl py-4">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="container-xl py-4 flex-grow-1">
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Scripts adicionales específicos de una vista (ej. el editor de
             representantes o el gráfico de consumo), agregados vía @push('scripts'). --}}
        @stack('scripts')
    </body>
</html>
