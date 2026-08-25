{{--
    Fila recursiva de la tabla jerárquica de locaciones (specs/013-arbol-jerarquico-locaciones,
    revisión 2026-08-23: reemplaza a nodo-arbol-locacion.blade.php).

    Props esperadas:
    - $locacion (App\Models\Locacion): la locación representada por esta fila.
    - $hijos (array<int, array{locacion: Locacion, hijos: array}>): sub-árbol ya
      construido por ServicioConstruccionArbolLocaciones::construir().
    - $profundidad (int): nivel de anidamiento (0 para raíces), usado para la
      indentación de la columna Nombre/Locación.
--}}
<div class="fila-arbol">
    <div class="fila-arbol__nombre" style="padding-left: calc({{ $profundidad }} * 1.5rem);">
        @if (! empty($hijos))
            <button
                type="button"
                class="fila-arbol__toggle"
                data-bs-toggle="collapse"
                data-bs-target="#hijos-locacion-{{ $locacion->id }}"
                aria-expanded="true"
                aria-controls="hijos-locacion-{{ $locacion->id }}"
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

    <div>
        <span class="badge {{ $locacion->es_alquilable ? 'text-bg-success' : 'text-bg-secondary' }}">
            {{ $locacion->es_alquilable ? 'Alquilable' : 'No Alquilable' }}
        </span>
    </div>

    <div>{{ $locacion->etiquetaTipo() }}</div>

    <div class="d-flex gap-2">
        <a
            href="{{ route('locaciones.create', ['locacion_padre_id' => $locacion->id]) }}"
            class="btn btn-sm btn-outline-primary"
            aria-label="Agregar locación hija de {{ $locacion->nombre }}"
        ><i class="bi bi-plus-lg" aria-hidden="true"></i></a>

        <a href="{{ route('locaciones.edit', $locacion) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil-square" aria-hidden="true"></i> Editar
        </a>
    </div>
</div>

@if (! empty($hijos))
    <div class="collapse show" id="hijos-locacion-{{ $locacion->id }}">
        <div class="fila-arbol__hijos">
            @foreach ($hijos as $nodo)
                @include('locaciones.partials.fila-arbol-locacion', ['locacion' => $nodo['locacion'], 'hijos' => $nodo['hijos'], 'profundidad' => $profundidad + 1])
            @endforeach
        </div>
    </div>
@endif
