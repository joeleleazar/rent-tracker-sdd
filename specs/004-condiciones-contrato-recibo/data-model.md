# Data Model: Condiciones del Contrato y Costos de Referencia para Recibos

**Feature**: `004-condiciones-contrato-recibo` | **Date**: 2026-08-20

## Entidades

### Contrato (de `specs/002-gestion-contratos`, se altera su esquema)

Columnas nuevas agregadas mediante migración de alteración; el resto del esquema (`locacion_id`, `inquilino_id`, `fecha_inicio`, `fecha_fin`, `monto_renta`, `estado`) no cambia.

| Campo nuevo | Tipo | Reglas |
|---|---|---|
| `costo_agua` | decimal(12,2), por defecto 0.00 | Obligatorio con default (FR-002) |
| `costo_luz` | decimal(12,2), por defecto 0.00 | Obligatorio con default |
| `costo_pasadizo` | decimal(12,2), por defecto 0.00 | Obligatorio con default |
| `costo_seguridad` | decimal(12,2), por defecto 0.00 | Obligatorio con default |
| `notificado_30_dias_en` | timestamp, nullable | Se reinicia a `null` si cambia `fecha_fin` (ver `research.md` §4) |
| `notificado_15_dias_en` | timestamp, nullable | Ídem |
| `notificado_7_dias_en` | timestamp, nullable | Ídem |

**Nueva relación**: `recibos(): HasMany` hacia `Recibo`.

### ConfiguracionGeneral (nueva, fila única)

| Campo | Tipo | Reglas |
|---|---|---|
| `id` | Entero, PK | Siempre `1` (patrón singleton, ver `research.md` §2) |
| `correo_notificaciones_vencimiento` | string (email), obligatorio | Editable por el Administrador; `email` + `required` en `SolicitudActualizarConfiguracionGeneral` |
| `created_at` / `updated_at` | timestamps | |

**Notas de extensibilidad** (ver `research.md` §2): `specs/005` agregará `tarifa_luz_por_unidad`; `specs/008` agregará `dias_anticipacion_alerta_pago` y `alerta_pago_mes_enviada_en`, ambas como columnas nuevas de esta misma tabla vía migraciones de alteración futuras, no tablas nuevas.

**Helper**: `ConfiguracionGeneral::actual(): self` — `firstOrCreate(['id' => 1], ['correo_notificaciones_vencimiento' => config('mail.from.address')])`.

### Recibo (nueva)

| Campo | Tipo | Reglas |
|---|---|---|
| `id` | Entero, PK, autoincremental | |
| `contrato_id` | Entero, FK → `contratos.id`, `restrictOnDelete()` | Obligatorio |
| `monto_renta` | decimal(12,2), obligatorio | Copia editable del valor de referencia al momento de emitir (FR-005/006/007) |
| `monto_agua` | decimal(12,2), por defecto 0.00 | Editable |
| `monto_luz` | decimal(12,2), por defecto 0.00 | Editable |
| `monto_pasadizo` | decimal(12,2), por defecto 0.00 | Editable |
| `monto_seguridad` | decimal(12,2), por defecto 0.00 | Editable |
| `periodo` | date, obligatorio | Se normaliza siempre al día 1 del mes/año facturado (ver nota de formato abajo); sin restricción de unicidad en esta feature (la introduce `specs/005` FR-009) |
| `fecha_emision` | date, obligatorio, por defecto hoy | |
| `created_at` / `updated_at` | timestamps | |

**Nota de formato de `periodo`** (decisión que rige también `specs/005`, `specs/006` y `specs/008`): se almacena como `DATE` de PostgreSQL con el día fijado en `01` (ej. "Agosto 2026" → `2026-08-01`), en vez de dos columnas separadas `mes`/`anio` o un string libre. Esto permite ordenar y comparar periodos con operadores de fecha nativos de PostgreSQL sin parseo adicional, y es consistente con `fecha_inicio`/`fecha_fin` de `Contrato` que ya usan `date`.

**Validaciones de negocio**:
- Al iniciar la generación de un recibo, el controlador precarga `monto_renta`/`costo_*` desde el `Contrato` asociado como valores iniciales del formulario (FR-005), pero el modelo `Recibo` no tiene ninguna referencia ni sincronización automática hacia esos valores después de guardado — son una copia independiente (FR-007, SC-003).
- Editar los costos de referencia de un `Contrato` después de emitidos uno o más `Recibo` NO afecta los recibos ya persistidos (Edge Case).

## Relaciones

```text
Contrato (1) ──< (N) Recibo   [contrato_id]
ConfiguracionGeneral           [fila única, sin relaciones]
```

## Notas de migración

- `contratos`: migración de alteración `ALTER TABLE contratos ADD COLUMN costo_agua NUMERIC(12,2) DEFAULT 0 NOT NULL, ...` (y las 3 columnas de notificación como `TIMESTAMP NULL`).
- `configuracion_general`: migración de creación + seeder/migración de datos que inserta la fila `id = 1` con un correo por defecto (ej. `config('mail.from.address')`) para que `ConfiguracionGeneral::actual()` nunca falle por ausencia de fila en un entorno recién desplegado.
- `recibos`: migración de creación nueva; índice simple en `(contrato_id, periodo)` (sin `UNIQUE` todavía, ver nota de `periodo` arriba y `specs/005` FR-009 donde se añade la restricción de unicidad definitiva).
