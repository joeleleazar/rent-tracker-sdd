<x-mail::message>
# Contrato próximo a vencer

El contrato de la locación **{{ $contrato->locacion->nombre }}** está próximo a vencer en **{{ $diasAnticipacion }} días**.

- **Locación**: {{ $contrato->locacion->nombre }}
- **Inquilino**: {{ $contrato->inquilinoPrincipal()?->nombreCompleto() ?? '—' }}
- **Fecha de fin del contrato**: {{ $contrato->fecha_fin->format('d/m/Y') }}

Por favor, gestione la renovación o rescisión de este contrato a tiempo.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
