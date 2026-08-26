# Feature Specification: Mejoras al Flujo de Recibos y Lecturas

**Feature Branch**: `026-mejoras-recibos-lecturas`

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "En el menu del registro de registro de lecturas, Integra en la misma fila la tarifa por KwH, la navegacion de los meses y los botones para exportar, corrige como se muestran los conceptos ya utilizados ya que el local 101 tiene recibos anulados pero sus conceptos siguen apareciendo como si lo estuvieran, en el menu de "Emitir recibos" te de acceso a ver los recibos generados dicho boton debe ser visible ademas, te debe redirigir a la lista de recibos generados, la vista debe mostrar una lista de recibos en caso sean varios y te permita escoger cual ver y solo es uno directamente ese, que ya no sea un modal y te permita guardar el borrador, debe quedar claro en esa vista de generar el recibo, completa la funcionalidad para eliminar un concepto de gasto fijo"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Un recibo anulado deja de bloquear su periodo (Priority: P1)

Un administrador anula un recibo por error de registro. Al volver a la pantalla de emisión masiva de
recibos, espera que los conceptos que ese recibo cubría (renta, agua, luz, etc.) vuelvan a mostrarse como
disponibles para emitir un recibo nuevo y correcto — no como si ya estuvieran cubiertos por un recibo que
ya no es válido.

**Why this priority**: Es un defecto de integridad de datos visible para el usuario ahora mismo (Local 101
tiene un recibo anulado cuyos conceptos siguen marcados como cubiertos), bloquea la operación normal
(no se puede volver a emitir un recibo correcto para ese periodo) y su corrección es autocontenida: no
depende de ninguna otra historia de este documento.

**Independent Test**: Anular el único recibo de una locación para un periodo dado y verificar que, en la
pantalla de emisión masiva, esa locación vuelve a mostrar todos sus conceptos como disponibles y permite
generar un recibo nuevo que los cubra.

**Acceptance Scenarios**:

1. **Given** una locación cuyo único recibo del periodo visible fue anulado, **When** el administrador abre
   la pantalla de emisión masiva de recibos, **Then** los conceptos de ese recibo anulado se muestran como
   disponibles (no como cubiertos) y el botón para generar un recibo nuevo está habilitado.
2. **Given** una locación con dos recibos del mismo periodo (uno vigente y otro anulado, cubriendo
   conceptos distintos), **When** el administrador revisa esa fila, **Then** solo los conceptos del recibo
   vigente se muestran como cubiertos; los del recibo anulado se muestran como disponibles.
3. **Given** un concepto del catálogo cuyos únicos recibos que lo usan están todos anulados, **When** el
   administrador intenta eliminarlo desde "Conceptos de Gasto Fijo", **Then** el sistema ya no lo considera
   "en uso" por esos recibos anulados y permite eliminarlo (si tampoco está configurado en ningún contrato).
4. **Given** el conteo y total del periodo por locación (introducidos en la funcionalidad de catálogo
   dinámico de conceptos), **When** un recibo se anula, **Then** ese conteo y total siguen excluyendo al
   recibo anulado (comportamiento ya existente, no debe romperse con este cambio).

---

### User Story 2 - Generar un recibo en una vista propia, con borrador guardable (Priority: P2)

Un administrador está generando el recibo de una locación desde la pantalla de emisión masiva. Necesita
revisar con calma qué conceptos incluir y sus montos, quizás interrumpir la tarea y volver más tarde sin
perder lo que ya marcó, y tener claridad total de para qué locación y periodo está generando el recibo.
Hoy esto ocurre en una ventana emergente (modal) que no permite guardar avances a medio llenar.

**Why this priority**: Es el cambio de mayor esfuerzo de este conjunto y la razón principal por la que el
usuario pidió esta feature; sin embargo depende conceptualmente de que los conceptos disponibles ya se
calculen correctamente (User Story 1), por lo que se prioriza justo después.

**Independent Test**: Desde la pantalla de emisión masiva, iniciar la generación de un recibo para una
locación con conceptos disponibles; verificar que se navega a una página propia (no aparece como ventana
emergente superpuesta), que la locación y el periodo son evidentes en esa página, que se puede guardar el
progreso como borrador sin completar el recibo, y que al volver a esa misma página el borrador guardado
se recupera.

**Acceptance Scenarios**:

1. **Given** una locación con conceptos disponibles para el periodo visible, **When** el administrador
   inicia la generación de su recibo, **Then** el sistema navega a una página dedicada a esa acción (no una
   ventana emergente) que indica claramente la locación y el periodo para los que se está generando el
   recibo.
2. **Given** esa página de generación de recibo, **When** el administrador marca algunos conceptos y sus
   montos pero no finaliza, **Then** puede guardar ese avance como borrador y salir sin perder la
   información ingresada.
