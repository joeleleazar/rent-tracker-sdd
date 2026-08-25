# Data Model: Corrección de Lectura Previa y Autoguardado en Registro Masivo

**Feature**: `016-correccion-registro-masivo-lecturas` | **Date**: 2026-08-25

Esta corrección **no agrega ni modifica columnas, tablas ni relaciones**. Ambas entidades
involucradas ya existen desde specs/015 (ver `specs/015-registro-masivo-lecturas/data-model.md`
para su definición completa); aquí solo se deja constancia de las reglas de cómputo que esta
feature debe hacer volver a cumplir con exactitud.

## Entidades reutilizadas (sin cambios de esquema)

### LecturaMedidor

| Campo usado | Regla que esta corrección debe sostener |
|---|---|
| `locacion_id`, `periodo` | La "lectura del periodo anterior" de una locación para un periodo `P` es la `lectura_actual` de su `LecturaMedidor` con `periodo < P` y el `periodo` máximo entre esas — es decir, el periodo cronológicamente más cercano por debajo, sin exigir continuidad mensual (FR-001/FR-003, Edge Case de periodos salteados y de cruce de año). |
| `lectura_actual` | Valor mostrado tal cual, sin redondeo ni transformación adicional (FR-001). |

### BorradorLecturaMedidor

| Campo usado | Regla que esta corrección debe sostener |
|---|---|
| `usuario_id`, `periodo`, `locacion_id` | Clave de upsert ya existente (índice único compuesto) — cada ciclo de autoguardado (cada 2 minutos) DEBE sobrescribir la misma fila, no crear duplicados ni fallar silenciosamente en ciclos sucesivos (FR-004). |
| `lectura_actual` | Refleja el valor tal como está tipeado en pantalla en el momento del autoguardado más reciente; se restaura automáticamente en el campo correspondiente al reabrir la pantalla para el mismo periodo (FR-005), sin sobrescribir un borrador existente con menos datos si el ciclo no tuvo cambios nuevos (Edge Case). |

## Relaciones

Sin cambios respecto a `specs/015-registro-masivo-lecturas/data-model.md`:

```text
Locacion (1) ──< (N) LecturaMedidor           [sin cambios]
Locacion (1) ──< (N) BorradorLecturaMedidor   [sin cambios]
User     (1) ──< (N) BorradorLecturaMedidor   [sin cambios]
```

## Reglas de validación

Sin cambios respecto a specs/015. Esta corrección no introduce nuevas reglas de negocio ni relaja
las existentes — únicamente corrige la fidelidad con la que el sistema calcula/persiste/restaura
valores según las reglas ya vigentes.
