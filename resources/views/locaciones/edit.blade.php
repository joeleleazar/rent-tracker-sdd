<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Editar Locación — {{ $locacion->nombre }}
        </h2>
    </x-slot>

    <div class="col-12 col-lg-8" style="max-width: 42rem;">
        @if ($errors->any())
            <x-mensaje-alerta tipo="error" class="mb-4">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-mensaje-alerta>
        @endif

        <form method="POST" action="{{ route('locaciones.update', $locacion) }}" class="d-flex flex-column gap-4">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="nombre" value="Nombre" />
                <x-text-input id="nombre" name="nombre" type="text" :value="old('nombre', $locacion->nombre)" required />
                <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="tamano" value="Tamaño (m²)" />
                <x-text-input id="tamano" name="tamano" type="number" step="0.01" min="0.01" :value="old('tamano', $locacion->tamano)" required />
                <x-input-error :messages="$errors->get('tamano')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="ubicacion_fisica" value="Ubicación física" />
                <x-text-input id="ubicacion_fisica" name="ubicacion_fisica" type="text" :value="old('ubicacion_fisica', $locacion->ubicacion_fisica)" required />
                <x-input-error :messages="$errors->get('ubicacion_fisica')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="descripcion" value="Descripción" />
                <textarea id="descripcion" name="descripcion" class="form-control" rows="3" required>{{ old('descripcion', $locacion->descripcion) }}</textarea>
                <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="tipo" value="Tipo" />
                <select id="tipo" name="tipo" class="form-select" required>
                    <option value="">Seleccione un tipo</option>
                    @foreach (\App\Models\Locacion::TIPOS as $valor => $datos)
                        <option value="{{ $valor }}" @selected(old('tipo', $locacion->tipo) === $valor)>
                            {{ $datos['etiqueta'] }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="locacion_padre_id" value="Locación padre (opcional)" />
                <select id="locacion_padre_id" name="locacion_padre_id" class="form-select">
                    <option value="">Ninguna (locación raíz)</option>
                    @foreach ($locaciones as $opcion)
                        <option value="{{ $opcion->id }}" @selected(old('locacion_padre_id', $locacion->locacion_padre_id) == $opcion->id)>
                            {{ $opcion->nombre }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('locacion_padre_id')" class="mt-2" />
            </div>

            <div class="form-check d-flex align-items-center gap-2 ps-0">
                <input type="hidden" name="es_alquilable" value="0">
                <input type="checkbox" id="es_alquilable" name="es_alquilable" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" @checked(old('es_alquilable', $locacion->es_alquilable))>
                <label for="es_alquilable" class="form-check-label fw-semibold">Es alquilable</label>
            </div>

            <div class="d-flex flex-wrap gap-3">
                <x-primary-button>Guardar Cambios</x-primary-button>
                <a href="{{ route('locaciones.show', $locacion) }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
            </div>
        </form>
    </div>
</x-layouts.app-bootstrap>
