# Data Model: Registro y Seguimiento de Pagos de Recibos

## Pago (nueva)

Un movimiento de pago registrado contra un recibo.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | bigint | PK | — |
| `recibo_id` | bigint | FK `recibos.id`, `cascadeOnDelete()`, `NOT NULL` | El recibo al que corresponde este pago |
| `monto` | decimal(12,2) | `NOT NULL`, > 0 (validado en `SolicitudGuardarPago`, no a nivel de columna) | Monto recibido en este pago |
| `fecha_pago` | date | `NOT NULL` | Fecha en la que se recibió el pago (no necesariamente la fecha en que se registra en el sistema) |
| `registrado_por_id` | bigint | FK `users.id`, `nullOnDelete()`, nullable | Quién registró el pago (research.md Decisión 2) |
| `created_at` / `updated_at` | timestamp | — | Estándar Eloquent |

**Relaciones**:
- `Pago belongsTo Recibo` (`recibo_id`).
- `Pago belongsTo User` (`registrado_por_id`).
- `Recibo hasMany Pago` (`pagos()`).

**Reglas de validación** (aplicadas en `SolicitudGuardarPago`, no en la base de datos):
- `monto` DEBE ser mayor a cero (FR-004).
- `monto` DEBE ser menor o igual al saldo pendiente del recibo en el momento de guardar — es decir,
  `monto ≤ recibo->saldoPendiente()` al crear, o `monto ≤ recibo->saldoPendiente() + $pago->monto` (el
  propio saldo antes de contar el pago que se está editando) al editar (FR-003).
- El recibo asociado DEBE estar vigente (`estado != 'anulado'`) para crear, editar o eliminar un pago
  sobre él (FR-011).

## Recibo (extendida — sin cambios de esquema)

Se agregan métodos derivados, análogos a `total()` ya existente; no se agrega ninguna columna nueva a
`recibos`.

| Método | Retorna | Descripción |
|---|---|---|
| `pagos()` | `HasMany<Pago>` | Los pagos registrados contra este recibo |
| `montoPagado()` | `float` | Suma de `pagos.monto` |
| `saldoPendiente()` | `float` | `total() - montoPagado()`, nunca negativo |
| `estaPagadoPorCompleto()` | `bool` | `saldoPendiente() <= 0` (con la misma tolerancia de redondeo que ya usa `total()`) |

**Regla de estado** (research.md Decisión 3, recalculada por `ServicioGestionPagosRecibo` en cada
registrar/editar/eliminar pago, nunca por asignación manual salvo la transición hacia/desde `anulado`):

- `montoPagado() == 0` → `estado = 'pendiente'`, `fecha_pago = null`.
- `0 < montoPagado() < total()` → `estado = 'pendiente'` (el avance parcial se muestra por separado, no
  como un tercer valor de `estado` — ver contracts/vista-seguimiento-pagos.md), `fecha_pago = null`.
- `montoPagado() >= total()` → `estado = 'pagado'`, `fecha_pago = now()` en el momento en que se cruza ese
  umbral.
- `estado == 'anulado'` no se recalcula nunca por esta regla — solo cambia por
  `ServicioCambioEstadoRecibo::anular()`/`reactivar()`.

## Relaciones y consistencia

- Un `Recibo` tiene cero, uno o varios `Pago` (1 a N).
- La suma de `pagos.monto` de un recibo nunca puede superar `recibo->total()` — se hace cumplir en
  `SolicitudGuardarPago`/`ServicioGestionPagosRecibo`, no con una restricción de base de datos (el total
  del recibo es un valor calculado en PHP a partir de `monto_renta` + `recibo_conceptos`, no una columna
  contra la que la base de datos pueda validar directamente).
- Eliminar un recibo elimina en cascada sus pagos (`cascadeOnDelete()`) — consistente con que un recibo
  eliminado no debería dejar pagos huérfanos; en la práctica, este proyecto no expone ninguna acción de
  eliminar un recibo ya emitido (solo anularlo), así que esta cascada es una red de seguridad, no una ruta
  usada por la interfaz.
