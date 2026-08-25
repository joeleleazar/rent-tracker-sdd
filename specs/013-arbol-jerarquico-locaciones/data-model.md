# Data Model: Árbol Jerárquico Horizontal de Locaciones

**Feature**: `013-arbol-jerarquico-locaciones` | **Date**: 2026-08-23

## Entidades

**Revisión 2026-08-23**: se agrega el campo `tipo` a `Locacion` (ver research.md §8). El resto de la entidad y toda la estructura en memoria de esta sección se mantienen sin cambios respecto a la iteración anterior.

### Locacion (extendida en esta revisión)

| Campo | Tipo | Notas |
|---|---|---|
| `id` | Entero, PK | |
| `nombre` | string | Texto mostrado en la columna Nombre/Locación de cada fila |
| `tamano` | decimal(2) | No se muestra en la fila (visible en `locaciones.show`) |
| `ubicacion_fisica` | string | No se muestra en la fila |
| `descripcion` | string | No se muestra en la fila |
| `locacion_padre_id` | Entero, FK nullable → `locaciones.id` | Determina la posición e indentación de la fila; `null` = raíz |
| `es_alquilable` | boolean | Determina el badge de la columna Estado (FR-003) |
| `tipo` | string (`enum`/`CHECK`: `galeria`\|`piso`\|`sector`\|`pasillo`\|`local`), **nullable** | **Nuevo**. Determina el ícono y la etiqueta de la columna Tipo (FR-010). Nullable a nivel de base de datos porque las locaciones registradas antes de esta revisión no tienen un valor asignado (ver Edge Case "Locación sin Tipo asignado"); obligatorio en el formulario para locaciones nuevas o editadas. |

## Estructura en memoria (no persistida)

`ServicioConstruccionArbolLocaciones::construir()` produce una estructura de árbol en memoria a partir de una única consulta, sin nueva tabla ni caché persistente:

```text
NodoArbol {
    locacion: Locacion
    hijos: array<NodoArbol>   // ordenados por Locacion.nombre
}

Arbol = array<NodoArbol>   // uno por cada locación raíz (locacion_padre_id === null), ordenados por nombre
```

**Reglas de construcción**:
- Se agrupan todas las locaciones por `locacion_padre_id` en una sola pasada (`Collection::groupBy`).
- Cada raíz (`locacion_padre_id === null`) inicia un árbol independiente (FR-004).
- La recursión para poblar `hijos` se detiene a los `MAXIMO_PROFUNDIDAD_ARBOL` niveles (mismo valor 1000 que `Locacion::MAXIMO_SALTOS_ANCESTROS`) como salvaguarda defensiva (FR-008); no se espera alcanzarlo en operación normal dado que `specs/001` (FR-003) ya impide ciclos al guardar.
- Ninguna locación queda excluida del árbol (FR-001, SC-004): tanto `es_alquilable = true` como `false` se incluyen como nodos.

## Relaciones

Sin cambios respecto a `specs/001-jerarquia-locaciones`:

```text
Locacion (1) ──< (N) Locacion   [auto-referencial vía locacion_padre_id, ya existente]
```
