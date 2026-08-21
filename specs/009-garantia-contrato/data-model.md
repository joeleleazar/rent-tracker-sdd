# Data Model: Registro de Garantía Entregada por Contrato

**Feature**: `009-garantia-contrato` | **Date**: 2026-08-20

## Entidades

### Contrato (extensión de `specs/002-gestion-contratos`, ya extendida por `specs/004`)

Columnas nuevas agregadas mediante migración de alteración:

| Campo nuevo | Tipo | Reglas |
|---|---|---|
| `monto_garantia` | decimal(12,2), nullable, por defecto `null`/`0.00` | Opcional (FR-001); `0.00` se trata como "sin garantía" para efectos de visualización (Edge Case) |
| `fecha_entrega_garantia` | date, nullable | Obligatoria solo si `monto_garantia > 0` (FR-002), validado en `SolicitudGuardarContrato` |
| `medio_entrega_garantia` | string/enum (`efectivo`, `transferencia`, `cheque`), nullable | Opcional (FR-003) |
| `estado_garantia` | string/enum (`entregada`, `resuelta`), nullable | `entregada` por defecto cuando `monto_garantia > 0`; `resuelta` tras registrar la resolución (FR-009) |
| `monto_devuelto_garantia` | decimal(12,2), nullable | `null` hasta que se registre la resolución |
| `monto_retenido_garantia` | decimal(12,2), nullable, por defecto `0.00` | `null`/`0.00` hasta que se registre la resolución |
| `motivo_retencion_garantia` | text, nullable | Obligatorio solo si `monto_retenido_garantia > 0` (FR-007) |
| `fecha_resolucion_garantia` | timestamp, nullable | Se asigna al registrar la resolución (FR-009) |

**Comportamiento nuevo**:
- Helper `tieneGarantia(): bool` — `$this->monto_garantia !== null && $this->monto_garantia > 0` (Edge Case "Garantía con monto igual a cero").
- Helper `garantiaResuelta(): bool` — `$this->estado_garantia === 'resuelta'`.

**Validaciones de negocio** (en `ServicioResolucionGarantiaContrato`, dentro de `DB::transaction`, ver `research.md` §2-3):
- FR-006/Edge Case: la opción de registrar una resolución solo se ofrece si `tieneGarantia()` es verdadero.
- FR-007: `motivo_retencion_garantia` obligatorio si `monto_retenido_garantia > 0`.
- FR-008: `monto_devuelto_garantia + monto_retenido_garantia` MUST ser exactamente igual a `monto_garantia` (comparación con `bccomp`, no `==` sobre floats).
- FR-010: corregir una resolución ya marcada `resuelta` exige confirmación explícita (`confirmado = true`).

## Relaciones

Sin relaciones nuevas; todos los campos son propiedades directas de `Contrato` (de `specs/002`).

## Notas de migración

- Migración de alteración sobre `contratos`: `monto_garantia`/`monto_devuelto_garantia`/`monto_retenido_garantia` como `decimal(12,2)` nullable; `fecha_entrega_garantia` como `date` nullable; `medio_entrega_garantia` como `enum(['efectivo','transferencia','cheque'])` nullable; `estado_garantia` como `enum(['entregada','resuelta'])` nullable; `motivo_retencion_garantia` como `text` nullable; `fecha_resolucion_garantia` como `timestamp` nullable.
- Los contratos ya existentes (de `specs/002`/`004`) quedan con todos estos campos en `null`, interpretado correctamente por la aplicación como "Sin garantía registrada" (FR-004, sin necesidad de una migración de datos adicional).
