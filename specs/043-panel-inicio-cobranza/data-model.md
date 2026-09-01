# Data Model: Panel de Inicio — Estado de Cobranza

Esta feature **no persiste nada**: 0 migraciones, 0 columnas, 0 tablas. Todo se deriva en cada request de
entidades ya existentes. Este documento describe (1) las entidades de lectura que consume el panel y (2) las
entidades **derivadas** que el/los servicio(s) calculan en memoria.

## 1. Entidades de lectura (existentes, sin cambios)

| Entidad | Campos usados | Notas |
|---|---|---|
| `Recibo` | `estado`, `periodo` (date), `monto_renta` (decimal:2), `contrato_id`, `locacion_id` | `total()` = `monto_renta` + Σ `conceptos.monto`; `montoPagado()` = Σ `pagos.monto`; `saldoPendiente()` = `max(0, total − pagado)`; `scopeVigente()` = `estado != 'anulado'`. La consulta del panel usa `withSum` en vez de hidratar `conceptos`/`pagos`. |
| `ReciboConcepto` | `monto` | Solo como `withSum('conceptos','monto')`. |
| `Pago` | `monto` (decimal:2), `fecha_pago` (date), `recibo_id` | Como `withSum('pagos','monto')` para el saldo, y en consulta aparte por `fecha_pago` para "recaudado este mes". |
| `Contrato` | `estado` (`borrador`/`activo`/`vencido`/`rescindido`), `fecha_fin` (date), `locacion_id` | Panel usa `estado = 'activo'` para "por vencer"; `inquilinoPrincipal()` para la columna de inquilino. |
| `contrato_inquilino` / `Inquilino` | pivot `es_principal`, `Inquilino.nombre` (y afines) | Vía `Contrato::inquilinoPrincipal()` con `inquilinos` eager-cargada. |
| `Locacion` | `id`, `nombre`, `locacion_padre_id`, `tipo` | `rutaJerarquiaTruncada()` para el breadcrumb del local; `locacion_padre_id` para resolver la rama del filtro. |

## 2. Entidades derivadas (calculadas por `ServicioPanelCobranza`)

### 2.1 `FilaMoroso` (una por recibo moroso)

| Campo | Derivación |
|---|---|
| `recibo` | el modelo (para el enlace a `recibos.show`) |
| `inquilino` | `recibo.contrato.inquilinoPrincipal()?->nombre` — si es `null`, marcador visible ("—") |
| `locacion` | `recibo.locacion` (+ `rutaJerarquiaTruncada()`) |
| `periodo` | `recibo.periodo` |
| `montoTotal` | `monto_renta + suma_conceptos` |
| `montoPagado` | `suma_pagos` |
| `saldoPendiente` | `max(0, montoTotal − montoPagado)` — **> 0** por definición de moroso |
| `fechaLimite` | `ServicioCalculoFechaLimitePago->calcular(periodo)` |
| `diasDeAtraso` | `now()->startOfDay()->diffInDays(fechaLimite)` (entero ≥ 1) |
| `tramoAntiguedad` | `1..30 → '1-30'`, `31..60 → '31-60'`, `61..90 → '61-90'`, `>90 → '90+'` |

**Invariantes**:
- INV-M1: `FilaMoroso` existe ⟺ `estado != 'anulado'` ∧ `saldoPendiente > 0` ∧ `fechaLimite < now()->startOfDay()` (FR-005).
- INV-M2: `saldoPendiente` nunca negativo (FR-008).
- INV-M3: dos recibos morosos del mismo contrato → dos `FilaMoroso` distintas, nunca agregadas (FR-012).
- INV-M4: orden del listado = `diasDeAtraso` descendente (equiv. `fechaLimite` ascendente) (FR-010).

### 2.2 `ResumenMorosidad` (uno; se recalcula sobre el subconjunto filtrado)

| Campo | Derivación |
|---|---|
| `cantidadRecibos` | nº de `FilaMoroso` |
| `cantidadInquilinos` | nº de `inquilino` distintos (por identidad del inquilino principal) con ≥ 1 `FilaMoroso` |
| `montoAdeudadoVencido` | Σ `saldoPendiente` de las filas |
| `porTramo['1-30' | '31-60' | '61-90' | '90+']` | `{ cantidad, monto }` por tramo |

**Invariantes**:
- INV-R1: `Σ porTramo[*].monto == montoAdeudadoVencido` (SC-003).
- INV-R2: `Σ porTramo[*].cantidad == cantidadRecibos`.
- INV-R3: con filtros aplicados, `ResumenMorosidad` se calcula sobre las filas que pasan **ambos** filtros
  (tramo + rama); sin filtros, sobre todas las filas morosas del negocio (FR-022).

