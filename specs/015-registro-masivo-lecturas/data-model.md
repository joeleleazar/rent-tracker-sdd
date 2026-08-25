# Data Model: Registro Masivo de Lecturas de Luz

**Feature**: `015-registro-masivo-lecturas` | **Date**: 2026-08-24

## Entidades

### Locacion (sin cambios)

| Campo relevante | Tipo | Uso en esta feature |
|---|---|---|
| `es_alquilable` | boolean | Determina qué locaciones muestran un campo de lectura editable (FR-001) frente a las que solo aparecen como encabezado organizativo del árbol |
| `locacion_padre_id` | Entero, FK nullable | Reutilizado por `ServicioConstruccionArbolLocaciones` para la agrupación jerárquica (FR-002) |

### LecturaMedidor (sin cambios de esquema)

| Campo | Tipo | Uso en esta feature |
|---|---|---|
| `locacion_id`, `periodo` | FK, date | Determina si una fila ya está "completada" (FR-005): existe una `LecturaMedidor` con ese `locacion_id` + `periodo` |
| `lectura_anterior`, `lectura_actual`, `consumo_calculado` | decimal | Se completan igual que en el flujo individual, fila por fila, reutilizando `ServicioCalculoConsumoMedidor` |

Esta feature no agrega columnas a `lecturas_medidor`; el registro masivo es una vía de creación
adicional sobre la misma tabla, no una entidad nueva de "lectura".

### ConfiguracionGeneral (sin cambios de esquema, reutilizada por FR-013/FR-015)

| Campo relevante | Tipo | Uso en esta feature |
|---|---|---|
| `tarifa_luz_por_unidad` | decimal:4 | Valor por defecto del input global de tarifa (FR-013); se actualiza en esta misma fila singleton (`id = 1`, vía `ConfiguracionGeneral::actual()`) cuando el usuario lo edita desde la pantalla de registro masivo (FR-015), quedando disponible también para la generación de recibos (specs/005) |

Esta feature no agrega columnas a `configuracion_general` ni crea una entidad de "tarifa" nueva;
el totalizado es una vía adicional de lectura/escritura sobre el mismo valor singleton ya
existente.

### BorradorLecturaMedidor (nueva)

| Campo | Tipo | Notas |
|---|---|---|
| `id` | Entero, PK | |
| `usuario_id` | Entero, FK → `users.id`, `cascadeOnDelete` | Quién está completando el borrador (Assumption: un borrador es por usuario, spec.md) |
| `periodo` | date | Mes del lote, igual formato que `lecturas_medidor.periodo` (primer día del mes) |
| `locacion_id` | Entero, FK → `locaciones.id`, `cascadeOnDelete` | La fila del lote a la que corresponde este valor de borrador |
| `lectura_actual` | decimal(10,2) nullable | El valor tal como está escrito en pantalla en el momento del autoguardado, **sin validar** (Assumption: sin las validaciones de negocio de FR-008/FR-009 hasta el guardado final) |
| `created_at`, `updated_at` | timestamps | `updated_at` determina "guardado hace un momento" si se muestra un indicador |

**Restricción de unicidad**: índice único compuesto `(usuario_id, periodo, locacion_id)` — el
autoguardado hace `upsert()` sobre esta clave, nunca duplica una fila de borrador para la misma
combinación (FR-010, Edge Case de sesiones concurrentes: la última escritura gana porque
`upsert()` sobrescribe el mismo registro).

**Ciclo de vida**: se crea/actualiza en cada autoguardado (cada 2 minutos, User Story 3); se lee
al abrir la pantalla para restaurar valores (FR-011); se elimina por completo (todas las filas de
ese `usuario_id` + `periodo`) al completar el guardado final exitoso del lote (FR-012). No tiene
ninguna relación con `Recibo` ni con `Contrato` — es un estado transitorio exclusivo de esta
pantalla.

## Relaciones

```text
Locacion (1) ──< (N) LecturaMedidor           [ya existente, sin cambios]
Locacion (1) ──< (N) BorradorLecturaMedidor   [nueva]
User     (1) ──< (N) BorradorLecturaMedidor   [nueva]
```

## Reglas de validación

- `lectura_actual` en `BorradorLecturaMedidor`: sin reglas de negocio (nullable, se acepta
  cualquier valor intermedio de tipeo); solo se valida al promoverse a `LecturaMedidor` en el
  guardado final, reutilizando `SolicitudGuardarLecturaMedidor` por fila.
- El guardado final (`SolicitudGuardarRegistroMasivoLecturas`) valida cada fila del arreglo
  enviado con las mismas reglas ya existentes (`numeric`, `min:0`) aplicadas por fila, más la
  regla de que filas vacías se omiten de la validación (FR-004) en vez de fallar como
  "requerido".

## Notas

No hay transición de estado explícita a nivel de modelo: `BorradorLecturaMedidor` no tiene un
campo `estado`; su sola existencia (o ausencia) para una combinación `usuario_id` + `periodo` es
la señal de si hay o no un borrador pendiente de restaurar.

`ConfiguracionGeneral` no tiene relación (FK) con `Locacion`, `LecturaMedidor` ni
`BorradorLecturaMedidor`: es una fila singleton global (`id = 1`) sin ámbito por periodo ni por
locación — el mismo valor de tarifa aplica a todos los periodos y locaciones en un momento dado,
consistente con la clarificación registrada en `spec.md` (un único input global, no uno por fila).
