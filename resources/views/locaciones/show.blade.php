<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Detalle de Locación
        </h2>
    </x-slot>

    <div class="max-w-2xl space-y-6">
        @if (session('mensaje'))
            <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
        @endif

        @if ($errors->any())
            <x-mensaje-alerta tipo="error">
                <ul class="list-disc space-y-1 pl-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-mensaje-alerta>
        @endif

        <div class="space-y-4 rounded-md border-2 border-gray-300 bg-white p-6">
            <x-ruta-jerarquia-locacion :ruta="$locacion->rutaJerarquiaTruncada()" />

            <dl class="space-y-4">
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Tamaño</dt>
                    <dd class="text-lg text-gray-900">{{ number_format((float) $locacion->tamano, 2) }} m²</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Ubicación física</dt>
                    <dd class="text-lg text-gray-900">{{ $locacion->ubicacion_fisica }}</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Descripción</dt>
                    <dd class="text-lg text-gray-900">{{ $locacion->descripcion }}</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Alquilable</dt>
                    <dd class="text-lg text-gray-900">
                        <span class="rounded-md border-2 border-gray-700 bg-gray-100 px-3 py-1 font-semibold">
                            {{ $locacion->es_alquilable ? 'Sí' : 'No' }}
                        </span>
                    </dd>
                </div>
            </dl>

            <div class="flex flex-wrap gap-4 pt-2">
                <a href="{{ route('locaciones.edit', $locacion) }}" class="btn-senior-primario">Editar Locación</a>
                @if ($locacion->es_alquilable)
                    <a href="{{ route('contratos.index', $locacion) }}" class="btn-senior-secundario">Ver Contratos</a>
                @endif

                <x-danger-button
                    type="button"
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'confirmar-eliminar-locacion')"
                >Eliminar Locación</x-danger-button>
            </div>
        </div>

        <x-modal name="confirmar-eliminar-locacion" focusable>
            <form method="POST" action="{{ route('locaciones.destroy', $locacion) }}" class="p-6">
                @csrf
                @method('delete')

                <h2 class="text-xl font-bold text-gray-900">
                    ¿Está seguro de eliminar "{{ $locacion->nombre }}"?
                </h2>

                <p class="mt-2 text-lg text-gray-700">
                    Esta acción no se puede deshacer. Si la locación tiene sub-locaciones asociadas, no podrá eliminarse.
                </p>

                <div class="mt-6 flex justify-end gap-4">
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">
                        No, cancelar
                    </x-secondary-button>

                    <x-danger-button>
                        Sí, eliminar locación
                    </x-danger-button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
