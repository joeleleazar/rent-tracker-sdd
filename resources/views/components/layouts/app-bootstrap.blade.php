<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Rent Tracker') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo-nicson-plaza.png') }}">

        {{-- Instrument Sans se auto-hospeda vía el plugin de fuentes de Vite
             (ver vite.config.js); el enlace bunny.net a Figtree que había antes
             quedó mudo (la hoja `body` nunca pidió esa familia), descargando
             una fuente que ninguna regla usaba mientras Instrument Sans caía a
             la fuente del sistema por falta de @font-face. --}}
        {{ \Illuminate\Support\Facades\Vite::fonts() }}

        @vite(['resources/css/bootstrap.scss', 'resources/js/bootstrap.js', 'resources/js/htmx.js'])
        {{-- specs/025: el color y las dimensiones base de .sidebar-principal se
             consolidaron en resources/css/bootstrap.scss (token $dark), junto a
             sus reglas hermanas (.nav-link:hover/.active) — ya no viven aquí
             duplicadas como hex literal. --}}
        <style>
            body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        </style>
    </head>
    <body>
        {{--
            Barra de carga de navegación (specs/042, US2): barra fina fija en el
            borde superior de la ventana que aparece cuando una navegación
            boosteada (petición GET) tarda más que el umbral anti-parpadeo y se
            retira al completarse, fallar o abortarse. Los envíos de formulario
            NO la disparan (conservan el botón "Guardando…"). Lógica en
            resources/js/htmx.js; estilos en resources/css/bootstrap.scss (§18).
        --}}
        <div class="barra-carga-navegacion progress d-none" aria-hidden="true">
            <div class="progress-bar"></div>
        </div>

        {{--
            hx-boost (specs/011): convierte todos los enlaces y formularios internos
            en peticiones asíncronas sin recarga completa de página, sin requerir
            ningún cambio en controladores/rutas — ver contracts/convenciones-htmx.md.
            Si htmx no carga, este atributo se ignora y todo navega/envía de forma
            clásica (degradación elegante, FR-007).
        --}}
        <div class="app-shell d-flex flex-column flex-md-row min-vh-100" hx-boost="true">
            {{--
                Barra superior compacta (solo < md): en pantallas angostas el
                sidebar deja de vivir permanentemente en pantalla y pasa a un
                panel lateral (offcanvas) que se abre con el botón ☰. Esta barra
                queda fija arriba para dar contexto de marca y acceso al menú sin
                empujar el contenido hacia abajo.
            --}}
            @auth
                <header class="sidebar-topbar d-flex d-md-none align-items-center gap-3 px-3 py-2 text-white">
                    <button
                        class="sidebar-topbar__toggle btn btn-dark d-inline-flex align-items-center gap-2 border-0"
                        type="button"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#sidebar-principal"
                        aria-controls="sidebar-principal"
                    >
                        <i class="bi bi-list fs-4" aria-hidden="true"></i>
                        <span>Menú</span>
                    </button>
                    <a href="{{ url('/') }}" class="ms-auto d-inline-flex align-items-center text-decoration-none" aria-label="{{ config('app.name', 'Rent Tracker') }} — ir al inicio">
                        <span class="bg-white rounded-3 px-2 py-1 d-inline-flex align-items-center justify-content-center">
                            <img src="{{ asset('images/logo-nicson-plaza.png') }}" alt="Nicson Plaza" style="height: 1.75rem; width: auto;">
                        </span>
                    </a>
                </header>
            @endauth

            {{--
                Sidebar de navegación (Principio III) — rail vertical fijo y
                siempre visible en escritorio (≥768px); en pantallas angostas es
                un `offcanvas` que se abre desde el borde izquierdo con la barra
                superior y se cierra al elegir una opción o tocar fuera. La clase
                `offcanvas-md` de Bootstrap conmuta sola entre ambos modos.
            --}}
            <nav
                id="sidebar-principal"
                class="sidebar-principal offcanvas-md offcanvas-start d-flex flex-column flex-shrink-0 text-white"
                tabindex="-1"
                aria-label="Navegación principal"
            >
                <div class="offcanvas-header d-md-none border-bottom border-secondary">
                    <a href="{{ url('/') }}" class="d-flex align-items-center text-decoration-none" aria-label="{{ config('app.name', 'Rent Tracker') }} — ir al inicio">
                        <span class="bg-white rounded-3 px-2 py-1 d-inline-flex align-items-center justify-content-center">
                            <img src="{{ asset('images/logo-nicson-plaza.png') }}" alt="Nicson Plaza" style="height: 1.75rem; width: auto;">
                        </span>
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebar-principal" aria-label="Cerrar menú"></button>
                </div>

                <div class="offcanvas-body d-flex flex-column p-3">
                    {{--
                        specs/030: el logo (PNG con fondo transparente) usa "nicson" en azul oscuro,
                        con poco contraste directo sobre el fondo casi negro del sidebar ($dark,
                        #111827) — se enmarca en una tarjeta blanca para que se siga leyendo bien
                        (Principio III), dimensionada por alto con ancho automático para respetar la
                        proporción real del archivo (1769×962, no cuadrada) en vez de recortarlo
                        dentro de una caja cuadrada. En < md el logo ya vive en la barra superior y
                        en la cabecera del panel, así que aquí solo aparece en escritorio.
                    --}}
                    <a href="{{ url('/') }}" class="d-none d-md-flex align-items-center text-decoration-none mb-3" aria-label="{{ config('app.name', 'Rent Tracker') }} — ir al inicio">
                        <span class="bg-white rounded-3 px-2 py-1 d-inline-flex align-items-center justify-content-center">
                            <img src="{{ asset('images/logo-nicson-plaza.png') }}" alt="Nicson Plaza" style="height: 2.25rem; width: auto;">
                        </span>
                    </a>

                    @auth
                        <ul class="nav nav-pills flex-column gap-2 mb-3">
                            <li class="nav-item">
                                <a href="{{ route('dashboard') }}" class="nav-link text-white d-flex align-items-center gap-2 py-2 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                    <i class="bi bi-clipboard-data" aria-hidden="true"></i> Inicio
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('locaciones.index') }}" class="nav-link text-white d-flex align-items-center gap-2 py-2 {{ request()->routeIs('locaciones.*') ? 'active' : '' }}">
                                    <i class="bi bi-diagram-3" aria-hidden="true"></i> Gestionar Locaciones
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('lecturas.registroMasivo.index') }}" class="nav-link text-white d-flex align-items-center gap-2 py-2 {{ request()->routeIs('lecturas.registroMasivo.*') ? 'active' : '' }}">
                                    <i class="bi bi-speedometer2" aria-hidden="true"></i> Registrar Lecturas
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('recibos.registroMasivo.index') }}" class="nav-link text-white d-flex align-items-center gap-2 py-2 {{ request()->routeIs('recibos.registroMasivo.*') ? 'active' : '' }}">
                                    <i class="bi bi-receipt" aria-hidden="true"></i> Emitir Recibos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('pagos.seguimiento.index') }}" class="nav-link text-white d-flex align-items-center gap-2 py-2 {{ request()->routeIs('pagos.seguimiento.*') ? 'active' : '' }}">
                                    <i class="bi bi-cash-coin" aria-hidden="true"></i> Registro de Pagos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('conceptosGastoFijo.index') }}" class="nav-link text-white d-flex align-items-center gap-2 py-2 {{ request()->routeIs('conceptosGastoFijo.*') ? 'active' : '' }}">
                                    <i class="bi bi-tags" aria-hidden="true"></i> Conceptos de Gasto Fijo
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('configuracion.edit') }}" class="nav-link text-white d-flex align-items-center gap-2 py-2 {{ request()->routeIs('configuracion.*') ? 'active' : '' }}">
                                    <i class="bi bi-gear" aria-hidden="true"></i> Configuración
                                </a>
                            </li>
                            {{-- specs/040: la gestión de usuarios es exclusiva del perfil Master. --}}
                            @can('gestionar-usuarios')
                                <li class="nav-item">
                                    <a href="{{ route('usuarios.index') }}" class="nav-link text-white d-flex align-items-center gap-2 py-2 {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                                        <i class="bi bi-people" aria-hidden="true"></i> Usuarios
                                    </a>
                                </li>
                            @endcan
                        </ul>

                        <div class="d-flex flex-column align-items-stretch gap-2 mt-md-auto pt-3 border-top border-secondary">
                            <span class="text-white-50 small text-truncate">{{ Auth::user()->name }}</span>
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
