# Quickstart: Columna de Consumo y Alineación del Ícono de Completado en Registro Masivo

**Feature**: `017-columna-consumo-lecturas` | **Date**: 2026-08-25

Guía de validación end-to-end. Ver `data-model.md` y `contracts/columna-consumo-y-icono.md` para
el detalle técnico, y `tasks.md` para las tareas de construcción.

## Prerrequisitos

- Usuario autenticado.
- **Nota de entorno**: usar el binario de PHP de Herd
  (`C:\Users\joel5\.config\herd\bin\php.bat`) para `artisan`/`pest`.
- Datos de ejemplo:
  - **Locación A**: lectura de julio de 2026 y lectura ya guardada de agosto de 2026 (fila
    "completada").
  - **Locación B**: lectura de julio de 2026, sin lectura guardada de agosto todavía (fila
    "pendiente" con referencia anterior disponible).
  - **Locación C**: ninguna lectura registrada en ningún periodo (fila "pendiente" sin referencia).

## Escenario 1 — Ver el consumo de una fila ya completada (US1, FR-001/FR-002)

1. Abrir `/lecturas/registro-masivo?periodo=2026-08`.
2. **Resultado esperado**: la fila de Locación A muestra una columna "Consumo" (entre "Lectura
   Actual" y "Total") con el valor exacto `lectura_actual − lectura_anterior` de esa locación.
3. Exportar a Excel o PDF desde la misma pantalla y comparar la columna "Consumo (kWh)" de la
   exportación contra el valor visto en pantalla — deben coincidir exactamente (SC-001).

## Escenario 2 — Consumo en vivo mientras se tipea (US1, FR-003)

1. En la misma pantalla, escribir un valor en el campo "Lectura Actual" de Locación B, sin
   guardar el lote todavía.
2. **Resultado esperado**: la columna "Consumo" de esa fila se actualiza en el momento reflejando
   el valor tipeado menos la lectura anterior de Locación B, sin recargar la página.

## Escenario 3 — Sin lectura anterior disponible (US1, FR-004)

1. Ver la fila de Locación C (sin ninguna lectura previa).
2. **Resultado esperado**: la columna "Consumo" muestra el indicador de "sin dato" (`—`), nunca un
   valor vacío ambiguo ni un `0`.

## Escenario 4 — Ícono de completado alineado a la izquierda (US2, FR-006)

1. Ver la fila de Locación A (ya completada).
2. **Resultado esperado**: el ícono verde de confirmación aparece inmediatamente a la izquierda del
   valor de la lectura actual, no a su derecha.
3. Hacer clic en el ícono para editar la lectura en línea.
4. **Resultado esperado**: la fila cambia a modo edición exactamente igual que antes de este
   cambio — el reacomodo no afecta el comportamiento de edición.

## Regresión (specs/015/016, no debe romperse)

- El total general y el total por fila (columna "Total") siguen calculándose igual que antes,
  ahora en una columna más a la derecha de "Consumo".
- La exportación a Excel/PDF no cambia de formato ni de columnas — ya tenía "Consumo (kWh)" desde
  specs/015.
- El autoguardado (specs/016) y la restauración de borradores siguen funcionando igual; esta
  feature no toca esa lógica.
