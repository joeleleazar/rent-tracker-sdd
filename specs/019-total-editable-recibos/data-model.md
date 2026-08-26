# Data Model: Lectura Anterior por Defecto y Total Editable y Persistido

**Feature**: `019-total-editable-recibos` | **Date**: 2026-08-25

## Cambios de esquema

### `lecturas_medidor` (columna nueva)

| Campo | Tipo | Notas |
|---|---|---|
| `total` | `decimal(12,2)`, `NOT NULL` (tras backfill) | Monto final de luz de esa lectura — el sugerido (consumo × tarifa vigente al guardar) o el editado a mano por el usuario (FR-003/FR-004). Se completa para todas las filas existentes al desplegar esta feature (FR-008, research.md Decisión 6) y para toda fila nueva en `store()`. Nunca se recalcula automáticamente después de guardado (FR-005) — ni por cambio de tarifa, ni por la edición en línea de "Lectura Actual" (`actualizarInline()`, que sigue sin tocar `total`). |

`consumo_calculado` (ya existente) no cambia — sigue calculándose y guardándose exactamente igual
que hoy (research.md Decisión 5).

### `borradores_lectura_medidor` (columna nueva)

| Campo | Tipo | Notas |
|---|---|---|
| `total` | `decimal(12,2)`, nullable | Espejo del borrador de `lectura_actual` ya existente — protege un total editado a mano antes de que el usuario guarde el lote (research.md Decisión 4). Sin regla de negocio: se acepta cualquier valor numérico intermedio, igual que `lectura_actual` en el borrador. |

## Regla de cálculo actualizada

- **Lectura anterior por defecto (FR-001, solo registro masivo)**: si no existe ninguna
  `LecturaMedidor` de la locación con `periodo` estrictamente anterior al periodo seleccionado, la
  lectura anterior usada para calcular consumo es `0`, no `null`. El registro individual de una
  lectura (`LecturaMedidorController`, `ServicioCalculoConsumoMedidor::sugerirLecturaAnterior()`)
  no cambia — sigue devolviendo `null` en ese caso (specs/006 FR-002).
- **Total sugerido**: `consumo × tarifa_vigente_al_guardar`, redondeado a 2 decimales — sin cambios
  respecto al cálculo que ya existía (solo pasa de ser efímero/no guardado a persistirse).
- **Monto de luz sugerido de un recibo** (`ServicioGeneracionReciboPeriodo::
  calcularMontoLuzSugerido()`): pasa a ser `lectura->total` directamente (0.0 si no hay lectura para
  ese periodo/locación), en vez de `lectura->consumo_calculado × tarifa_vigente_al_generar_el_recibo`.

## Relaciones

Sin cambios respecto a `specs/015-registro-masivo-lecturas/data-model.md` y
`specs/018-optimizacion-esquema-postgresql/data-model.md`:

```text
Locacion (1) ──< (N) LecturaMedidor           [sin cambios de relación; + columna total]
Locacion (1) ──< (N) BorradorLecturaMedidor   [sin cambios de relación; + columna total]
LecturaMedidor (1) ──< (N) Recibo             [ya existente vía recibos.lectura_medidor_id; monto_luz sugerido ahora deriva de LecturaMedidor.total]
```

## Reglas de validación

- `total` en `store()` (guardado final): numérico, ≥ 0 — mismo criterio ya usado para
  `lectura_actual` (FR-003 de specs/015), validado a mano por fila (no en `SolicitudGuardarRegistroMasivoLecturas`,
  por el mismo motivo ya documentado: una `FormRequest` abortaría todo el lote ante la primera fila
  inválida).
- `total` en `BorradorLecturaMedidor`: sin reglas de negocio (nullable, cualquier valor intermedio
  de tipeo), igual que `lectura_actual` en el borrador.
- Si el `total` recibido en `store()` no es numérico o falta (JS deshabilitado), el servidor lo
  recalcula como `consumo × tarifa_vigente` en vez de rechazar la fila (research.md Decisión 2) —
  no es un error de validación, es un valor por defecto.
