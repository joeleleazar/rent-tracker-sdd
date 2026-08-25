<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Editar Contrato #{{ $contrato->id }} — {{ $contrato->locacion->nombre }}
        </h2>
    </x-slot>

    <div class="col-12 col-lg-8" style="max-width: 42rem;">
        @php
            $erroresGenericos = collect($errors->keys())->reject(fn ($campo) => $campo === 'solapamiento');
        @endphp
        @if ($erroresGenericos->isNotEmpty())
            <x-mensaje-alerta tipo="error" class="mb-4">
                <ul class="mb-0 ps-3">
                    @foreach ($erroresGenericos as $campo)
                        @foreach ($errors->get($campo) as $mensaje)
                            <li>{{ $mensaje }}</li>
                        @endforeach
                    @endforeach
                </ul>
            </x-mensaje-alerta>
        @endif

        <form method="POST" action="{{ route('contratos.update', $contrato) }}" class="d-flex flex-column gap-4">
            @csrf
            @method('PUT')

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
                <div class="input-group">
                    <span class="input-group-text">S/</span>
                    <x-text-input id="monto_renta" name="monto_renta" type="number" step="0.01" min="0.01" :value="old('monto_renta', $contrato->monto_renta)" required />
                </div>
                <x-input-error :messages="$errors->get('monto_renta')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="estado" value="Estado" />
                <select id="estado" name="estado" class="form-select" required>
                    @foreach (['borrador' => 'Borrador', 'activo' => 'Activo', 'vencido' => 'Vencido', 'rescindido' => 'Rescindido (finalizar contrato)'] as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(old('estado', $contrato->estado) === $valor)>
                            {{ $etiqueta }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('estado')" class="mt-2" />
                <p class="mt-2">
                    Para finalizar anticipadamente este contrato y liberar sus fechas, seleccione "Rescindido" y guarde.
                </p>
            </div>

            @include('contratos.partials.costos-fijos-contrato', ['contrato' => $contrato])

            @include('contratos.partials.garantia-contrato', ['contrato' => $contrato])

            <div class="d-flex flex-wrap gap-3">
                <x-primary-button>Guardar Cambios</x-primary-button>
                <a href="{{ route('contratos.show', $contrato) }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
            </div>
        </form>

        @include('contratos.partials.modal-solapamiento')
    </div>

    @push('scripts')
        @vite(['resources/js/costos-fijos-contrato.js'])
    @endpush
</x-layouts.app-bootstrap>
