<x-mail::message>
# Fecha límite de pago próxima

El **{{ $fechaLimite->translatedFormat('d/m/Y') }}** (último sábado del mes) vence el plazo de pago mensual de los alquileres.

Por favor, revise los recibos pendientes de cobro antes de esa fecha.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
