# Feature Specification: Barra de Progreso de Pagos

**Feature Branch**: `034-barra-progreso-pagos`

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "y agregar una barra de progreso y mostrar cuanto se ha ido pagando" (continuación de specs/033: la pantalla de "Registro de Pagos" — jerarquía de locales para registrar y revisar pagos)

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ver el avance de pago de un vistazo mediante una barra visual (Priority: P1)

Un administrador abre "Registro de Pagos" y, para cada locación con recibos vigentes en el período, ve una
barra de progreso que se llena proporcionalmente a lo pagado, además del detalle numérico ya existente
(monto pagado / monto total). Puede distinguir a simple vista qué locaciones están sin pagos, cuáles van a
medias y cuáles ya están completas, sin tener que leer y comparar cifras una por una.

**Why this priority**: Es el pedido explícito de esta feature — reforzar visualmente un dato que hoy solo
se comunica en texto, para que revisar el estado de cobro de todo un período sea más rápido.

**Independent Test**: Con locaciones en distintos estados de avance (sin pagos, parcial, completo), abrir
"Registro de Pagos" y verificar que cada una muestra una barra de progreso cuyo llenado corresponde a su
proporción pagada, junto al monto exacto.

**Acceptance Scenarios**:

1. **Given** una locación con un recibo vigente sin ningún pago registrado, **When** se revisa su fila en
   "Registro de Pagos", **Then** su barra de progreso se muestra vacía (0% de avance).
2. **Given** una locación con un recibo vigente pagado parcialmente (por ejemplo, la mitad de su total),
   **When** se revisa su fila, **Then** su barra de progreso se muestra llena hasta aproximadamente esa
   proporción, ni vacía ni completa.
3. **Given** una locación con su(s) recibo(s) vigente(s) completamente pagados, **When** se revisa su fila,
   **Then** su barra de progreso se muestra completamente llena.
4. **Given** cualquiera de los casos anteriores, **When** se revisa la fila, **Then** el monto pagado y el
   monto total siguen mostrándose en texto junto a la barra, no reemplazados por ella.

---

### User Story 2 - Ver la misma barra de progreso en el detalle de un recibo individual (Priority: P2)

El mismo administrador, al abrir el detalle de un recibo puntual para registrar o revisar sus pagos, ve la
misma barra de progreso que ya reconoce de "Registro de Pagos", reforzando visualmente el avance de ese
recibo en particular.

**Why this priority**: Mantiene el mismo lenguaje visual en el lugar donde el avance de pago ya se muestra
hoy en texto (specs/032); es un complemento de la historia principal, no la razón de ser de esta feature.

**Independent Test**: Abrir el detalle de un recibo con un pago parcial registrado y verificar que se
muestra una barra de progreso consistente con su monto pagado y su total.

**Acceptance Scenarios**:

1. **Given** el detalle de un recibo con un pago parcial registrado, **When** se revisa la sección de
   pagos, **Then** se muestra una barra de progreso llena hasta la proporción pagada, junto al monto exacto
   ya mostrado ahí.

---

### Edge Cases

- ¿Qué pasa con una locación sin ningún recibo vigente en el período? No se muestra ninguna barra de
  progreso — no hay nada que medir (mismo criterio ya usado en specs/033 para el resto del avance).
- ¿Qué pasa si se elimina un pago que hacía que un recibo estuviera completamente pagado? La barra
  correspondiente debe reflejar de inmediato el nuevo avance parcial (o vacío), sin quedar desactualizada.
- ¿Qué pasa en una locación con varios recibos vigentes en el mismo período, unos pagados y otros no? La
  barra de su fila refleja el avance agregado de todos ellos juntos, el mismo criterio de agregación que ya
  usa specs/033 para el monto pagado/total mostrado en texto.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE mostrar una barra de progreso visual junto al detalle numérico de avance de
  pago (monto pagado / monto total) ya existente en la pantalla "Registro de Pagos", para cada locación con
  al menos un recibo vigente en el período.
- **FR-002**: La barra de progreso DEBE representar visualmente la proporción pagada del monto total,
  distinguiendo sin ambigüedad entre sin avance (vacía), avance parcial (parcialmente llena) y completado
  (completamente llena).
- **FR-003**: El sistema DEBE mostrar la misma barra de progreso en el detalle de un recibo individual
  (specs/032), junto al detalle numérico de avance ya mostrado ahí.
- **FR-004**: La barra de progreso DEBE reflejar el avance recalculado de inmediato después de registrar,
  editar o eliminar un pago — nunca un valor desactualizado.
- **FR-005**: El sistema NO DEBE mostrar ninguna barra de progreso para una locación sin ningún recibo
  vigente en el período.
- **FR-006**: La barra de progreso DEBE acompañar al detalle numérico existente, no reemplazarlo — el
  monto pagado y el monto total siguen siendo visibles como texto en todos los casos.
- **FR-007**: La barra de progreso DEBE comunicar el mismo estado de avance (sin pagos/parcial/pagado) que
  ya comunica el badge de estado ya existente, de forma visualmente consistente con él (por ejemplo,
  variando su color según el estado, no solo su llenado).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador puede distinguir, sin leer ningún número, si una locación no tiene pagos,
  tiene un pago parcial, o está completamente pagada, con solo mirar el llenado de su barra de progreso.
- **SC-002**: El 100% de las locaciones con recibos vigentes en el período que muestran un monto de avance
  en texto también muestran una barra de progreso consistente con ese mismo monto.
- **SC-003**: Al registrar, editar o eliminar un pago, la barra de progreso mostrada coincide exactamente
  con el nuevo avance calculado, sin desfasarse del detalle numérico.

## Assumptions

- Esta feature es un refuerzo visual sobre el avance de pago ya calculado por specs/032 y ya mostrado en
  texto por specs/032 (detalle del recibo) y specs/033 (pantalla de "Registro de Pagos") — no cambia cómo
  se calcula el avance de pago, solo cómo se representa.
- No se requiere ninguna animación de transición específica al cambiar el avance — basta con que el valor
  mostrado sea siempre el correcto en cada carga de pantalla.
- La barra de progreso se agrega en los dos lugares donde el avance de pago ya se comunica hoy en texto
  (la fila de una locación en "Registro de Pagos", y el detalle de un recibo individual), para mantener un
  mismo lenguaje visual consistente entre ambos.
