<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Contrato #{{ $contrato->id }} — {{ $contrato->locacion->nombre }}
        </h2>
    </x-slot>

    <div class="max-w-2xl space-y-6">
        @if (session('mensaje'))
            <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
        @endif

        <div class="space-y-4 rounded-md border-2 border-gray-300 bg-white p-6">
            <dl class="space-y-4">
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Locación</dt>
                    <dd class="text-lg text-gray-900">{{ $contrato->locacion->nombre }}</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Inquilino</dt>
                    <dd class="text-lg text-gray-900">{{ $contrato->inquilino->nombre }}</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Fecha de inicio</dt>
                    <dd class="text-lg text-gray-900">{{ $contrato->fecha_inicio->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Fecha de fin</dt>
                    <dd class="text-lg text-gray-900">{{ $contrato->fecha_fin->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Monto de renta</dt>
                    <dd class="text-lg text-gray-900">S/ {{ number_format((float) $contrato->monto_renta, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Estado</dt>
                    <dd class="text-lg text-gray-900">
                        <span class="rounded-md border-2 border-gray-700 bg-gray-100 px-3 py-1 font-semibold">
                            {{ ucfirst($contrato->estado) }}
                        </span>
                    </dd>
                </div>
            </dl>

            <div class="flex flex-wrap gap-4 pt-2">
                <a href="{{ route('contratos.edit', $contrato) }}" class="btn-senior-primario">Editar Contrato</a>
                <a href="{{ route('contratos.index', $contrato->locacion) }}" class="btn-senior-secundario">Ver Historial</a>
            </div>
        </div>

        <div class="space-y-4 rounded-md border-2 border-gray-300 bg-white p-6">
            <h3 class="text-xl font-bold text-gray-900">Documentos del Contrato</h3>

            @if ($errors->any())
                <x-mensaje-alerta tipo="error">
                    <ul class="list-disc space-y-1 pl-6">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-mensaje-alerta>
            @endif

            @php
                $tienePdf = $contrato->documentos->contains('tipo_archivo', 'pdf');
                $totalImagenes = $contrato->documentos->where('tipo_archivo', 'imagen')->count();
            @endphp

            @if ($contrato->documentos->isNotEmpty())
                @include('contratos.partials.galeria-documentos', ['contrato' => $contrato])
            @endif

            @unless ($tienePdf)
                <form method="POST" action="{{ route('contratos.documentos.store', $contrato) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div class="flex flex-wrap gap-4">
                        @if ($totalImagenes === 0)
                            <label class="btn-senior-primario cursor-pointer">
                                Seleccionar PDF del Contrato
                                <input type="file" name="archivo_pdf" accept="application/pdf" class="hidden" onchange="this.form.requestSubmit()">
                            </label>
                        @endif
                        @if ($totalImagenes < 10)
                            <label class="btn-senior-primario cursor-pointer">
                                Subir Foto de Página
                                <input type="file" name="archivo_imagenes[]" accept="image/jpeg,image/png" multiple class="hidden" onchange="this.form.requestSubmit()">
                            </label>
                        @endif
                    </div>
                </form>
            @endunless
        </div>
    </div>
</x-app-layout>
