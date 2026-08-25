@props(['tipo' => 'exito'])

@php
    $claseBootstrap = match ($tipo) {
        'error' => 'alert-danger',
        default => 'alert-success',
    };
    $icono = match ($tipo) {
        'error' => 'bi-exclamation-triangle-fill',
        default => 'bi-check-circle-fill',
    };
@endphp

{{--
    Mensaje persistente (no se oculta automáticamente) de alto contraste con
    ícono de apoyo, conforme al Principio III de la Constitución ("iconos de
    soporte comprensibles").
--}}
<div {{ $attributes->merge(['class' => "alert $claseBootstrap d-flex align-items-start gap-2"]) }} role="alert">
    <i class="bi {{ $icono }} fs-5 flex-shrink-0" aria-hidden="true"></i>
    <div class="flex-grow-1">{{ $slot }}</div>
</div>
