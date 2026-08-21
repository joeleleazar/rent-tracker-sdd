# Data Model: Migración de la Interfaz a Bootstrap 5

**Feature**: `010-migracion-interfaz-bootstrap` | **Date**: 2026-08-21

## Entidades

*No aplica.* Esta feature no introduce, modifica ni elimina ninguna entidad de datos, tabla, columna o relación. Reutiliza exactamente el mismo modelo de datos ya definido por las especificaciones 001 a 009 (`Locacion`, `Contrato`, `Inquilino`, `Representante`, `DocumentoContrato`, `ConfiguracionGeneral`, `Recibo`, `LecturaMedidor`).

## Relaciones

*Sin cambios* respecto a las relaciones ya documentadas en `specs/001-jerarquia-locaciones/data-model.md` a través de `specs/009-garantia-contrato/data-model.md`.

## Notas de migración

- No se requiere ninguna migración de base de datos para esta feature.
- El único artefacto "de datos" nuevo es el arreglo `{periodo, consumo}` que alimenta el gráfico Chart.js de FR-005 (ver `research.md` §5), que se deriva de una consulta ya existente sobre `LecturaMedidor` — no es una entidad nueva ni requiere una columna adicional.
