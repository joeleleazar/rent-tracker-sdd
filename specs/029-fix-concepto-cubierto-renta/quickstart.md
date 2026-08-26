# Quickstart: Corregir Cobertura de Conceptos y Edición de Renta en Recibos

Escenarios de validación manual, a correr tras implementar. Usar el binario de PHP de Herd:
`C:\Users\joel5\.config\herd\bin\php.bat`.

## Escenario 1 — Editar el monto de Renta de un recibo ya emitido (US1)

1. Abrir el detalle de un recibo que incluye Renta y hacer clic en "Editar Recibo".
2. Verificar que "Incluir Renta" aparece marcado, con su monto actual, en el mismo formulario que los demás
   conceptos.
3. Cambiar el monto de Renta y guardar — verificar que el recibo actualizado refleja el nuevo monto.
4. Volver a editar ese mismo recibo y desmarcar "Incluir Renta" — guardar y verificar que el recibo ya no
   incluye Renta ni su monto en el total.
5. Para un recibo que NO incluye Renta (de una locación/periodo donde ningún otro recibo la cubre),
   verificar que "Incluir Renta" sigue apareciendo disponible para agregarla (sin cambios respecto a hoy).

## Escenario 2 — Los badges de conceptos solo marcan cobertura vigente (US2)

1. Crear un concepto de prueba y configurarle un valor de referencia en el contrato de una locación, sin
   incluirlo en ningún recibo.
2. Abrir `/recibos/registro-masivo` para el periodo de esa locación y verificar que el concepto se muestra
   disponible (badge claro, sin check), no cubierto.
3. Generar un recibo que lo incluya — verificar que pasa a mostrarse cubierto (badge oscuro con check,
   enlazando a ese recibo).
4. Anular ese recibo — verificar que el concepto vuelve a mostrarse disponible de inmediato.
5. Repetir el paso 2 específicamente para Renta, en una locación/periodo donde ningún recibo vigente la
   cubra.

**Verificación manual ya realizada (2026-08-26, navegador real contra `rent_tracker_dev`)**: ambos
escenarios se recorrieron en vivo con resultado exitoso.

- **Escenario 1**: `/recibos/1/editar` (Local 101, recibo que ya incluía Renta en S/800.00) mostró
  "Incluir Renta" marcado y editable, junto al resto de conceptos. Se cambió el monto a S/825.00 y se
  guardó — "Recibo actualizado correctamente.", con "Monto de Renta: S/ 825.00" y "Total: S/ 960.75"
  reflejados en el detalle.
- **Escenario 2**: `/recibos/registro-masivo` (agosto 2026) mostró "Internet" con el badge claro de
  "disponible" (sin check) tanto para Local 101 como Local 102, mientras Renta/Agua/Luz/Luz de
  Pasadizo/Seguridad se mostraron correctamente cubiertos — confirmando que el invariante de specs/026 ya
  se cumple contra los datos actuales, tal como predijo research.md Decisión 2.
