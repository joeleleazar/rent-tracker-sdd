{{--
    Fila recursiva del registro masivo de lecturas (specs/015-registro-masivo-lecturas),
    análoga a locaciones/partials/fila-arbol-locacion.blade.php pero con un campo
    de lectura en vez de acciones de gestión.

    Props esperadas:
    - $locacion (App\Models\Locacion)
    - $hijos (array<int, array{locacion: Locacion, hijos: array}>)
    - $profundidad (int)
    - $periodo (Illuminate\Support\Carbon)
    - $lecturasDelPeriodo (Illuminate\Support\Collection<int, App\Models\LecturaMedidor>) keyBy locacion_id
    - $lecturasAnteriores (Illuminate\Support\Collection<int, App\Models\LecturaMedidor>) keyBy locacion_id
    - $borradores (Illuminate\Support\Collection<int, App\Models\BorradorLecturaMedidor>) keyBy locacion_id
--}}
<div class="fila-registro-masivo">
    <div class="fila-arbol__nombre" style="padding-left: calc({{ $profundidad }} * 1.5rem);">
        @if (! empty($hijos))
            <button
                type="button"
                class="fila-arbol__toggle"
                data-bs-toggle="collapse"
                data-bs-target="#hijos-registro-masivo-{{ $locacion->id }}"
                aria-expanded="true"
                aria-controls="hijos-registro-masivo-{{ $locacion->id }}"
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
        @php
            $lecturaDelPeriodo = $lecturasDelPeriodo->get($locacion->id);
            $lecturaAnterior = $lecturasAnteriores->get($locacion->id);
            $borrador = $borradores->get($locacion->id);
        @endphp

        <div>
            @if ($lecturaAnterior === null)
                Sin lectura previa registrada
            @else
                {{ $lecturaAnterior->lectura_actual }}
            @endif
        </div>

        @include('lecturas.registro-masivo.partials.campo-lectura-registro-masivo', [
            'locacion' => $locacion,
            'lecturaDelPeriodo' => $lecturaDelPeriodo,
            'lecturaAnterior' => $lecturaAnterior,
            'borrador' => $borrador,
            'modoEdicion' => false,
        ])

        <div class="cifra fila-registro-masivo__consumo" id="consumo-fila-{{ $locacion->id }}" aria-label="Consumo de {{ $locacion->nombre }}">—</div>
        <div class="cifra fila-registro-masivo__total" id="total-fila-{{ $locacion->id }}" aria-label="Total de {{ $locacion->nombre }}">—</div>
    @else
        <div></div>
        <div></div>
        <div></div>
        <div></div>
    @endif
</div>

@if (! empty($hijos))
    <div class="collapse show" id="hijos-registro-masivo-{{ $locacion->id }}">
        <div class="fila-arbol__hijos">
            @foreach ($hijos as $nodo)
                @include('lecturas.registro-masivo.partials.fila-registro-masivo', [
                    'locacion' => $nodo['locacion'],
                    'hijos' => $nodo['hijos'],
                    'profundidad' => $profundidad + 1,
                    'periodo' => $periodo,
                    'lecturasDelPeriodo' => $lecturasDelPeriodo,
                    'lecturasAnteriores' => $lecturasAnteriores,
                    'borradores' => $borradores,
                ])
            @endforeach
        </div>
    </div>
@endif
