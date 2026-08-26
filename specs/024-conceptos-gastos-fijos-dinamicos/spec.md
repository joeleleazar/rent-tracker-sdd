# Feature Specification: Catálogo Dinámico de Conceptos de Gastos Fijos, Periodo Ágil y Totales por Locación

**Feature Branch**: `024-conceptos-gastos-fijos-dinamicos`

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "Los conceptos de gastos fijos deben tener su propio mantenimiento, ademas que se deben obtener el valor referencial desde de la configuracion del contrato del local, modificar la forma de cambiar de periodo en el frontend a algo mas dinamico, mostrar a cuanto asciende el pago total del periodo por local, cuantos recibos tiene"

## Clarifications

### Session 2026-08-26

- Q: ¿Qué significa "mantenimiento propio" para los conceptos de gastos fijos (renta, agua, luz, pasadizo, seguridad)? → A: Catálogo dinámico — una pantalla para administrar los tipos de concepto (crear, renombrar, desactivar) como catálogo compartido por todo el sistema, reemplazando los 5 campos fijos actuales; cada contrato configura su propio valor de referencia por cada concepto del catálogo que le aplique.
- Q: ¿Cómo debería funcionar el cambio de periodo "más dinámico" en las pantallas de registro masivo? → A: Flechas «anterior»/«siguiente» junto al selector de mes, y elegir una fecha en el selector dispara el cambio solo (sin botón "Cambiar Periodo"), sin recargar la página completa.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Administrar el catálogo de conceptos de gastos fijos (Priority: P1)

Como Administrador, quiero mantener una lista de los tipos de concepto de gasto fijo que existen en el sistema (hoy: Renta, Agua, Luz, Pasadizo, Seguridad) — poder agregar uno nuevo, renombrar uno existente, cambiar su orden de aparición y desactivar uno que ya no se use — sin que agregar un concepto nuevo requiera un cambio de código.

**Why this priority**: Es el pedido central — sin un catálogo mantenible, los conceptos siguen siendo los 5 fijos de siempre y ninguna de las demás historias tiene sentido.

**Independent Test**: Crear un concepto nuevo (ej. "Internet"), verificar que aparece disponible para configurarse en un contrato y para incluirse en un recibo; desactivar un concepto existente y verificar que deja de ofrecerse para contratos nuevos sin afectar los recibos ya emitidos que ya lo incluían.

**Acceptance Scenarios**:

1. **Given** el catálogo de conceptos con los 5 conceptos existentes, **When** el administrador agrega un concepto nuevo con un nombre, **Then** el concepto queda disponible para configurarse en cualquier contrato y para incluirse en un recibo nuevo.
2. **Given** un concepto existente, **When** el administrador lo renombra, **Then** el nuevo nombre se refleja en todas las pantallas donde se muestra ese concepto, sin alterar los recibos ya emitidos que lo incluyen (conservan su monto histórico).
3. **Given** un concepto que ya no se usa, **When** el administrador lo desactiva, **Then** deja de ofrecerse para nuevos contratos o recibos, pero los contratos y recibos existentes que ya lo tenían configurado no se ven afectados.
4. **Given** el concepto "Renta", **When** el administrador intenta desactivarlo o eliminarlo, **Then** el sistema lo impide, indicando que es un concepto protegido del que depende el cálculo de alquiler del sistema.

---

### User Story 2 - Configurar el valor de referencia de cada concepto por contrato (Priority: P1)

Como Administrador, quiero que cada contrato tenga su propio valor de referencia para cada concepto de gasto fijo del catálogo que le aplique (por ejemplo, el costo de agua de este contrato específico), de modo que al emitir un recibo el monto sugerido de cada concepto salga de esa configuración, igual que ya ocurre hoy con agua, pasadizo y seguridad.

**Why this priority**: Es el complemento necesario de la Historia 1 — un catálogo de conceptos sin un valor de referencia por contrato no podría sugerir ningún monto al emitir recibos, perdiendo la funcionalidad que ya existe hoy.

**Independent Test**: Configurar en un contrato un valor de referencia para el concepto nuevo "Internet", abrir el flujo de emisión de recibo (individual o masivo) de esa locación, y verificar que "Internet" aparece disponible con ese monto como sugerido.

**Acceptance Scenarios**:

1. **Given** un contrato y un concepto activo del catálogo todavía sin configurar en ese contrato, **When** el administrador le asigna un valor de referencia, **Then** ese valor queda disponible como monto sugerido para ese concepto al emitir un recibo de esa locación.
2. **Given** un contrato con un valor de referencia ya configurado para un concepto, **When** el administrador lo actualiza, **Then** los recibos ya emitidos con el valor anterior no cambian, y los recibos nuevos usan el valor actualizado.
3. **Given** el concepto "Renta", **When** se configura o edita en un contrato, **Then** sigue siendo el mismo campo de renta ya existente (con su prorrateo automático si el contrato no cubre el mes completo) — no un valor de referencia adicional independiente.
4. **Given** el concepto "Luz", **When** se emite un recibo, **Then** el monto sugerido sigue proviniendo de la lectura de medidor del periodo (comportamiento ya existente), no de un valor de referencia configurado a mano en el contrato — "Luz" es la única excepción a la Historia 2.

