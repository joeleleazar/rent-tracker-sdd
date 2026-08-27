<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Seguimiento de Pagos
        </h2>
    </x-slot>

    <div class="col-12">
        <div class="d-flex flex-column gap-3">
            <div id="contenido-periodo-pagos">
            {{-- specs/032: mismo patrón de selector de período ágil (con navegación
                 anterior/siguiente vía htmx) ya usado en recibos/registro-masivo/index.blade.php
                 (FR-008) — sin el botón "Ir" por el mismo motivo de degradación elegante ya
                 documentado ahí (specs/028). --}}
            <form method="GET" action="{{ route('pagos.seguimiento.index') }}" class="card">
                <div class="card-body d-flex flex-wrap align-items-end gap-3">
                    <div class="d-flex align-items-end gap-2">
                        <a
                            href="{{ route('pagos.seguimiento.index', ['periodo' => $periodo->copy()->subMonth()->format('Y-m')]) }}"
                            hx-get="{{ route('pagos.seguimiento.index', ['periodo' => $periodo->copy()->subMonth()->format('Y-m')]) }}"
                            hx-select="#contenido-periodo-pagos"
                            hx-target="#contenido-periodo-pagos"
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
                                hx-get="{{ route('pagos.seguimiento.index') }}"
                                hx-trigger="change"
                                hx-select="#contenido-periodo-pagos"
                                hx-target="#contenido-periodo-pagos"
                                hx-swap="outerHTML"
                            >
                        </div>
                        <a
                            href="{{ route('pagos.seguimiento.index', ['periodo' => $periodo->copy()->addMonth()->format('Y-m')]) }}"
                            hx-get="{{ route('pagos.seguimiento.index', ['periodo' => $periodo->copy()->addMonth()->format('Y-m')]) }}"
                            hx-select="#contenido-periodo-pagos"
                            hx-target="#contenido-periodo-pagos"
                            hx-swap="outerHTML"
                            class="btn btn-outline-secondary"
                            aria-label="Periodo siguiente"
                        >
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </form>

            @if (empty($raices))
                <x-estado-vacio icono="bi-cash-coin">Todavía no hay locaciones registradas.</x-estado-vacio>
            @else
                <div class="tabla-seguimiento-pagos">
                    <div class="tabla-seguimiento-pagos__encabezado">
                        <div>Nombre / Locación</div>
                        <div>Estado de Pago</div>
                        <div>Avance</div>
                        <div>Acción</div>
                    </div>

                    @foreach ($raices as $nodo)
                        @include('pagos.seguimiento.partials.fila-seguimiento-pagos', [
                            'locacion' => $nodo['locacion'],
                            'hijos' => $nodo['hijos'],
                            'profundidad' => 0,
                            'periodo' => $periodo,
                            'montoPagadoPorLocacion' => $montoPagadoPorLocacion,
                            'montoTotalPorLocacion' => $montoTotalPorLocacion,
                            'cantidadRecibosPorLocacion' => $cantidadRecibosPorLocacion,
                            'estadoAgregadoPorLocacion' => $estadoAgregadoPorLocacion,
                        ])
                    @endforeach
                </div>
            @endif
            </div>
        </div>
    </div>
</x-layouts.app-bootstrap>
