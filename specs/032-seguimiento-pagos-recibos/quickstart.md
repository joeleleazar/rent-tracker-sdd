# Quickstart: Registro y Seguimiento de Pagos de Recibos

Validación manual, a correr tras implementar. Usar el binario de PHP de Herd
(`C:\Users\joel5\.config\herd\bin\php.bat`) y el dominio real del proyecto en esta máquina
(`rent-tracker-sdd.test`).

## Preparación

1. Tener al menos un recibo emitido y pendiente, con un total conocido (ej. S/ 960.75).
2. Tener, si es posible, una locación con más de un recibo vigente en el mismo período (para el Escenario
   2, Edge Case de agregación).

## Escenario 1 — Registrar un pago parcial y completarlo (US1)

1. Abrir el detalle de un recibo pendiente sin pagos.
2. Registrar un pago por un monto menor al total (ej. S/ 500.00 de S/ 960.75) y confirmar que se acepta,
   que aparece en la lista de pagos del recibo, y que el recibo sigue en "Pendiente" mostrando el avance
   (S/ 500.00 de S/ 960.75).
3. Intentar registrar un segundo pago que exceda el saldo pendiente (ej. S/ 600.00) y confirmar que el
   sistema lo rechaza indicando el monto máximo disponible (S/ 460.75).
4. Registrar un segundo pago por el saldo exacto (S/ 460.75) y confirmar que el recibo pasa a "Pagado".
5. Intentar registrar un pago de S/ 0.00 sobre cualquier recibo y confirmar que se rechaza.

## Escenario 2 — Ver el avance de pago en la jerarquía de locales (US2)

1. Abrir `pagos/seguimiento` para el período usado en el Escenario 1.
2. Verificar que aparece la misma jerarquía de galería/piso/local que en `recibos/registro-masivo`.
3. Verificar que la locación del recibo pagado en el Escenario 1 muestra "Pagado"; una locación con un
   recibo sin pagos muestra "Sin pagos"; una locación sin ningún recibo emitido ese período no muestra
   ningún estado de pago.
4. Cambiar de período con el selector (igual que en emisión de recibos) y confirmar que el árbol se
   actualiza.
5. Si hay una locación con más de un recibo vigente en el período, hacer clic en "Ver Pagos" y confirmar
   que lleva a la lista de recibos del período (no directo a uno solo); en una locación con un único
   recibo, confirmar que "Ver Pagos" lleva directo al detalle de ese recibo.

## Escenario 3 — Corregir un pago registrado por error (US3)

1. Sobre un recibo con al menos un pago registrado, editar el monto de ese pago a un valor distinto y
   confirmar que el avance de pago del recibo (y su estado, si corresponde) se recalcula.
2. Eliminar un pago y confirmar que el sistema pide confirmación explícita antes de eliminarlo; tras
   confirmar, verificar que el avance de pago se recalcula sin ese pago (un recibo que estaba "Pagado"
   puede volver a "Pendiente" si el pago eliminado era necesario para completar el total).

## Casos límite a revisar

- Anular un recibo con pagos ya registrados: confirmar que los pagos siguen visibles en el detalle del
  recibo, pero que la locación deja de contarse en `pagos/seguimiento` para ese período.
- Reactivar (revertir la anulación de) ese mismo recibo: confirmar que su estado Pendiente/Pagado se
  recalcula solo a partir de los pagos que ya tenía, sin pedir elegir un estado manualmente.
- Intentar registrar, editar o eliminar un pago sobre un recibo anulado: confirmar que el sistema lo
  impide.

## Regresión a confirmar

- `php artisan test` completo sigue en verde.
- El comprobante de recibo (specs/031) sigue mostrando correctamente el estado "Pagado"/"Pendiente" de un
  recibo cuyo estado ahora se calcula en vez de asignarse a mano.
- La pantalla de emisión de recibos (`recibos/registro-masivo`) no cambia su comportamiento.