---

### User Story 3 - Cambiar de periodo sin recargar la pantalla, con flechas de avance y retroceso (Priority: P2)

Como Administrador, quiero cambiar el periodo visible en las pantallas de registro masivo (lecturas y recibos) con flechas «anterior»/«siguiente» junto al selector de mes, y que elegir una fecha directamente también actualice la pantalla al instante, sin tener que hacer clic en un botón "Cambiar Periodo" ni esperar una recarga completa de la página.

**Why this priority**: Es una mejora de fluidez sobre una interacción que el usuario repite constantemente (revisar mes a mes), pero no bloquea el uso de las demás historias — son mejoras independientes entre sí.

**Independent Test**: En cualquiera de las dos pantallas de registro masivo, hacer clic en la flecha «siguiente» y verificar que la tabla se actualiza al periodo siguiente sin recargar la página completa ni perder la posición de scroll; repetir eligiendo un mes directamente en el selector.

**Acceptance Scenarios**:

1. **Given** una pantalla de registro masivo mostrando un periodo, **When** el usuario hace clic en la flecha «siguiente», **Then** la tabla se actualiza para mostrar el periodo inmediatamente posterior, sin recargar la página completa.
2. **Given** la misma pantalla, **When** el usuario hace clic en la flecha «anterior», **Then** la tabla se actualiza para mostrar el periodo inmediatamente anterior, sin recargar la página completa.
3. **Given** la misma pantalla, **When** el usuario elige un mes distinto directamente en el selector, **Then** la tabla se actualiza a ese periodo de inmediato, sin necesidad de un botón adicional.

---

### User Story 4 - Ver el total facturado y la cantidad de recibos de cada locación en el periodo (Priority: P2)

Como Administrador, quiero ver, junto a cada locación en la pantalla de registro masivo de recibos, a cuánto asciende el total ya facturado en el periodo (la suma de todos sus recibos de ese periodo) y cuántos recibos tiene emitidos, para dimensionar de un vistazo el estado de facturación de cada locación sin tener que abrir cada recibo.

**Why this priority**: Es información de apoyo que se vuelve más relevante justo porque specs/023 permitió que una locación tenga más de un recibo por periodo — antes de esa capacidad, "cuántos recibos tiene" siempre hubiera sido 0 o 1 y el dato aportaba poco.

**Independent Test**: Con una locación que tiene dos recibos generados en el periodo visible (uno cubriendo renta, otro cubriendo el resto), verificar que su fila muestra "2 recibos" y el monto total es la suma de ambos.

**Acceptance Scenarios**:

1. **Given** una locación sin ningún recibo en el periodo visible, **When** el usuario ve su fila, **Then** se indica "0 recibos" y un total de S/ 0.00.
2. **Given** una locación con dos recibos en el periodo visible, **When** el usuario ve su fila, **Then** se indica "2 recibos" y el total mostrado es la suma exacta de los montos totales de ambos recibos.
3. **Given** un recibo anulado dentro del periodo visible de una locación, **When** el usuario ve el total y la cantidad de esa locación, **Then** el recibo anulado se excluye tanto del conteo como del total (un recibo anulado no representa un cobro vigente).

---

### Edge Cases

