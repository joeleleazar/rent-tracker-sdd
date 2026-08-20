<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Locaciones
        </h2>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        @if ($locaciones->isEmpty())
            <p class="text-lg text-gray-700">Todavía no hay locaciones registradas.</p>
        @else
            <ul class="space-y-4">
                @foreach ($locaciones as $locacion)
                    <li class="rounded-md border-2 border-gray-300 bg-white p-6">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="text-lg font-bold text-gray-900">{{ $locacion->nombre }}</p>
                                <p class="text-lg text-gray-700">{{ $locacion->ubicacion_fisica }}</p>
                            </div>
                            <a href="{{ route('contratos.index', $locacion) }}" class="btn-senior-primario">Ver Contratos</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>
