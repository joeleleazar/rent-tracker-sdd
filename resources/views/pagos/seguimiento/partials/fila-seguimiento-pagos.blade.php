{{--
    Fila recursiva del seguimiento de pagos (specs/032), análoga a
    recibos/registro-masivo/partials/fila-registro-masivo-recibos.blade.php.

    Props esperadas:
    - $locacion (App\Models\Locacion)
    - $hijos (array<int, array{locacion: Locacion, hijos: array}>)
    - $profundidad (int)
    - $periodo (Illuminate\Support\Carbon)
    - $montoPagadoPorLocacion (array<int, float>) por locacion_id
    - $montoTotalPorLocacion (array<int, float>) por locacion_id
    - $cantidadRecibosPorLocacion (array<int, int>) por locacion_id
    - $estadoAgregadoPorLocacion (array<int, string>) por locacion_id — sin_recibos|sin_pagos|parcial|pagado
--}}
<div class="fila-seguimiento-pagos">
    <div class="fila-arbol__nombre" style="padding-left: calc({{ $profundidad }} * 1.5rem);">
        @if (! empty($hijos))
            <button
                type="button"
                class="fila-arbol__toggle"
                data-bs-toggle="collapse"
                data-bs-target="#hijos-seguimiento-pagos-{{ $locacion->id }}"
                aria-expanded="true"
                aria-controls="hijos-seguimiento-pagos-{{ $locacion->id }}"
                aria-label="Contraer o expandir las locaciones dentro de {{ $locacion->nombre }}"
            >
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
        @else
            <span class="fila-arbol__espaciador-toggle" aria-hidden="true"></span>
        @endif

        <i class="bi {{ $locacion->iconoTipo() }}" aria-hidden="true"></i>
        <span class="fw-semibold">{{ $locacion->nombre }}</span>
    </div>

    @if ($locacion->es_alquilable)
        @include('pagos.seguimiento.partials.estado-pago-locacion', [
            'locacion' => $locacion,
            'periodo' => $periodo,
            'montoPagado' => $montoPagadoPorLocacion[$locacion->id] ?? 0.0,
            'montoTotal' => $montoTotalPorLocacion[$locacion->id] ?? 0.0,
            'cantidadRecibos' => $cantidadRecibosPorLocacion[$locacion->id] ?? 0,
            'estadoAgregado' => $estadoAgregadoPorLocacion[$locacion->id] ?? 'sin_recibos',
        ])
    @else
        <div></div>
        <div></div>
        <div></div>
    @endif
</div>

@if (! empty($hijos))
    <div class="collapse show" id="hijos-seguimiento-pagos-{{ $locacion->id }}">
        <div class="fila-arbol__hijos">
            @foreach ($hijos as $nodo)
                @include('pagos.seguimiento.partials.fila-seguimiento-pagos', [
                    'locacion' => $nodo['locacion'],
                    'hijos' => $nodo['hijos'],
                    'profundidad' => $profundidad + 1,
                    'periodo' => $periodo,
                    'montoPagadoPorLocacion' => $montoPagadoPorLocacion,
                    'montoTotalPorLocacion' => $montoTotalPorLocacion,
                    'cantidadRecibosPorLocacion' => $cantidadRecibosPorLocacion,
                    'estadoAgregadoPorLocacion' => $estadoAgregadoPorLocacion,
                ])
            @endforeach
        </div>
    </div>
@endif
