<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Registro Masivo de Recibos
        </h2>
    </x-slot>

    <div class="col-12">
        <div class="d-flex flex-column gap-3">
            @if (session('mensaje'))
                <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
            @endif

            <div id="contenido-periodo-recibos">
            {{-- specs/024 (periodo ágil): ver el mismo patrón ya explicado en lecturas/registro-masivo/index.blade.php --}}
            <form method="GET" action="{{ route('recibos.registroMasivo.index') }}" class="card">
                <div class="card-body d-flex flex-wrap align-items-end gap-3">
                    <div class="d-flex align-items-end gap-2">
                        <a
                            href="{{ route('recibos.registroMasivo.index', ['periodo' => $periodo->copy()->subMonth()->format('Y-m')]) }}"
                            hx-get="{{ route('recibos.registroMasivo.index', ['periodo' => $periodo->copy()->subMonth()->format('Y-m')]) }}"
                            hx-select="#contenido-periodo-recibos"
                            hx-target="#contenido-periodo-recibos"
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
                                hx-get="{{ route('recibos.registroMasivo.index') }}"
                                hx-trigger="change"
                                hx-select="#contenido-periodo-recibos"
                                hx-target="#contenido-periodo-recibos"
                                hx-swap="outerHTML"
                            >
                        </div>
                        <a
                            href="{{ route('recibos.registroMasivo.index', ['periodo' => $periodo->copy()->addMonth()->format('Y-m')]) }}"
                            hx-get="{{ route('recibos.registroMasivo.index', ['periodo' => $periodo->copy()->addMonth()->format('Y-m')]) }}"
                            hx-select="#contenido-periodo-recibos"
                            hx-target="#contenido-periodo-recibos"
                            hx-swap="outerHTML"
                            class="btn btn-outline-secondary"
                            aria-label="Periodo siguiente"
                        >
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    </div>
                    <x-secondary-button type="submit">Ir</x-secondary-button>
                </div>
            </form>

            @if (empty($raices))
                <x-estado-vacio icono="bi-receipt">Todavía no hay locaciones registradas.</x-estado-vacio>
            @else
                <div class="tabla-registro-masivo-recibos">
                    <div class="tabla-registro-masivo-recibos__encabezado">
                        <div>Nombre / Locación</div>
                        <div>Contrato</div>
                        <div>Conceptos</div>
                        <div>Total del Periodo</div>
                        <div>Acción</div>
                    </div>

                    @foreach ($raices as $nodo)
                        @include('recibos.registro-masivo.partials.fila-registro-masivo-recibos', [
                            'locacion' => $nodo['locacion'],
                            'hijos' => $nodo['hijos'],
                            'profundidad' => 0,
                            'periodo' => $periodo,
                            'contratosActivos' => $contratosActivos,
                            'conceptosActivos' => $conceptosActivos,
                            'conceptosDisponiblesPorLocacion' => $conceptosDisponiblesPorLocacion,
                            'reciboQueCubrePorLocacion' => $reciboQueCubrePorLocacion,
                            'cantidadRecibosPorLocacion' => $cantidadRecibosPorLocacion,
                            'totalFacturadoPorLocacion' => $totalFacturadoPorLocacion,
                        ])
                    @endforeach
                </div>
            @endif
            </div>
        </div>
    </div>

    <x-modal-bootstrap name="modal-recibo-registro-masivo" maxWidth="lg" focusable>
        <div id="contenido-modal-recibo"></div>
    </x-modal-bootstrap>

    @vite(['resources/js/registro-masivo-recibos.js'])
</x-layouts.app-bootstrap>