### 2.3 `FilaProximoVencimiento` (una por recibo en plazo con saldo)

Mismos campos que `FilaMoroso` salvo: `diasRestantes` (= `now()->startOfDay()->diffInDays(fechaLimite)`, ≥ 0)
en vez de `diasDeAtraso`, y sin `tramoAntiguedad`.

**Invariantes**:
- INV-P1: existe ⟺ `estado != 'anulado'` ∧ `saldoPendiente > 0` ∧ `fechaLimite >= now()->startOfDay()` (FR-023).
- INV-P2: un recibo es `FilaMoroso` **o** `FilaProximoVencimiento`, nunca ambos (partición por `fechaLimite`).
- INV-P3: orden = `fechaLimite` ascendente (FR-023).
- INV-P4: `ResumenProximos = { cantidad, montoTotal = Σ saldoPendiente }` (FR-025).

### 2.4 `IndicadoresCobranza` (uno)

| Campo | Derivación |
|---|---|
| `facturadoDelPeriodo` | Σ `montoTotal` de los recibos no anulados con `periodo` en el mes de `now()` |
| `cobradoDeRecibosDelPeriodo` | Σ `suma_pagos` de esos mismos recibos (cualquier `fecha_pago`) |
| `recaudadoEsteMes` | Σ `Pago.monto` con `fecha_pago` en el mes de `now()` y recibo no anulado (cualquier periodo) |
| `tasaDeCobranza` | `facturadoDelPeriodo > 0 ? cobradoDeRecibosDelPeriodo / facturadoDelPeriodo * 100 : null` |
| `carteraTotalPorCobrar` | Σ `saldoPendiente` (clamp por recibo) de **todos** los recibos no anulados |

**Invariantes**:
- INV-I1: `tasaDeCobranza == null` ⟹ la vista muestra "—" / "sin datos", nunca una división por cero (FR-030).
- INV-I2: `recaudadoEsteMes` es independiente de `cobradoDeRecibosDelPeriodo` y no interviene en `tasaDeCobranza` (Q1 = C).
- INV-I3: `carteraTotalPorCobrar = Σ max(0, total_i − pagado_i)`, nunca `Σtotal − Σpagos` global (FR-008).
- INV-I4: todos los cálculos excluyen `estado = 'anulado'`, incluido `recaudadoEsteMes` (FR-004).

### 2.5 `ContratoPorVencer` y sus tres grupos acumulativos

`ContratoPorVencer`: `{ contrato, fechaFin, diasRestantes = now()->startOfDay()->diffInDays(fechaFin) }`.

| Grupo | Contenido |
|---|---|
| `dentro7` | contratos `estado = 'activo'` con `fechaFin` ∈ `[hoy, hoy+7]` |
| `dentro15` | contratos `estado = 'activo'` con `fechaFin` ∈ `[hoy, hoy+15]` |
| `dentro30` | contratos `estado = 'activo'` con `fechaFin` ∈ `[hoy, hoy+30]` |

**Invariantes**:
- INV-C1: grupos **acumulativos** — `dentro7 ⊆ dentro15 ⊆ dentro30`; un contrato a 5 días aparece en los tres (Q2 = A, FR-032).
- INV-C2: contrato con `fechaFin < hoy` no aparece en ningún grupo (US3 AS7).
- INV-C3: contrato con `estado != 'activo'` no aparece en ningún grupo (FR-032).
- INV-C4: cada grupo es una **lista de contratos** con enlace a `contratos.show`, no solo un conteo (FR-032/FR-033).
- INV-C5: orden dentro de cada grupo = `fechaFin` ascendente.

## 3. Filtros (parámetros de lectura de la ruta)

| Parámetro (query string) | Valores | Efecto |
|---|---|---|
| `tramo` | vacío / `1-30` / `31-60` / `61-90` / `90+` | filtra `FilaMoroso` por `tramoAntiguedad`; recalcula `ResumenMorosidad` |
| `locacion` | vacío / id de locación | filtra `FilaMoroso` a recibos cuyo `locacion_id` ∈ `idsDeRama(locacion)` (locación + descendientes); recalcula `ResumenMorosidad` |

- Valores inválidos → se ignoran (se tratan como "sin filtro"), no producen error.
- Los filtros **solo** afectan al bloque de morosos (US1); no tocan próximos vencimientos ni indicadores.
- Ningún filtro modifica datos (FR-037).
