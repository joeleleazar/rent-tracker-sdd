@props(['status'])

{{-- Dual Tailwind/Bootstrap: ver nota en components/mensaje-alerta.blade.php --}}
@if ($status)
    <div {{ $attributes->merge(['class' => 'fw-semibold fs-5 text-success font-medium text-sm text-green-600']) }}>
        {{ $status }}
    </div>
@endif
