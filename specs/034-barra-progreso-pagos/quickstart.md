# Quickstart: Barra de Progreso de Pagos

Validación manual, a correr tras implementar. Usar el binario de PHP de Herd
(`C:\Users\joel5\.config\herd\bin\php.bat`) y el dominio real del proyecto en esta máquina
(`rent-tracker-sdd.test`).

## Preparación

1. Tener 3 locaciones con recibos vigentes del periodo actual: una sin ningún pago, una con pago parcial, y
   una completamente pagada.

## Escenario 1 — Barra de progreso en "Registro de Pagos" (US1)

1. Abrir "Registro de Pagos".
2. En la locación sin pagos, confirmar que su barra se ve vacía (0%) y de color gris/secundario.
3. En la locación con pago parcial, confirmar que la barra está parcialmente llena (aproximadamente
   proporcional a lo pagado) y de color ámbar/warning, y que el monto pagado/total en texto sigue visible
   junto a ella.
4. En la locación completamente pagada, confirmar que la barra está completamente llena y de color verde.
5. Confirmar que una locación sin ningún recibo vigente en el periodo no muestra ninguna barra.

## Escenario 2 — Barra de progreso en el detalle de un recibo (US2)

1. Abrir el detalle de un recibo con un pago parcial registrado.
2. Confirmar que la sección de Pagos muestra una barra de progreso consistente con el monto pagado/total ya
   mostrado en texto ahí.
3. Registrar un pago nuevo (o eliminar uno existente) y confirmar que la barra se actualiza de inmediato al
   recargar la página, sin quedar desactualizada respecto al nuevo total pagado.

## Casos límite a revisar

- Locación con varios recibos vigentes en el periodo, unos pagados y otros no: la barra de su fila refleja
  el avance agregado (mismo criterio que specs/033 usa para el texto).
- Eliminar el único pago de un recibo completamente pagado: su barra vuelve a mostrarse vacía.

## Regresión a confirmar

- `php artisan test` completo sigue en verde.
- El texto de avance (monto pagado / monto total) sigue visible sin cambios en ambos lugares — la barra se
  agrega, no reemplaza nada (FR-006).
- "Registro de Pagos" (specs/033) y el detalle de recibo (specs/032) no pierden ninguna otra funcionalidad.
