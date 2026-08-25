@props(['ruta'])

{{--
    Breadcrumb accesible: alto contraste, truncado a los últimos 3 niveles
    (FR-004) para mantener la ruta legible sin abrumar la interfaz.

    Dual Tailwind/Bootstrap (ver nota en components/mensaje-alerta.blade.php):
    se agrega la clase `breadcrumb` de Bootstrap al `<ol>` y `breadcrumb-item`
    solo a los `<li>` de nivel real, NUNCA a los separadores manuales "…"/"&gt;"
    — así el separador `::before` automático de Bootstrap (que solo aplica
    entre dos `.breadcrumb-item` consecutivos) nunca llega a activarse, y el
    separador visible sigue siendo el mismo texto literal ya usado hoy, sin
    duplicarlo.
--}}
<nav aria-label="Ruta de jerarquía" {{ $attributes->merge(['class' => 'fw-semibold text-lg font-semibold text-gray-800']) }}>
    <ol class="breadcrumb flex flex-wrap items-center gap-2 mb-0">
        @if ($ruta['omitido'])
            <li aria-hidden="true" class="text-secondary text-gray-500">…</li>
            <li aria-hidden="true" class="text-secondary text-gray-500">&gt;</li>
        @endif
        @foreach ($ruta['niveles'] as $indice => $nivel)
            @if ($indice > 0)
                <li aria-hidden="true" class="text-secondary text-gray-500">&gt;</li>
            @endif
            <li class="breadcrumb-item">{{ $nivel->nombre }}</li>
        @endforeach
    </ol>
</nav>
