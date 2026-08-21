<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Historial de Contratos — {{ $locacion->nombre }}
        </h2>
    </x-slot>

    <div class="col-12 col-lg-9" style="max-width: 48rem;">
        <div class="d-flex flex-column gap-3">
            @if (session('mensaje'))
                <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
            @endif

            <a href="{{ route('contratos.create', $locacion) }}" class="btn btn-primary btn-lg align-self-start">
                Registrar Nuevo Contrato
            </a>

            @if ($contratos->isEmpty())
                <p class="fs-5">Esta locación todavía no tiene contratos registrados.</p>
            @else
                {{--
                    Timeline de historial (specs/012, FR-003): indicador de fecha +
                    badge de estado por contrato, con una línea vertical que conecta
                    las entradas — construido con primitivas de Bootstrap
                    (border-start, badge), sin librería nueva (ver research.md §1).
                --}}
                <div class="d-flex flex-column">
                    @foreach ($contratos as $contrato)
                        @php
                            $colorEstado = match ($contrato->estado) {
                                'activo' => 'success',
                                'rescindido' => 'danger',
                                default => 'secondary',
                            };
                        @endphp
                        <div class="border-start border-4 border-{{ $colorEstado }} ps-4 pb-4 position-relative">
                            <span
                                class="position-absolute top-0 start-0 translate-middle-x rounded-circle bg-{{ $colorEstado }}"
                                style="width: 1rem; height: 1rem; margin-top: 0.4rem;"
                                aria-hidden="true"
                            ></span>

                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <span class="fs-5 text-secondary">
                                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                    {{ $contrato->fecha_inicio->format('d/m/Y') }} — {{ $contrato->fecha_fin->format('d/m/Y') }}
                                </span>
                                <span class="badge text-bg-{{ $colorEstado }} fs-6">{{ ucfirst($contrato->estado) }}</span>
                            </div>

                            <p class="fs-5 fw-bold mb-1">Contrato #{{ $contrato->id }}</p>
                            <p class="fs-5 mb-2">Inquilino: {{ $contrato->inquilino->nombre }}</p>

                            <a href="{{ route('contratos.show', $contrato) }}" class="btn btn-outline-secondary btn-lg"><i class="bi bi-eye" aria-hidden="true"></i> Ver Detalle</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.app-bootstrap>
