<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Detalle de Locación
        </h2>
    </x-slot>

    <div class="col-12 col-lg-8" style="max-width: 42rem;">
        <div class="d-flex flex-column gap-3">
            @if (session('mensaje'))
                <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
            @endif

            @if ($errors->any())
                <x-mensaje-alerta tipo="error">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-mensaje-alerta>
            @endif

            <div class="card">
                <div class="card-body d-flex flex-column gap-3">
                    <x-ruta-jerarquia-locacion :ruta="$locacion->rutaJerarquiaTruncada()" />

                    <dl class="row mb-0">
                        <dt class="col-sm-4 fs-5 fw-semibold">Tamaño</dt>
                        <dd class="col-sm-8 fs-5">{{ number_format((float) $locacion->tamano, 2) }} m²</dd>

                        <dt class="col-sm-4 fs-5 fw-semibold">Ubicación física</dt>
                        <dd class="col-sm-8 fs-5">{{ $locacion->ubicacion_fisica }}</dd>

                        <dt class="col-sm-4 fs-5 fw-semibold">Descripción</dt>
                        <dd class="col-sm-8 fs-5">{{ $locacion->descripcion }}</dd>

                        <dt class="col-sm-4 fs-5 fw-semibold">Alquilable</dt>
                        <dd class="col-sm-8 fs-5">
                            <span class="badge text-bg-secondary fs-6">
                                {{ $locacion->es_alquilable ? 'Sí' : 'No' }}
                            </span>
                        </dd>
                    </dl>

                    <div class="d-flex flex-wrap gap-3 pt-2">
                        <a href="{{ route('locaciones.edit', $locacion) }}" class="btn btn-primary btn-lg"><i class="bi bi-pencil-square" aria-hidden="true"></i> Editar Locación</a>
                        @if ($locacion->es_alquilable)
                            <a href="{{ route('contratos.index', $locacion) }}" class="btn btn-outline-secondary btn-lg"><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Ver Contratos</a>
                        @endif

                        <x-danger-button
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#confirmar-eliminar-locacion"
                        ><i class="bi bi-trash" aria-hidden="true"></i> Eliminar Locación</x-danger-button>
                    </div>
                </div>
            </div>

            <x-modal-bootstrap name="confirmar-eliminar-locacion" focusable>
                <form method="POST" action="{{ route('locaciones.destroy', $locacion) }}">
                    @csrf
                    @method('delete')

                    <div class="modal-body p-4">
                        <h2 class="fs-4 fw-bold">
                            ¿Está seguro de eliminar "{{ $locacion->nombre }}"?
                        </h2>

                        <p class="fs-5 mb-0">
                            Esta acción no se puede deshacer. Si la locación tiene sub-locaciones asociadas, no podrá eliminarse.
                        </p>
                    </div>

                    <div class="modal-footer">
                        <x-secondary-button type="button" data-bs-dismiss="modal">
                            No, cancelar
                        </x-secondary-button>

                        <x-danger-button>
                            Sí, eliminar locación
                        </x-danger-button>
                    </div>
                </form>
            </x-modal-bootstrap>
        </div>
    </div>
</x-layouts.app-bootstrap>
