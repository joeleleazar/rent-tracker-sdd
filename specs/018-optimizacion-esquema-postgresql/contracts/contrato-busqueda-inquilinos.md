# Contrato de Comportamiento: Búsqueda de inquilinos con índice `pg_trgm`

**Feature**: `018-optimizacion-esquema-postgresql` | **Date**: 2026-08-25

El índice GIN de trigramas (research.md R5) DEBE ser transparente para `InquilinoController::buscar()` — ningún cambio de código en el controlador, solo de esquema.

## Contrato 1 — Mismo conjunto de resultados

- Una búsqueda por un término que coincide con el inicio de un DNI o apellido sigue devolviendo esa coincidencia.
- Una búsqueda por un término que coincide en medio de la palabra (substring, no solo prefijo) sigue devolviendo esa coincidencia — el índice `pg_trgm` DEBE soportar esto sin degradar el conjunto de resultados, a diferencia de una alternativa de solo-prefijo.
- Una búsqueda sin coincidencias sigue devolviendo una lista vacía, no un error.
- El orden y límite de resultados devueltos no cambia respecto al comportamiento actual (mismo `ORDER BY`/`LIMIT` ya presente en el controlador).

## Contrato 2 — Rendimiento estable (FR-003, SC-002)

- El tiempo de respuesta de la búsqueda no debe degradarse de forma proporcional al crecimiento de la tabla `inquilinos` una vez creado el índice — verificable con `EXPLAIN ANALYZE` mostrando un `Bitmap Index Scan` sobre el índice GIN en vez de un `Seq Scan`.