3. **Given** un borrador guardado previamente para una locación y periodo, **When** el administrador vuelve
   a abrir la generación de recibo para esa misma locación y periodo, **Then** encuentra sus marcas y
   montos previamente guardados, listos para continuar o corregir.
4. **Given** un borrador con al menos un concepto marcado, **When** el administrador confirma la emisión,
   **Then** el recibo se genera con lo indicado y el borrador correspondiente se descarta (deja de
   aparecer como pendiente).
5. **Given** una locación sin contrato activo vigente para el periodo, o con todos sus conceptos ya
   cubiertos, **When** el administrador intenta generar su recibo, **Then** la página de generación lo
   comunica con claridad y no permite continuar, igual que ocurre hoy en la ventana emergente.

---

### User Story 3 - Ver los recibos ya generados de una locación y periodo (Priority: P3)

Desde la pantalla de emisión masiva de recibos, un administrador quiere confirmar qué se emitió realmente
para una locación en el periodo que está viendo, sin tener que ir concepto por concepto adivinando a qué
recibo pertenece cada uno.

**Why this priority**: Mejora de navegación que depende de tener datos correctos de cobertura (User
Story 1) para ser útil, pero es independiente de si la generación es modal o página propia (User Story 2).

**Independent Test**: Para una locación con al menos un recibo generado en el periodo visible, hacer clic
en la acción de ver sus recibos y comprobar que: si hay exactamente un recibo, se muestra directamente su
detalle; si hay más de uno, se presenta primero una lista para elegir cuál ver.

**Acceptance Scenarios**:

1. **Given** una locación sin ningún recibo en el periodo visible, **When** el administrador revisa esa
   fila en la pantalla de emisión masiva, **Then** no se ofrece una acción para "ver recibos" (no hay nada
   que ver todavía).
2. **Given** una locación con exactamente un recibo en el periodo visible, **When** el administrador hace
   clic en la acción de ver sus recibos, **Then** es llevado directamente al detalle de ese recibo.
3. **Given** una locación con más de un recibo en el periodo visible (por ejemplo uno que cubre renta y
   otro que cubre el resto de conceptos), **When** el administrador hace clic en la acción de ver sus
   recibos, **Then** se le presenta una lista con los recibos de esa locación y periodo, para elegir cuál
   abrir.
4. **Given** que la acción de ver recibos existe para una fila, **When** el administrador mira la tabla,
   **Then** esa acción es visible directamente en la fila (no está escondida detrás de otro menú).

---

### User Story 4 - Barra de herramientas del registro de lecturas en una sola fila (Priority: P4)

Un administrador registrando lecturas de medidor quiere tener a la mano, sin desplazarse por la pantalla,
la tarifa vigente por kWh, la navegación entre periodos y los botones para exportar el registro — hoy
estos controles están repartidos en distintas filas/bloques.

**Why this priority**: Es un ajuste de disposición visual, sin lógica de negocio nueva ni dependencias con
el resto de historias; se prioriza al final por ser el de menor riesgo e impacto funcional.

**Independent Test**: Abrir la pantalla de registro masivo de lecturas y verificar que la tarifa por kWh,
los controles de navegación de mes y los botones de exportar están todos visibles en una misma fila de la
barra de herramientas, sin necesidad de desplazamiento adicional entre ellos.

**Acceptance Scenarios**:

1. **Given** la pantalla de registro masivo de lecturas, **When** el administrador la abre en un ancho de
   pantalla de escritorio habitual, **Then** la tarifa por kWh, la navegación de periodo y los botones de
   exportar aparecen en la misma fila de controles.
2. **Given** esa misma pantalla en una ventana angosta, **When** los controles no caben todos en una fila,
   **Then** se reorganizan de forma legible (por ejemplo, apilándose) sin solaparse ni perder
   funcionalidad.

---

### User Story 5 - Completar la eliminación de un concepto de gasto fijo (Priority: P5)

Un administrador creó un concepto de gasto fijo que ya no necesita (por ejemplo, uno de prueba) y quiere
eliminarlo del catálogo por completo, no solo desactivarlo, siempre que nada dependa realmente de él.

**Why this priority**: La mayor parte de esta funcionalidad ya existe; lo que falta es el ajuste menor de
que el conteo de "en uso" no considere recibos anulados (cubierto por User Story 1). Se documenta como
historia propia para que quede explícitamente verificada de punta a punta.

**Independent Test**: Crear un concepto nuevo, no usarlo en ningún contrato ni recibo vigente, y
eliminarlo desde "Conceptos de Gasto Fijo"; verificar que desaparece del catálogo. Luego, para un concepto
cuyo único uso está en recibos anulados, verificar que también puede eliminarse.

