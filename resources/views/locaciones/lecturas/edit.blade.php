<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Editar Lectura del Medidor — {{ $lectura->locacion->nombre }}
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

            @if ($reciboEmitido !== null)
                <x-mensaje-alerta tipo="error">
                    Ya existe un recibo emitido para este periodo. Si corrige la lectura, el recibo ya emitido NO se actualizará automáticamente; edítelo manualmente si corresponde.
                    <a href="{{ route('recibos.edit', $reciboEmitido) }}" class="fw-bold">Editar el recibo</a>.
                </x-mensaje-alerta>
            @endif

            <form method="POST" action="{{ route('lecturas.update', $lectura) }}" class="card">
                <div class="card-body d-flex flex-column gap-3">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="periodo" value="{{ $lectura->periodo->format('Y-m-d') }}">

                    <p class="fs-5 mb-0">
                        Periodo: <strong>{{ $lectura->periodo->translatedFormat('F Y') }}</strong>
                    </p>

                    <div>
                        <x-input-label for="lectura_anterior" value="Lectura Anterior" />
                        <x-text-input id="lectura_anterior" name="lectura_anterior" type="number" step="0.01" min="0" :value="old('lectura_anterior', $lectura->lectura_anterior)" />
                        <x-input-error :messages="$errors->get('lectura_anterior')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="lectura_actual" value="Lectura Actual" />
                        <x-text-input id="lectura_actual" name="lectura_actual" type="number" step="0.01" min="0" :value="old('lectura_actual', $lectura->lectura_actual)" required />
                        <x-input-error :messages="$errors->get('lectura_actual')" class="mt-2" />
                    </div>

                    @if ($errors->has('lectura_actual'))
                        <div class="form-check d-flex align-items-center gap-2">
                            <input type="checkbox" id="confirmar_consumo_negativo" name="confirmar_consumo_negativo" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;">
                            <label for="confirmar_consumo_negativo" class="form-check-label fs-5">
                                Confirmo que la lectura es correcta aunque resulte en un consumo negativo
                            </label>
                        </div>
                    @endif

                    <div class="d-flex flex-wrap gap-3">
                        <x-primary-button>Guardar Cambios</x-primary-button>
                        <a href="{{ route('locaciones.lecturas.index', $lectura->locacion) }}" class="btn btn-outline-secondary btn-lg"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app-bootstrap>
