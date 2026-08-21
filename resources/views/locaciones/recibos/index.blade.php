<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Recibos — {{ $locacion->nombre }}
        </h2>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        @if (session('mensaje'))
            <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
        @endif

        <div class="flex flex-wrap gap-4">
            <a href="{{ route('locaciones.recibos.create', $locacion) }}" class="btn-senior-primario">Emitir Recibo</a>
        </div>

        @if ($recibos->isEmpty())
            <p class="text-lg text-gray-700">Esta locación todavía no tiene recibos emitidos.</p>
        @else
            <div class="space-y-4">
                @foreach ($recibos as $recibo)
                    <a href="{{ route('recibos.show', $recibo) }}" class="block rounded-md border-2 border-gray-300 bg-white p-6 hover:bg-gray-50">
                        <p class="text-lg font-semibold text-gray-900">
                            Periodo: {{ $recibo->periodo->translatedFormat('F Y') }}
                        </p>
                        <p class="text-lg text-gray-700">
                            Total cobrado: S/ {{ number_format($recibo->total(), 2) }}
                        </p>
                        <p class="text-lg text-gray-700">
                            Emitido el {{ $recibo->fecha_emision->format('d/m/Y') }}
                        </p>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
