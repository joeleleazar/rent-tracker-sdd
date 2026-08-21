<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Registrar Lectura del Medidor — {{ $locacion->nombre }}
        </h2>
    </x-slot>

    <div class="max-w-2xl space-y-6">
        @if ($errors->any())
            <x-mensaje-alerta tipo="error">
                <ul class="list-disc space-y-1 pl-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-mensaje-alerta>
        @endif

        <form method="GET" action="{{ route('locaciones.lecturas.create', $locacion) }}" class="flex flex-wrap items-end gap-4 rounded-md border-2 border-gray-300 bg-white p-6">
            <div>
                <x-input-label for="periodo_selector" value="Periodo (mes)" />
                <input id="periodo_selector" name="periodo" type="month" class="campo-senior" value="{{ $periodo->format('Y-m') }}">
            </div>
            <x-secondary-button>Cambiar Periodo</x-secondary-button>
        </form>

        <form method="POST" action="{{ route('locaciones.lecturas.store', $locacion) }}" class="space-y-6 rounded-md border-2 border-gray-300 bg-white p-6">
            @csrf

            <input type="hidden" name="periodo" value="{{ $periodo->format('Y-m-d') }}">

            <p class="text-lg text-gray-700">
                Periodo: <strong>{{ $periodo->translatedFormat('F Y') }}</strong>
            </p>

            <div>
                <x-input-label for="lectura_anterior" value="Lectura Anterior" />
                @if ($lecturaAnteriorSugerida === null)
                    <p class="mb-2 text-lg font-semibold text-gray-700">Sin lectura previa registrada</p>
                @endif
                <x-text-input id="lectura_anterior" name="lectura_anterior" type="number" step="0.01" min="0" :value="old('lectura_anterior', $lecturaAnteriorSugerida)" />
                <x-input-error :messages="$errors->get('lectura_anterior')" class="mt-2" />
                <p class="mt-2 text-lg text-gray-700">
                    Precargada automáticamente con la lectura actual del periodo anterior; puede editarla si es necesario.
                </p>
            </div>

            <div>
                <x-input-label for="lectura_actual" value="Lectura Actual" />
                <x-text-input id="lectura_actual" name="lectura_actual" type="number" step="0.01" min="0" :value="old('lectura_actual')" required />
                <x-input-error :messages="$errors->get('lectura_actual')" class="mt-2" />
            </div>

            @if ($errors->has('lectura_actual'))
                <label class="flex items-center gap-2 text-lg">
                    <input type="checkbox" name="confirmar_consumo_negativo" value="1" class="h-6 w-6">
                    Confirmo que la lectura es correcta aunque resulte en un consumo negativo
                </label>
            @endif

            <div class="flex flex-wrap gap-4">
                <x-primary-button>Guardar Lectura del Periodo</x-primary-button>
                <a href="{{ route('locaciones.lecturas.index', $locacion) }}" class="btn-senior-secundario">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
