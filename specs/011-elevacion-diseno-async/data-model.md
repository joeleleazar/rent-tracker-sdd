# Data Model: Elevación de Diseño, Login como Entrada y Escritura Asíncrona

**Feature**: `011-elevacion-diseno-async` | **Date**: 2026-08-21

## Entidades

*No aplica.* Esta feature no introduce, modifica ni elimina ninguna entidad de datos, tabla, columna o relación. Reutiliza exactamente el mismo modelo de datos ya definido por las especificaciones 001 a 010.

## Relaciones

*Sin cambios* respecto a las relaciones ya documentadas en `specs/001-jerarquia-locaciones/data-model.md` a través de `specs/010-migracion-interfaz-bootstrap/data-model.md`.

## Notas de migración

- No se requiere ninguna migración de base de datos para esta feature.
- El único cambio de comportamiento a nivel de servidor es la ruta `/` (US2), que no involucra ninguna entidad de datos — es una redirección condicional basada en el estado de sesión ya provisto por el sistema de autenticación existente.
