# Contrato de Comportamiento: Navegación de Periodo (Registro Masivo de Recibos)

**Feature**: `028-simplificar-navegacion-periodo-recibos` | **Date**: 2026-08-26

Idéntico al contrato de specs/027 (`contracts/contrato-navegacion-periodo.md`), aplicado a `/recibos/registro-masivo`:

## Contrato 1 — Flechas
Un clic en "Periodo anterior"/"Periodo siguiente" navega igual que antes de este feature.

## Contrato 2 — Autoenvío del campo de fecha
Un cambio en el campo de fecha navega automáticamente igual que antes.

## Contrato 3 — Ausencia del botón "Ir"
El marcado de `/recibos/registro-masivo` no contiene ningún botón con texto "Ir".
