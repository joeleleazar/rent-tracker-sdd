# Quickstart: Saldo Histórico en el Comprobante de Pago

Validación manual, a correr tras implementar. Usar el binario de PHP de Herd
(`C:\Users\joel5\.config\herd\bin\php.bat`) y el dominio real del proyecto en esta máquina
(`rent-tracker-sdd.test`).

## Preparación

1. Tener un recibo con un total conocido (ej. S/ 1,000.00).

## Escenario 1 — El comprobante de un pago antiguo no muestra el estado actual (US1)

1. Registrar un primer pago parcial (ej. S/ 300.00) sobre el recibo.
2. Abrir el comprobante de ese primer pago y confirmar: acumulado S/ 300.00, saldo pendiente S/ 700.00.
3. Registrar un segundo pago (ej. S/ 700.00) que completa el total — el recibo pasa a "Pagado".
4. Volver a abrir el comprobante del **primer** pago (no el segundo) y confirmar que sigue mostrando
   acumulado S/ 300.00 y saldo pendiente S/ 700.00 — NO el estado actual del recibo (que ya está
   completamente pagado).
5. Abrir el comprobante del segundo pago y confirmar: acumulado S/ 1,000.00, saldo pendiente S/ 0.00.

## Casos límite a revisar

- Editar el monto del primer pago (ej. de S/ 300.00 a S/ 500.00) y volver a abrir el comprobante del
  segundo pago: su acumulado debe recalcularse a S/ 1,200.00 (500 + 700) y su saldo pendiente reflejar el
  nuevo total pagado, sin quedar desactualizado.
- Eliminar el primer pago y volver a abrir el comprobante del segundo pago: su acumulado debe recalcularse
  para excluir el pago eliminado (solo S/ 700.00).
- Un recibo con un único pago: su comprobante no cambia de comportamiento respecto a antes de esta feature.

## Regresión a confirmar

- `php artisan test` completo sigue en verde.
- El resto del comprobante de pago (monto propio del pago, metadatos, firma, cierre — specs/035) no cambia.
- El detalle del recibo, "Registro de Pagos" (specs/033) y la barra de progreso (specs/034) siguen
  mostrando el avance **actual** del recibo sin cambios — esta feature no los toca.
