<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Lecturas de Medidor — {{ $locacion->nombre }}
        </h2>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        @if (session('mensaje'))
            <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
        @endif

        <div class="flex flex-wrap gap-4">
            <a href="{{ route('locaciones.lecturas.create', $locacion) }}" class="btn-senior-primario">Registrar Lectura del Medidor</a>
        </div>

        @if ($lecturas->isEmpty())
            <p class="text-lg text-gray-700">Esta locación todavía no tiene lecturas de medidor registradas.</p>
        @else
            <div class="space-y-4">
                @foreach ($lecturas as $lectura)
                    @php
                        $reciboDelPeriodo = $recibosPorPeriodo->get($lectura->periodo->format('Y-m-d'));
                    @endphp
                    <div class="rounded-md border-2 border-gray-300 bg-white p-6">
                        <p class="text-lg font-semibold text-gray-900">
                            Periodo: {{ $lectura->periodo->translatedFormat('F Y') }}
                        </p>
                        <p class="text-lg text-gray-700">
                            Lectura anterior:
                            @if ($lectura->lectura_anterior === null)
                                sin lectura previa registrada
                            @else
                                {{ number_format((float) $lectura->lectura_anterior, 2) }}
                            @endif
                        </p>
                        <p class="text-lg text-gray-700">
                            Lectura actual: {{ number_format((float) $lectura->lectura_actual, 2) }}
                        </p>
                        <p class="text-lg text-gray-700">
                            Consumo:
                            @if ($lectura->consumo_calculado === null)
                                sin dato anterior
                            @else
                                {{ number_format((float) $lectura->consumo_calculado, 2) }} unidades
                            @endif
                        </p>
                        @if ($lectura->discrepanciaConSiguiente())
                            <x-mensaje-alerta tipo="error" class="mt-2">
                                Advertencia: la lectura actual de este periodo no coincide con la lectura anterior usada en el periodo siguiente.
                            </x-mensaje-alerta>
                        @endif
                        <div class="mt-2 flex flex-wrap gap-4">
                            <a href="{{ route('lecturas.edit', $lectura) }}" class="btn-senior-secundario">Editar Lectura</a>
                            @if ($reciboDelPeriodo !== null)
                                <a href="{{ route('recibos.show', $reciboDelPeriodo) }}" class="btn-senior-secundario">Ver Recibo del Periodo</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
