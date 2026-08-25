<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Lecturas de Medidor — {{ $locacion->nombre }}
        </h2>
    </x-slot>

    <div class="col-12 col-lg-9" style="max-width: 48rem;">
        <div class="d-flex flex-column gap-3">
            @if (session('mensaje'))
                <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
            @endif

            <div class="d-flex flex-wrap gap-3">
                <a href="{{ route('locaciones.lecturas.create', $locacion) }}" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Registrar Lectura del Medidor</a>
            </div>

            @if ($lecturas->isEmpty())
                <p>Esta locación todavía no tiene lecturas de medidor registradas.</p>
            @else
                @php
                    // Gráfico de consumo histórico (FR-005): mismo dato ya calculado
                    // por LecturaMedidorController@index para la tabla de abajo, sin
                    // lógica de cálculo nueva — ver resources/js/historial-consumo-medidor.js.
                    $datosConsumo = $lecturas->map(fn ($lectura) => [
                        'periodo' => ucfirst($lectura->periodo->translatedFormat('F Y')),
                        'consumo' => $lectura->consumo_calculado !== null ? (float) $lectura->consumo_calculado : null,
                    ])->values();
                @endphp

                @if ($datosConsumo->count() > 1)
                    <div class="card">
                        <div class="card-body">
                            <h3 class="fs-4 fw-bold">Consumo Histórico</h3>
                            <div style="height: 20rem;">
                                <canvas id="grafico-consumo-medidor" data-consumos="{{ $datosConsumo->toJson() }}" role="img" aria-label="Gráfico de consumo de medidor por periodo"></canvas>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="d-flex flex-column gap-3">
                    @foreach ($lecturas as $lectura)
                        @php
                            $reciboDelPeriodo = $recibosPorPeriodo->get($lectura->periodo->format('Y-m-d'));
                        @endphp
                        <div class="card">
                            <div class="card-body">
                                <p class="fw-semibold mb-1">
                                    Periodo: {{ $lectura->periodo->translatedFormat('F Y') }}
                                </p>
                                <p class="mb-1">
                                    Lectura anterior:
                                    @if ($lectura->lectura_anterior === null)
                                        sin lectura previa registrada
                                    @else
                                        {{ number_format((float) $lectura->lectura_anterior, 2) }}
                                    @endif
                                </p>
                                <p class="mb-1">
                                    Lectura actual: {{ number_format((float) $lectura->lectura_actual, 2) }}
                                </p>
                                <p class="mb-1">
                                    Consumo:
                                    @if ($lectura->consumo_calculado === null)
                                        sin dato anterior
                                    @else
                                        {{ number_format((float) $lectura->consumo_calculado, 2) }} unidades
                                    @endif
                                </p>
                                @if ($lectura->discrepanciaConSiguiente())
                                    <x-mensaje-alerta tipo="error" class="mt-2">
                                        Advertencia: la lectura actual de este periodo no coincide con la lectura anterior usada en el periodo siguiente.
                                    </x-mensaje-alerta>
                                @endif
                                <div class="mt-2 d-flex flex-wrap gap-3">
                                    <a href="{{ route('lecturas.edit', $lectura) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil-square" aria-hidden="true"></i> Editar Lectura</a>
                                    @if ($reciboDelPeriodo !== null)
                                        <a href="{{ route('recibos.show', $reciboDelPeriodo) }}" class="btn btn-outline-secondary">Ver Recibo del Periodo</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        @vite(['resources/js/historial-consumo-medidor.js'])
    @endpush
</x-layouts.app-bootstrap>
