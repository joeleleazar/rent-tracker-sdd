@props(['messages'])

{{--
    Dual Tailwind/Bootstrap: ver nota en components/mensaje-alerta.blade.php.
    Se usa `invalid-feedback d-block` (en vez de solo `invalid-feedback`)
    porque Bootstrap oculta ese componente por defecto salvo que el input
    hermano tenga la clase `is-invalid`, algo que no gestionan estos
    componentes; como este `<ul>` ya solo se renderiza cuando hay mensajes
    (`@if ($messages)`), forzar `d-block` es más simple y robusto que
    cablear `is-invalid` en cada formulario.
--}}
@if ($messages)
    <ul {{ $attributes->merge(['class' => 'invalid-feedback d-block mt-2 space-y-1 text-lg font-semibold text-red-800']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
