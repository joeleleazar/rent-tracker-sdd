<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Locaciones Alquilables
        </h2>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        @if (session('mensaje'))
            <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
        @endif

        <div class="flex justify-end">
            <a href="{{ route('locaciones.create') }}" class="btn-senior-primario">Nueva Locación</a>
        </div>

        @if ($locaciones->isEmpty())
            <p class="text-lg text-gray-700">Todavía no hay locaciones alquilables registradas.</p>
        @else
            <ul class="space-y-4">
                @foreach ($locaciones as $locacion)
                    <li class="rounded-md border-2 border-gray-300 bg-white p-6">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <x-ruta-jerarquia-locacion :ruta="$locacion->rutaJerarquiaTruncada()" />
                                <p class="mt-1 text-lg text-gray-700">{{ $locacion->ubicacion_fisica }}</p>
                            </div>
                            <a href="{{ route('locaciones.show', $locacion) }}" class="btn-senior-primario">Ver Detalle</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>
