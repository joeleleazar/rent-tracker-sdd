# Quickstart: Distribución en Dos Columnas del Detalle de Recibo

Validación manual, a correr tras implementar. Usar el dominio real del proyecto en esta máquina
(`rent-tracker-sdd.test`).

## Preparación

1. Tener un recibo vigente (no anulado) con al menos un pago registrado.
2. Tener un recibo anulado, para el Acceptance Scenario 3.

## Escenario 1 — Dos columnas en pantalla ancha (US1)

1. Abrir el detalle de un recibo vigente en una ventana ancha.
2. Confirmar que el resumen del recibo (estado, locación, período, emisión, conceptos, total) se muestra en
   una columna a la izquierda.
3. Confirmar que a la derecha se muestran, apiladas, la tarjeta de Pagos (con su barra de progreso, avance y
   lista de pagos) y la tarjeta de Estado del Recibo.
4. Abrir el detalle de un recibo anulado y confirmar que la columna derecha muestra solo la tarjeta de
   Estado del Recibo (sin un hueco vacío donde iría Pagos).

## Escenario 2 — Apilado en pantalla angosta (US2)

1. Reducir el ancho de la ventana (o simular una pantalla angosta).
2. Confirmar que el resumen del recibo, la tarjeta de Pagos y la tarjeta de Estado del Recibo se apilan
   verticalmente en ese orden, sin scroll horizontal ni contenido cortado.

## Regresión a confirmar

- `php artisan test` completo sigue en verde.
- Registrar un pago, editar un pago, eliminar un pago, subir evidencia, y anular/reactivar un recibo siguen
  funcionando exactamente igual que antes (mismos modales, mismas rutas).
- Los mensajes de éxito/error se siguen mostrando por encima de las dos columnas.
