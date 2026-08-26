<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Editar Concepto — {{ $concepto->nombre }}
        </h2>
    </x-slot>

    <div class="col-12 col-lg-6" style="max-width: 32rem;">
        <div class="d-flex flex-column gap-3">
            @if ($concepto->esProtegido())
                <x-mensaje-alerta tipo="exito">
                    Este es un concepto protegido: no puede desactivarse ni eliminarse, porque el sistema depende de que esté siempre disponible.
                </x-mensaje-alerta>
            @endif

            <form method="POST" action="{{ route('conceptosGastoFijo.update', $concepto) }}" class="card">
                <div class="card-body d-flex flex-column gap-3">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="nombre" value="Nombre" />
                        <x-text-input id="nombre" name="nombre" type="text" :value="old('nombre', $concepto->nombre)" required autofocus />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="orden" value="Orden de aparición" />
                        <x-text-input id="orden" name="orden" type="number" min="0" :value="old('orden', $concepto->orden)" required />
                        <x-input-error :messages="$errors->get('orden')" class="mt-2" />
                    </div>

                    @unless ($concepto->esProtegido())
                        <div class="form-check d-flex align-items-center gap-2">
                            <input type="checkbox" id="activo" name="activo" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" @checked(old('activo', $concepto->activo))>
                            <label for="activo" class="form-check-label fw-semibold">Concepto activo</label>
                        </div>
                        <x-input-error :messages="$errors->get('activo')" class="mt-0" />
                    @endunless

                    <div class="d-flex flex-wrap gap-3">
                        <x-primary-button>Guardar Cambios</x-primary-button>
                        <a href="{{ route('conceptosGastoFijo.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app-bootstrap>
