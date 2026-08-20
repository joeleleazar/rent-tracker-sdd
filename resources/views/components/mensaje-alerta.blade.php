@props(['tipo' => 'exito'])

@php
    $estilos = match ($tipo) {
        'error' => 'border-red-800 bg-red-50 text-red-900',
        default => 'border-green-800 bg-green-50 text-green-900',
    };
@endphp

{{--
    Mensaje persistente (no se oculta automáticamente) de alto contraste,
    conforme al Principio III (Senior-First) de la Constitución.
--}}
<div {{ $attributes->merge(['class' => "rounded-md border-2 px-6 py-4 text-lg font-semibold $estilos"]) }} role="alert">
    {{ $slot }}
</div>
