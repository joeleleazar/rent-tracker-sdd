{{--
    Fila recursiva del registro masivo de recibos (specs/023), análoga a
    lecturas/registro-masivo/partials/fila-registro-masivo.blade.php.

    Props esperadas:
    - $locacion (App\Models\Locacion)
    - $hijos (array<int, array{locacion: Locacion, hijos: array}>)
    - $profundidad (int)
    - $periodo (Illuminate\Support\Carbon)
    - $contratosActivos (Illuminate\Support\Collection<int, App\Models\Contrato>) keyBy locacion_id
    - $conceptosActivos (Illuminate\Support\Collection<int, App\Models\ConceptoGastoFijo>)
    - $conceptosDisponiblesPorLocacion (array<int, Illuminate\Support\Collection>) por locacion_id
    - $reciboQueCubrePorLocacion (array<int, Illuminate\Support\Collection>) por locacion_id
    - $cantidadRecibosPorLocacion (array<int, int>) por locacion_id
    - $totalFacturadoPorLocacion (array<int, float>) por locacion_id
    - $tieneRecibosPorLocacion (array<int, bool>) por locacion_id
--}}
<div class="fila-registro-masivo-recibos">
    <div class="fila-arbol__nombre" style="padding-left: calc({{ $profundidad }} * 1.5rem);">
        @if (! empty($hijos))
            <button
                type="button"
                class="fila-arbol__toggle"
                data-bs-toggle="collapse"
                data-bs-target="#hijos-registro-masivo-recibos-{{ $locacion->id }}"
                aria-expanded="true"
                aria-controls="hijos-registro-masivo-recibos-{{ $locacion->id }}"
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
        @include('recibos.registro-masivo.partials.estado-recibo-locacion', [
            'locacion' => $locacion,
            'periodo' => $periodo,
            'contratoActivo' => $contratosActivos->get($locacion->id),
            'conceptosActivos' => $conceptosActivos,
            'conceptosDisponibles' => $conceptosDisponiblesPorLocacion[$locacion->id] ?? collect(),
            'reciboQueCubre' => $reciboQueCubrePorLocacion[$locacion->id] ?? collect(),
            'cantidadRecibos' => $cantidadRecibosPorLocacion[$locacion->id] ?? 0,
            'totalFacturado' => $totalFacturadoPorLocacion[$locacion->id] ?? 0.0,
            'tieneRecibos' => $tieneRecibosPorLocacion[$locacion->id] ?? false,
        ])
    @else
        <div></div>
        <div></div>
        <div></div>
        <div></div>
    @endif
</div>

@if (! empty($hijos))
    <div class="collapse show" id="hijos-registro-masivo-recibos-{{ $locacion->id }}">
        <div class="fila-arbol__hijos">
            @foreach ($hijos as $nodo)
                @include('recibos.registro-masivo.partials.fila-registro-masivo-recibos', [
                    'locacion' => $nodo['locacion'],
                    'hijos' => $nodo['hijos'],
                    'profundidad' => $profundidad + 1,
                    'periodo' => $periodo,
                    'contratosActivos' => $contratosActivos,
                    'conceptosActivos' => $conceptosActivos,
                    'conceptosDisponiblesPorLocacion' => $conceptosDisponiblesPorLocacion,
                    'reciboQueCubrePorLocacion' => $reciboQueCubrePorLocacion,
                    'cantidadRecibosPorLocacion' => $cantidadRecibosPorLocacion,
                    'totalFacturadoPorLocacion' => $totalFacturadoPorLocacion,
                    'tieneRecibosPorLocacion' => $tieneRecibosPorLocacion,
                ])
            @endforeach
        </div>
    </div>
@endif
