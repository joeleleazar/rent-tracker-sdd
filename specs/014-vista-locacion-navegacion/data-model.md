# Data Model: Navegación a Contratos y Recibos desde las Vistas de Locación

**Feature**: `014-vista-locacion-navegacion` | **Date**: 2026-08-24 (revisado tras `/speckit-clarify`)

## Entidades

Esta feature no crea, modifica ni elimina columnas, tablas ni relaciones. Es un cambio de
navegación en dos vistas existentes (el detalle de la locación y la fila de la tabla jerárquica
en `/locaciones`); las tres entidades involucradas ya existen sin cambios de esquema.

### Locacion (sin cambios)

| Campo relevante | Tipo | Uso en esta feature |
|---|---|---|
| `es_alquilable` | boolean | Único criterio que determina si se muestran las opciones "Ver Contratos" y "Ver Recibos", tanto en `locaciones/show.blade.php` (FR-001, FR-002, FR-005) como en el menú desplegable de `fila-arbol-locacion.blade.php` (FR-009) |

### Contrato (sin cambios)

No se lee ni escribe ningún campo nuevo. `ContratoController@index` ya expone el historial
completo (vigentes y finalizados) de contratos de una `Locacion`, y `@create`/`@edit` ya
implementan el CRUD que piden FR-003/FR-004.

### Recibo (sin cambios)

No se lee ni escribe ningún campo nuevo. `ReciboController@index` ya expone
`$locacion->recibos()->orderByDesc('periodo')->get()` como historial completo (FR-006).

## Relaciones

Sin cambios respecto al esquema ya existente:

```text
Locacion (1) ──< (N) Contrato
Locacion (1) ──< (N) Recibo
```

## Notas

No aplica sección de reglas de validación ni de transiciones de estado: no hay formulario ni
escritura de datos nueva en esta feature (es una feature de solo navegación/lectura, ver
Assumptions en `spec.md`).
