# Data Model: Representantes de Contrato

**Feature**: `003-representantes-contrato` | **Date**: 2026-08-20

## Entidades

### Representante (directorio global, nuevo)

| Campo | Tipo | Reglas |
|---|---|---|
| `id` | Entero, PK, autoincremental | |
| `apellidos` | string, obligatorio | |
| `nombres` | string, obligatorio | |
| `dni` | string, `UNIQUE`, obligatorio | Formato: 8 dígitos numéricos (ver `research.md` §4; ajustar si el usuario aclara otro formato) |
| `fecha_nacimiento` | date, obligatorio | MUST resultar en edad ≥ 18 años al momento de guardar (Asunción A-001) |
| `created_at` / `updated_at` | timestamps | |

### Contrato (de `specs/002-gestion-contratos`, no modificada en su esquema)

Se agrega únicamente la relación `belongsToMany` hacia `Representante`; `inquilino_id` se mantiene sin cambios (ver `research.md` §1).

| Campo relevante | Notas |
|---|---|
| `inquilino_id` | Sin cambios; coexiste con los representantes |

### ContratoRepresentante (tabla pivote, nueva)

| Campo | Tipo | Reglas |
|---|---|---|
| `contrato_id` | Entero, FK → `contratos.id`, `onDelete('cascade')` | Obligatorio |
| `representante_id` | Entero, FK → `representantes.id`, `restrictOnDelete()` | Obligatorio; no se permite borrar un `Representante` mientras esté asociado a algún contrato |
| `es_principal` | boolean, por defecto `false` | Exactamente uno debe ser `true` por `contrato_id` (validado en `ServicioAsociacionRepresentantesContrato`, no como `CHECK` de base de datos por no ser expresable de forma portátil a nivel de fila) |
| `created_at` / `updated_at` | timestamps | |

**Clave única compuesta**: `(contrato_id, representante_id)` para impedir asociar dos veces al mismo representante en un mismo contrato.

**Validaciones de negocio** (FR-003, FR-004, en `ServicioAsociacionRepresentantesContrato` dentro de `DB::transaction`):
- Un contrato MUST tener como mínimo un `Representante` asociado antes de guardarse o pasar a estado `activo`.
- Si un contrato tiene más de un representante, exactamente uno MUST tener `es_principal = true`.
- No se permite remover un representante si es el único asociado al contrato (Edge Case "Eliminación del Último Representante").

## Relaciones

```text
Representante (1) ──< (N) ContratoRepresentante >── (1) Contrato   [pivote muchos-a-muchos]
Inquilino (1) ──< (N) Contrato   [sin cambios, de specs/002]
```

## Notas de migración

- `representantes.dni`: índice `UNIQUE`, tipo `string` (no `integer`, para preservar ceros a la izquierda si el formato de documento lo requiere).
- `contrato_representante`: tabla pivote explícita (no la convención implícita `belongsToMany` sin modelo), dado que tiene una columna adicional (`es_principal`) más allá de las dos claves foráneas — se define como migración propia con índice único compuesto `(contrato_id, representante_id)`.
- Todas las tablas y columnas en español (Principio II): `representantes`, `contrato_representante`, `apellidos`, `nombres`, `dni`, `fecha_nacimiento`, `es_principal`.
