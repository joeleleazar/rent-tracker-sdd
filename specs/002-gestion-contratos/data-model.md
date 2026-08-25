# Data Model: Gestión de Contratos de Locación

**Feature**: `002-gestion-contratos` | **Date**: 2026-08-19

## Entidades

### Locacion (prerrequisito de la especificación 001, no modificada por esta feature)

Referenciada por `Contrato.locacion_id`. Se asume ya implementada según `specs/001-jerarquia-locaciones/spec.md`:

| Campo | Tipo | Notas |
|---|---|---|
| `id` | Entero, PK | |
| `nombre` | string | |
| `es_alquilable` | boolean | Solo locaciones con `es_alquilable = true` pueden tener contratos (Asunción A-003 de esta feature) |

### Inquilino (entidad mínima de soporte, ver `research.md` §6)

| Campo | Tipo | Reglas |
|---|---|---|
| `id` | Entero, PK, autoincremental | |
| `nombre` | string, obligatorio | Nombre/referencia del inquilino |
| `created_at` / `updated_at` | timestamps | |

### Contrato

Representa el acuerdo de arrendamiento de una locación para un periodo específico.

| Campo | Tipo | Reglas |
|---|---|---|
| `id` | Entero, PK, autoincremental | |
| `locacion_id` | Entero, FK → `locaciones.id` | Obligatorio; la locación referenciada debe tener `es_alquilable = true` (A-003) |
| `inquilino_id` | Entero, FK → `inquilinos.id` | Obligatorio |
| `fecha_inicio` | date | Obligatorio |
| `fecha_fin` | date | Obligatorio; MUST ser >= `fecha_inicio` |
| `monto_renta` | decimal(12,2) | Obligatorio; `NUMERIC` en PostgreSQL, cast `decimal:2` en Eloquent (Principio V) |
| `estado` | enum string: `borrador`, `activo`, `vencido`, `rescindido` | Obligatorio; por defecto `borrador` |
| `created_at` / `updated_at` | timestamps | |

**Validaciones de negocio** (FR-003, aplicadas en un Service dentro de `DB::transaction`, no solo en el Form Request):
- Ningún otro contrato de la misma `locacion_id` con estado distinto de `rescindido`/`cancelado` puede solaparse en rango de fechas: se rechaza si `fecha_inicio <= existente.fecha_fin AND fecha_fin >= existente.fecha_inicio`.
- Al insertar/actualizar, se bloquean (`lockForUpdate()`) los contratos existentes de esa `locacion_id` antes de evaluar el solapamiento, para evitar condiciones de carrera.

**Transiciones de estado** (dentro del alcance de 002; la edge case "Terminación Anticipada" de la especificación exige rescisión manual):
- `borrador` → `activo` → `vencido` (transición automática o manual fuera del alcance detallado aquí; ver especificaciones futuras de recibos/notificaciones)
- cualquier estado → `rescindido` (acción manual explícita del Administrador, libera el rango de fechas para nuevos contratos)

**Índices sugeridos**: `(locacion_id, fecha_inicio, fecha_fin)` para acelerar la verificación de solapamiento; `(locacion_id, estado)` para listados de historial.

### DocumentoContrato

Representa el archivo digital de respaldo (PDF o foto) adjunto a un contrato.

| Campo | Tipo | Reglas |
|---|---|---|
| `id` | Entero, PK, autoincremental | |
| `contrato_id` | Entero, FK → `contratos.id`, `onDelete('cascade')` | Obligatorio |
| `nombre_archivo` | string | Nombre original del archivo subido |
| `ruta_archivo` | string | Ruta relativa dentro de `storage/app/private/contratos/{contrato_id}/ (disco `local` por defecto)`; MUST no incluir el binario ni una URL pública directa |
| `tipo_archivo` | enum string: `pdf`, `imagen` | |
| `secuencia` | integer, por defecto 1 | Orden de las fotos de páginas cuando `tipo_archivo = imagen` |
| `created_at` / `updated_at` | timestamps | |

**Validaciones de negocio** (FR-004, en el Form Request + Service):
- Un contrato tiene exactamente **un** documento de `tipo_archivo = pdf` (máx. 15MB) **O** hasta 10 documentos de `tipo_archivo = imagen` (máx. 5MB cada uno) — no ambos tipos simultáneamente para el mismo contrato.
- Antes de eliminar un `DocumentoContrato`, la interfaz MUST solicitar confirmación explícita (FR-005); el borrado del archivo físico y del registro ocurre dentro de la misma transacción.

## Relaciones

```text
Locacion (1) ──< (N) Contrato
Inquilino (1) ──< (N) Contrato
Contrato (1) ──< (N) DocumentoContrato   [onDelete: cascade]
```

## Notas de migración

- `contratos.monto_renta`: `NUMERIC(12,2)` en PostgreSQL, nunca `FLOAT`/`DOUBLE` (Principio V).
- `contratos.estado`: columna `string` con `CHECK` a nivel de base de datos restringiendo los 4 valores válidos (evita depender de `ENUM` nativo de PostgreSQL, que dificulta migraciones futuras si se agregan estados).
- Todas las tablas y columnas en español, siguiendo el Principio II de la Constitución (`locaciones`, `inquilinos`, `contratos`, `documentos_contrato`, `locacion_id`, `fecha_inicio`, etc.).
