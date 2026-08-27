# Quickstart: Más Espacio para Firmar y Aprovechamiento Horizontal en el Comprobante de Pago

Validación manual, a correr tras implementar. Usar el dominio real del proyecto en esta máquina
(`rent-tracker-sdd.test`).

## Preparación

1. Tener un recibo con al menos 2 pagos registrados.

## Escenario 1 — Más espacio para la firma (US1)

1. Abrir el comprobante de cualquiera de esos pagos.
2. Confirmar que el área en blanco antes de la línea de firma es notoriamente mayor que antes de esta
   feature.
3. Activar la vista previa de impresión y confirmar que ese espacio se conserva en el documento impreso.

## Escenario 2 — Lista de pagos junto al comprobante (US2)

1. En el mismo comprobante, confirmar que en pantallas anchas se ve, junto al contenido principal, una
   lista con todos los pagos del recibo (fecha y monto de cada uno).
2. Confirmar que el pago que corresponde a este comprobante en particular está claramente marcado dentro de
   esa lista (badge "Este pago").
3. Abrir el comprobante del otro pago del mismo recibo y confirmar que en su lista el marcado "Este pago"
   ahora corresponde al otro registro.
4. Reducir el ancho de la ventana (o simular una pantalla angosta) y confirmar que el contenido principal y
   la lista de pagos se apilan verticalmente sin perder ningún dato ni generar scroll horizontal.

## Casos límite a revisar

- Comprobante de un pago de un recibo con un único pago: la lista muestra ese único pago, marcado como
  "Este pago", sin ningún caso especial visible.

## Regresión a confirmar

- `php artisan test` completo sigue en verde.
- El monto de este pago y el avance histórico (specs/036) siguen mostrando las mismas cifras que antes —
  solo cambia su disposición visual.
- El botón "Imprimir Comprobante" y "Volver al Recibo" siguen funcionando sin cambios.
