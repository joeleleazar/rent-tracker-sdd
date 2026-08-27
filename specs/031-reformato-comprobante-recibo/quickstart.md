# Quickstart: Reformato de Jerarquía Visual del Comprobante de Recibo

Validación manual, a correr tras implementar. Usar el binario de PHP de Herd
(`C:\Users\joel5\.config\herd\bin\php.bat`) y el dominio real del proyecto (`rent-tracker-sdd.test` en esta
máquina — verificar con `cat ~/.config/herd/config/valet/Sites` si cambia).

## Preparación

1. En Configuración General, completar "Nombre del propietario/administrador" con un valor de ejemplo (ej.
   "Carlos Mendoza — Nicson Plaza") y guardar.
2. Tener a mano un recibo ya emitido con al menos alquiler + 2 gastos fijos, y otro recibo anulado.

## Escenario 1 — Leer el comprobante de un vistazo, de arriba hacia abajo (US1)

1. Abrir el comprobante de un recibo pagado.
2. Verificar que aparecen, en este orden y separados entre sí (línea o espacio consistente): encabezado
   (logo + "Recibo de Pago"), metadatos (N.° de recibo, fecha de emisión, período, estado), partes (Recibí
   de / Recibido por / Locación), detalle de conceptos, total, cierre.
3. Contar los tamaños/pesos de fuente distintos usados en el documento — no debe haber más de 3 niveles
   (título, texto base, total).

## Escenario 2 — Identificar el total de inmediato (US2)

1. Con el comprobante abierto, mirarlo por 1-2 segundos sin leer el detalle.
2. Confirmar que el monto total es lo primero que se identifica, por su fondo de color, tamaño y peso —
   claramente por encima de cualquier monto individual de los conceptos.

## Escenario 3 — Verificar el detalle de conceptos (US3)

1. Abrir el comprobante de un recibo con alquiler + varios gastos fijos.
2. Confirmar que cada concepto (alquiler y cada gasto fijo) aparece en su propia línea, con su propio
   monto — ninguno combinado con otro.
3. Confirmar que todos los montos del documento (metadatos si aplica, conceptos, total) quedan alineados en
   la misma columna vertical a la derecha.

## Casos límite a revisar

- Abrir el comprobante de un recibo sin monto de alquiler (solo gastos fijos) — no debe aparecer una línea
  vacía de "Alquiler".
- Sin haber configurado "Nombre del propietario" en Configuración General (o volviéndolo a vaciar
  temporalmente), abrir un comprobante y confirmar que la fila "Recibido por" no aparece y el resto del
  documento se ve igual de completo (spec.md FR-005a) — luego restaurar el valor configurado.
- Abrir el comprobante de un recibo anulado y confirmar que la marca "Anulado" se sigue viendo con claridad
  y no se superpone con el bloque de total.
- Editar temporalmente el nombre de la locación o del inquilino por un texto largo y confirmar que no
  desalinea la columna de montos ni rompe el layout — luego revertir.
- Repetir el Escenario 1 en la vista previa de impresión (Ctrl+P) y en la captura para WhatsApp ("Enviar
  por WhatsApp") — ambas deben seguir produciendo un resultado completo y legible (spec.md FR-013).

## Regresión a confirmar

- `php artisan test` completo sigue en verde (línea base: 316/316 antes de esta feature).
- Los recibos ya existentes (creados antes de configurar "Nombre del propietario") siguen mostrando un
  comprobante válido, solo sin la fila "Recibido por".
