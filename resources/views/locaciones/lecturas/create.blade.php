<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Registrar Lectura del Medidor — {{ $locacion->nombre }}
        </h2>
    </x-slot>

    <div class="col-12 col-lg-8" style="max-width: 42rem;">
        <div class="d-flex flex-column gap-3">
            @if ($errors->any())
                <x-mensaje-alerta tipo="error">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-mensaje-alerta>
            @endif

            <form method="GET" action="{{ route('locaciones.lecturas.create', $locacion) }}" class="card">
                <div class="card-body d-flex flex-wrap align-items-end gap-3">
                    <div>
                        <x-input-label for="periodo_selector" value="Periodo (mes)" />
                        <input id="periodo_selector" name="periodo" type="month" class="form-control" value="{{ $periodo->format('Y-m') }}">
                    </div>
                    <x-secondary-button>Cambiar Periodo</x-secondary-button>
                </div>
            </form>

            <form method="POST" action="{{ route('locaciones.lecturas.store', $locacion) }}" class="card">
                <div class="card-body d-flex flex-column gap-3">
                    @csrf

                    <input type="hidden" name="periodo" value="{{ $periodo->format('Y-m-d') }}">

                    <p class="mb-0">
                        Periodo: <strong>{{ $periodo->translatedFormat('F Y') }}</strong>
                    </p>

                    <div>
                        <x-input-label for="lectura_anterior" value="Lectura Anterior" />
                        @if ($lecturaAnteriorSugerida === null)
                            <p class="fw-semibold mb-2">Sin lectura previa registrada</p>
                        @endif
                        <x-text-input id="lectura_anterior" name="lectura_anterior" type="number" step="0.01" min="0" :value="old('lectura_anterior', $lecturaAnteriorSugerida)" />
                        <x-input-error :messages="$errors->get('lectura_anterior')" class="mt-2" />
                        <p class="mt-2 mb-0">
                            Precargada automáticamente con la lectura actual del periodo anterior; puede editarla si es necesario.
                        </p>
                    </div>

                    <div>
                        <x-input-label for="lectura_actual" value="Lectura Actual" />
                        <x-text-input id="lectura_actual" name="lectura_actual" type="number" step="0.01" min="0" :value="old('lectura_actual')" required />
                        <x-input-error :messages="$errors->get('lectura_actual')" class="mt-2" />
                    </div>

                    @if ($errors->has('lectura_actual'))
                        <div class="form-check d-flex align-items-center gap-2">
                            <input type="checkbox" id="confirmar_consumo_negativo" name="confirmar_consumo_negativo" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;">
                            <label for="confirmar_consumo_negativo" class="form-check-label">
                                Confirmo que la lectura es correcta aunque resulte en un consumo negativo
                            </label>
                        </div>
                    @endif

                    <div class="d-flex flex-wrap gap-3">
                        <x-primary-button>Guardar Lectura del Periodo</x-primary-button>
                        <a href="{{ route('locaciones.lecturas.index', $locacion) }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app-bootstrap>
