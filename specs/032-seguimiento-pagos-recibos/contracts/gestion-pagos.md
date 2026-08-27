# Contrato: registrar, editar y eliminar pagos de un recibo

## Rutas

| Método | Ruta | Nombre | Acción |
|---|---|---|---|
| POST | `/recibos/{recibo}/pagos` | `pagos.store` | Registra un pago nuevo contra `$recibo` |
| PUT | `/pagos/{pago}` | `pagos.update` | Corrige el monto/fecha de un pago ya registrado |
| DELETE | `/pagos/{pago}` | `pagos.destroy` | Elimina un pago (con confirmación explícita en la UI) |

Las tres rutas exigen sesión autenticada, igual que el resto de `routes/web.php`.

## `pagos.store` — `SolicitudGuardarPago`

| Campo | Regla | Mensaje si falla |
|---|---|---|
| `monto` | `required`, `numeric`, `gt:0`, `≤ saldo pendiente del recibo` | "El monto debe ser mayor a cero." / "El monto no puede superar el saldo pendiente (S/ {saldo})." |
| `fecha_pago` | `required`, `date`, `≤ hoy` | "Debe indicar la fecha del pago." / "La fecha del pago no puede ser futura." |

**Precondición de negocio**: `$recibo->estado !== 'anulado'` — si el recibo está anulado, la solicitud se
rechaza (403 o redirect con error), sin llegar a evaluar el monto (FR-011).

**Efecto**: crea la fila en `pagos` con `registrado_por_id = Auth::id()`, y dispara el recálculo de
`recibos.estado`/`fecha_pago` descrito en data-model.md, todo dentro de una única transacción.

## `pagos.update` — mismo `SolicitudGuardarPago`

Misma validación que `pagos.store`, con una diferencia: el saldo pendiente contra el que se valida `monto`
se calcula **excluyendo el propio pago que se está editando** de la suma ya registrada (de lo contrario,
un pago no podría nunca editarse a un monto igual o mayor al que ya tenía).

**Efecto**: actualiza el monto/fecha del pago y vuelve a disparar el mismo recálculo de estado.

## `pagos.destroy`

Sin cuerpo de solicitud. Exige confirmación explícita en la UI (modal, Principio III) antes de enviarse.

**Efecto**: elimina la fila de `pagos` y vuelve a disparar el recálculo de estado sobre el saldo restante
(que puede hacer que un recibo `pagado` vuelva a `pendiente`).

## Casos de error comunes a las tres rutas

| Situación | Resultado |
|---|---|
| El recibo asociado está `anulado` | Rechazada — un recibo anulado no admite pagos nuevos ni cambios (FR-011) |
| `monto` haría que la suma supere el total del recibo | Rechazada — mensaje con el monto máximo disponible (FR-003) |
| `monto` ≤ 0 | Rechazada (FR-004) |
