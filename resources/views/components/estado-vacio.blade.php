@props(['icono' => 'bi-inbox'])

{{--
    Estado vacío reutilizable (ver resources/css/bootstrap.scss §9): reemplaza
    los avisos "todavía no hay X" que antes eran un <p> plano por un bloque
    centrado con ícono de apoyo, conforme al Principio III de la Constitución
    ("iconos de soporte comprensibles").
--}}
<div {{ $attributes->merge(['class' => 'estado-vacio']) }}>
    <i class="bi {{ $icono }} estado-vacio__icono d-block mb-2" aria-hidden="true"></i>
    <p class="mb-0">{{ $slot }}</p>
</div>
