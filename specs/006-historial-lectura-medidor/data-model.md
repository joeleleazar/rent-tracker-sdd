# Data Model: Traslado Editable de Lectura Anterior e Historial de Medidor

**Feature**: `006-historial-lectura-medidor` | **Date**: 2026-08-20

## Entidades

### LecturaMedidor (refinamiento de `specs/005-lecturas-medidor-recibo-periodo`)

| Campo | Tipo | Reglas |
|---|---|---|
| `id` | Entero, PK, autoincremental | Ya existe |
| `locacion_id` | Entero, FK → `locaciones.id` | Ya existe |
| `periodo` | date | Ya existe; `UNIQUE` compuesto con `locacion_id` |
| `lectura_anterior` | decimal(12,2), nullable, editable | **Nuevo** (reemplaza el uso implícito de la fila previa de `specs/005`); autocompletado con `lectura_actual` del periodo cronológicamente más reciente ya registrado de la misma locación (`ServicioCalculoConsumoMedidor::sugerirLecturaAnterior()`), o `null`/vacío si no existe periodo previo (FR-002) |
| `lectura_actual` | decimal(12,2), obligatorio | **Renombrado** desde `lectura` (de `specs/005`) |
| `consumo_calculado` | decimal(12,2), nullable | Recalculado como `lectura_actual - lectura_anterior`; `null` si `lectura_anterior` es `null` |
| `fecha_registro` | timestamp, obligatorio | Ya existe |
| `created_at` / `updated_at` | timestamps | Ya existe |

**Comportamiento nuevo**:
- `discrepanciaConSiguiente(): bool` — compara `lectura_actual` de este registro contra `lectura_anterior` del periodo siguiente de la misma locación (ver `research.md` §3); usado solo para mostrar advertencia en el historial, no almacenado.

**Validaciones de negocio**:
- FR-003: el administrador puede editar libremente el valor autocompletado de `lectura_anterior` antes de guardar; no se exige que coincida con el valor trasladado.
- FR-006: una vez guardado un periodo, editar su `lectura_anterior` NO modifica el periodo previo del cual se trasladó el valor (registros independientes tras el guardado).
- FR-007: la discrepancia entre el `lectura_actual` de un periodo y el `lectura_anterior` del periodo siguiente se advierte, pero no bloquea ningún guardado.

## Relaciones

Sin cambios respecto a `specs/005-lecturas-medidor-recibo-periodo/data-model.md` (mismas relaciones con `Locacion` y `Recibo`).

## Notas de migración

- Migración de alteración sobre `lecturas_medidor` (de `specs/005`): `renameColumn('lectura', 'lectura_actual')`, `addColumn lectura_anterior decimal(12,2) nullable`, seguida de una migración de datos que recorre cada `locacion_id` en orden de `periodo` y asigna `lectura_anterior` = `lectura_actual` de la fila cronológicamente previa (o `null` si no existe), recalculando `consumo_calculado` en el mismo paso (ver `research.md` §1, Asunción A-002 de la especificación).
- No se requiere ninguna migración adicional sobre `recibos` ni `configuracion_general`; esta feature es un refinamiento acotado a `lecturas_medidor`.
