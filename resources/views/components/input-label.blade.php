@props(['value'])

{{-- Dual Tailwind/Bootstrap: ver nota en components/mensaje-alerta.blade.php --}}
<label {{ $attributes->merge(['class' => 'form-label fw-semibold']) }}>
    {{ $value ?? $slot }}
</label>
