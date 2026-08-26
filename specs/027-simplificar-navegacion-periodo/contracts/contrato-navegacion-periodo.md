# Contrato de Comportamiento: Navegación de Periodo (Registro Masivo de Lecturas)

**Feature**: `027-simplificar-navegacion-periodo` | **Date**: 2026-08-26

## Contrato 1 — Flechas

- Un clic en la flecha "Periodo anterior" o "Periodo siguiente" DEBE navegar al periodo correspondiente exactamente igual que antes de este feature (mismo `href`, mismos atributos `hx-*`).

## Contrato 2 — Autoenvío del campo de fecha

- Un cambio de valor en el campo de fecha (mes) DEBE navegar automáticamente al periodo seleccionado, sin ninguna acción adicional del usuario, exactamente igual que antes de este feature.

## Contrato 3 — Ausencia del botón "Ir"

- El marcado de `/lecturas/registro-masivo` NO DEBE contener ningún botón con el texto "Ir" bajo ninguna condición.

## Contrato 4 — Aislamiento del formulario de periodo

- Cambiar el periodo (por flecha o por campo de fecha) NO DEBE enviar ni modificar los campos de tarifa por kWh ni los enlaces de exportar de la misma fila de controles.
