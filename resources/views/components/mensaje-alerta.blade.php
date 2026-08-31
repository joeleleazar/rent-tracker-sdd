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
    Notificación de respuesta efímera (specs/042): alto contraste con ícono de
    apoyo (Principio III) y, además, descartable — se autocierra tras un máximo
    de 8 s salvo que el puntero o el foco de teclado estén encima (lógica en
    resources/js/bootstrap.js), y siempre ofrece el botón de cierre manual.
    Sin JavaScript el mensaje queda visible de forma persistente (degradación
    elegante). Las clases `fade show` habilitan la transición de cierre.
--}}
<div {{ $attributes->merge(['class' => "alert $claseBootstrap alert-dismissible fade show d-flex align-items-start gap-2"]) }} role="alert">
    <i class="bi {{ $icono }} fs-5 flex-shrink-0" aria-hidden="true"></i>
    <div class="flex-grow-1">{{ $slot }}</div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
