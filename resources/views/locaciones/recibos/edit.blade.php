<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Editar Recibo #{{ $recibo->id }} — {{ $recibo->locacion->nombre }}
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

            <p class="fs-5">
                Periodo: <strong>{{ $recibo->periodo->translatedFormat('F Y') }}</strong>
            </p>

            <form method="POST" action="{{ route('recibos.update', $recibo) }}" class="card">
                <div class="card-body d-flex flex-column gap-3">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="periodo" value="{{ $recibo->periodo->format('Y-m-d') }}">

                    <div class="d-flex flex-wrap align-items-center gap-3 border border-2 rounded p-3">
                        <div class="form-check d-flex align-items-center gap-2 flex-shrink-0">
                            <input type="checkbox" id="incluye_alquiler" name="incluye_alquiler" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" @checked(old('incluye_alquiler', $recibo->incluye_alquiler))>
                            <label for="incluye_alquiler" class="form-check-label fs-5 fw-semibold">Incluir Alquiler</label>
                        </div>
                        <div class="input-group input-group-lg" style="max-width: 16rem;">
                            <span class="input-group-text">S/</span>
                            <x-text-input id="monto_renta" name="monto_renta" type="number" step="0.01" min="0" :value="old('monto_renta', $recibo->monto_renta)" required />
                        </div>
                        <x-input-error :messages="$errors->get('monto_renta')" />
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3 border border-2 rounded p-3">
                        <div class="form-check d-flex align-items-center gap-2 flex-shrink-0">
                            <input type="checkbox" id="incluye_luz" name="incluye_luz" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" @checked(old('incluye_luz', $recibo->incluye_luz))>
                            <label for="incluye_luz" class="form-check-label fs-5 fw-semibold">Incluir Luz</label>
                        </div>
                        <div class="input-group input-group-lg" style="max-width: 16rem;">
                            <span class="input-group-text">S/</span>
                            <x-text-input id="monto_luz" name="monto_luz" type="number" step="0.01" min="0" :value="old('monto_luz', $recibo->monto_luz)" />
                        </div>
                        <x-input-error :messages="$errors->get('monto_luz')" />
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3 border border-2 rounded p-3">
                        <div class="form-check d-flex align-items-center gap-2 flex-shrink-0">
                            <input type="checkbox" id="incluye_agua" name="incluye_agua" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" @checked(old('incluye_agua', $recibo->incluye_agua))>
                            <label for="incluye_agua" class="form-check-label fs-5 fw-semibold">Incluir Agua</label>
                        </div>
                        <div class="input-group input-group-lg" style="max-width: 16rem;">
                            <span class="input-group-text">S/</span>
                            <x-text-input id="monto_agua" name="monto_agua" type="number" step="0.01" min="0" :value="old('monto_agua', $recibo->monto_agua)" />
                        </div>
                        <x-input-error :messages="$errors->get('monto_agua')" />
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3 border border-2 rounded p-3">
                        <div class="form-check d-flex align-items-center gap-2 flex-shrink-0">
                            <input type="checkbox" id="incluye_pasadizo" name="incluye_pasadizo" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" @checked(old('incluye_pasadizo', $recibo->incluye_pasadizo))>
                            <label for="incluye_pasadizo" class="form-check-label fs-5 fw-semibold">Incluir Luz de Pasadizo</label>
                        </div>
                        <div class="input-group input-group-lg" style="max-width: 16rem;">
                            <span class="input-group-text">S/</span>
                            <x-text-input id="monto_pasadizo" name="monto_pasadizo" type="number" step="0.01" min="0" :value="old('monto_pasadizo', $recibo->monto_pasadizo)" />
                        </div>
                        <x-input-error :messages="$errors->get('monto_pasadizo')" />
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3 border border-2 rounded p-3">
                        <div class="form-check d-flex align-items-center gap-2 flex-shrink-0">
                            <input type="checkbox" id="incluye_seguridad" name="incluye_seguridad" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" @checked(old('incluye_seguridad', $recibo->incluye_seguridad))>
                            <label for="incluye_seguridad" class="form-check-label fs-5 fw-semibold">Incluir Seguridad</label>
                        </div>
                        <div class="input-group input-group-lg" style="max-width: 16rem;">
                            <span class="input-group-text">S/</span>
                            <x-text-input id="monto_seguridad" name="monto_seguridad" type="number" step="0.01" min="0" :value="old('monto_seguridad', $recibo->monto_seguridad)" />
                        </div>
                        <x-input-error :messages="$errors->get('monto_seguridad')" />
                    </div>

                    <div>
                        <x-input-label for="fecha_emision" value="Fecha de Emisión" />
                        <x-text-input id="fecha_emision" name="fecha_emision" type="date" :value="old('fecha_emision', $recibo->fecha_emision->format('Y-m-d'))" required />
                        <x-input-error :messages="$errors->get('fecha_emision')" class="mt-2" />
                    </div>

                    <div class="d-flex flex-wrap gap-3">
                        <x-primary-button>Guardar Cambios del Recibo</x-primary-button>
                        <a href="{{ route('recibos.show', $recibo) }}" class="btn btn-outline-secondary btn-lg"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app-bootstrap>
