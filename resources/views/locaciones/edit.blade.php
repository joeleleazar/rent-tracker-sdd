<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Editar Locación — {{ $locacion->nombre }}
        </h2>
    </x-slot>

    <div class="max-w-2xl">
        @if ($errors->any())
            <x-mensaje-alerta tipo="error" class="mb-6">
                <ul class="list-disc space-y-1 pl-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-mensaje-alerta>
        @endif

        <form method="POST" action="{{ route('locaciones.update', $locacion) }}" class="space-y-6">
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
                <textarea id="descripcion" name="descripcion" class="campo-senior" rows="3" required>{{ old('descripcion', $locacion->descripcion) }}</textarea>
                <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="locacion_padre_id" value="Locación padre (opcional)" />
                <select id="locacion_padre_id" name="locacion_padre_id" class="campo-senior">
                    <option value="">Ninguna (locación raíz)</option>
                    @foreach ($locaciones as $opcion)
                        <option value="{{ $opcion->id }}" @selected(old('locacion_padre_id', $locacion->locacion_padre_id) == $opcion->id)>
                            {{ $opcion->nombre }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('locacion_padre_id')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3">
                <input type="hidden" name="es_alquilable" value="0">
                <input type="checkbox" id="es_alquilable" name="es_alquilable" value="1" class="h-6 w-6" @checked(old('es_alquilable', $locacion->es_alquilable))>
                <x-input-label for="es_alquilable" value="Es alquilable" class="!mb-0" />
            </div>

            <div class="flex flex-wrap gap-4">
                <x-primary-button>Guardar Cambios</x-primary-button>
                <a href="{{ route('locaciones.show', $locacion) }}" class="btn-senior-secundario">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
