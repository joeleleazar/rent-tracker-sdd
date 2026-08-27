{{--
    Columnas 2-4 (Estado de Pago, Avance, Acción) de una locación en el
    seguimiento de pagos (specs/032). `display: contents` en el wrapper deja
    que estas divs participen como items directos del grid de
    `.fila-seguimiento-pagos`, mismo patrón que
    recibos/registro-masivo/partials/estado-recibo-locacion.blade.php.

    Props esperadas:
    - $locacion (App\Models\Locacion)
    - $periodo (Illuminate\Support\Carbon)
    - $montoPagado (float)
    - $montoTotal (float)
    - $cantidadRecibos (int)
    - $estadoAgregado (string) — sin_recibos|sin_pagos|parcial|pagado
--}}
<div id="fila-pago-{{ $locacion->id }}" style="display: contents;">
    <div>
        @if ($estadoAgregado === 'sin_pagos')
            <span class="badge bg-secondary">Sin pagos</span>
        @elseif ($estadoAgregado === 'parcial')
            <span class="badge bg-warning">Parcial</span>
        @elseif ($estadoAgregado === 'pagado')
            <span class="badge bg-success">Pagado</span>
        @endif
    </div>

    <div class="fila-seguimiento-pagos__avance">
        @if ($estadoAgregado !== 'sin_recibos')
            <div class="d-flex flex-column align-items-end gap-1">
                <span>
                    <span class="cifra">S/ {{ number_format($montoPagado, 2) }}</span>
                    <span class="text-secondary"> / </span>
                    <span class="cifra">S/ {{ number_format($montoTotal, 2) }}</span>
                </span>
                <x-barra-progreso-pago :monto-pagado="$montoPagado" :monto-total="$montoTotal" class="w-100" />
            </div>
        @endif
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        @if ($estadoAgregado === 'sin_pagos' || $estadoAgregado === 'parcial')
            {{-- specs/033 (FR-005; research.md Decisión 2/3): reutiliza la misma ruta que "Ver
                 Pagos" — ya redirige directo al recibo si hay uno solo, o a un selector si hay
                 varios (specs/026). --}}
            <a
                href="{{ route('recibos.registroMasivo.recibosDelPeriodo', ['locacion' => $locacion->id, 'periodo' => $periodo->format('Y-m')]) }}"
                class="btn btn-outline-primary btn-sm"
                aria-label="Registrar pago de {{ $locacion->nombre }}"
            >
                <i class="bi bi-cash-coin" aria-hidden="true"></i> Registrar Pago
            </a>
        @endif

        @if ($cantidadRecibos > 0)
            <a
                href="{{ route('recibos.registroMasivo.recibosDelPeriodo', ['locacion' => $locacion->id, 'periodo' => $periodo->format('Y-m')]) }}"
                class="btn btn-outline-secondary btn-sm"
                aria-label="Ver pagos de {{ $locacion->nombre }}"
            >
                <i class="bi bi-eye" aria-hidden="true"></i> Ver Pagos
            </a>
        @endif
    </div>
</div>
