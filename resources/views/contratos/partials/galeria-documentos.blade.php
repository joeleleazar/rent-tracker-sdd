@props(['contrato'])

<div class="space-y-4">
    @if ($contrato->documentos->first()->tipo_archivo === 'pdf')
        @php $documento = $contrato->documentos->first(); @endphp
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-md border-2 border-gray-300 p-4">
            <a href="{{ route('contratos.documentos.show', [$contrato, $documento]) }}" target="_blank"
               class="text-lg font-semibold text-blue-800 underline">
                {{ $documento->nombre_archivo }}
            </a>
            <button type="button" class="btn-senior-peligro"
                    x-data
                    @click="$dispatch('abrir-confirmacion-borrado', {
                        accion: '{{ route('contratos.documentos.destroy', [$contrato, $documento]) }}'
                    })">
                Eliminar
            </button>
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            @foreach ($contrato->documentos->sortBy('secuencia') as $documento)
                <div class="space-y-2 rounded-md border-2 border-gray-300 p-3">
                    <a href="{{ route('contratos.documentos.show', [$contrato, $documento]) }}" target="_blank">
                        <img src="{{ route('contratos.documentos.show', [$contrato, $documento]) }}"
                             alt="{{ $documento->nombre_archivo }}"
                             class="h-40 w-full rounded-md object-cover">
                    </a>
                    <button type="button" class="btn-senior-peligro w-full"
                            x-data
                            @click="$dispatch('abrir-confirmacion-borrado', {
                                accion: '{{ route('contratos.documentos.destroy', [$contrato, $documento]) }}'
                            })">
                        Eliminar
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Modal de confirmación de borrado (Senior-First: FR-005, borrado nunca implícito) --}}
    <div x-data="{ visible: false, accion: '' }"
         x-on:abrir-confirmacion-borrado.window="visible = true; accion = $event.detail.accion"
         x-show="visible"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         style="display: none;">
        <div class="w-full max-w-md space-y-6 rounded-md bg-white p-6" @click.outside="visible = false">
            <h4 class="text-xl font-bold text-gray-900">¿Eliminar este documento?</h4>
            <p class="text-lg text-gray-700">Esta acción no se puede deshacer.</p>
            <form method="POST" :action="accion" class="flex flex-wrap gap-4">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-senior-peligro">Sí, Eliminar</button>
                <button type="button" class="btn-senior-secundario" @click="visible = false">Cancelar</button>
            </form>
        </div>
    </div>
</div>
