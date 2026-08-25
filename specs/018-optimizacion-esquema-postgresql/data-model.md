# Data Model: Optimización de Esquema y Consultas PostgreSQL

**Feature**: `018-optimizacion-esquema-postgresql` | **Date**: 2026-08-25

Este feature no introduce entidades de negocio nuevas (ver spec.md "Key Entities"). Documenta los cambios de forma sobre entidades ya existentes.

## Índices nuevos (sin cambio de forma, FR-004)

| Tabla | Columna | Tipo de índice | Razón |
|---|---|---|---|
| `documentos_contrato` | `contrato_id` | btree (default) | FK sin cobertura, usada por `Contrato::documentos()` |
| `recibos` | `contrato_id` | btree (default) | FK sin cobertura, usada por `Contrato::recibos()` |
| `recibos` | `lectura_medidor_id` | btree (default) | FK sin cobertura, usada por `Recibo::lecturaMedidor()` inverso |
| `contrato_inquilino` | `inquilino_id` | btree (default) | Solo cubierta como 2da columna de la unique `(contrato_id, inquilino_id)`; `Inquilino::contratos()` filtra por `inquilino_id` |
| `borradores_lectura_medidor` | `locacion_id` | btree (default) | Solo cubierta como 3ra columna de la unique `(usuario_id, periodo, locacion_id)` |
| `locaciones` | `locacion_padre_id` | btree (default) | FK autorreferencial sin cobertura, usada por `Locacion::locacionesHijas()` |

## Migración de zona horaria (FR-005, FR-006)

Todas las columnas `timestamp` (sin zona horaria) pasan a `timestamptz`, reinterpretando el valor existente como UTC (ver research.md R1). Columnas afectadas por tabla:

| Tabla | Columnas |
|---|---|
| Todas las tablas de dominio (9) | `created_at`, `updated_at` |
| `users` | `email_verified_at` |
| `contratos` | `notificado_30_dias_en`, `notificado_15_dias_en`, `notificado_7_dias_en`, `fecha_resolucion_garantia` |
| `recibos` | `fecha_pago`, `fecha_anulacion` |
| `lecturas_medidor` | `fecha_registro` |
| `configuracion_general` (nueva forma) | `alerta_pago_mes_enviada_en` (ahora dentro de `valor` jsonb — ver más abajo, no es una columna de tabla) |

Columnas `date` puras (`periodo`, `fecha_inicio`, `fecha_fin`, `fecha_entrega_garantia`, `fecha_nacimiento`, `fecha_emision`) **no** cambian de tipo — representan un día calendario de negocio, no un instante (ver Assumptions de spec.md).

## `configuracion_general` — de tabla ancha a clave-valor (FR-007)

**Forma anterior** (una fila, `id = 1`, una columna por parámetro):

| Columna | Tipo |
|---|---|
| `id` | bigint PK |
| `correo_notificaciones_vencimiento` | string |
| `tarifa_luz_por_unidad` | decimal(12,4) |
| `dias_anticipacion_alerta_pago` | unsignedInteger |
| `alerta_pago_mes_enviada_en` | timestamp |
| `created_at`, `updated_at` | timestamp |

**Forma nueva** (N filas, una por parámetro):

| Columna | Tipo | Restricciones |
|---|---|---|
| `id` | bigint PK | — |
| `clave` | varchar | `UNIQUE`, `NOT NULL` |
| `valor` | jsonb | `NOT NULL` |
| `created_at`, `updated_at` | timestamptz | — |

**Filas esperadas tras la migración de datos** (una por cada parámetro ya existente en la fila `id = 1` de la forma anterior, más los que se agreguen a futuro sin migración de esquema):

| `clave` | `valor` (JSON) | Corresponde a |
|---|---|---|
| `correo_notificaciones_vencimiento` | `"correo@ejemplo.com"` | `ConfiguracionGeneral::actual()->correo_notificaciones_vencimiento` |
| `tarifa_luz_por_unidad` | `0.1234` | `->tarifa_luz_por_unidad` (cast `decimal:4`) |
| `dias_anticipacion_alerta_pago` | `5` | `->dias_anticipacion_alerta_pago` (cast `integer`) |
| `alerta_pago_mes_enviada_en` | `"2026-08-01T00:00:00Z"` o `null` | `->alerta_pago_mes_enviada_en` (cast `datetime`) |

**Contrato del modelo `ConfiguracionGeneral`** (interfaz pública, ver `contracts/contrato-configuracion-general.md`): `actual()`, `->update([...])`, y el acceso a cada atributo por nombre se comportan exactamente igual que con la forma anterior — ver research.md R3 para el mecanismo interno.

## `periodo` — restricción de integridad nueva (FR-007b)

| Tabla | Columna | Restricción nueva |
|---|---|---|
| `lecturas_medidor` | `periodo` | `CHECK (EXTRACT(DAY FROM periodo) = 1)` |
| `recibos` | `periodo` | `CHECK (EXTRACT(DAY FROM periodo) = 1)` |
| `borradores_lectura_medidor` | `periodo` | `CHECK (EXTRACT(DAY FROM periodo) = 1)` |

Precondición de la migración (FR-010): 0 filas existentes con `EXTRACT(DAY FROM periodo) != 1` en las tres tablas (verificado antes de `ADD CONSTRAINT`; ver research.md R6).

## `inquilinos` — índices de búsqueda (FR-008)

| Columna | Tipo de índice nuevo |
|---|---|
| `dni` | GIN (`gin_trgm_ops`), adicional al `UNIQUE` btree ya existente |
| `apellidos` | GIN (`gin_trgm_ops`) |

Requiere `CREATE EXTENSION IF NOT EXISTS pg_trgm` a nivel de base de datos (una vez, ver research.md R5).
