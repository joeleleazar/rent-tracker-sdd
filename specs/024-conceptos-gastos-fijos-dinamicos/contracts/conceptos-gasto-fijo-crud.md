# Contrato: CRUD de Conceptos de Gasto Fijo

Rutas nuevas, dentro de `Route::middleware('auth')`.

## `GET conceptosGastoFijo.index`

`/conceptos-gasto-fijo`

Lista todos los conceptos (activos e inactivos) ordenados por `orden`, indicando para cada uno si es
protegido (`clave='renta'`) y en cuántos contratos/recibos está en uso (para que la UI pueda anticipar si un
intento de eliminar será rechazado, FR-003).

## `GET conceptosGastoFijo.create` / `POST conceptosGastoFijo.store`

Crea un concepto nuevo: `nombre` (requerido), `orden` (requerido, entero). `clave` NUNCA se acepta desde el
formulario — todo concepto creado desde la UI nace con `clave = null` (FR-001: los 2 conceptos protegidos ya
existen desde el sembrado inicial, no se crean más).

## `GET conceptosGastoFijo.edit` / `PUT conceptosGastoFijo.update`

Edita `nombre`, `orden` y `activo`. Si `clave='renta'`, el campo `activo` se ignora o se rechaza con un
mensaje explícito (FR-002) — no puede desactivarse.

## `DELETE conceptosGastoFijo.destroy`

- Si `clave` no es nula (Renta): rechazado, "Este concepto no puede eliminarse." (FR-002).
- Si el concepto tiene al menos una fila en `contrato_valores_concepto` o `recibo_conceptos`: rechazado,
  indicando la cantidad de contratos/recibos que lo usan y sugiriendo desactivarlo en vez de eliminarlo
  (FR-003).
- Caso contrario: elimina el concepto.

Toda acción destructiva (eliminar) pasa por el modal de confirmación con dos botones ya exigido por el
Principio III de la constitución.
