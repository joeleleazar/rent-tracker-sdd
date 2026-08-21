# Data Model: Estado de Recibos y Envío por WhatsApp o Impresión

**Feature**: `007-estado-envio-recibo` | **Date**: 2026-08-20

## Entidades

### Recibo (extensión de `specs/004-condiciones-contrato-recibo`, refinada en `005`)

Columnas nuevas agregadas mediante migración de alteración sobre la tabla `recibos` ya existente:

| Campo nuevo | Tipo | Reglas |
|---|---|---|
| `estado` | string/enum (`pendiente`, `pagado`, `anulado`), por defecto `pendiente` | `enum()` de Laravel (compilado a `varchar` + `CHECK` en el driver `pgsql`, mismo patrón que `contratos.estado` de `specs/002`); asignado automáticamente al emitir (FR-001) |
| `fecha_pago` | timestamp, nullable | Se asigna al entrar en `pagado`; se limpia a `null` si el recibo sale de ese estado (FR-003) |
| `fecha_anulacion` | timestamp, nullable | Se asigna al entrar en `anulado`; se limpia a `null` si el recibo sale de ese estado (FR-003) |

**Comportamiento nuevo**:
- Todas las transiciones entre `pendiente`/`pagado`/`anulado` están permitidas (FR-005); las que involucran `anulado` (entrada o salida) exigen confirmación explícita antes de ejecutarse (FR-004, ver `research.md` §3).
- Helper `esValido(): bool` no aplica (no hay estados inválidos); en su lugar, `ServicioCambioEstadoRecibo` es la única vía autorizada para cambiar `estado` fuera de la creación inicial.

**Validaciones de negocio**:
- FR-001: todo `Recibo` recién creado (`ReciboController@store`, de `specs/004`/`005`) inicia en `estado = 'pendiente'` (ya es el valor por defecto de la columna, no requiere lógica adicional en el controlador de creación).
- FR-004: `ReciboController@actualizarEstado` MUST rechazar con 422 cualquier transición hacia/desde `anulado` que no incluya el parámetro de confirmación explícita.

## Relaciones

Sin cambios respecto a `specs/005-lecturas-medidor-recibo-periodo/data-model.md`; esta feature solo agrega columnas de estado/fechas a `Recibo`, sin nuevas tablas ni relaciones.

## Notas de migración

- Migración de alteración sobre `recibos`: `estado` con `enum(['pendiente','pagado','anulado'])->default('pendiente')`, `fecha_pago` y `fecha_anulacion` como `timestamp` nullable.
- Los recibos ya existentes (creados bajo `specs/004`/`005` antes de esta migración) reciben `estado = 'pendiente'` por el valor por defecto de la columna, sin necesidad de una migración de datos adicional (ningún recibo previo tenía noción de estado de pago).
