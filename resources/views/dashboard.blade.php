<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Locaciones
        </h2>
    </x-slot>

    <div class="col-12 col-lg-9" style="max-width: 48rem;">
        @if ($locaciones->isEmpty())
            <p class="fs-5">Todavía no hay locaciones registradas.</p>
        @else
            <div class="d-flex flex-column gap-3">
                @foreach ($locaciones as $locacion)
                    <div class="card">
                        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <p class="fs-5 fw-bold mb-0">{{ $locacion->nombre }}</p>
                                <p class="fs-5 mb-0">{{ $locacion->ubicacion_fisica }}</p>
                            </div>
                            <a href="{{ route('contratos.index', $locacion) }}" class="btn btn-primary btn-lg"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Ver Contratos</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app-bootstrap>
