# Feature Specification: Corrección de Exportación, Cambio de Periodo e Ícono de Edición en Registro Masivo

**Feature Branch**: `020-correccion-exportar-periodo-icono`

**Created**: 2026-08-25

**Status**: Draft

**Input**: User description: "no funcionan los botones de exportar pdf y exportar a excel, tampoco funciona bien la opcion de cambiar de periodo por lo que si por ejemplo estoy en agosto y me voy a septiembre los valores actuales no se vuelven los valores anteriores, el tooltip del icono en cada lectura individual se bugea visualmente cuando la haces clic se qieda abierto por que la celda se volvio un input, mejor dejalo como icono y agrega otro que indique especificamente que vas a editarlo"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Confiar en la lectura del periodo anterior al cambiar de periodo (Priority: P1)

Un usuario que cambia el periodo seleccionado en el registro masivo (por ejemplo, de agosto a
septiembre) necesita que la columna "Lectura Periodo Anterior" de cada locación muestre la lectura
real del periodo inmediatamente anterior al recién seleccionado — en este ejemplo, los valores que
eran la "lectura actual" de agosto deben pasar a ser la "lectura anterior" mostrada al ver
septiembre. Hoy ese cambio no refleja correctamente esa relación, lo que le hace perder la
referencia contra la cual detectar un error de tipeo al completar el nuevo periodo.

**Why this priority**: Es la funcionalidad que sostiene la verificación visual central de toda la
pantalla (specs/015 US2, specs/016 US1) — si cambiar de periodo no actualiza la referencia
correctamente, cada visita a un periodo distinto del que se abrió primero queda con una salvaguarda
rota, no solo un caso aislado.

**Independent Test**: Registrar lecturas para una locación en un periodo (ej. agosto), cambiar el
selector de periodo al mes siguiente (septiembre), y verificar que la "Lectura Periodo Anterior"
mostrada para esa locación es exactamente el valor que se registró como lectura actual de agosto.

**Acceptance Scenarios**:

1. **Given** una locación con una lectura ya registrada en el periodo actualmente visible, **When**
   el usuario cambia el selector de periodo al mes inmediatamente siguiente, **Then** la "Lectura
   Periodo Anterior" de esa locación en la nueva vista es exactamente el valor de la lectura que
   acababa de ver como "lectura actual" en el periodo anterior.
2. **Given** el usuario alterna repetidamente entre dos periodos distintos, **When** cada cambio se
   completa, **Then** la referencia de "Lectura Periodo Anterior" mostrada corresponde siempre al
   periodo recién seleccionado, nunca a un periodo intermedio o al que estaba antes del cambio.

---

### User Story 2 - Exportar el registro masivo a Excel o PDF (Priority: P2)

Un usuario que necesita compartir o archivar el registro de lecturas de un periodo hace clic en
"Exportar a Excel" o "Exportar a PDF" y espera que el archivo correspondiente se descargue. Hoy
ninguno de los dos botones produce una descarga.

**Why this priority**: Es una vía secundaria de valor (la información también es visible en
pantalla), pero al estar completamente inoperante dos funcionalidades ya prometidas (specs/015
FR-016) quedan inutilizables.

**Independent Test**: Con al menos una locación con datos en el periodo visible, hacer clic en
"Exportar a Excel" y verificar que se descarga un archivo `.xlsx`; repetir con "Exportar a PDF" y
verificar la descarga de un archivo `.pdf`.

**Acceptance Scenarios**:

1. **Given** el usuario está en la pantalla de registro masivo de un periodo con datos, **When**
   hace clic en "Exportar a Excel", **Then** el navegador descarga un archivo `.xlsx` con el
   contenido de ese periodo.
2. **Given** la misma situación, **When** hace clic en "Exportar a PDF", **Then** el navegador
   descarga un archivo `.pdf` con el mismo contenido.

---

### User Story 3 - Distinguir el ícono de "completada" del control de editar, sin tooltips atascados (Priority: P3)

Un usuario que pasa el cursor o hace clic sobre el ícono verde de una lectura ya registrada
necesita que ese ícono siga siendo un indicador visual claro de "esta lectura ya está registrada",
y necesita un control aparte, específico, para iniciar la edición de esa lectura — sin que el
tooltip de ayuda quede visualmente pegado en pantalla después de hacer clic (hoy ocurre porque la
celda entera se reemplaza por un campo editable sin cerrar antes ese tooltip).

**Why this priority**: Es un defecto visual molesto pero no bloqueante — el usuario puede seguir
editando la lectura a pesar del tooltip atascado; por eso su prioridad es menor a las otras dos
correcciones de esta especificación.

**Independent Test**: Ver una fila con una lectura ya completada, pasar el cursor sobre el ícono de
completada (debe mostrar solo información, sin iniciar edición), y hacer clic en el control de
editar (distinto del ícono de completada) — la fila debe pasar a modo edición sin dejar ningún
tooltip visible en pantalla.

