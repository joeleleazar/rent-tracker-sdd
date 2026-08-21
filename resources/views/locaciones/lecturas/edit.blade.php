<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Editar Lectura del Medidor — {{ $lectura->locacion->nombre }}
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

        @if ($reciboEmitido !== null)
            <x-mensaje-alerta tipo="error">
                Ya existe un recibo emitido para este periodo. Si corrige la lectura, el recibo ya emitido NO se actualizará automáticamente; edítelo manualmente si corresponde.
                <a href="{{ route('recibos.edit', $reciboEmitido) }}" class="font-bold underline">Editar el recibo</a>.
            </x-mensaje-alerta>
        @endif

        <form method="POST" action="{{ route('lecturas.update', $lectura) }}" class="space-y-6 rounded-md border-2 border-gray-300 bg-white p-6">
            @csrf
            @method('PUT')

            <input type="hidden" name="periodo" value="{{ $lectura->periodo->format('Y-m-d') }}">

            <p class="text-lg text-gray-700">
                Periodo: <strong>{{ $lectura->periodo->translatedFormat('F Y') }}</strong>
            </p>

            <div>
                <x-input-label for="lectura_anterior" value="Lectura Anterior" />
                <x-text-input id="lectura_anterior" name="lectura_anterior" type="number" step="0.01" min="0" :value="old('lectura_anterior', $lectura->lectura_anterior)" />
                <x-input-error :messages="$errors->get('lectura_anterior')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="lectura_actual" value="Lectura Actual" />
                <x-text-input id="lectura_actual" name="lectura_actual" type="number" step="0.01" min="0" :value="old('lectura_actual', $lectura->lectura_actual)" required />
                <x-input-error :messages="$errors->get('lectura_actual')" class="mt-2" />
            </div>

            @if ($errors->has('lectura_actual'))
                <label class="flex items-center gap-2 text-lg">
                    <input type="checkbox" name="confirmar_consumo_negativo" value="1" class="h-6 w-6">
                    Confirmo que la lectura es correcta aunque resulte en un consumo negativo
                </label>
            @endif

            <div class="flex flex-wrap gap-4">
                <x-primary-button>Guardar Cambios</x-primary-button>
                <a href="{{ route('locaciones.lecturas.index', $lectura->locacion) }}" class="btn-senior-secundario">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
