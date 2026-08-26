# Research: Simplificar Navegación de Periodo (Recibos)

**Feature**: `028-simplificar-navegacion-periodo-recibos` | **Date**: 2026-08-26

## R1 — Réplica de la decisión de specs/027

**Decision**: Aplicar exactamente el mismo cambio (eliminar `<x-secondary-button type="submit">Ir</x-secondary-button>`, línea 57 de `recibos/registro-masivo/index.blade.php`) sin reevaluar el trade-off, ya que el usuario lo pidió explícitamente como réplica ("lo mismo para recibos/registro-masivo") tras haber visto y aceptado el análisis completo en specs/027.

**Rationale**: Esta vista comparte el mismo patrón de `specs/024` (flechas + autoenvío) que `lecturas/registro-masivo`, incluso con el mismo comentario `{{-- specs/024 (periodo ágil): ver el mismo patrón ya explicado en lecturas/registro-masivo/index.blade.php --}}` (línea 15) apuntando explícitamente a esa vista gemela como referencia. A diferencia de `lecturas/registro-masivo`, esta vista NO fue fusionada con otros controles en una fila compartida (no hay tarifa/exportar aquí, ver specs/026 US4 que sí tocó la vista de lecturas) — el `<form>` del periodo ya es la única tarjeta de esa sección, sin necesidad de ajustar ningún comentario sobre aislamiento de campos.

**Alternatives considered**: Ninguna — es una réplica directa de una decisión ya tomada, no una decisión nueva a evaluar.

## R2 — Prueba de ausencia (FR-004)

**Decision**: Agregar un test nuevo (no existía ninguno sobre el botón "Ir" en este archivo, a diferencia de `lecturas/registro-masivo` que sí tenía uno de specs/024) que confirme que "Ir" no aparece en el marcado, análogo al agregado en specs/027 (T002).

**Rationale**: `tests/Feature/RegistroMasivoRecibosControllerTest.php` nunca tuvo una prueba que exigiera el botón (a diferencia de la vista de lecturas), así que no hay nada que reemplazar — solo agregar la protección negativa para que una reintroducción futura del botón sea detectada.