**Acceptance Scenarios**:

1. **Given** una locación con una lectura ya registrada, **When** el usuario ve su fila, **Then**
   el ícono verde de "completada" y el control para editar esa lectura son dos elementos visuales
   distintos, cada uno con su propia indicación de qué hace.
2. **Given** esa misma fila, **When** el usuario hace clic en el control de editar, **Then** la
   celda cambia a modo edición y ningún tooltip queda visible en pantalla después del clic.
3. **Given** el ícono de "completada", **When** el usuario pasa el cursor sobre él sin hacer clic,
   **Then** se comporta como un indicador informativo (no dispara la edición).

---

### Edge Cases

- ¿Qué pasa si el usuario cambia de periodo antes de que la lectura del periodo anterior termine de
  cargar? La vista debe reflejar siempre el periodo del último cambio solicitado, no una respuesta
  desactualizada de un cambio anterior.
- ¿Qué pasa si se exporta un periodo sin ninguna lectura registrada? Debe descargarse igual un
  archivo válido (vacío o solo con encabezados), no fallar silenciosamente ni quedar sin respuesta
  visible para el usuario.
- ¿Qué pasa con el tooltip del nuevo control de editar cuando la fila ya está en modo edición? Deja
  de mostrarse (el control de editar ya no está visible en ese estado), consistente con que ya
  existen los botones de guardar/cancelar propios del modo edición.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Al cambiar el periodo seleccionado en el registro masivo, el sistema DEBE recalcular
  y mostrar, para cada locación, la "Lectura Periodo Anterior" correspondiente al periodo
  recién seleccionado — el valor que era "lectura actual" del periodo inmediatamente anterior al
  nuevo periodo, cuando exista.
- **FR-002**: El botón "Exportar a Excel" DEBE iniciar la descarga de un archivo `.xlsx` con el
  contenido del periodo visible al hacer clic.
- **FR-003**: El botón "Exportar a PDF" DEBE iniciar la descarga de un archivo `.pdf` con el
  contenido del periodo visible al hacer clic.
- **FR-004**: El sistema DEBE mostrar el ícono verde de "lectura completada" como un indicador
  puramente visual, sin ninguna acción de clic asociada a él.
- **FR-005**: El sistema DEBE ofrecer, para cada lectura ya completada, un control adicional y
  distinto del ícono de "completada" cuya única función sea iniciar la edición de esa lectura, con
  su propia indicación accesible de que esa es la acción que realiza.
- **FR-006**: Al iniciar la edición de una lectura completada, el sistema NO DEBE dejar ningún
  tooltip visualmente abierto en pantalla una vez que la celda pasa a modo edición.

### Key Entities *(include if feature involves data)*

- **Lectura de Medidor**: entidad ya existente (specs/005, specs/015-019); esta corrección no
  cambia su forma ni sus datos, solo la fiabilidad con la que la pantalla de registro masivo
  recalcula su referencia de periodo anterior al cambiar de periodo, exporta su contenido, y
  presenta el control para editarla.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los cambios de periodo en el registro masivo muestran, para cada
  locación, la lectura real del periodo inmediatamente anterior al recién seleccionado.
- **SC-002**: El 100% de los clics en "Exportar a Excel" y "Exportar a PDF" inician la descarga del
  archivo correspondiente.
- **SC-003**: En una revisión visual de la pantalla, cero tooltips quedan visibles después de
  iniciar la edición de cualquier lectura completada.
- **SC-004**: Un usuario puede identificar, sin ambigüedad y sin hacer clic, cuál ícono indica
  "ya registrada" y cuál control inicia la edición.

## Assumptions

- El diagnóstico de la causa raíz de cada uno de los tres defectos (por qué la exportación no
  descarga, por qué el cambio de periodo no refleja la lectura anterior correcta, y por qué el
  tooltip queda atascado) se determina durante la fase de planificación/implementación, no en esta
  especificación — consistente con el mismo criterio ya usado en
  specs/016-correccion-registro-masivo-lecturas.
- El nuevo control de "editar" reutiliza el ícono ya establecido para esa acción en el resto de la
  aplicación (`bi-pencil-square`, según la convención de iconografía documentada en la constitución
  del proyecto), en vez de introducir un ícono nuevo sin precedente.
- Esta corrección no cambia el contenido ni el formato de los archivos exportados (specs/015
  FR-016), ni el comportamiento ya existente de guardar/cancelar una edición en línea (specs/015
  FR-005/FR-017) — solo corrige que la descarga se inicie, que la referencia de periodo anterior
  sea correcta, y que el disparador de edición esté separado del ícono informativo.
- El ícono de "completada" conserva una indicación informativa propia (por ejemplo, un tooltip no
  interactivo) que ya no compite con la acción de editar, ahora exclusiva del nuevo control.
