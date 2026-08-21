@props(['disabled' => false])

{{-- Dual Tailwind/Bootstrap: ver nota en components/mensaje-alerta.blade.php --}}
<input @disabled($disabled) {{ $attributes->merge(['class' => 'form-control form-control-lg']) }}>
