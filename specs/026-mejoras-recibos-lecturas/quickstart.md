# Quickstart: Mejoras al Flujo de Recibos y Lecturas

Escenarios de validación manual, a correr tras implementar. Usar el binario de PHP de Herd:
`C:\Users\joel5\.config\herd\bin\php.bat`.

**Verificación manual ya realizada (2026-08-26, navegador real contra `rent_tracker_dev` con
`claude-in-chrome`)**: los 5 escenarios se recorrieron en vivo con resultado exitoso:

- **Escenario 1**: se anuló el recibo #4 (Local 101, "Internet") y `/recibos/registro-masivo` volvió a
  mostrar "Internet" como disponible de inmediato, con "Generar Recibo" habilitado otra vez; el conteo bajó
  de "1 recibo · S/935.75+77" a "1 recibo · S/935.75" (excluyendo el anulado, regresión de specs/024
  intacta).
- **Escenario 2**: "Generar Recibo" navegó a `/locaciones/4/recibos/crear?periodo=2026-08` (página propia,
  no modal), con la locación y el periodo evidentes en el encabezado. Se editó el monto de "Internet" a 77,
  se guardó el borrador ("Borrador guardado a las 14:15."), se navegó fuera y al volver se recuperó
  automáticamente ("Se recuperó un borrador guardado..."). Se confirmó la emisión (Recibo #4, S/77.00).
- **Escenario 3**: tras el paso anterior, Local 101 pasó a "Periodo completo" con acción "Ver Recibos"
  visible; con 2 recibos del periodo (uno pagado con Renta+Agua+Luz+Pasadizo+Seguridad, otro pendiente con
  Internet) mostró la lista de elección; para Local 102 (1 solo recibo) redirigió directo al detalle.
- **Escenario 4**: `/lecturas/registro-masivo` muestra tarifa por kWh, navegación de periodo (flechas +
  selector + "Ir") y los dos botones de exportar todos en la misma fila de controles.
- **Escenario 5**: se creó un concepto de prueba ("Prueba Eliminacion"), se incluyó en un recibo nuevo
  (Local 102, septiembre 2026, monto S/0) y se anuló ese recibo — el catálogo mostró "0 registros" en uso y
  el botón "Eliminar" habilitado; la eliminación se confirmó con éxito ("Concepto eliminado
  correctamente."), y el recibo #5 (histórico, anulado) que lo referenciaba siguió mostrándose sin errores,
  con la línea "Monto de concepto eliminado" en vez de romperse — confirma en producción real el hallazgo
  de la FK documentado en `research.md` (Decisión 2). Por separado, se confirmó que "Internet" (con un
  valor de referencia configurado en el contrato de Local 101) sigue correctamente bloqueado para
  eliminar ("Internet" en uso en 1 registro — el del contrato, no el del recibo anulado), verificando que
  el fix no relaja de más la protección.

## Escenario 1 — Un recibo anulado deja de bloquear su periodo (US1, SC-001)

1. En `/recibos/registro-masivo`, para una locación con un único recibo en el periodo visible, anotar sus
   conceptos cubiertos (badges oscuros con check).
2. Anular ese recibo (`recibos.estado.update` → "Anulado").
3. Recargar `/recibos/registro-masivo` en ese mismo periodo — verificar que todos los conceptos de esa
   locación vuelven a mostrarse como disponibles (no cubiertos) y que "Generar Recibo" está habilitado.
4. Generar un recibo nuevo para esa locación cubriendo los mismos conceptos que el anulado — verificar que
   se genera sin el error "conceptos ya cubiertos".
5. Ir a "Conceptos de Gasto Fijo": para un concepto cuyo único uso en recibos es el recibo recién anulado
   (y sin uso en ningún contrato), verificar que ahora puede eliminarse.
6. Verificar que el conteo/total de recibos por locación (columna "Total del Periodo") sigue excluyendo el
   recibo anulado (regresión de specs/024).

## Escenario 2 — Generar un recibo en una página propia con borrador (US2, SC-002)

1. En `/recibos/registro-masivo`, hacer clic en "Generar Recibo" de una locación con conceptos
   disponibles — verificar que navega a una página propia (`/locaciones/{id}/recibos/crear?periodo=...`),
   no una ventana emergente, y que la locación y el periodo son evidentes en el encabezado.
2. Marcar algunos conceptos y montos, sin enviar el formulario. Hacer clic en "Guardar Borrador" —
   verificar la confirmación visible ("Borrador guardado a las...").
3. Salir de la página (navegar a otra pantalla) y volver a abrir la generación de recibo para la misma
   locación y periodo — verificar que los conceptos y montos marcados siguen ahí.
4. Completar y confirmar la emisión — verificar que el recibo se crea con lo indicado y que, al volver a
   abrir la generación para esa misma locación/periodo (si quedan conceptos disponibles), ya no aparece el
   borrador anterior (fue descartado).
5. Repetir el paso 1-2 pero dejar pasar más de 2 minutos sin guardar manualmente — verificar que el
   autoguardado silencioso también deja un borrador recuperable.
6. Para una locación sin contrato activo o con todos los conceptos ya cubiertos, verificar que la página
   lo comunica con claridad y no ofrece un formulario para enviar.

## Escenario 3 — Ver los recibos generados de una locación y periodo (US3, SC-003)

1. Para una locación sin ningún recibo en el periodo visible, verificar que la fila no ofrece una acción
   "Ver Recibos".
2. Para una locación con exactamente un recibo en el periodo visible, hacer clic en "Ver Recibos" —
   verificar que va directo al detalle de ese recibo.
3. Generar un segundo recibo para la misma locación y periodo (cubriendo un concepto distinto al primero).
   Hacer clic en "Ver Recibos" — verificar que ahora se presenta una lista con ambos recibos, cada uno con
   enlace a su detalle.

## Escenario 4 — Barra de herramientas de lecturas en una sola fila (US4, SC-004)

1. Abrir `/lecturas/registro-masivo` en una ventana de escritorio habitual — verificar que la tarifa por
   kWh, la navegación de periodo y los botones de exportar están todos en la misma fila de controles.
2. Reducir el ancho de la ventana (o simular un viewport angosto) — verificar que los controles se
   reorganizan de forma legible, sin solaparse, y que todos siguen siendo utilizables.

## Escenario 5 — Eliminar un concepto de gasto fijo (US5, SC-005)

1. Crear un concepto de prueba sin usarlo en ningún contrato ni recibo. Eliminarlo desde "Conceptos de
   Gasto Fijo" — verificar que desaparece del catálogo.
2. Para un concepto configurado en al menos un contrato vigente, verificar que el intento de eliminarlo es
   rechazado con un mensaje explícito, y que "Desactivar" sigue disponible como alternativa.
3. Repetir el Escenario 1 paso 5 (concepto cuyo único uso está en recibos anulados) como caso adicional de
   este escenario.
