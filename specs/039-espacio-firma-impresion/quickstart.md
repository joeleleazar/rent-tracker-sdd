# Quickstart: Más Espacio para la Firma en la Impresión del Comprobante de Pago

Validación manual, a correr tras implementar. Usar el dominio real del proyecto en esta máquina
(`rent-tracker-sdd.test`).

## Escenario 1 — Más espacio para la firma (US1)

1. Abrir el comprobante de cualquier pago.
2. Confirmar que el área en blanco antes de la línea de firma es notoriamente mayor que antes de esta
   feature.
3. Activar la vista previa de impresión y confirmar que ese espacio se conserva en el documento impreso.
4. Confirmar que el resto del documento (metadatos, monto de este pago, avance histórico, cierre) no
   cambió.

## Regresión a confirmar

- `php artisan test` completo sigue en verde.
- El comprobante sigue en una sola columna (specs/037 no se reintroduce).
