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

            <form method="GET" action="{{ route('lecturas.registroMasivo.index') }}" class="card">
                <div class="card-body d-flex flex-wrap align-items-end gap-3">
                    <div>
                        <x-input-label for="periodo_selector" value="Periodo (mes)" />
                        <input id="periodo_selector" name="periodo" type="month" class="form-control" value="{{ $periodo->format('Y-m') }}">
                    </div>
                    <x-secondary-button>Cambiar Periodo</x-secondary-button>
                </div>
            </form>

            <div class="card">
                <div class="card-body d-flex flex-wrap align-items-end gap-3">
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
                        <a href="{{ route('lecturas.registroMasivo.exportarExcel', ['periodo' => $periodo->format('Y-m')]) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Exportar a Excel
                        </a>
                        <a href="{{ route('lecturas.registroMasivo.exportarPdf', ['periodo' => $periodo->format('Y-m')]) }}" class="btn btn-outline-secondary btn-sm">
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

    @vite(['resources/js/registro-masivo-lecturas.js'])
</x-layouts.app-bootstrap>
