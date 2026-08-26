# Data Model: Consumo Calculado en el Momento en vez de Almacenado

**Feature**: `021-derivar-consumo-calculado` | **Date**: 2026-08-25

## Cambio de esquema

### `lecturas_medidor` (columna eliminada)

| Campo | Antes | Después |
|---|---|---|
| `consumo_calculado` | `decimal(12,2)` nullable, persistida, escrita por 4 puntos distintos del código | **Eliminada.** Reemplazada por un accessor de Eloquent (`LecturaMedidor::consumoCalculado()`) que calcula el mismo valor en el momento, a partir de `lectura_actual` y `lectura_anterior` de la misma fila. |

Sin backfill: el valor eliminado nunca dependió de ningún dato externo a la propia fila — se
deriva siempre de `lectura_actual` y `lectura_anterior`, que ya existen. `total` (specs/019) no se
ve afectado: sigue siendo la única columna persistida para efectos de facturación.

## Regla de cálculo del accessor

`consumo_calculado = lectura_actual − (lectura_anterior ?? 0)`, redondeado a 2 decimales,
devuelto como string (mismo formato que el cast `decimal:2` que reemplaza) — **sin excepción por
pantalla ni por vía de creación** (decisión confirmada Q1 de spec.md): el mismo criterio de "sin
lectura anterior → 0" que specs/019 FR-001 aplicaba solo al registro masivo pasa a regir también
el flujo individual (specs/006).

Este cálculo no afecta ni lee `lectura_anterior` de ninguna otra fila — es puro, en memoria, sobre
los dos campos ya cargados con el propio modelo (sin consultas adicionales, sin relaciones).

## Relaciones

Sin cambios respecto a `specs/019-total-editable-recibos/data-model.md`:

```text
Locacion (1) ──< (N) LecturaMedidor           [sin cambios de relación; − columna consumo_calculado]
LecturaMedidor (1) ──< (N) Recibo             [sin cambios — el monto de luz sigue derivando de LecturaMedidor.total, no de consumo_calculado]
```

## Reglas de validación

Sin cambios: el accessor no valida nada nuevo — `lectura_actual` y `lectura_anterior` ya se validan
donde siempre se validaron (specs/006/015/019); el consumo derivado hereda esa validez sin agregar
una capa propia.
