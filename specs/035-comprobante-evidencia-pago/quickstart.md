# Quickstart: Comprobante de Pago Firmado y Evidencia de Pago

Validación manual, a correr tras implementar. Usar el binario de PHP de Herd
(`C:\Users\joel5\.config\herd\bin\php.bat`) y el dominio real del proyecto en esta máquina
(`rent-tracker-sdd.test`).

## Preparación

1. Tener un recibo con al menos dos pagos parciales registrados (uno de ellos no completa el total, para
   ver un saldo pendiente distinto de cero en el comprobante).

## Escenario 1 — Exportar el comprobante de un pago (US1)

1. Abrir el detalle del recibo y localizar uno de sus pagos.
2. Abrir el comprobante de ese pago específico.
3. Verificar que muestra: N.° de recibo, N.° de pago, fecha del pago, locación, inquilino, el monto de ese
   pago (destacado por encima del resto), el total del recibo, el monto pagado acumulado (incluyendo este
   pago y los anteriores) y el saldo pendiente.
4. Confirmar a mano que monto de este pago + saldo pendiente, sumado al resto de pagos ya registrados,
   coincide con el total del recibo.
5. Activar la vista previa de impresión y confirmar que el documento se ve completo y legible, sin los
   controles de pantalla (botón "Imprimir", enlace "Volver"), y con un espacio de firma visible.
6. Repetir sobre el pago que sí completa el total y confirmar que el saldo pendiente se muestra en
   S/ 0.00.

## Escenario 2 — Subir la evidencia de un pago firmado (US2)

1. Sobre un pago sin evidencia todavía, confirmar que el sistema lo indica claramente como pendiente de
   evidencia.
2. Subir una imagen (JPG o PNG) como evidencia y confirmar que queda asociada a ese pago.
3. Abrir/descargar la evidencia recién subida y confirmar que corresponde al archivo subido.
4. Subir un archivo nuevo para el mismo pago y confirmar que reemplaza al anterior (al volver a abrir la
   evidencia, se obtiene el archivo nuevo, no el anterior).
5. Repetir la subida con un PDF en vez de una imagen, y confirmar que también se acepta.

## Casos límite a revisar

- Intentar subir un archivo de un tipo no admitido (por ejemplo, un `.docx`) y confirmar que el sistema lo
  rechaza con un mensaje claro, sin afectar el pago.
- Intentar subir un archivo que supere el límite de tamaño admitido y confirmar el mismo tipo de rechazo.
- Exportar el comprobante de un pago cuyo recibo ya está anulado y confirmar que el documento sigue
  disponible (deja constancia de un pago ya hecho, independiente del estado actual del recibo).
- Editar el monto de un pago después de haber exportado su comprobante y volver a exportarlo: confirmar que
  el nuevo comprobante refleja el monto actualizado (no el original), consistente con que el documento se
  genera al vuelo (research.md Decisión 4).

## Regresión a confirmar

- `php artisan test` completo sigue en verde.
- El comprobante del recibo completo (specs/031) y la pantalla de "Registro de Pagos" (specs/033/034) no
  cambian su comportamiento.
