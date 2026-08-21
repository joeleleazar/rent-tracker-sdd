{{-- Dual Tailwind/Bootstrap: ver nota en components/mensaje-alerta.blade.php --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-outline-secondary btn-lg']) }}>
    {{ $slot }}
</button>