**Acceptance Scenarios**:

1. **Given** un concepto de gasto fijo no protegido y sin ningún uso (ni en contratos ni en recibos
   vigentes), **When** el administrador confirma su eliminación, **Then** el concepto desaparece
   permanentemente del catálogo.
2. **Given** un concepto de gasto fijo configurado en al menos un contrato o incluido en al menos un
   recibo no anulado, **When** el administrador intenta eliminarlo, **Then** el sistema lo impide con un
   mensaje explícito indicando que está en uso, y ofrece desactivarlo en su lugar.
3. **Given** un concepto protegido ("Renta" o "Luz"), **When** el administrador intenta eliminarlo,
   **Then** el sistema lo impide igual que hoy, sin excepción.

---

### Edge Cases

- Un recibo con estado "pendiente" o "pagado" sigue contando como cobertura vigente de sus conceptos; solo
  "anulado" deja de contar.
- Si dos administradores generan un recibo para la misma locación y periodo al mismo tiempo, la validación
  de conceptos superpuestos (ya existente) sigue aplicando en el momento de confirmar, incluso si ambos
  partieron de la misma vista de generación con un borrador guardado.
- Un borrador guardado que queda "huérfano" porque, mientras tanto, otro recibo ya cubrió alguno de sus
  conceptos marcados, debe advertírselo al usuario al intentar confirmar la emisión (reutilizando el mismo
  mensaje de conceptos ya cubiertos que existe hoy), en vez de fallar en silencio o duplicar cobertura.
- Un borrador sin ningún concepto marcado no debe poder confirmarse como recibo (misma regla que ya existe
  hoy para el flujo actual: al menos un concepto).
- Los borradores de generación de recibo son por usuario, locación y periodo: si dos administradores
  distintos generan el recibo de la misma locación y periodo, cada uno ve y guarda su propio borrador sin
  pisar el del otro.
- Al eliminar un concepto de gasto fijo, sus posibles referencias en recibos anulados no bloquean la
  eliminación, pero tampoco se borran esas filas históricas — el recibo anulado conserva su registro
  para fines de auditoría; solo deja de contar como "uso activo".

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema NO DEBE considerar un recibo con estado "anulado" como cobertura vigente de sus
  conceptos, en ningún cálculo de disponibilidad de conceptos para un periodo y locación dados.
- **FR-002**: La pantalla de emisión masiva de recibos DEBE reflejar el cambio de FR-001: los conceptos de
  un recibo anulado deben mostrarse como disponibles, no como cubiertos.
- **FR-003**: El sistema DEBE seguir permitiendo generar un recibo nuevo que cubra los mismos conceptos que
  antes cubría un recibo ahora anulado, para la misma locación y periodo.
- **FR-004**: El conteo de "en uso" que bloquea la eliminación de un concepto de gasto fijo NO DEBE incluir
  referencias provenientes de recibos anulados.
- **FR-005**: El conteo de recibos y el total facturado por locación y periodo (introducidos previamente)
  DEBEN seguir excluyendo los recibos anulados; este cambio no debe alterar ese comportamiento existente.
- **FR-006**: El sistema DEBE ofrecer una acción para generar el recibo de una locación como una página
  propia, en vez de una ventana emergente superpuesta sobre la pantalla de emisión masiva.
- **FR-007**: Esa página de generación de recibo DEBE mostrar de forma inequívoca para qué locación y
  periodo se está generando el recibo.
- **FR-008**: Esa página DEBE mostrar, para el periodo, el contrato activo (o su ausencia), los conceptos
  disponibles con su monto sugerido, y los conceptos ya cubiertos por otros recibos del mismo periodo,
  igual que hoy hace la ventana emergente.
- **FR-009**: El sistema DEBE permitir guardar el estado en progreso de esa página (conceptos marcados y
  montos ingresados) como un borrador, sin exigir que el recibo esté completo ni que se confirme la
  emisión.
- **FR-010**: El sistema DEBE recuperar un borrador previamente guardado para la misma locación, periodo y
  usuario, la próxima vez que se abra esa página de generación.
- **FR-011**: Al confirmarse la emisión de un recibo a partir de un borrador, el sistema DEBE descartar ese
  borrador (deja de estar disponible para recuperarse).
- **FR-012**: Un borrador DEBE ser propio de cada combinación de usuario, locación y periodo — no debe ser
  visible ni editable por otro usuario, ni mezclarse con el borrador de otra locación o periodo.
- **FR-013**: Si, al confirmar la emisión desde un borrador, alguno de sus conceptos marcados ya fue
  cubierto por otro recibo mientras tanto, el sistema DEBE rechazar la confirmación con un mensaje claro,
  igual que hace hoy la validación de conceptos superpuestos, sin generar el recibo ni descartar el
  borrador.
