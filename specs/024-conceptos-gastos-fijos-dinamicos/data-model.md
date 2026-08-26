# Data Model: Catálogo Dinámico de Conceptos de Gastos Fijos, Periodo Ágil y Totales por Locación

## ConceptoGastoFijo (nuevo)

Tabla `conceptos_gasto_fijo`.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `nombre` | string | editable libremente por el administrador (FR-001) |
| `clave` | string, nullable, unique | `'renta'` / `'luz'` para los 2 conceptos especiales; `null` para el resto (research.md Decisión 1) — nunca editable desde la UI |
| `orden` | integer | orden de aparición en toda pantalla que liste conceptos |
| `activo` | boolean, default true | un concepto inactivo no se ofrece para contratos/recibos nuevos (FR-001), pero los ya existentes que lo usan no se ven afectados |
| `created_at`/`updated_at` | timestamptz | |

**Reglas de negocio**:
- `clave = 'renta'` no puede desactivarse ni eliminarse (FR-002).
- Ningún concepto puede eliminarse (DELETE) si existe al menos una fila que lo referencie en
  `contrato_valores_concepto` o `recibo_conceptos` (FR-003) — la acción disponible en ese caso es
  desactivar, no eliminar.
- Sembrado inicial (dentro de la propia migración, research.md Decisión 4): Renta (orden 1, clave `renta`),
  Agua (orden 2), Luz (orden 3, clave `luz`), Luz de Pasadizo (orden 4), Seguridad (orden 5).

## ValorConceptoContrato (nuevo)

Tabla `contrato_valores_concepto` — valor de referencia de un concepto para un contrato específico
(reemplaza `costo_agua`/`costo_luz`/`costo_pasadizo`/`costo_seguridad` de `contratos`).

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `contrato_id` | FK → `contratos`, cascade on delete | |
| `concepto_gasto_fijo_id` | FK → `conceptos_gasto_fijo`, restrict on delete | nunca apunta a un concepto con `clave` no nula (Renta/Luz) — validado en la capa de aplicación, no en la BD |
| `valor` | decimal(12,2) | |
| `created_at`/`updated_at` | timestamptz | |

`unique(contrato_id, concepto_gasto_fijo_id)` — un contrato no puede tener dos valores para el mismo
concepto.

`Contrato::valoresConceptos()` — `hasMany(ValorConceptoContrato::class)`.
`Contrato::valorDeConcepto(ConceptoGastoFijo $concepto): ?float` — helper de conveniencia, `null` si no
está configurado.

## ReciboConcepto (nuevo)

Tabla `recibo_conceptos` — un concepto efectivamente incluido en un recibo, con su monto
(reemplaza `incluye_agua`/`incluye_luz`/`incluye_pasadizo`/`incluye_seguridad`/`monto_agua`/`monto_luz`/
`monto_pasadizo`/`monto_seguridad` de `recibos`; `incluye_alquiler` deja de existir porque un recibo con
"Renta" simplemente tiene `monto_renta` no nulo — ver más abajo).

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `recibo_id` | FK → `recibos`, cascade on delete | |
| `concepto_gasto_fijo_id` | FK → `conceptos_gasto_fijo`, restrict on delete | nunca apunta a "Renta" (`clave='renta'`) — Renta se representa con `recibos.monto_renta`, no con una fila aquí |
| `monto` | decimal(12,2) | |
| `created_at`/`updated_at` | timestamptz | |

`unique(recibo_id, concepto_gasto_fijo_id)` — un recibo no puede repetir el mismo concepto (consistente con
la regla de no-superposición de specs/023, que ahora aplica sobre esta tabla en vez de sobre 5 columnas
booleanas fijas).

`Recibo::conceptos()` — `hasMany(ReciboConcepto::class)`.

## Recibo (existente, modificado)

| Campo | Cambio |
|---|---|
| `monto_renta` | se conserva (research.md Decisión 2) — sigue siendo el monto de renta de ese recibo, con su prorrateo ya calculado; `null` si ese recibo no incluye "Renta" |
| `incluye_alquiler` | eliminado — "incluye Renta" pasa a ser `monto_renta !== null` |
| `incluye_agua`/`incluye_luz`/`incluye_pasadizo`/`incluye_seguridad` | eliminados — "incluye X" pasa a ser "existe una fila en `conceptos()` para el concepto X" |
| `monto_agua`/`monto_luz`/`monto_pasadizo`/`monto_seguridad` | eliminados — se leen desde `conceptos()` |

`Recibo::total()` se reescribe: `(float) $this->monto_renta + $this->conceptos->sum('monto')` (con
`monto_renta` tratado como 0 si es `null`).

## Contrato (existente, modificado)

| Campo | Cambio |
|---|---|
| `costo_agua`/`costo_luz`/`costo_pasadizo`/`costo_seguridad` | eliminados (research.md Decisión 2; `costo_luz` se descarta sin backfill, Decisión 5) |
| `monto_renta` | sin cambio — sigue siendo el campo base de renta, con su prorrateo ya existente (specs/008) |

## No-superposición de conceptos (specs/023, adaptada)

`ServicioGeneracionReciboPeriodo::conceptosDisponibles(Locacion, Carbon)` deja de operar sobre un array fijo
de 5 claves (`self::CONCEPTOS`) y pasa a operar sobre `ConceptoGastoFijo::activos()->ordenados()->get()` —
un concepto está disponible para una locación y periodo si está activo y ningún `ReciboConcepto` existente de
esa locación/periodo lo referencia (más el caso especial de "Renta", disponible si ningún recibo de esa
locación/periodo tiene `monto_renta` no nulo).

## Total y cantidad de recibos por locación (Historia 4)

Concepto derivado, no persistido — por cada locación y periodo, sobre la misma colección de recibos ya
agrupada en `RegistroMasivoRecibosController::datosDelPeriodo()` (excluyendo `estado = 'anulado'`):
`cantidadRecibos = recibos->count()`, `totalFacturado = recibos->sum(fn ($r) => $r->total())`.
