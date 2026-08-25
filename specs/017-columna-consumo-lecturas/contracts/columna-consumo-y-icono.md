# Contrato de Interfaz: Columna "Consumo" e Ícono de Completado

**Feature**: `017-columna-consumo-lecturas` | **Date**: 2026-08-25

No hay rutas ni endpoints nuevos — esta feature es puramente de presentación sobre
`GET lecturas.registroMasivo.index` (ya documentada en
`specs/015-registro-masivo-lecturas/contracts/rutas-registro-masivo-lecturas.md`). Este documento
fija el contrato de marcado (HTML/DOM) que la vista y el JavaScript asociado DEBEN cumplir.

## Contrato 1 — Encabezado y celda de "Consumo"

- `index.blade.php` DEBE declarar una cuarta celda de encabezado con el texto `"Consumo"`, ubicada
  entre "Lectura Actual" y "Total".
- Cada fila de locación alquilable en `fila-registro-masivo.blade.php` DEBE incluir un elemento
  `<div id="consumo-fila-{locacion_id}">` ubicado en esa misma posición (entre la celda de
  `campo-lectura-registro-masivo` y la celda `#total-fila-{locacion_id}`).
- Contenido de esa celda:
  - `"—"` en el marcado inicial (server-rendered), igual que `#total-fila-{locacion_id}` hoy.
  - Tras la ejecución de `recalcularTotales()` (carga inicial o cualquier `htmx:afterSettle`):
    - El valor de `consumo_calculado` con 2 decimales, si la fila ya tiene lectura guardada.
    - El resultado en vivo de `lectura_actual (input) − lectura_anterior`, con 2 decimales, si hay
      un valor tipeado y una lectura anterior disponible.
    - `"—"`, si no hay lectura anterior disponible o no hay valor tipeado/guardado todavía.
- Fila de total general (`.tabla-registro-masivo__total-general`) DEBE agregar una celda vacía
  adicional en esa misma posición, para conservar la alineación de columnas con el encabezado y las
  filas.
- Locaciones no alquilables (solo encabezado organizativo del árbol) DEBEN agregar una celda vacía
  adicional en esa posición, por la misma razón.

## Contrato 2 — Orden del ícono de lectura completada

En `campo-lectura-registro-masivo.blade.php`, para una fila con `$lecturaDelPeriodo !== null &&
! $modoEdicion`:

- El elemento `<button>` con el ícono `bi-check-circle-fill` DEBE preceder, en el DOM, al
  `<span class="cifra">` con el valor de la lectura — no al revés.
- El `aria-label`, el `title` del tooltip y el comportamiento `hx-get` de edición en línea del
  botón NO cambian de valor ni de elemento; solo cambia su posición relativa al `<span>` hermano.
- El modo edición en línea (`$modoEdicion === true`) no se ve afectado por este contrato — conserva
  su marcado actual (campo editable + botones guardar/cancelar) sin cambios de orden.
