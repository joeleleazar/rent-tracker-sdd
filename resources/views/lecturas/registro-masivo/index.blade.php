<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Registro Masivo de Lecturas de Luz
        </h2>
    </x-slot>

    <div class="col-12">
        <div class="d-flex flex-column gap-3">
            @if (session('mensaje'))
                <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
            @endif

            @if ($errors->any())
                <x-mensaje-alerta tipo="error">
                    Revise las filas señaladas más abajo: algunas lecturas no se guardaron.
                </x-mensaje-alerta>
            @endif

            <div id="contenido-periodo-lecturas">
            {{--
                specs/024 (periodo ágil): flechas + autoenvío del selector, sin botón "Cambiar
                Periodo" ni recarga completa — hx-select re-extrae este mismo contenedor de la
                respuesta completa de la ruta (misma vista de siempre), así el controlador no
                necesita distinguir entre una petición htmx y una navegación normal.
                specs/026 US4: tarifa, navegación de periodo y exportar comparten una misma fila
                de controles (antes eran dos `card` separadas) — el `<form>` de periodo sigue
                siendo su propio elemento, para que el autoenvío del campo de fecha (`hx-trigger
                ="change"`) nunca incluya los campos de tarifa/exportar de los `<div>` hermanos,
                pero ya no lleva su propio marco de `card`; el marco único es el `div.card` que
                envuelve los tres grupos.
                specs/027: se retiró el botón "Ir" (fallback de degradación elegante sin
                JavaScript, specs/024) — la navegación oficial queda limitada a las flechas y al
                autoenvío del campo de fecha, ambos ya funcionales por sí solos.
            --}}
            <div class="card">
                <div class="card-body d-flex flex-wrap align-items-end gap-3">
                    <form method="GET" action="{{ route('lecturas.registroMasivo.index') }}" class="d-flex flex-wrap align-items-end gap-2">
                        <div class="d-flex align-items-end gap-2">
                            <a
                                href="{{ route('lecturas.registroMasivo.index', ['periodo' => $periodo->copy()->subMonth()->format('Y-m')]) }}"
                                hx-get="{{ route('lecturas.registroMasivo.index', ['periodo' => $periodo->copy()->subMonth()->format('Y-m')]) }}"
                                hx-select="#contenido-periodo-lecturas"
                                hx-target="#contenido-periodo-lecturas"
                                hx-swap="outerHTML"
                                class="btn btn-outline-secondary"
                                aria-label="Periodo anterior"
                            >
                                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                            </a>
                            <div>
                                <x-input-label for="periodo_selector" value="Periodo (mes)" />
                                <input
                                    id="periodo_selector"
                                    name="periodo"
                                    type="month"
                                    class="form-control"
                                    value="{{ $periodo->format('Y-m') }}"
                                    hx-get="{{ route('lecturas.registroMasivo.index') }}"
                                    hx-trigger="change"
                                    hx-select="#contenido-periodo-lecturas"
                                    hx-target="#contenido-periodo-lecturas"
                                    hx-swap="outerHTML"
                                >
                            </div>
                            <a
                                href="{{ route('lecturas.registroMasivo.index', ['periodo' => $periodo->copy()->addMonth()->format('Y-m')]) }}"
                                hx-get="{{ route('lecturas.registroMasivo.index', ['periodo' => $periodo->copy()->addMonth()->format('Y-m')]) }}"
                                hx-select="#contenido-periodo-lecturas"
                                hx-target="#contenido-periodo-lecturas"
                                hx-swap="outerHTML"
                                class="btn btn-outline-secondary"
                                aria-label="Periodo siguiente"
                            >
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </form>

                    <div>
                        <x-input-label for="tarifa_kwh" value="Tarifa por kWh" />
                        <div class="input-group input-group-sm" style="max-width: 12rem;">
                            <span class="input-group-text">S/</span>
                            <input
                                id="tarifa_kwh"
                                name="tarifa_luz_por_unidad"
                                type="number"
                                step="0.0001"
                                min="0"
                                class="form-control"
                                value="{{ old('tarifa_luz_por_unidad', $tarifa) }}"
                                hx-patch="{{ route('lecturas.registroMasivo.actualizarTarifa') }}"
                                hx-trigger="change"
                                hx-swap="none"
                                aria-label="Tarifa por kWh, usada para calcular el total por local"
                            >
                        </div>
                    </div>

                    <div class="d-flex gap-2 ms-auto">
                        {{--
                            specs/020: hx-boost="false" excluye estos dos enlaces del
                            hx-boost="true" heredado del layout raíz (specs/011) — sin esto, htmx
                            intercepta la descarga y trata la respuesta binaria como si fuera HTML
                            para reemplazar la página, en vez de dejar que el navegador la descargue.
                        --}}
                        <a href="{{ route('lecturas.registroMasivo.exportarExcel', ['periodo' => $periodo->format('Y-m')]) }}" class="btn btn-outline-secondary btn-sm" hx-boost="false">
                            <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Exportar a Excel
                        </a>
                        <a href="{{ route('lecturas.registroMasivo.exportarPdf', ['periodo' => $periodo->format('Y-m')]) }}" class="btn btn-outline-secondary btn-sm" hx-boost="false">
                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Exportar a PDF
                        </a>
                    </div>
                </div>
            </div>

            @if (empty($raices))
                <x-estado-vacio icono="bi-speedometer2">Todavía no hay locaciones registradas.</x-estado-vacio>
            @else
                <form id="formulario-registro-masivo" method="POST" action="{{ route('lecturas.registroMasivo.store') }}">
                    @csrf
                    <input type="hidden" name="periodo" value="{{ $periodo->format('Y-m-d') }}">

                    <div class="d-flex flex-column gap-3">
                        {{--
                            Autoguardado de borrador (specs/015, User Story 3): un
                            elemento no-<form> para que resources/js/htmx.js no le
                            aplique el tratamiento visual de "Guardando…" reservado al
                            envío manual (ese listener solo actúa sobre evento.target
                            con tagName === 'FORM'). hx-include recolecta los valores
                            del formulario principal sin que el usuario haga nada.
                        --}}
                        <div
                            id="autoguardado-borrador"
                            hx-post="{{ route('lecturas.registroMasivo.borrador') }}"
                            hx-trigger="every 120s"
                            hx-include="#formulario-registro-masivo"
                            hx-target="#estado-autoguardado"
                            hx-swap="innerHTML"
                        ></div>
                        <p id="estado-autoguardado" class="text-secondary small mb-0" aria-live="polite"></p>

                        <div class="tabla-registro-masivo">
                            <div class="tabla-registro-masivo__encabezado">
                                <div>Nombre / Locación</div>
                                <div>Lectura Periodo Anterior</div>
                                <div>Lectura Actual</div>
                                <div>Consumo</div>
                                <div>Total</div>
                            </div>

                            @foreach ($raices as $nodo)
                                @include('lecturas.registro-masivo.partials.fila-registro-masivo', [
                                    'locacion' => $nodo['locacion'],
                                    'hijos' => $nodo['hijos'],
                                    'profundidad' => 0,
                                    'periodo' => $periodo,
                                    'lecturasDelPeriodo' => $lecturasDelPeriodo,
                                    'lecturasAnteriores' => $lecturasAnteriores,
                                    'borradores' => $borradores,
                                ])
                            @endforeach

                            <div class="tabla-registro-masivo__total-general">
                                <div>Total general</div>
                                <div></div>
                                <div></div>
                                <div></div>
                                <div id="total-general-registro-masivo" class="cifra">—</div>
                            </div>
                        </div>

                        <x-primary-button class="align-self-start">Guardar Lecturas</x-primary-button>
                    </div>
                </form>
            @endif
            </div>
        </div>
    </div>

    @vite(['resources/js/registro-masivo-lecturas.js'])
</x-layouts.app-bootstrap>
