@props(['tipo' => 'exito'])

@php
    // Clases Tailwind (layout Tailwind actual) y Bootstrap (layout nuevo)
    // conviven en el mismo componente: cada página carga solo una de las dos
    // hojas de estilo (nunca ambas a la vez, ver research.md §3 de la spec
    // 010-migracion-interfaz-bootstrap), así que las clases del framework no
    // usado en cada página quedan simplemente inertes, sin conflicto visual.
    $estilos = match ($tipo) {
        'error' => 'border-red-800 bg-red-50 text-red-900',
        default => 'border-green-800 bg-green-50 text-green-900',
    };
    $claseBootstrap = match ($tipo) {
        'error' => 'alert-danger',
        default => 'alert-success',
    };
@endphp

{{--
    Mensaje persistente (no se oculta automáticamente) de alto contraste,
    conforme al Principio III de la Constitución.
--}}
<div {{ $attributes->merge(['class' => "alert $claseBootstrap rounded-md px-6 py-4 text-lg font-semibold $estilos"]) }} role="alert">
    {{ $slot }}
</div>
