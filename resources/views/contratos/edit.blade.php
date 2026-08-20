<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Editar Contrato #{{ $contrato->id }} — {{ $contrato->locacion->nombre }}
        </h2>
    </x-slot>

    <div class="max-w-2xl">
        @if ($errors->any())
            <x-mensaje-alerta tipo="error" class="mb-6">
                <ul class="list-disc space-y-1 pl-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-mensaje-alerta>
        @endif

        <form method="POST" action="{{ route('contratos.update', $contrato) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="inquilino_id" value="Inquilino" />
                <select id="inquilino_id" name="inquilino_id" class="campo-senior" required>
                    @foreach ($inquilinos as $inquilino)
                        <option value="{{ $inquilino->id }}" @selected(old('inquilino_id', $contrato->inquilino_id) == $inquilino->id)>
                            {{ $inquilino->nombre }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('inquilino_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="fecha_inicio" value="Fecha de inicio" />
                <x-text-input id="fecha_inicio" name="fecha_inicio" type="date" :value="old('fecha_inicio', $contrato->fecha_inicio->format('Y-m-d'))" required />
                <x-input-error :messages="$errors->get('fecha_inicio')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="fecha_fin" value="Fecha de fin" />
                <x-text-input id="fecha_fin" name="fecha_fin" type="date" :value="old('fecha_fin', $contrato->fecha_fin->format('Y-m-d'))" required />
                <x-input-error :messages="$errors->get('fecha_fin')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="monto_renta" value="Monto de renta" />
                <x-text-input id="monto_renta" name="monto_renta" type="number" step="0.01" min="0.01" :value="old('monto_renta', $contrato->monto_renta)" required />
                <x-input-error :messages="$errors->get('monto_renta')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="estado" value="Estado" />
                <select id="estado" name="estado" class="campo-senior" required>
                    @foreach (['borrador' => 'Borrador', 'activo' => 'Activo', 'vencido' => 'Vencido', 'rescindido' => 'Rescindido (finalizar contrato)'] as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(old('estado', $contrato->estado) === $valor)>
                            {{ $etiqueta }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('estado')" class="mt-2" />
                <p class="mt-2 text-lg text-gray-700">
                    Para finalizar anticipadamente este contrato y liberar sus fechas, seleccione "Rescindido" y guarde.
                </p>
            </div>

            <div class="flex flex-wrap gap-4">
                <x-primary-button>Guardar Cambios</x-primary-button>
                <a href="{{ route('contratos.show', $contrato) }}" class="btn-senior-secundario">Cancelar</a>
            </div>
        </form>
    </div>
</x-app-layout>
