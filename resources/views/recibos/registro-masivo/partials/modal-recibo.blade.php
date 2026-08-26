{{--
    Contenido del modal de una locación (specs/023, US2/US3; specs/024 conceptos
    dinámicos) — swapeado dentro del contenedor de modal compartido de
    index.blade.php vía hx-get.

    Props esperadas:
    - $locacion (App\Models\Locacion)
    - $periodo (Illuminate\Support\Carbon)
    - $contratoActivo (?App\Models\Contrato)
    - $conceptosDisponibles (Illuminate\Support\Collection<int, App\Models\ConceptoGastoFijo>)
    - $montosSugeridos (Illuminate\Support\Collection<int, float>) keyBy concepto_gasto_fijo_id
    - $prorrateo (?array{dias_activos: int, dias_totales: int, monto_renta_sugerido: float})
--}}
<div class="modal-header">
    <h5 class="modal-title fs-5 fw-bold">Generar Recibo — {{ $locacion->nombre }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div id="errores-modal-recibo"></div>

@if ($contratoActivo === null)
    <div class="modal-body">
        <x-mensaje-alerta tipo="error">
            No hay un contrato activo vigente para {{ $periodo->translatedFormat('F Y') }} en esta locación.
        </x-mensaje-alerta>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
    </div>
@elseif ($conceptosDisponibles->isEmpty())
    <div class="modal-body">
        <x-mensaje-alerta tipo="exito">
            Todos los conceptos de {{ $periodo->translatedFormat('F Y') }} ya están cubiertos por uno o más recibos de esta locación.
        </x-mensaje-alerta>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
    </div>
@else
    <form
        id="formulario-modal-recibo"
        method="POST"
        action="{{ route('recibos.registroMasivo.store', $locacion) }}"
        hx-post="{{ route('recibos.registroMasivo.store', $locacion) }}"
        hx-target="#fila-recibo-{{ $locacion->id }}"
        hx-swap="outerHTML"
    >
        @csrf
        <input type="hidden" name="periodo" value="{{ $periodo->format('Y-m-d') }}">

        <div class="modal-body d-flex flex-column gap-3">
            @if ($prorrateo !== null && $conceptosDisponibles->contains(fn ($c) => $c->esRenta()))
                <x-mensaje-alerta tipo="exito">
                    Este contrato estuvo activo <strong>{{ $prorrateo['dias_activos'] }} días de {{ $prorrateo['dias_totales'] }}</strong> en este periodo. Se sugiere un monto de renta prorrateado, editable antes de confirmar.
                </x-mensaje-alerta>
            @endif

            @foreach ($conceptosDisponibles as $concepto)
                @php
                    $nombreCheckbox = $concepto->esRenta() ? 'incluye_alquiler' : "conceptos[{$concepto->id}][incluido]";
                    $nombreMonto = $concepto->esRenta() ? 'monto_renta' : "conceptos[{$concepto->id}][monto]";
                    $idCampo = $concepto->esRenta() ? 'monto_renta' : "concepto_{$concepto->id}";
                @endphp
                <div class="d-flex flex-wrap align-items-center gap-3 border rounded p-3">
                    <div class="form-check d-flex align-items-center gap-2 flex-shrink-0">
                        <input type="checkbox" id="incluir_{{ $idCampo }}" name="{{ $nombreCheckbox }}" value="1" class="form-check-input m-0" style="width: 1.5em; height: 1.5em;" checked>
                        <label for="incluir_{{ $idCampo }}" class="form-check-label fw-semibold">
                            Incluir {{ $concepto->nombre }}{{ $concepto->esLuz() ? ' (calculado por consumo)' : '' }}
                        </label>
                    </div>
                    <div class="input-group" style="max-width: 16rem;">
                        <span class="input-group-text">S/</span>
                        <input type="number" step="0.01" min="0" id="{{ $idCampo }}" name="{{ $nombreMonto }}" class="form-control" value="{{ number_format($montosSugeridos[$concepto->id], 2, '.', '') }}">
                    </div>
                </div>
            @endforeach
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Generar Recibo</button>
        </div>
    </form>
@endif
