@props(['montoPagado', 'montoTotal'])

@php
    $montoPagado = (float) $montoPagado;
    $montoTotal = (float) $montoTotal;
    $porcentaje = $montoTotal > 0 ? (int) min(100, round($montoPagado / $montoTotal * 100)) : 0;
    $colorClase = match (true) {
        $montoPagado <= 0.0 => 'bg-secondary',
        $montoPagado >= $montoTotal => 'bg-success',
        default => 'bg-warning',
    };
@endphp

{{--
    specs/034: refuerzo visual del avance de pago ya mostrado en texto por el llamador — el
    porcentaje y el color se derivan de los mismos dos montos que ese texto usa, para que nunca
    puedan desincronizarse entre sí (research.md Decisión 1).
--}}
<div {{ $attributes->merge(['class' => 'progress']) }} style="height: 0.5rem;" role="progressbar" aria-label="Avance de pago" aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100">
    <div class="progress-bar {{ $colorClase }}" style="width: {{ $porcentaje }}%"></div>
</div>
