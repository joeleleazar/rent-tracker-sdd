<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Emitir Recibo del Periodo — {{ $locacion->nombre }}
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

        <form method="GET" action="{{ route('locaciones.recibos.create', $locacion) }}" class="flex flex-wrap items-end gap-4 rounded-md border-2 border-gray-300 bg-white p-6">
            <div>
                <x-input-label for="periodo" value="Periodo (mes)" />
                <input id="periodo" name="periodo" type="month" class="campo-senior" value="{{ $periodo->format('Y-m') }}">
            </div>
            <x-secondary-button>Cambiar Periodo</x-secondary-button>
        </form>

        @if ($reciboExistente !== null)
            <x-mensaje-alerta tipo="error">
                Ya existe un recibo emitido para {{ $periodo->translatedFormat('F Y') }} en esta locación.
                <a href="{{ route('recibos.edit', $reciboExistente) }}" class="font-bold underline">Editar el recibo existente</a>.
            </x-mensaje-alerta>
        @elseif ($contratoActivo === null)
            <x-mensaje-alerta tipo="error">
                No hay un contrato activo vigente para {{ $periodo->translatedFormat('F Y') }} en esta locación. No se puede emitir un recibo para este periodo, pero puede registrar la lectura del medidor de forma independiente.
            </x-mensaje-alerta>
        @else
            <p class="text-lg text-gray-700">
                @if ($lectura !== null && $lectura->consumo_calculado !== null)
                    Consumo del periodo: <strong>{{ number_format((float) $lectura->consumo_calculado, 2) }}</strong> unidades.
                @else
                    Sin lectura de medidor registrada para este periodo (monto de luz sugerido: S/ 0.00).
                @endif
            </p>

            @if ($prorrateo !== null)
                <x-mensaje-alerta tipo="exito">
                    Este contrato estuvo activo <strong>{{ $prorrateo['dias_activos'] }} días de {{ $prorrateo['dias_totales'] }}</strong> en este periodo. Se sugiere un monto de renta prorrateado de <strong>S/ {{ number_format($prorrateo['monto_renta_sugerido'], 2) }}</strong>, editable antes de confirmar.
                </x-mensaje-alerta>
            @endif

            <form method="POST" action="{{ route('locaciones.recibos.store', $locacion) }}" class="space-y-6 rounded-md border-2 border-gray-300 bg-white p-6">
                @csrf

                <input type="hidden" name="periodo" value="{{ $periodo->format('Y-m-d') }}">

                <p class="text-lg text-gray-700">
                    Contrato vigente: inquilino <strong>{{ $contratoActivo->inquilino->nombre }}</strong>. Marque los conceptos a incluir y edite los montos antes de emitir.
                </p>

                <div class="flex flex-wrap items-center gap-3 rounded-md border-2 border-gray-300 p-4">
                    <input type="checkbox" id="incluye_alquiler" name="incluye_alquiler" value="1" class="h-6 w-6" checked>
                    <x-input-label for="incluye_alquiler" value="Incluir Alquiler" class="flex-none" />
                    <x-text-input id="monto_renta" name="monto_renta" type="number" step="0.01" min="0" :value="old('monto_renta', $prorrateo['monto_renta_sugerido'] ?? $contratoActivo->monto_renta)" class="max-w-xs" required />
                    <x-input-error :messages="$errors->get('monto_renta')" />
                </div>

                <div class="flex flex-wrap items-center gap-3 rounded-md border-2 border-gray-300 p-4">
                    <input type="checkbox" id="incluye_luz" name="incluye_luz" value="1" class="h-6 w-6" checked>
                    <x-input-label for="incluye_luz" value="Incluir Luz (calculado por consumo)" class="flex-none" />
                    <x-text-input id="monto_luz" name="monto_luz" type="number" step="0.01" min="0" :value="old('monto_luz', $montoLuzSugerido)" class="max-w-xs" />
                    <x-input-error :messages="$errors->get('monto_luz')" />
                </div>

                <div class="flex flex-wrap items-center gap-3 rounded-md border-2 border-gray-300 p-4">
                    <input type="checkbox" id="incluye_agua" name="incluye_agua" value="1" class="h-6 w-6" checked>
                    <x-input-label for="incluye_agua" value="Incluir Agua" class="flex-none" />
                    <x-text-input id="monto_agua" name="monto_agua" type="number" step="0.01" min="0" :value="old('monto_agua', $contratoActivo->costo_agua)" class="max-w-xs" />
                    <x-input-error :messages="$errors->get('monto_agua')" />
                </div>

                <div class="flex flex-wrap items-center gap-3 rounded-md border-2 border-gray-300 p-4">
                    <input type="checkbox" id="incluye_pasadizo" name="incluye_pasadizo" value="1" class="h-6 w-6" checked>
                    <x-input-label for="incluye_pasadizo" value="Incluir Luz de Pasadizo" class="flex-none" />
                    <x-text-input id="monto_pasadizo" name="monto_pasadizo" type="number" step="0.01" min="0" :value="old('monto_pasadizo', $contratoActivo->costo_pasadizo)" class="max-w-xs" />
                    <x-input-error :messages="$errors->get('monto_pasadizo')" />
                </div>

                <div class="flex flex-wrap items-center gap-3 rounded-md border-2 border-gray-300 p-4">
                    <input type="checkbox" id="incluye_seguridad" name="incluye_seguridad" value="1" class="h-6 w-6" checked>
                    <x-input-label for="incluye_seguridad" value="Incluir Seguridad" class="flex-none" />
                    <x-text-input id="monto_seguridad" name="monto_seguridad" type="number" step="0.01" min="0" :value="old('monto_seguridad', $contratoActivo->costo_seguridad)" class="max-w-xs" />
                    <x-input-error :messages="$errors->get('monto_seguridad')" />
                </div>

                <div>
                    <x-input-label for="fecha_emision" value="Fecha de Emisión" />
                    <x-text-input id="fecha_emision" name="fecha_emision" type="date" :value="old('fecha_emision', now()->format('Y-m-d'))" required />
                    <x-input-error :messages="$errors->get('fecha_emision')" class="mt-2" />
                </div>

                <div class="flex flex-wrap gap-4">
                    <x-primary-button>Emitir Recibo del Periodo</x-primary-button>
                    <a href="{{ route('locaciones.recibos.index', $locacion) }}" class="btn-senior-secundario">Cancelar</a>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
