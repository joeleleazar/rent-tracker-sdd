# Contrato de Comportamiento: Validación de "tipo" en Locación

**Feature**: `025-correcciones-auditoria-impeccable` | **Date**: 2026-08-26

## Contrato 1 — Editar una locación sin tipo previo

- `PUT /locaciones/{locacion}` sobre una locación con `tipo = null` DEBE aceptar la petición y guardar los demás campos aunque `tipo` llegue vacío.
- El resultado guardado DEBE mantener `tipo = null` (no se asigna un valor por defecto ni se inventa uno).

## Contrato 2 — Editar una locación que ya tiene tipo asignado

- `PUT /locaciones/{locacion}` sobre una locación con un `tipo` ya asignado, si el campo `tipo` llega vacío, DEBE seguir siendo rechazado con el mismo mensaje de error que produce hoy (`"Debe seleccionar el tipo de locación."`).

## Contrato 3 — Crear una locación nueva

- `POST /locaciones` sin `tipo` DEBE seguir siendo rechazado el 100% de las veces, sin excepción, exactamente igual que antes de este feature.

## Contrato 4 — Aspecto visual del sidebar

- El sidebar de navegación (`app-bootstrap.blade.php`) DEBE verse y comportarse igual (color, ancho en desktop, franja horizontal en móvil) antes y después de mover su estilo base a `bootstrap.scss`.
