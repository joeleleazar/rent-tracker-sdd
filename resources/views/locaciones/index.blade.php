<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Locaciones Alquilables
        </h2>
    </x-slot>

    <div class="col-12 col-lg-9" style="max-width: 48rem;">
        <div class="d-flex flex-column gap-3">
            @if (session('mensaje'))
                <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
            @endif

            <div class="d-flex justify-content-end">
                <a href="{{ route('locaciones.create') }}" class="btn btn-primary btn-lg"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva Locación</a>
            </div>

            @if ($locaciones->isEmpty())
                <p class="fs-5">Todavía no hay locaciones alquilables registradas.</p>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach ($locaciones as $locacion)
                        <div class="card">
                            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div>
                                    <x-ruta-jerarquia-locacion :ruta="$locacion->rutaJerarquiaTruncada()" />
                                    <p class="fs-5 mb-0 mt-1">{{ $locacion->ubicacion_fisica }}</p>
                                </div>
                                <a href="{{ route('locaciones.show', $locacion) }}" class="btn btn-primary btn-lg"><i class="bi bi-eye" aria-hidden="true"></i> Ver Detalle</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.app-bootstrap>
