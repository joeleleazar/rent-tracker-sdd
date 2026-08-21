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
                <div class="d-flex flex-column gap-3">
                    @foreach ($contratos as $contrato)
                        <div class="card {{ $contrato->estado === 'activo' ? 'border-success' : '' }}">
                            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div>
                                    <p class="fs-5 fw-bold mb-0">Contrato #{{ $contrato->id }}</p>
                                    <p class="fs-5 mb-0">
                                        {{ $contrato->fecha_inicio->format('d/m/Y') }} — {{ $contrato->fecha_fin->format('d/m/Y') }}
                                    </p>
                                    <p class="fs-5 mb-0">Inquilino: {{ $contrato->inquilino->nombre }}</p>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    @if ($contrato->estado === 'activo')
                                        <span class="badge text-bg-success fs-6">
                                            Activo
                                        </span>
                                    @else
                                        <span class="badge text-bg-secondary fs-6">
                                            {{ ucfirst($contrato->estado) }}
                                        </span>
                                    @endif
                                    <a href="{{ route('contratos.show', $contrato) }}" class="btn btn-outline-secondary btn-lg">Ver Detalle</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.app-bootstrap>
