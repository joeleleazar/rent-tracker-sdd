{{-- Dual Tailwind/Bootstrap: ver nota en components/mensaje-alerta.blade.php --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-primary btn-lg']) }}>
    {{ $slot }}
</button>
