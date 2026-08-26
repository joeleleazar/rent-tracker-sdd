{{--
    Celda de "lectura actual" de una locación alquilable dentro del registro
    masivo (specs/015 FR-005/FR-013/FR-017): se re-renderiza sola vía htmx al
    editar en línea una lectura ya registrada, sin recargar el resto de la
    fila ni del árbol. También expone `data-consumo`/`data-lectura-anterior`
    para que resources/js/registro-masivo-lecturas.js calcule el total de la
    fila sin volver a golpear el servidor (FR-013).

    Props esperadas:
    - $locacion (App\Models\Locacion)
    - $lecturaDelPeriodo (?App\Models\LecturaMedidor) — null si aún no hay lectura para el periodo
    - $lecturaAnterior (?App\Models\LecturaMedidor)
    - $borrador (?App\Models\BorradorLecturaMedidor)
    - $modoEdicion (bool) — true cuando se reemplazó la vista de solo lectura por el input editable
    - $valorIntentado (string|null) — valor recién enviado que disparó la confirmación de
      consumo negativo; distinto de `old()` porque esta respuesta nunca es un redirect con
      sesión flasheada, es la propia respuesta fragmentada de actualizarInline
--}}
<div
    id="campo-lectura-{{ $locacion->id }}"
    class="campo-lectura-registro-masivo"
    data-consumo="{{ (! $modoEdicion && $lecturaDelPeriodo !== null) ? $lecturaDelPeriodo->consumo_calculado : '' }}"
    data-lectura-anterior="{{ $lecturaAnterior?->lectura_actual }}"
>
    @if ($lecturaDelPeriodo !== null && ! $modoEdicion)
        {{--
            specs/020 FR-004/FR-005: el ícono de completada es puramente informativo (sin
            hx-get) — el botón de editar es un control aparte, con su propio ícono y tooltip,
            para que un tooltip nunca quede huérfano al reemplazarse esta celda (ver
            resources/js/registro-masivo-lecturas.js, htmx:beforeCleanupElement).
        --}}
        <span
            class="me-2"
            aria-label="Lectura completada"
            data-bs-toggle="tooltip"
            title="Lectura completada"
        >
            <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i>
        </span>
        <span class="cifra me-2">{{ $lecturaDelPeriodo->lectura_actual }}</span>
        <button
            type="button"
            class="btn btn-sm btn-link p-0 text-decoration-none"
            hx-get="{{ route('lecturas.registroMasivo.editarInline', $lecturaDelPeriodo) }}"
            hx-target="#campo-lectura-{{ $locacion->id }}"
            hx-swap="outerHTML"
            aria-label="Editar lectura de {{ $locacion->nombre }}: {{ $lecturaDelPeriodo->lectura_actual }}"
            data-bs-toggle="tooltip"
            title="Editar lectura"
        >
            <i class="bi bi-pencil-square" aria-hidden="true"></i>
        </button>
    @elseif ($lecturaDelPeriodo !== null && $modoEdicion)
        @php $claveErrorInline = 'lectura_actual'; @endphp
        {{--
            Sin <form>: esta celda vive dentro del <form id="formulario-registro-masivo">
            del lote (index.blade.php), y HTML no admite formularios anidados —
            el navegador descartaría el tag y el hx-patch nunca se dispararía.
            Mismo patrón que el autoguardado (un elemento no-<form> con
            hx-include, ver research.md Decisión 4-5): el botón "Guardar" es el
            propio disparador, y hx-include="closest .campo-lectura-registro-masivo"
            recolecta los campos de esta celda sin necesidad de un <form>.
        --}}
        <div class="d-flex flex-column gap-1">
            <input type="hidden" name="periodo" value="{{ $lecturaDelPeriodo->periodo->format('Y-m-d') }}">
            <div class="d-flex align-items-center gap-1">
                <x-text-input
                    name="lectura_actual"
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control-sm"
                    :value="$valorIntentado ?? $lecturaDelPeriodo->lectura_actual"
                    aria-label="Editar lectura actual de {{ $locacion->nombre }}"
                    hx-patch="{{ route('lecturas.registroMasivo.actualizarInline', $lecturaDelPeriodo) }}"
                    hx-include="closest .campo-lectura-registro-masivo"
                    hx-target="#campo-lectura-{{ $locacion->id }}"
                    hx-swap="outerHTML"
                    hx-trigger="keyup[key=='Enter']"
                />
                <button
                    type="button"
                    class="btn btn-sm btn-primary"
                    hx-patch="{{ route('lecturas.registroMasivo.actualizarInline', $lecturaDelPeriodo) }}"
                    hx-include="closest .campo-lectura-registro-masivo"
                    hx-target="#campo-lectura-{{ $locacion->id }}"
                    hx-swap="outerHTML"
                    aria-label="Guardar lectura de {{ $locacion->nombre }}"
                >
                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    hx-get="{{ route('lecturas.registroMasivo.editarInline', $lecturaDelPeriodo) }}?cancelar=1"
                    hx-target="#campo-lectura-{{ $locacion->id }}"
                    hx-swap="outerHTML"
                    aria-label="Cancelar edición de {{ $locacion->nombre }}"
                >
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get($claveErrorInline)" class="mb-0" />

            @if ($errors->has($claveErrorInline))
                <div class="form-check d-flex align-items-center gap-2">
                    <input type="checkbox" id="confirmar_inline_{{ $locacion->id }}" name="confirmar_consumo_negativo" value="1" class="form-check-input m-0">
                    <label for="confirmar_inline_{{ $locacion->id }}" class="form-check-label small">
                        Confirmo el consumo negativo
                    </label>
                </div>
            @endif
        </div>
    @else
        @php $claveError = 'lecturas.' . $locacion->id . '.lectura_actual'; @endphp

        <x-text-input
            name="lecturas[{{ $locacion->id }}][lectura_actual]"
            type="number"
            step="0.01"
            min="0"
            class="form-control-sm"
            :value="old($claveError, $borrador?->lectura_actual)"
            aria-label="Lectura actual de {{ $locacion->nombre }}"
        />
        <x-input-error :messages="$errors->get($claveError)" class="mt-1" />

        @if ($errors->has($claveError))
            <div class="form-check d-flex align-items-center gap-2 mt-1">
                <input type="checkbox" id="confirmar_{{ $locacion->id }}" name="lecturas[{{ $locacion->id }}][confirmar_consumo_negativo]" value="1" class="form-check-input m-0">
                <label for="confirmar_{{ $locacion->id }}" class="form-check-label small">
                    Confirmo el consumo negativo
                </label>
            </div>
        @endif
    @endif
</div>