- **FR-014**: La pantalla de emisión masiva de recibos DEBE ofrecer, por cada locación con al menos un
  recibo en el periodo visible, una acción visible para ver sus recibos generados.
- **FR-015**: Al activar esa acción, si la locación tiene exactamente un recibo en el periodo visible, el
  sistema DEBE llevar directamente al detalle de ese recibo.
- **FR-016**: Al activar esa acción, si la locación tiene más de un recibo en el periodo visible, el
  sistema DEBE presentar primero una lista de esos recibos para que el usuario elija cuál ver.
- **FR-017**: La pantalla de registro masivo de lecturas DEBE presentar la tarifa por kWh, la navegación de
  periodo (mes anterior/siguiente y selector) y los botones de exportación dentro de una misma fila de
  controles.
- **FR-018**: En anchos de pantalla reducidos, esos mismos controles DEBEN seguir siendo utilizables
  (reorganizándose de forma legible) sin perder ninguna de sus funciones.
- **FR-019**: El sistema DEBE permitir eliminar por completo (no solo desactivar) un concepto de gasto
  fijo no protegido que no tenga ningún uso activo, tal como ya ocurre hoy, y esa capacidad DEBE seguir
  funcionando tras aplicar FR-004.

### Key Entities *(include if feature involves data)*

- **Borrador de Recibo**: Representa el avance no confirmado de la generación de un recibo para una
  locación y periodo específicos, asociado a un único usuario. Contiene qué conceptos están marcados para
  incluirse y el monto propuesto para cada uno. Es transitorio: se descarta automáticamente al confirmarse
  la emisión del recibo correspondiente. Es análogo al borrador ya existente para el registro masivo de
  lecturas, pero aplicado a la generación de recibos.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Después de anular el único recibo de una locación y periodo, el 100% de sus conceptos vuelve
  a mostrarse como disponible en la pantalla de emisión masiva, sin recargar datos manualmente.
- **SC-002**: Un administrador puede iniciar la generación de un recibo, guardar un borrador, salir de la
  pantalla, volver más tarde y retomarlo exactamente donde lo dejó, sin perder ningún concepto ni monto
  ingresado.
- **SC-003**: Desde la pantalla de emisión masiva, un administrador puede llegar al detalle de cualquier
  recibo ya generado de una locación en el periodo visible en dos clics o menos.
- **SC-004**: En la pantalla de registro masivo de lecturas, un administrador puede ver simultáneamente la
  tarifa vigente, navegar de periodo y exportar sin necesidad de desplazarse verticalmente entre esos
  controles, en una resolución de escritorio habitual.
- **SC-005**: Un concepto de gasto fijo sin uso activo (incluyendo el caso en que su único uso previo esté
  en recibos ya anulados) puede eliminarse exitosamente en el 100% de los intentos.

## Assumptions

- El botón para "ver recibos generados" (User Story 3) se ofrece por cada fila de locación en la propia
  pantalla de emisión masiva, acotado al periodo actualmente visible — no se introduce una pantalla nueva
  de búsqueda global de recibos de todas las locaciones y todos los periodos a la vez. El historial
  completo por locación (todos los periodos) que ya existe hoy en la ficha de cada locación no se modifica.
- "Guardar el borrador" (User Story 2) sigue el mismo patrón ya establecido en el registro masivo de
  lecturas: un registro transitorio por usuario, ligado a la locación y el periodo, que se descarta al
  confirmarse la operación final. No se introduce un nuevo estado "borrador" en el propio recibo emitido;
  los estados de un recibo emitido (pendiente/pagado/anulado) no cambian.
- La página dedicada de generación de recibo (User Story 2) reemplaza únicamente el flujo de generación
  usado desde la pantalla de emisión masiva (hoy una ventana emergente); no se exige fusionar o eliminar
  el flujo de creación individual de recibo que ya existe por locación, aunque ambos deberían sentirse
  consistentes entre sí.
- Anular un recibo sigue sin eliminar sus filas de detalle (conceptos, montos); solo deja de contarlo como
  cobertura vigente, conteo o total. El recibo anulado sigue siendo visible en el historial y su
  comprobante sigue mostrando la marca de "anulado", sin cambios respecto al comportamiento actual.
- El ajuste de disposición de la barra de herramientas de lecturas (User Story 4) es puramente visual/de
  layout; no cambia ninguna de las funciones existentes de exportar, navegar de periodo o editar la
  tarifa.
- La funcionalidad de eliminar un concepto de gasto fijo (User Story 5) ya existe en su mayor parte; el
  único vacío identificado es que el conteo de "en uso" contaba referencias de recibos anulados, cubierto
  por FR-004. No se ha identificado ningún otro caso en que la eliminación esté ausente o rota.
