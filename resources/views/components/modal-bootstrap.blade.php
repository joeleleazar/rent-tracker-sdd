@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'focusable' => false,
])

@php
    // Bootstrap solo define modal-sm/modal-lg/modal-xl; el resto de anchos
    // (md/2xl) usa el ancho por defecto de `.modal-dialog`.
    $anchoBootstrap = [
        'sm' => 'modal-sm',
        'md' => '',
        'lg' => 'modal-lg',
        'xl' => 'modal-xl',
        '2xl' => 'modal-xl',
    ][$maxWidth] ?? '';
@endphp

{{--
    Reemplazo Bootstrap nativo de `x-modal` (Alpine), usado exclusivamente por
    vistas ya migradas al layout Bootstrap (`x-layouts.app-bootstrap`). No
    puede ser el mismo archivo/tag que `components/modal.blade.php` porque
    cada layout carga un motor de interactividad distinto (Alpine vs. el JS
    nativo de Bootstrap) y nunca conviven ambos en la misma página — ver
    specs/010-migracion-interfaz-bootstrap/research.md §2-3.

    Uso: se abre con un botón con atributos `data-bs-toggle="modal"` y
    `data-bs-target="#nombre-del-modal"` (coincidiendo con el prop `name`)
    en vez de `x-on:click="$dispatch('open-modal', '...')"`, y se cierra con
    `data-bs-dismiss="modal"` en vez de `x-on:click="$dispatch('close')"`.
    `focusable` se conserva por compatibilidad de firma con `x-modal` pero no
    tiene efecto propio: Bootstrap gestiona el foco de accesibilidad
    automáticamente al abrir. `show`, en cambio, SÍ se conserva con su
    comportamiento original — se usa para reabrir automáticamente un modal
    tras una recarga con errores de validación (ej. "Eliminar cuenta" con
    contraseña incorrecta); como Bootstrap siempre parte cerrado por
    definición de su propio CSS, esto se resuelve con `data-autoshow` +
    `resources/js/bootstrap.js`, que abre el modal en el primer pintado.
--}}
<div
    {{ $attributes->merge(['class' => 'modal fade']) }}
    id="{{ $name }}"
    tabindex="-1"
    aria-hidden="true"
    @if ($show) data-autoshow="1" @endif
>
    <div class="modal-dialog modal-dialog-centered {{ $anchoBootstrap }}">
        <div class="modal-content">
            {{ $slot }}
        </div>
    </div>
</div>
