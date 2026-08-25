# Data Model: Inquilinos de Contrato (Inquilino Principal)

**Feature**: `003-representantes-contrato` | **Date**: 2026-08-20 | **Revisado**: 2026-08-23

## Entidades

### Inquilino (extendida; ya existente desde `specs/002-gestion-contratos`)

| Campo | Tipo | Reglas |
|---|---|---|
| `id` | Entero, PK, autoincremental | Ya existente |
| `apellidos` | string, obligatorio | **Nuevo** (antes vivía en `Representante`) |
| `nombres` | string, obligatorio | **Nuevo** (reemplaza al campo `nombre` original de `Inquilino`; ver Notas de migración) |
| `dni` | string, `UNIQUE`, obligatorio | **Nuevo**. Formato: 8 dígitos numéricos (ver `research.md` §4; ajustar si el usuario aclara otro formato) |
| `fecha_nacimiento` | date, obligatorio | **Nuevo**. MUST resultar en edad ≥ 18 años al momento de guardar (Asunción A-001) |
| `created_at` / `updated_at` | timestamps | Ya existente |

### Contrato (de `specs/002-gestion-contratos`, esquema modificado)

| Campo | Notas |
|---|---|
| `inquilino_id` | **Eliminado** en esta feature (ver `research.md` §1). Reemplazado por la fila del pivote `contrato_inquilino` con `es_principal = true`. |

Relación Eloquent: `belongsTo(Inquilino::class)` se reemplaza por `belongsToMany(Inquilino::class)->using(...)->withPivot('es_principal')`, más un accesor `inquilinoPrincipal()` que retorna el inquilino cuya fila de pivote tiene `es_principal = true`.

### ContratoInquilino (tabla pivote; reemplaza a `contrato_representante`)

| Campo | Tipo | Reglas |
|---|---|---|
| `contrato_id` | Entero, FK → `contratos.id`, `onDelete('cascade')` | Obligatorio |
| `inquilino_id` | Entero, FK → `inquilinos.id`, `restrictOnDelete()` | Obligatorio; no se permite borrar un `Inquilino` mientras esté asociado a algún contrato |
| `es_principal` | boolean, por defecto `false` | Exactamente uno debe ser `true` por `contrato_id` (validado en `ServicioAsociacionInquilinosContrato`, no como `CHECK` de base de datos por no ser expresable de forma portátil a nivel de fila) |
| `created_at` / `updated_at` | timestamps | |

**Clave única compuesta**: `(contrato_id, inquilino_id)` para impedir asociar dos veces al mismo inquilino en un mismo contrato.

**Validaciones de negocio** (FR-003, FR-004, FR-009, en `ServicioAsociacionInquilinosContrato` dentro de `DB::transaction`):
- Un contrato MUST tener como mínimo un `Inquilino` asociado antes de guardarse o pasar a estado `activo`.
- Si un contrato tiene más de un inquilino, exactamente uno MUST tener `es_principal = true`.
- No se permite remover un inquilino si es el único asociado al contrato (Edge Case "Eliminación del Último Inquilino").
- No se permite remover al inquilino Principal si existen otros inquilinos, salvo que se designe simultáneamente un nuevo Principal (Edge Case "Eliminación del Inquilino Principal cuando hay otros", FR-009).

## Relaciones

```text
Inquilino (1) ──< (N) ContratoInquilino >── (1) Contrato   [pivote muchos-a-muchos, es_principal]
```

`Inquilino (1) ──< (N) Contrato` vía `inquilino_id` (relación de `specs/002`) **se elimina** y queda reemplazada por la relación de arriba.

## Entidades eliminadas por esta corrección

- `Representante` (modelo y tabla `representantes`): sus columnas (`apellidos`, `nombres`, `dni`, `fecha_nacimiento`) migran a `Inquilino`.
- `ContratoRepresentante` (tabla pivote `contrato_representante`): su estructura y reglas migran a `ContratoInquilino`.

## Notas de migración

- `inquilinos.dni`: índice `UNIQUE`, tipo `string` (no `integer`, para preservar ceros a la izquierda si el formato de documento lo requiere).
- El campo `nombre` original de `Inquilino` (feature 002) se reemplaza por `apellidos` + `nombres`; los registros existentes deben migrarse (ver estrategia en `research.md` §1) antes de eliminar la columna `nombre`.
- `contrato_inquilino`: tabla pivote explícita (no la convención implícita `belongsToMany` sin modelo), dado que tiene una columna adicional (`es_principal`) más allá de las dos claves foráneas — se define como migración propia con índice único compuesto `(contrato_id, inquilino_id)`.
- La consolidación desde `representantes`/`contrato_representante`/`contratos.inquilino_id` hacia el modelo unificado se ejecuta con migraciones nuevas y aditivas, nunca editando migraciones ya aplicadas (Principio I); ver el detalle de pasos en `research.md` §1.
- Todas las tablas y columnas en español (Principio II): `inquilinos`, `contrato_inquilino`, `apellidos`, `nombres`, `dni`, `fecha_nacimiento`, `es_principal`.
