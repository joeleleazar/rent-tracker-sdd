# Quickstart: Corrección de Lectura Previa y Autoguardado en Registro Masivo

**Feature**: `016-correccion-registro-masivo-lecturas` | **Date**: 2026-08-25

Guía de validación end-to-end de la corrección. Ver `data-model.md` y
`contracts/lectura-anterior-y-autoguardado.md` para el detalle técnico, y `research.md` para el
protocolo de reproducción que debe correrse **antes** de escribir el fix (paso 0 de esta guía).

## Prerrequisitos

- Migración de `borradores_lectura_medidor` ya ejecutada (ver `php artisan migrate:status`).
- Usuario autenticado.
- **Nota de entorno**: usar el binario de PHP de Herd
  (`C:\Users\joel5\.config\herd\bin\php.bat`) para `artisan`/`pest` — el PHP 8.0.30 de PATH por
  defecto no cumple el mínimo `>= 8.4.1` de `composer.lock`.
- Datos de ejemplo:
  - **Locación A**: lecturas registradas en mayo y julio de 2026 (periodos no consecutivos).
  - **Locación B**: lectura registrada en junio de 2026.
  - **Locación C**: ninguna lectura registrada en ningún periodo.

## Paso 0 — Reproducir antes de tocar código (research.md, protocolo de reproducción)

1. Abrir `/lecturas/registro-masivo?periodo=2026-08` y comparar, fila por fila:
   - Locación A debe mostrar `julio` (su periodo anterior más reciente, no mayo).
   - Locación B debe mostrar `junio`.
   - Locación C debe mostrar "Sin lectura previa registrada".
2. Cambiar el periodo a `2026-01` (cruce de año) y confirmar que cada locación recalcula su
   referencia contra diciembre de 2025 hacia atrás, sin quedarse con el valor de agosto.
3. Completar un par de filas sin guardar, abrir la pestaña de Red del navegador y esperar ~120s:
   confirmar el `POST` a `lecturas.registroMasivo.borrador` con el payload esperado. Esperar un
   segundo ciclo y confirmar que el borrador en base de datos tiene el valor más reciente. Recargar
   la pantalla para el mismo periodo y confirmar que los campos se restauran solos.
4. Documentar el resultado real de cada verificación (qué se vio) antes de continuar — si algo no
   reprodujo el síntoma reportado, no se cambia ese código; se documenta como ya conforme.

## Escenario 1 — Lectura anterior correcta con periodos no consecutivos (US1, FR-001/FR-003)

1. Con los datos de ejemplo de arriba, abrir `/lecturas/registro-masivo?periodo=2026-08`.
2. **Resultado esperado**: Locación A muestra el valor de julio (no mayo, no un valor vacío, no el
   de Locación B); Locación B muestra el de junio; Locación C indica claramente que no hay lectura
   previa.

## Escenario 2 — Recalculo al cambiar de periodo, incluyendo cruce de año (US1, FR-003)

1. Desde la pantalla del Escenario 1, cambiar el selector de periodo a `2026-01`.
2. **Resultado esperado**: cada locación recalcula su "lectura periodo anterior" contra periodos de
   2025, no contra el periodo que estaba seleccionado antes del cambio.

## Escenario 3 — Autoguardado persiste y se actualiza en ciclos sucesivos (US2, FR-004)

1. Abrir la pantalla, completar la lectura actual de Locación B, esperar ~120s sin guardar
   manualmente.
2. **Resultado esperado**: consultando `borradores_lectura_medidor` para ese usuario/periodo, existe
   una fila con el valor tipeado.
3. Modificar el valor tipeado y esperar otro ciclo (~120s más).
4. **Resultado esperado**: la misma fila del borrador ahora tiene el valor más reciente (no una fila
   duplicada, no el valor del primer ciclo).

## Escenario 4 — Restauración automática al reabrir (US2, FR-005)

1. Con el borrador del Escenario 3 aún existente, cerrar la pestaña sin guardar el lote y volver a
   abrir `/lecturas/registro-masivo?periodo=2026-08` (mismo periodo, mismo usuario).
2. **Resultado esperado**: el campo de Locación B aparece prellenado con el último valor
   autoguardado, sin ninguna acción manual adicional.

## Regresión (specs/015, no debe romperse)

- Guardar el lote completo sigue eliminando el borrador de ese usuario/periodo (FR-012 de 015).
- Un error de consumo negativo sin confirmar en una fila sigue sin descartar las demás filas
  válidas (FR-009 de 015).
