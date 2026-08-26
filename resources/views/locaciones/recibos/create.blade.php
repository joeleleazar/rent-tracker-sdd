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

            @if ($contratoActivo === null)
                <x-mensaje-alerta tipo="error">
                    No hay un contrato activo vigente para {{ $periodo->translatedFormat('F Y') }} en esta locación. No se puede emitir un recibo para este periodo, pero puede registrar la lectura del medidor de forma independiente.
                </x-mensaje-alerta>
            @elseif ($conceptosDisponibles->isEmpty())
                <x-mensaje-alerta tipo="exito">
                    Todos los conceptos de {{ $periodo->translatedFormat('F Y') }} ya están cubiertos por uno o más recibos de esta locación. No queda ningún concepto disponible para un recibo nuevo.
                    <a href="{{ route('locaciones.recibos.index', $locacion) }}" class="fw-bold">Ver los recibos de esta locación</a>.
                </x-mensaje-alerta>
            @else
                @if ($reciboQueCubre->isNotEmpty())
                    <x-mensaje-alerta tipo="exito">
                        Algunos conceptos de {{ $periodo->translatedFormat('F Y') }} ya están cubiertos:
                        <ul class="mb-0 ps-3">
                            @foreach ($reciboQueCubre as $conceptoId => $recibo)
                                <li>
                                    {{ \App\Models\ConceptoGastoFijo::find($conceptoId)?->nombre }} —
                                    ya está cubierto por <a href="{{ route('recibos.show', $recibo) }}" class="fw-bold">este recibo</a>.
                                </li>
                            @endforeach
                        </ul>
                    </x-mensaje-alerta>
                @endif
                <p>
                    @if ($lectura !== null)
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

                @if ($borrador !== null)
                    <x-mensaje-alerta tipo="exito">
                        Se recuperó un borrador guardado para esta locación y periodo — revise los conceptos marcados antes de emitir.
                    </x-mensaje-alerta>
                @endif

                <form id="formulario-generar-recibo" method="POST" action="{{ route('locaciones.recibos.store', $locacion) }}" class="card">
                    <div class="card-body d-flex flex-column gap-3">
                        @csrf

                        <input type="hidden" name="periodo" value="{{ $periodo->format('Y-m-d') }}">

                        <p>
                            Contrato vigente: inquilino <strong>{{ $contratoActivo->inquilinoPrincipal()?->nombreCompleto() ?? '—' }}</strong>. Marque los conceptos a incluir y edite los montos antes de emitir.
                        </p>

                        @foreach ($conceptosDisponibles as $concepto)
                            @php
                                $valorBorrador = $borrador?->conceptos[$concepto->id] ?? null;
                                if ($concepto->esRenta()) {
                                    $incluidoPorDefecto = $borrador !== null ? $borrador->incluye_alquiler : true;
                                    $montoSugerido = ($borrador?->incluye_alquiler && $borrador->monto_renta !== null)
                                        ? (float) $borrador->monto_renta
                                        : ($prorrateo['monto_renta_sugerido'] ?? $contratoActivo->monto_renta);
                                } elseif ($concepto->esLuz()) {
                                    $incluidoPorDefecto = $borrador !== null ? $valorBorrador !== null : true;
                                    $montoSugerido = $valorBorrador ?? $montoLuzSugerido;
                                } else {
                                    $incluidoPorDefecto = $borrador !== null ? $valorBorrador !== null : true;
                                    $montoSugerido = $valorBorrador ?? ($contratoActivo->valorDeConcepto($concepto) ?? 0);
                                }
                                $nombreCheckbox = $concepto->esRenta() ? 'incluye_alquiler' : "conceptos[{$concepto->id}][incluido]";
                                $nombreMonto = $concepto->esRenta() ? 'monto_renta' : "conceptos[{$concepto->id}][monto]";
                                $idCampo = $concepto->esRenta() ? 'monto_renta' : "concepto_{$concepto->id}";
                            @endphp
                            <div class="d-flex flex-wrap align-items-center gap-3 border rounded p-3">
                                <div class="form-check d-flex align-items-center gap-2 flex-shrink-0">
                                    <input type="checkbox" id="incluir_{{ $idCampo }}" name="{{ $nombreCheckbox }}" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" @checked($incluidoPorDefecto)>
                                    <label for="incluir_{{ $idCampo }}" class="form-check-label fw-semibold">
                                        Incluir {{ $concepto->nombre }}{{ $concepto->esLuz() ? ' (calculado por consumo)' : '' }}
                                    </label>
                                </div>
                                <div class="input-group" style="max-width: 16rem;">
                                    <span class="input-group-text">S/</span>
                                    <x-text-input :id="$idCampo" :name="$nombreMonto" type="number" step="0.01" min="0" :value="old(str_replace(['[', ']'], ['.', ''], $nombreMonto), $montoSugerido)" :required="$concepto->esRenta()" />
                                </div>
                            </div>
                        @endforeach

                        <div>
                            <x-input-label for="fecha_emision" value="Fecha de Emisión" />
                            <x-text-input id="fecha_emision" name="fecha_emision" type="date" :value="old('fecha_emision', $borrador?->fecha_emision?->format('Y-m-d') ?? now()->format('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('fecha_emision')" class="mt-2" />
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <x-primary-button>Emitir Recibo del Periodo</x-primary-button>
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                hx-post="{{ route('locaciones.recibos.borrador', $locacion) }}"
                                hx-include="closest form"
                                hx-target="#estado-borrador-recibo"
                                hx-swap="innerHTML"
                            >
                                <i class="bi bi-save" aria-hidden="true"></i> Guardar Borrador
                            </button>
                            <a href="{{ route('locaciones.recibos.index', $locacion) }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg" aria-hidden="true"></i> Cancelar</a>
                        </div>
                        <p id="estado-borrador-recibo" class="text-secondary small mb-0" aria-live="polite"></p>

                        {{--
                            Autoguardado pasivo de borrador (specs/026, mismo mecanismo que
                            resources/views/lecturas/registro-masivo/index.blade.php, specs/015):
                            un elemento no-<form> para que resources/js/htmx.js no le aplique el
                            tratamiento visual de "Guardando…" reservado al envío manual.
                        --}}
                        <div
                            id="autoguardado-borrador-recibo"
                            hx-post="{{ route('locaciones.recibos.borrador', $locacion) }}"
                            hx-trigger="every 120s"
                            hx-include="closest form"
                            hx-target="#estado-borrador-recibo"
                            hx-swap="innerHTML"
                        ></div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app-bootstrap>
