# Quickstart: Lectura Anterior por Defecto y Total Editable y Persistido

**Feature**: `019-total-editable-recibos` | **Date**: 2026-08-25

Guía de validación end-to-end. Ver `data-model.md` y `contracts/total-editable-y-recibo.md` para el
detalle técnico, y `tasks.md` para las tareas de construcción.

## Prerrequisitos

- Usuario autenticado.
- **Nota de entorno**: usar el binario de PHP de Herd
  (`C:\Users\joel5\.config\herd\bin\php.bat`) para `artisan`/`pest`.
- Datos de ejemplo:
  - **Locación A**: ninguna lectura registrada en ningún periodo (para probar el default de 0).
  - **Locación B**: lectura de un periodo anterior, sin lectura del periodo actual todavía (para
    probar el total editable).
  - Un contrato activo sobre alguna de las locaciones anteriores, para poder generar un recibo de
    prueba (specs/005).
  - Tarifa por kWh configurada en Configuración General (mayor a 0).

## Escenario 1 — Consumo calculable sin lectura anterior (US1, FR-001)

1. Abrir `/lecturas/registro-masivo?periodo=2026-08` y completar la lectura actual de Locación A
   (ej. 500).
2. Guardar el lote.
3. **Resultado esperado**: la lectura se guarda sin error; su consumo calculado es igual a la
   lectura actual (500), como si la anterior fuese 0.

## Escenario 2 — Editar el total sugerido antes de guardar (US2, FR-002/FR-003/FR-004)

1. En la misma pantalla, completar la lectura actual de Locación B.
2. **Resultado esperado**: el campo "Total" de esa fila se prellena solo con el total sugerido
   (consumo × tarifa vigente).
3. Editar manualmente ese campo a un valor distinto.
4. Guardar el lote.
5. **Resultado esperado**: la fila de Locación B pasa a "completada" mostrando exactamente el valor
   editado (no el sugerido original) como su Total, de solo lectura.

## Escenario 3 — El total guardado no cambia si la tarifa cambia después (US2, FR-005)

1. Con la lectura de Locación B ya guardada (Escenario 2), cambiar la tarifa por kWh en
   Configuración General a un valor distinto.
2. Recargar `/lecturas/registro-masivo?periodo=2026-08`.
3. **Resultado esperado**: el Total de la fila de Locación B sigue siendo el valor guardado en el
   Escenario 2, sin recalcularse con la nueva tarifa.

## Escenario 4 — El recibo usa el total guardado, no uno recalculado (US2, FR-006)

1. Con la lectura de Locación B ya guardada y la tarifa ya cambiada (Escenario 3), ir a generar el
   recibo del periodo para esa locación (specs/005).
2. **Resultado esperado**: el monto de luz sugerido en el formulario de recibo es exactamente el
   Total guardado de la lectura, no `consumo × tarifa nueva`.

## Escenario 5 — El total sobrevive al autoguardado (US2, research.md Decisión 4)

1. Completar la lectura actual y editar el total de una tercera locación, sin guardar el lote.
2. Esperar un ciclo de autoguardado (~120s, specs/015/016) y confirmar en la tabla
   `borradores_lectura_medidor` que la fila tiene el total editado.
3. Recargar la pantalla para el mismo periodo.
4. **Resultado esperado**: el campo de total se restaura con el valor editado, no con el sugerido
   recalculado desde cero.

## Regresión (specs/015-018, no debe romperse)

- Una fila ya completada sigue sin poder editar su Total (solo "Lectura Actual" tiene edición en
  línea, Q2).
- El registro individual de una lectura (`LecturaMedidorController`) sigue tratando "sin lectura
  anterior" como "sin dato" (null), no como 0.
- Las exportaciones a Excel/PDF y la columna "Consumo" (specs/017) siguen mostrando los mismos
  valores que antes, salvo la columna "Total" que ahora refleja el valor persistido.
