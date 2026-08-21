# Data Model: Lecturas de Medidor de Luz y Recibo por Periodo

**Feature**: `005-lecturas-medidor-recibo-periodo` | **Date**: 2026-08-20

## Entidades

### LecturaMedidor (nueva)

| Campo | Tipo | Reglas |
|---|---|---|
| `id` | Entero, PK, autoincremental | |
| `locacion_id` | Entero, FK → `locaciones.id`, `restrictOnDelete()` | Obligatorio |
| `periodo` | date (día 1 del mes, ver `specs/004/data-model.md` nota de formato) | Obligatorio; `UNIQUE` compuesto con `locacion_id` (FR-003) |
| `lectura` | decimal(12,2), obligatorio | Valor acumulado del medidor (refinado por `specs/006` en `lectura_anterior`/`lectura_actual`, ver `research.md` §4) |
| `consumo_calculado` | decimal(12,2), nullable | Calculado por `ServicioCalculoConsumoMedidor`; `null` si no hay lectura previa ("sin dato anterior") |
| `fecha_registro` | timestamp, obligatorio, por defecto ahora | |
| `created_at` / `updated_at` | timestamps | |

**Validaciones de negocio**:
- FR-003: no se permite una segunda `LecturaMedidor` para la misma `(locacion_id, periodo)`; el controlador ofrece editar la existente en su lugar.
- Edge Case: si `lectura - lectura_anterior.lectura < 0`, se exige confirmación explícita antes de guardar (no bloqueo permanente).

### Recibo (extensión de `specs/004-condiciones-contrato-recibo`)

Columnas nuevas agregadas mediante migración de alteración sobre la tabla `recibos` ya existente (ver `research.md` §1 y §3 para la justificación de la reubicación locación-céntrica y de los campos):

| Campo nuevo | Tipo | Reglas |
|---|---|---|
| `locacion_id` | Entero, FK → `locaciones.id`, `restrictOnDelete()` | Obligatorio; backfill desde `contrato.locacion_id` para filas de 004 ya existentes; `UNIQUE` compuesto con `periodo` (FR-009) |
| `lectura_medidor_id` | Entero, FK → `lecturas_medidor.id`, `nullOnDelete()`, nullable | Usado para calcular el monto de luz sugerido (FR-006) |
| `incluye_alquiler` | boolean, por defecto `true` | `true` por defecto para preservar la semántica de los recibos ya emitidos en 004 |
| `incluye_luz` | boolean, por defecto `true` | Ídem |
| `incluye_agua` | boolean, por defecto `true` | Ídem |
| `incluye_seguridad` | boolean, por defecto `true` | Ídem |
| `incluye_pasadizo` | boolean, por defecto `true` | Ídem |

**Nueva restricción**: índice único `(locacion_id, periodo)` sustituye/complementa al índice simple `(contrato_id, periodo)` ya existente de 004 (FR-009).

### ConfiguracionGeneral (extensión de `specs/004`)

| Campo nuevo | Tipo | Reglas |
|---|---|---|
| `tarifa_luz_por_unidad` | decimal(12,4), obligatorio, editable | Aplicado al `consumo_calculado` vigente al momento de generar cada recibo (FR-006/FR-007); ver `research.md` §5 sobre la precisión |

### Contrato (de `specs/002`, sin cambios de esquema)

Se agrega únicamente el helper `Locacion::contratoActivoEnPeriodo(Carbon $periodo): ?Contrato` (ver `research.md` §2); no se altera la tabla `contratos`.

## Relaciones

```text
Locacion (1) ──< (N) LecturaMedidor        [locacion_id]
LecturaMedidor (1) ──< (0..N) Recibo        [lectura_medidor_id, opcional]
Locacion (1) ──< (N) Recibo                 [locacion_id, nuevo — de specs/005]
Contrato (1) ──< (N) Recibo                 [contrato_id, de specs/004, se conserva]
```

## Notas de migración

- `lecturas_medidor`: migración de creación nueva; índice único compuesto `(locacion_id, periodo)`.
- `recibos`: migración de alteración que agrega las columnas de esta sección sobre la tabla creada en `specs/004`; el backfill de `locacion_id` para recibos ya emitidos bajo 004 se resuelve con `UPDATE recibos SET locacion_id = (SELECT locacion_id FROM contratos WHERE contratos.id = recibos.contrato_id)` dentro de la propia migración.
- `configuracion_general`: migración de alteración que agrega `tarifa_luz_por_unidad` con un valor por defecto razonable (ej. `0.0000`, a configurar por el Administrador antes del primer uso real).
