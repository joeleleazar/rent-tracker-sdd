# Data Model: Jerarquía de Locaciones Alquilables

**Feature**: `001-jerarquia-locaciones` | **Date**: 2026-08-20

## Entidades

### Locacion

Representa cualquier espacio físico gestionable en el sistema (contenedor organizativo o unidad de alquiler final). La tabla y el modelo **ya existen** (creados como prerrequisito mínimo de `specs/002-gestion-contratos`); esta feature añade la lógica de negocio (ciclos, truncamiento, bloqueo de borrado) sin alterar el esquema.

| Campo | Tipo | Reglas |
|---|---|---|
| `id` | Entero, PK, autoincremental | Ya existe |
| `nombre` | string, obligatorio | Ya existe |
| `tamano` | decimal(10,2) | Ya existe; `NUMERIC` en PostgreSQL, cast `decimal:2` en Eloquent (Principio V) |
| `ubicacion_fisica` | text, obligatorio | Ya existe |
| `descripcion` | text, obligatorio | Ya existe |
| `locacion_padre_id` | Entero, FK → `locaciones.id`, nullable, `nullOnDelete()` | Ya existe; ver `research.md` §3 sobre el bloqueo de eliminación en la capa de aplicación |
| `es_alquilable` | boolean, por defecto `false` | Ya existe |
| `created_at` / `updated_at` | timestamps | Ya existe |

**Relaciones** (ya definidas en `app/Models/Locacion.php`):
- `locacionPadre(): BelongsTo` — la locación contenedora directa.
- `locacionesHijas(): HasMany` — las sub-locaciones directas.
- `contratos(): HasMany` — de `specs/002-gestion-contratos`.

**Nuevo comportamiento a añadir por esta feature**:

- **Scope `alquilables`**: `Locacion::alquilables()` filtra `es_alquilable = true` (FR-005).
- **Helper `ancestros(): array`**: recorre `locacionPadre` iterativamente hasta la raíz (o hasta 1,000 saltos, límite de seguridad), devolviendo la cadena completa de ancestros para uso interno del servicio de validación de ciclos.
- **Helper `rutaJerarquiaTruncada(): array`**: devuelve los últimos 3 niveles de la cadena completa (incluyendo la propia locación), con un indicador de omisión si la cadena original supera 3 niveles (FR-004).

**Validaciones de negocio** (aplicadas en `ServicioValidacionJerarquiaLocacion` dentro de `DB::transaction`, no solo en el Form Request):
- FR-003 / US3: al crear o editar, si `locacion_padre_id` se establece, MUST verificarse que la locación propuesta como padre no sea la propia locación ni ninguno de sus descendientes (lo que crearía un ciclo). Se rechaza con un mensaje explícito si se detecta.
- FR-007 / Edge Case "Locaciones Huérfanas": antes de eliminar, MUST verificarse `locacionesHijas()->exists() === false`. Si tiene hijas, se bloquea la eliminación con un mensaje explícito y persistente.
- Edge Case "Cambio de Atributo Alquilable": si `es_alquilable` pasa de `true` a `false` y la locación tiene contratos asociados (activos o históricos, vía `contratos()`), MUST bloquearse el cambio o requerir confirmación explícita adicional, para preservar la integridad transaccional (Principio V).

## Relaciones

```text
Locacion (1) ──< (N) Locacion   [locacion_padre_id, reflexiva]
Locacion (1) ──< (N) Contrato   [de specs/002-gestion-contratos, ya existente]
```

## Notas de migración

- No se requiere una nueva migración de esquema: la tabla `locaciones` y su clave foránea reflexiva ya existen y son suficientes para las reglas de esta feature.
- Mejora opcional (no bloqueante, ver `research.md` §3): considerar una migración futura que cambie `nullOnDelete()` por `restrictOnDelete()` en `locacion_padre_id`, como capa adicional de defensa a nivel de base de datos complementando la verificación de aplicación.