- **Concepto sin ningún contrato que lo tenga configurado**: sigue apareciendo en el catálogo y disponible para configurarse, sin romper ninguna pantalla que lo liste.
- **Concepto desactivado que un recibo ya emitido todavía incluye**: el recibo sigue mostrando ese concepto y su monto histórico con normalidad (desactivar no es eliminar ni ocultar retroactivamente).
- **Intento de eliminar (no solo desactivar) un concepto ya usado en algún contrato o recibo**: el sistema lo impide, indicando cuántos contratos o recibos lo usan — igual que ya ocurre en el sistema con otras entidades referenciadas (ej. no se puede eliminar una locación con contratos).
- **Cambiar de periodo con las flechas hasta cruzar un cambio de año**: se comporta igual que ya lo hace el selector de mes actual (recalcula correctamente contra diciembre del año anterior o enero del año siguiente).
- **Doble clic rápido en una flecha de periodo**: solo debe aplicarse el último cambio de periodo solicitado, sin resultados inconsistentes por respuestas que lleguen fuera de orden.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE permitir crear, renombrar, reordenar y desactivar conceptos de gasto fijo desde una pantalla de administración dedicada, sin requerir un cambio de código para agregar un concepto nuevo.
- **FR-002**: El sistema DEBE impedir eliminar o desactivar el concepto "Renta", por ser el concepto del que depende el cálculo central de alquiler y prorrateo del sistema.
- **FR-003**: El sistema DEBE impedir eliminar (no desactivar) cualquier concepto que ya esté configurado en al menos un contrato o incluido en al menos un recibo, indicando cuántos registros lo usan.
- **FR-004**: El sistema DEBE permitir configurar, por cada contrato, un valor de referencia independiente para cada concepto activo del catálogo (salvo "Renta", que sigue usando el campo de monto de renta ya existente, y "Luz", ver FR-006).
- **FR-005**: Al emitir un recibo (individual o masivo), el sistema DEBE sugerir, para cada concepto disponible, el valor de referencia configurado en el contrato activo de esa locación para ese concepto.
- **FR-006**: El concepto "Luz" DEBE seguir tomando su monto sugerido de la lectura de medidor del periodo (comportamiento ya existente, specs/019), no de un valor de referencia configurado a mano en el contrato.
- **FR-007**: Cambiar de concepto configurado en un contrato NO DEBE alterar el monto de ningún recibo ya emitido con el valor anterior.
- **FR-008**: Las pantallas de registro masivo de lecturas y de recibos DEBEN ofrecer flechas «anterior»/«siguiente» para cambiar de periodo un mes a la vez, sin recargar la página completa.
- **FR-009**: Elegir una fecha directamente en el selector de mes de esas pantallas DEBE actualizar el contenido al instante, sin requerir un botón adicional de confirmación.
- **FR-010**: La pantalla de registro masivo de recibos DEBE mostrar, para cada locación, la cantidad de recibos y el monto total facturado (suma de los totales de sus recibos) del periodo visible, excluyendo cualquier recibo anulado de ambos cálculos.

### Key Entities *(include if feature involves data)*

- **Concepto de Gasto Fijo** (nuevo): un tipo de concepto facturable (nombre, orden de aparición, activo/inactivo). Reemplaza a los 5 conceptos hoy codificados como columnas fijas de `Contrato`/`Recibo` (renta, agua, luz, pasadizo, seguridad) por un catálogo mantenible. "Renta" es un concepto protegido que no puede desactivarse ni eliminarse.
- **Contrato** (existente, extendido): en vez de columnas fijas por concepto (`costo_agua`, `costo_luz`, `costo_pasadizo`, `costo_seguridad`), pasa a tener un valor de referencia configurable por cada concepto del catálogo que le aplique (salvo "Renta", que conserva su campo de monto de renta ya existente, y "Luz", que no se configura a mano).
- **Recibo** (existente, extendido): en vez de los 5 pares fijos `incluye_*`/`monto_*`, pasa a incluir un conjunto de conceptos con su monto correspondiente, uno por cada concepto que ese recibo cubre — conservando la regla ya existente de no repetir conceptos entre los recibos de una misma locación y periodo (specs/023).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador puede agregar un concepto de gasto fijo nuevo y empezar a usarlo en un recibo sin ninguna intervención técnica (sin desplegar código nuevo).
- **SC-002**: El 100% de los recibos ya emitidos antes de esta feature conservan exactamente los mismos conceptos y montos después de migrar al catálogo dinámico.
- **SC-003**: Un administrador puede moverse 6 periodos hacia atrás o adelante en la pantalla de registro masivo sin ninguna recarga completa de página.
- **SC-004**: Un administrador puede identificar, de un vistazo y sin abrir ningún recibo, cuántos recibos y cuánto se ha facturado en el periodo visible para cualquier locación.

## Assumptions

- **A-001**: El catálogo de conceptos es global (compartido por todo el sistema) — no existe un catálogo distinto por locación o por contrato; lo que varía por contrato es únicamente el valor de referencia de cada concepto, no el conjunto de conceptos disponibles.
- **A-002**: "Renta" y "Luz" son los dos únicos conceptos con una fuente de valor distinta al valor de referencia configurado a mano en el contrato (renta usa el campo de monto de renta con prorrateo ya existente; luz usa la lectura de medidor ya existente) — cualquier concepto nuevo que se agregue al catálogo sigue la regla general de FR-004/FR-005 (valor de referencia configurado en el contrato).
- **A-003**: Esta feature migra los datos existentes (los 5 conceptos fijos de `Contrato`/`Recibo`) al nuevo catálogo dinámico como su estado inicial, sin pérdida de información — el detalle técnico de esa migración corresponde a la fase de planificación.
- **A-004**: Las flechas de cambio de periodo (Historia 3) se agregan a las dos pantallas de registro masivo ya existentes (lecturas, specs/015, y recibos, specs/023) — no a los flujos individuales de registro de lectura o emisión de recibo, que no tienen hoy un selector de periodo tipo "mes" recorrible de la misma forma.
- **A-005**: El total y la cantidad de recibos por locación (Historia 4) se muestran únicamente en la pantalla de registro masivo de recibos (specs/023), por ser la que ya lista todas las locaciones de un periodo — no se agrega a la pantalla de registro masivo de lecturas, que no tiene relación con montos facturados.
