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
            <span class="cifra">S/ {{ number_format($montoPagado, 2) }}</span>
            <span class="text-secondary"> / </span>
            <span class="cifra">S/ {{ number_format($montoTotal, 2) }}</span>
        @endif
    </div>

    <div>
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
