{{--
    specs/044 (US2): vista previa editable de la importación masiva de recibos.
    Se inyecta por htmx como respuesta de `importar.previsualizar`. Nada se ha
    guardado (FR-013). El total sugerido lo recalcula el JS
    (importacion-vista-previa.js) como renta + luz + Σ conceptos; el input de
    total sigue al sugerido hasta que el usuario lo edita a mano.
--}}
@php($filas = $resultado['filas'] ?? [])
@php($columnas = $resultado['columnas'] ?? collect())

@if (! ($resultado['ok'] ?? false))
    <x-mensaje-alerta tipo="error">
        {{ $resultado['motivoRechazo'] ?? 'No se pudo leer el archivo.' }}
    </x-mensaje-alerta>
@else
    @php($validas = collect($filas)->where('valida', true)->count())
    @php($conError = count($filas) - $validas)

    @foreach ($resultado['avisos'] ?? [] as $aviso)
        <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
            <i class="bi bi-info-circle-fill fs-5 flex-shrink-0" aria-hidden="true"></i>
            <div class="flex-grow-1">{{ $aviso }}</div>
        </div>
    @endforeach

    <form method="POST" action="{{ route('recibos.registroMasivo.importar.confirmar') }}" class="d-flex flex-column gap-3">
        @csrf
        <input type="hidden" name="periodo" value="{{ $periodo->format('Y-m-d') }}">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h3 class="fs-5 fw-bold mb-0">Vista previa de la importación</h3>
            <span class="badge bg-secondary" data-contador-vista-previa>
                {{ $validas }} {{ \Illuminate\Support\Str::plural('válida', $validas) }} · {{ $conError }} con error
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle tabla-vista-previa-recibos">
                <thead>
                    <tr>
                        <th scope="col">Local</th>
                        <th scope="col" class="text-end">Renta (S/)</th>
                        <th scope="col" class="text-end">Luz (S/)</th>
                        @foreach ($columnas as $concepto)
                            <th scope="col" class="text-end">{{ $concepto->nombre }} (S/)</th>
                        @endforeach
                        <th scope="col" class="text-end">Total sugerido</th>
                        <th scope="col" class="text-end">Total (S/)</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filas as $i => $fila)
                        <tr
                            data-fila
                            data-valida="{{ $fila->valida ? 'true' : 'false' }}"
                            data-error-servidor="{{ $fila->errorNoRecuperable ? 'true' : 'false' }}"
                        >
                            <td>
                                {{ $fila->nombre }}
                                <input type="hidden" name="filas[{{ $i }}][local_id]" value="{{ $fila->localId }}">
                            </td>
                            <td class="text-end">
                                <div class="input-group input-group-sm" style="max-width: 9rem; margin-left: auto;">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" step="0.01" min="0" class="form-control text-end"
                                        name="filas[{{ $i }}][renta]" data-componente
                                        value="{{ $fila->valores['renta'] }}"
                                        @disabled($fila->errorNoRecuperable)
                                        aria-label="Renta de {{ $fila->nombre }}">
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="input-group input-group-sm" style="max-width: 9rem; margin-left: auto;">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" step="0.01" min="0" class="form-control text-end"
                                        name="filas[{{ $i }}][luz]" data-componente
                                        value="{{ $fila->valores['luz'] }}"
                                        @disabled($fila->errorNoRecuperable)
                                        aria-label="Luz de {{ $fila->nombre }}">
                                </div>
                            </td>
                            @foreach ($columnas as $concepto)
                                <td class="text-end">
                                    <div class="input-group input-group-sm" style="max-width: 9rem; margin-left: auto;">
                                        <span class="input-group-text">S/</span>
                                        <input type="number" step="0.01" min="0" class="form-control text-end"
                                            name="filas[{{ $i }}][conceptos][{{ $concepto->id }}]" data-componente
                                            value="{{ $fila->valores['conceptos'][$concepto->id] ?? '' }}"
                                            @disabled($fila->errorNoRecuperable)
                                            aria-label="{{ $concepto->nombre }} de {{ $fila->nombre }}">
                                    </div>
                                </td>
                            @endforeach
                            <td class="text-end cifra" data-celda="total_sugerido">—</td>
                            <td class="text-end">
                                <div class="input-group input-group-sm" style="max-width: 9rem; margin-left: auto;">
                                    <span class="input-group-text">S/</span>
                                    <input type="number" step="0.01" class="form-control text-end"
                                        name="filas[{{ $i }}][total]" data-campo="total"
                                        value="{{ $fila->valores['total'] }}"
                                        @disabled($fila->errorNoRecuperable)
                                        aria-label="Total de {{ $fila->nombre }}">
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $fila->valida ? 'bg-success' : 'bg-danger' }}" data-badge-estado>
                                    {{ $fila->valida ? 'Válida' : 'Con error' }}
                                </span>
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
            <button type="submit" class="btn btn-primary" data-confirmar-importacion @disabled($validas === 0)>
                <i class="bi bi-check2-circle" aria-hidden="true"></i> Confirmar importación
            </button>
        </div>
    </form>

    @vite('resources/js/importacion-vista-previa.js')
@endif
