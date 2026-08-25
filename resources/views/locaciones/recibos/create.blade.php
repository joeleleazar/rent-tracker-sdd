<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Emitir Recibo del Periodo — {{ $locacion->nombre }}
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

            <form method="GET" action="{{ route('locaciones.recibos.create', $locacion) }}" class="card">
                <div class="card-body d-flex flex-wrap align-items-end gap-3">
                    <div>
                        <x-input-label for="periodo" value="Periodo (mes)" />
                        <input id="periodo" name="periodo" type="month" class="form-control" value="{{ $periodo->format('Y-m') }}">
                    </div>
                    <x-secondary-button>Cambiar Periodo</x-secondary-button>
                </div>
            </form>

            @if ($reciboExistente !== null)
                <x-mensaje-alerta tipo="error">
                    Ya existe un recibo emitido para {{ $periodo->translatedFormat('F Y') }} en esta locación.
                    <a href="{{ route('recibos.edit', $reciboExistente) }}" class="fw-bold">Editar el recibo existente</a>.
                </x-mensaje-alerta>
            @elseif ($contratoActivo === null)
                <x-mensaje-alerta tipo="error">
                    No hay un contrato activo vigente para {{ $periodo->translatedFormat('F Y') }} en esta locación. No se puede emitir un recibo para este periodo, pero puede registrar la lectura del medidor de forma independiente.
                </x-mensaje-alerta>
            @else
                <p>
                    @if ($lectura !== null && $lectura->consumo_calculado !== null)
                        Consumo del periodo: <strong>{{ number_format((float) $lectura->consumo_calculado, 2) }}</strong> unidades.
                    @else
                        Sin lectura de medidor registrada para este periodo (monto de luz sugerido: S/ 0.00).
                    @endif
                </p>

                @if ($prorrateo !== null)
                    <x-mensaje-alerta tipo="exito">
                        Este contrato estuvo activo <strong>{{ $prorrateo['dias_activos'] }} días de {{ $prorrateo['dias_totales'] }}</strong> en este periodo. Se sugiere un monto de renta prorrateado de <strong>S/ {{ number_format($prorrateo['monto_renta_sugerido'], 2) }}</strong>, editable antes de confirmar.
                    </x-mensaje-alerta>
                @endif

                <form method="POST" action="{{ route('locaciones.recibos.store', $locacion) }}" class="card">
                    <div class="card-body d-flex flex-column gap-3">
                        @csrf

                        <input type="hidden" name="periodo" value="{{ $periodo->format('Y-m-d') }}">

                        <p>
                            Contrato vigente: inquilino <strong>{{ $contratoActivo->inquilinoPrincipal()?->nombreCompleto() ?? '—' }}</strong>. Marque los conceptos a incluir y edite los montos antes de emitir.
                        </p>

                        <div class="d-flex flex-wrap align-items-center gap-3 border rounded p-3">
                            <div class="form-check d-flex align-items-center gap-2 flex-shrink-0">
                                <input type="checkbox" id="incluye_alquiler" name="incluye_alquiler" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" checked>
                                <label for="incluye_alquiler" class="form-check-label fw-semibold">Incluir Alquiler</label>
                            </div>
                            <div class="input-group" style="max-width: 16rem;">
                                <span class="input-group-text">S/</span>
                                <x-text-input id="monto_renta" name="monto_renta" type="number" step="0.01" min="0" :value="old('monto_renta', $prorrateo['monto_renta_sugerido'] ?? $contratoActivo->monto_renta)" required />
                            </div>
                            <x-input-error :messages="$errors->get('monto_renta')" />
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-3 border rounded p-3">
                            <div class="form-check d-flex align-items-center gap-2 flex-shrink-0">
                                <input type="checkbox" id="incluye_luz" name="incluye_luz" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" checked>
                                <label for="incluye_luz" class="form-check-label fw-semibold">Incluir Luz (calculado por consumo)</label>
                            </div>
                            <div class="input-group" style="max-width: 16rem;">
                                <span class="input-group-text">S/</span>
                                <x-text-input id="monto_luz" name="monto_luz" type="number" step="0.01" min="0" :value="old('monto_luz', $montoLuzSugerido)" />
                            </div>
                            <x-input-error :messages="$errors->get('monto_luz')" />
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-3 border rounded p-3">
                            <div class="form-check d-flex align-items-center gap-2 flex-shrink-0">
                                <input type="checkbox" id="incluye_agua" name="incluye_agua" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" checked>
                                <label for="incluye_agua" class="form-check-label fw-semibold">Incluir Agua</label>
                            </div>
                            <div class="input-group" style="max-width: 16rem;">
                                <span class="input-group-text">S/</span>
                                <x-text-input id="monto_agua" name="monto_agua" type="number" step="0.01" min="0" :value="old('monto_agua', $contratoActivo->costo_agua)" />
                            </div>
                            <x-input-error :messages="$errors->get('monto_agua')" />
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-3 border rounded p-3">
                            <div class="form-check d-flex align-items-center gap-2 flex-shrink-0">
                                <input type="checkbox" id="incluye_pasadizo" name="incluye_pasadizo" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" checked>
                                <label for="incluye_pasadizo" class="form-check-label fw-semibold">Incluir Luz de Pasadizo</label>
                            </div>
                            <div class="input-group" style="max-width: 16rem;">
                                <span class="input-group-text">S/</span>
                                <x-text-input id="monto_pasadizo" name="monto_pasadizo" type="number" step="0.01" min="0" :value="old('monto_pasadizo', $contratoActivo->costo_pasadizo)" />
                            </div>
                            <x-input-error :messages="$errors->get('monto_pasadizo')" />
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-3 border rounded p-3">
                            <div class="form-check d-flex align-items-center gap-2 flex-shrink-0">
                                <input type="checkbox" id="incluye_seguridad" name="incluye_seguridad" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" checked>
                                <label for="incluye_seguridad" class="form-check-label fw-semibold">Incluir Seguridad</label>
                            </div>
                            <div class="input-group" style="max-width: 16rem;">
                                <span class="input-group-text">S/</span>
                                <x-text-input id="monto_seguridad" name="monto_seguridad" type="number" step="0.01" min="0" :value="old('monto_seguridad', $contratoActivo->costo_seguridad)" />
                            </div>
                            <x-input-error :messages="$errors->get('monto_seguridad')" />
                        </div>

                        <div>
                            <x-input-label for="fecha_emision" value="Fecha de Emisión" />
                            <x-text-input id="fecha_emision" name="fecha_emision" type="date" :value="old('fecha_emision', now()->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('fecha_emision')" class="mt-2" />
                        </div>

                        <div class="d-flex flex-wrap gap-3">
                            <x-primary-button>Emitir Recibo del Periodo</x-primary-button>
                            <a href="{{ route('locaciones.recibos.index', $locacion) }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app-bootstrap>
