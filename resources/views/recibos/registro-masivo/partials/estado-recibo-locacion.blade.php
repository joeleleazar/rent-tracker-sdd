{{--
    Columnas 2-5 (Contrato, Conceptos, Total del Periodo, Acción) de una locación en el
    registro masivo de recibos (specs/023; specs/024 US4 agrega la columna de total).
    `display: contents` en el wrapper deja que estas divs participen como items directos
    del grid de `.fila-registro-masivo-recibos`, sin que el wrapper mismo genere una caja.
    specs/026: "Generar Recibo" navega a la página individual de generación (ya no un
    modal) — este parcial ya no se re-renderiza vía htmx tras generar un recibo, solo al
    cargar la pantalla completa.

    Props esperadas:
    - $locacion (App\Models\Locacion)
    - $periodo (Illuminate\Support\Carbon)
    - $contratoActivo (?App\Models\Contrato)
    - $conceptosActivos (Illuminate\Support\Collection<int, App\Models\ConceptoGastoFijo>) todos los
      conceptos activos del catálogo, para pintar el badge de cada uno sin re-consultar por fila
    - $conceptosDisponibles (Illuminate\Support\Collection<int, App\Models\ConceptoGastoFijo>)
    - $reciboQueCubre (Illuminate\Support\Collection<int, App\Models\Recibo>) keyBy concepto_gasto_fijo_id
    - $cantidadRecibos (int)
    - $totalFacturado (float)
    - $tieneRecibos (bool) — específicamente distinto de $cantidadRecibos > 0: incluye recibos
      anulados (specs/026 US3), para no esconder el acceso a auditar un recibo ya anulado
--}}
<div id="fila-recibo-{{ $locacion->id }}" style="display: contents;">
    <div>
        @if ($contratoActivo === null)
            <span class="badge bg-secondary">Sin contrato activo</span>
        @else
            <span class="badge bg-success">Contrato activo</span>
        @endif
    </div>

    <div class="fila-registro-masivo-recibos__conceptos">
        @if ($contratoActivo !== null)
            @foreach ($conceptosActivos as $concepto)
                @if ($reciboQueCubre->has($concepto->id))
                    <a
                        href="{{ route('recibos.show', $reciboQueCubre[$concepto->id]) }}"
                        class="badge bg-secondary text-decoration-none"
                        title="Ya cubierto por un recibo — ver detalle"
                    >
                        <i class="bi bi-check-lg" aria-hidden="true"></i> {{ $concepto->nombre }}
                    </a>
                @else
                    <span class="badge bg-light text-dark border">{{ $concepto->nombre }}</span>
                @endif
            @endforeach
        @endif
    </div>

    <div>
        <span class="cifra">{{ $cantidadRecibos }} {{ Str::plural('recibo', $cantidadRecibos) }}</span>
        <span class="text-secondary"> · </span>
        <span class="cifra">S/ {{ number_format($totalFacturado, 2) }}</span>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        @if ($contratoActivo !== null && $conceptosDisponibles->isNotEmpty())
            <a
                href="{{ route('locaciones.recibos.create', ['locacion' => $locacion->id, 'periodo' => $periodo->format('Y-m')]) }}"
                class="btn btn-outline-primary btn-sm"
                aria-label="Generar recibo de {{ $locacion->nombre }}"
            >
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Generar Recibo
            </a>
        @elseif ($contratoActivo !== null)
            <span class="badge bg-success"><i class="bi bi-check2-all" aria-hidden="true"></i> Periodo completo</span>
        @endif

        @if ($tieneRecibos)
            <a
                href="{{ route('recibos.registroMasivo.recibosDelPeriodo', ['locacion' => $locacion->id, 'periodo' => $periodo->format('Y-m')]) }}"
                class="btn btn-outline-secondary btn-sm"
                aria-label="Ver recibos de {{ $locacion->nombre }}"
            >
                <i class="bi bi-eye" aria-hidden="true"></i> Ver Recibos
            </a>
        @endif
    </div>
</div>
