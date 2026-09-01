{{--
    specs/044 (US1): vista previa editable de la importación masiva de lecturas.
    Se inyecta por htmx como respuesta de `importar.previsualizar`. Nada se ha
    guardado todavía (FR-013). El servidor revalida cada fila al confirmar;
    el JS (importacion-vista-previa.js) solo recalcula consumo/total y el badge
    de estado en vivo.
--}}
@php($filas = $resultado['filas'] ?? [])

@if (! ($resultado['ok'] ?? false))
    <x-mensaje-alerta tipo="error">
        {{ $resultado['motivoRechazo'] ?? 'No se pudo leer el archivo.' }}
    </x-mensaje-alerta>
@else
    @php($validas = collect($filas)->where('valida', true)->count())
    @php($conError = count($filas) - $validas)

    <form method="POST" action="{{ route('lecturas.registroMasivo.importar.confirmar') }}" class="d-flex flex-column gap-3">
        @csrf
        <input type="hidden" name="periodo" value="{{ $periodo->format('Y-m-d') }}">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h3 class="fs-5 fw-bold mb-0">Vista previa de la importación</h3>
            <span class="badge bg-secondary" data-contador-vista-previa>
                {{ $validas }} {{ \Illuminate\Support\Str::plural('válida', $validas) }} · {{ $conError }} con error
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle tabla-vista-previa-lecturas">
                <thead>
                    <tr>
                        <th scope="col">Local</th>
                        <th scope="col" class="text-end">Lectura anterior</th>
                        <th scope="col" class="text-end">Lectura actual</th>
                        <th scope="col" class="text-end">Consumo</th>
                        <th scope="col" class="text-end">Total sugerido (S/)</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filas as $i => $fila)
                        @php($anterior = $fila->valores['lectura_anterior'] ?? null)
                        <tr
                            data-fila
                            data-valida="{{ $fila->valida ? 'true' : 'false' }}"
                            data-error-servidor="{{ $fila->errorNoRecuperable ? 'true' : 'false' }}"
                            data-lectura-anterior="{{ $anterior !== null ? $anterior : '' }}"
                        >
                            <td>
                                {{ $fila->nombre }}
                                <input type="hidden" name="filas[{{ $i }}][local_id]" value="{{ $fila->localId }}">
                            </td>
                            <td class="text-end cifra">{{ $anterior !== null ? number_format($anterior, 2) : '—' }}</td>
                            <td class="text-end">
                                <div class="input-group input-group-sm justify-content-end" style="max-width: 10rem; margin-left: auto;">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="filas[{{ $i }}][lectura_actual]"
                                        data-campo="lectura_actual"
                                        class="form-control text-end"
                                        value="{{ $fila->valores['lectura_actual'] }}"
                                        @disabled($fila->errorNoRecuperable)
                                        aria-label="Lectura actual de {{ $fila->nombre }}"
                                    >
                                </div>
                            </td>
                            <td class="text-end cifra" data-celda="consumo">—</td>
                            <td class="text-end cifra" data-celda="total_sugerido">—</td>
                            <td>
                                <span
                                    class="badge {{ $fila->valida ? 'bg-success' : 'bg-danger' }}"
                                    data-badge-estado
                                >{{ $fila->valida ? 'Válida' : 'Con error' }}</span>
                                <div class="small text-danger mt-1">
                                    <span data-motivos-servidor>{{ implode(' ', $fila->motivos) }}</span>
                                    <span data-motivos-cliente></span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>
            <button
                type="submit"
                class="btn btn-primary"
                data-confirmar-importacion
                @disabled($validas === 0)
            >
                <i class="bi bi-check2-circle" aria-hidden="true"></i> Confirmar importación
            </button>
        </div>
    </form>

    @vite('resources/js/importacion-vista-previa.js')
@endif
