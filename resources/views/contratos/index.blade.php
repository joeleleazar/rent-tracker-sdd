<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Historial de Contratos — {{ $locacion->nombre }}
        </h2>
    </x-slot>

    <div class="max-w-3xl space-y-6">
        @if (session('mensaje'))
            <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
        @endif

        <a href="{{ route('contratos.create', $locacion) }}" class="btn-senior-primario inline-flex w-fit">
            Registrar Nuevo Contrato
        </a>

        @if ($contratos->isEmpty())
            <p class="text-lg text-gray-700">Esta locación todavía no tiene contratos registrados.</p>
        @else
            <ul class="space-y-4">
                @foreach ($contratos as $contrato)
                    <li class="rounded-md border-2 {{ $contrato->estado === 'activo' ? 'border-green-800 bg-green-50' : 'border-gray-300 bg-white' }} p-6">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <p class="text-lg font-bold text-gray-900">Contrato #{{ $contrato->id }}</p>
                                <p class="text-lg text-gray-700">
                                    {{ $contrato->fecha_inicio->format('d/m/Y') }} — {{ $contrato->fecha_fin->format('d/m/Y') }}
                                </p>
                                <p class="text-lg text-gray-700">Inquilino: {{ $contrato->inquilino->nombre }}</p>
                            </div>
                            <div class="flex items-center gap-4">
                                @if ($contrato->estado === 'activo')
                                    <span class="rounded-md border-2 border-green-800 bg-green-100 px-4 py-2 text-lg font-bold text-green-900">
                                        Activo
                                    </span>
                                @else
                                    <span class="rounded-md border-2 border-gray-700 bg-gray-100 px-4 py-2 text-lg font-semibold text-gray-900">
                                        {{ ucfirst($contrato->estado) }}
                                    </span>
                                @endif
                                <a href="{{ route('contratos.show', $contrato) }}" class="btn-senior-secundario">Ver Detalle</a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-app-layout>
