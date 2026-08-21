<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Editar Recibo #{{ $recibo->id }} — {{ $recibo->locacion->nombre }}
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

        <p class="text-lg text-gray-700">
            Periodo: <strong>{{ $recibo->periodo->translatedFormat('F Y') }}</strong>
        </p>

        <form method="POST" action="{{ route('recibos.update', $recibo) }}" class="space-y-6 rounded-md border-2 border-gray-300 bg-white p-6">
            @csrf
            @method('PUT')

            <input type="hidden" name="periodo" value="{{ $recibo->periodo->format('Y-m-d') }}">

            <div class="flex flex-wrap items-center gap-3 rounded-md border-2 border-gray-300 p-4">
                <input type="checkbox" id="incluye_alquiler" name="incluye_alquiler" value="1" class="h-6 w-6" @checked(old('incluye_alquiler', $recibo->incluye_alquiler))>
                <x-input-label for="incluye_alquiler" value="Incluir Alquiler" class="flex-none" />
                <x-text-input id="monto_renta" name="monto_renta" type="number" step="0.01" min="0" :value="old('monto_renta', $recibo->monto_renta)" class="max-w-xs" required />
                <x-input-error :messages="$errors->get('monto_renta')" />
            </div>

            <div class="flex flex-wrap items-center gap-3 rounded-md border-2 border-gray-300 p-4">
                <input type="checkbox" id="incluye_luz" name="incluye_luz" value="1" class="h-6 w-6" @checked(old('incluye_luz', $recibo->incluye_luz))>
                <x-input-label for="incluye_luz" value="Incluir Luz" class="flex-none" />
                <x-text-input id="monto_luz" name="monto_luz" type="number" step="0.01" min="0" :value="old('monto_luz', $recibo->monto_luz)" class="max-w-xs" />
                <x-input-error :messages="$errors->get('monto_luz')" />
            </div>

            <div class="flex flex-wrap items-center gap-3 rounded-md border-2 border-gray-300 p-4">
                <input type="checkbox" id="incluye_agua" name="incluye_agua" value="1" class="h-6 w-6" @checked(old('incluye_agua', $recibo->incluye_agua))>
                <x-input-label for="incluye_agua" value="Incluir Agua" class="flex-none" />
                <x-text-input id="monto_agua" name="monto_agua" type="number" step="0.01" min="0" :value="old('monto_agua', $recibo->monto_agua)" class="max-w-xs" />
                <x-input-error :messages="$errors->get('monto_agua')" />
            </div>

            <div class="flex flex-wrap items-center gap-3 rounded-md border-2 border-gray-300 p-4">
                <input type="checkbox" id="incluye_pasadizo" name="incluye_pasadizo" value="1" class="h-6 w-6" @checked(old('incluye_pasadizo', $recibo->incluye_pasadizo))>
                <x-input-label for="incluye_pasadizo" value="Incluir Luz de Pasadizo" class="flex-none" />
                <x-text-input id="monto_pasadizo" name="monto_pasadizo" type="number" step="0.01" min="0" :value="old('monto_pasadizo', $recibo->monto_pasadizo)" class="max-w-xs" />
                <x-input-error :messages="$errors->get('monto_pasadizo')" />
            </div>

            <div class="flex flex-wrap items-center gap-3 rounded-md border-2 border-gray-300 p-4">
                <input type="checkbox" id="incluye_seguridad" name="incluye_seguridad" value="1" class="h-6 w-6" @checked(old('incluye_seguridad', $recibo->incluye_seguridad))>
                <x-input-label for="incluye_seguridad" value="Incluir Seguridad" class="flex-none" />
                <x-text-input id="monto_seguridad" name="monto_seguridad" type="number" step="0.01" min="0" :value="old('monto_seguridad', $recibo->monto_seguridad)" class="max-w-xs" />
                <x-input-error :messages="$errors->get('monto_seguridad')" />
            </div>

            <div>
                <x-input-label for="fecha_emision" value="Fecha de Emisión" />
                <x-text-input id="fecha_emision" name="fecha_emision" type="date" :value="old('fecha_emision', $recibo->fecha_emision->format('Y-m-d'))" required />
                <x-input-error :messages="$errors->get('fecha_emision')" class="mt-2" />
            </div>

            <div class="flex flex-wrap gap-4">
                <x-primary-button>Guardar Cambios del Recibo</x-primary-button>
                <a href="{{ route('recibos.show', $recibo) }}" class="btn-senior-secundario">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
