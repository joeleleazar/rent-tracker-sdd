# Data Model: Reconstrucción de Vistas según la Guía de Referencia Bootstrap

**Feature**: `012-reconstruccion-vistas-guia` | **Date**: 2026-08-21

## Entidades

*No aplica.* Esta feature no introduce, modifica ni elimina ninguna entidad de datos, tabla, columna o relación. Reutiliza exactamente el mismo modelo de datos ya definido por las especificaciones 001 a 011.

## Datos expuestos de forma nueva a una vista (sin nueva entidad)

- `ContratoController@store`/`@update`: al capturar `ContratoSolapadoException`, se adjunta a la respuesta el objeto `$contratoEnConflicto` (ya una instancia existente de `Contrato`, sin campos nuevos) para que el modal de solapamiento (FR-002) pueda mostrar sus datos reales. No es una entidad nueva ni un campo nuevo de base de datos — es el mismo modelo `Contrato` que la excepción ya calculaba internamente, ahora expuesto a la vista.

## Relaciones

*Sin cambios* respecto a las relaciones ya documentadas en `specs/001` a `specs/011`.

## Notas de migración

- No se requiere ninguna migración de base de datos para esta feature.
