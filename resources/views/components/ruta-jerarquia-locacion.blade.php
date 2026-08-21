@props(['ruta'])

{{--
    Breadcrumb accesible (Senior-First): tipografía >= 18px, alto contraste,
    sin menús desplegables, truncado a los últimos 3 niveles (FR-004).
--}}
<nav aria-label="Ruta de jerarquía" {{ $attributes->merge(['class' => 'text-lg font-semibold text-gray-800']) }}>
    <ol class="flex flex-wrap items-center gap-2">
        @if ($ruta['omitido'])
            <li aria-hidden="true" class="text-gray-500">…</li>
            <li aria-hidden="true" class="text-gray-500">&gt;</li>
        @endif
        @foreach ($ruta['niveles'] as $indice => $nivel)
            @if ($indice > 0)
                <li aria-hidden="true" class="text-gray-500">&gt;</li>
            @endif
            <li>{{ $nivel->nombre }}</li>
        @endforeach
    </ol>
</nav>
