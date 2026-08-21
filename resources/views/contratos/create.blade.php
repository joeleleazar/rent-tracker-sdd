<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Nuevo Contrato — {{ $locacion->nombre }}
        </h2>
    </x-slot>

    <div class="col-12 col-lg-8" style="max-width: 42rem;">
        @if ($errors->any())
            <x-mensaje-alerta tipo="error" class="mb-4">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-mensaje-alerta>
        @endif

        <form method="POST" action="{{ route('contratos.store', $locacion) }}" class="d-flex flex-column gap-4">
            @csrf

            <div>
                <x-input-label for="inquilino_id" value="Inquilino" />
                <select id="inquilino_id" name="inquilino_id" class="form-select form-select-lg" required>
                    <option value="">Seleccione un inquilino</option>
                    @foreach ($inquilinos as $inquilino)
                        <option value="{{ $inquilino->id }}" @selected(old('inquilino_id') == $inquilino->id)>
                            {{ $inquilino->nombre }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('inquilino_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="fecha_inicio" value="Fecha de inicio" />
                <x-text-input id="fecha_inicio" name="fecha_inicio" type="date" :value="old('fecha_inicio')" required />
                <x-input-error :messages="$errors->get('fecha_inicio')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="fecha_fin" value="Fecha de fin" />
                <x-text-input id="fecha_fin" name="fecha_fin" type="date" :value="old('fecha_fin')" required />
                <x-input-error :messages="$errors->get('fecha_fin')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="monto_renta" value="Monto de renta" />
                <div class="input-group input-group-lg">
                    <span class="input-group-text">S/</span>
                    <x-text-input id="monto_renta" name="monto_renta" type="number" step="0.01" min="0.01" :value="old('monto_renta')" required />
                </div>
                <x-input-error :messages="$errors->get('monto_renta')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="estado" value="Estado" />
                <select id="estado" name="estado" class="form-select form-select-lg" required>
                    @foreach (['borrador' => 'Borrador', 'activo' => 'Activo', 'vencido' => 'Vencido', 'rescindido' => 'Rescindido'] as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(old('estado', 'borrador') === $valor)>
                            {{ $etiqueta }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('estado')" class="mt-2" />
            </div>

            @include('contratos.partials.costos-fijos-contrato', ['contrato' => null])

            @include('contratos.partials.garantia-contrato', ['contrato' => null])

            @include('contratos.partials.representantes-contrato', ['contrato' => null])

            <div class="d-flex flex-wrap gap-3">
                <x-primary-button>Guardar Contrato</x-primary-button>
                <a href="{{ route('contratos.index', $locacion) }}" class="btn btn-outline-secondary btn-lg">Cancelar</a>
            </div>
        </form>
    </div>

    @push('scripts')
        @vite(['resources/js/representantes-contrato.js'])
    @endpush
</x-layouts.app-bootstrap>
