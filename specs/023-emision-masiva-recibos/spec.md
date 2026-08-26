# Feature Specification: Emisión Masiva de Recibos por Periodo

**Feature Branch**: `023-emision-masiva-recibos`

**Created**: 2026-08-25

**Status**: Draft

**Input**: User description: "al igual que en el menu Registrar Lecturas requiero replicar el diseño para emitir los recibos de forma teniendo en cuenta la logica indicada anteriormente para las mismas, muestra lo que se va pagar de acuerdo a lo consignado en el contrato activo y la posibilidad de registrar los otros gastos tambien especificados anteriormente, agrega la posibilidad de generar varios recibos por periodo y local pero teniendo en cuento que no se pueden repetir los conceptos, muestra en esta vista si ya se genero el recibo y que conceptos ya se han ido comprendiendo"

## Clarifications

### Session 2026-08-25

- Q: En cada visita, ¿la pantalla debe mostrar qué locaciones ya generaron recibo y qué conceptos quedaron cubiertos, y decidir qué incluir en un recibo nuevo se hace en la misma fila o mediante un componente aparte? → A: Mediante un modal (u otro componente equivalente) abierto desde la fila de esa locación, que muestra solo los conceptos todavía no cubiertos con su monto sugerido editable.
- Q: Al confirmar el modal de una locación con los conceptos elegidos, ¿se genera ese recibo al toque o queda armado en pantalla para confirmar todo junto al final? → A: Se genera al confirmar el modal — cada confirmación es su propia operación; la fila se actualiza sola, y puede volver a abrirse el modal de esa misma locación para cubrir lo que quedó, en la misma visita o en una posterior.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ver de un vistazo qué se debe cobrar en todas las locaciones de un periodo (Priority: P1)

Como Administrador, quiero abrir una sola pantalla (igual en estructura a "Registro Masivo de Lecturas") que liste todas las locaciones alquilables ordenadas jerárquicamente para un periodo elegido, y ver junto a cada una: si tiene un contrato activo vigente en ese periodo, el monto que corresponde cobrar por cada concepto según lo consignado en ese contrato (renta ya prorrateada si el contrato no cubrió el periodo completo, luz según el consumo registrado, agua/pasadizo/seguridad según los montos fijos del contrato), y qué conceptos de ese periodo ya quedaron cubiertos por un recibo generado anteriormente — todo esto sin tener que entrar locación por locación. Cada vez que vuelva a abrir esta pantalla (en la misma sesión de trabajo o días después) quiero ver ese estado actualizado, sin tener que recordar qué locaciones ya facturé.

**Why this priority**: Es la funcionalidad central pedida — reemplaza el recorrido manual, locación por locación, por una vista consolidada, igual que ya ocurre con el registro de lecturas.

**Independent Test**: Con al menos tres locaciones (una con contrato activo y sin recibo del periodo, una con contrato activo y ya con un recibo previo que cubre solo renta, y una sin contrato activo en el periodo), abrir la pantalla para ese periodo y verificar que cada una muestra correctamente su situación: montos sugeridos, conceptos ya cubiertos, o el aviso de que no hay contrato activo.

**Acceptance Scenarios**:

1. **Given** una locación con contrato activo en el periodo seleccionado y sin ningún recibo generado todavía para ese periodo, **When** el usuario abre la pantalla, **Then** la fila de esa locación muestra el monto sugerido de cada concepto (renta, luz, agua, pasadizo, seguridad) según el contrato activo y la lectura del periodo, todos disponibles para incluir.
2. **Given** una locación sin ningún contrato activo en el periodo seleccionado, **When** el usuario ve su fila, **Then** se indica claramente que no hay contrato activo y no se ofrece ninguna forma de generar un recibo para esa fila en ese periodo.
3. **Given** una locación que ya tiene un recibo generado en el periodo seleccionado cubriendo únicamente el concepto "renta", **When** el usuario ve su fila, **Then** se indica claramente que "renta" ya está cubierta (con un enlace o referencia al recibo que la cubre) y los demás conceptos (luz, agua, pasadizo, seguridad) siguen disponibles para incluir en un nuevo recibo.

---

### User Story 2 - Generar el recibo de una locación desde un modal, sin salir de la pantalla (Priority: P1)

Como Administrador, quiero hacer clic en una acción de la fila de una locación para abrir un modal (u otro componente equivalente) que me muestre únicamente los conceptos todavía no cubiertos de esa locación con su monto sugerido, marcar cuáles incluir (editando el monto si hace falta) y confirmar, para que el sistema genere ese recibo en el momento y la fila se actualice sola — pudiendo repetir la misma acción en otras locaciones sin recargar toda la pantalla, en vez de repetir el formulario individual de "Emitir Recibo" locación por locación.

**Why this priority**: Junto con la User Story 1, es el otro componente central del pedido — sin esta acción, la vista consolidada de la Historia 1 solo ahorraría lectura, no trabajo de registro.

**Independent Test**: Abrir el modal de una locación con contrato activo y sin recibo previo, marcar los conceptos a incluir (con sus montos sugeridos o editados), confirmar, y verificar que se creó un recibo nuevo con exactamente esos conceptos y montos, y que la fila de esa locación se actualizó para reflejarlo sin recargar la página completa.

**Acceptance Scenarios**:

1. **Given** una locación con contrato activo en el periodo y con conceptos disponibles, **When** el usuario abre su modal, **Then** el modal muestra únicamente los conceptos todavía no cubiertos, cada uno con su monto sugerido, editable.
2. **Given** el modal abierto de una locación, **When** el usuario marca uno o más conceptos disponibles y confirma, **Then** se genera de inmediato un recibo con exactamente esos conceptos y montos, y la fila de esa locación se actualiza para mostrar lo recién cubierto, sin recargar toda la pantalla.
3. **Given** el usuario generó el recibo de una locación y quiere continuar con otra, **When** abre el modal de una segunda locación sin haber salido de la pantalla, **Then** puede generar su recibo de la misma forma, quedando ambos recibos creados de forma independiente.
4. **Given** un error de validación al confirmar el modal (ej. un monto negativo), **When** ocurre, **Then** el modal muestra el error y permite corregir sin perder lo ya marcado, y sin afectar el estado de ninguna otra locación de la pantalla.

---

### User Story 3 - Volver a generar recibo para la misma locación hasta cubrir todos sus conceptos (Priority: P2)

Como Administrador, quiero poder volver a abrir el modal de una locación que ya tiene un recibo generado en el periodo (en la misma visita a la pantalla o en una posterior) para cubrir con un nuevo recibo los conceptos que todavía faltan, sin que el sistema me deje marcar de nuevo un concepto que ya quedó cubierto por otro recibo de ese mismo periodo y locación.

**Why this priority**: Cubre el caso de cobro fraccionado explícitamente pedido ("generar varios recibos por periodo y local... teniendo en cuenta que no se pueden repetir los conceptos"), como una capacidad adicional sobre las Historias 1 y 2 — sin ellas no hay pantalla ni modal desde el cual generar el primer ni el siguiente recibo.

**Independent Test**: Generar un recibo para una locación cubriendo solo "renta", volver a abrir el modal de esa misma locación (sin necesidad de recargar la página), y verificar que "renta" ya no aparece como opción disponible mientras los demás conceptos sí; marcarlos y confirmar, y verificar que se generó un segundo recibo independiente con exactamente esos conceptos.

**Acceptance Scenarios**:

1. **Given** una locación con contrato activo y ningún concepto cubierto todavía, **When** el usuario abre su modal, marca solo "renta" y confirma, **Then** se genera un recibo cubriendo únicamente "renta".
2. **Given** la locación del escenario anterior, **When** el usuario vuelve a abrir el modal de esa misma locación (misma visita o una posterior), **Then** "renta" ya no aparece como opción disponible, mientras que "luz", "agua", "pasadizo" y "seguridad" sí.
3. **Given** el modal reabierto del escenario anterior, **When** el usuario marca los conceptos restantes y confirma, **Then** se genera un segundo recibo independiente del primero, con exactamente esos conceptos, sin duplicar "renta".
4. **Given** una locación cuyos conceptos ya están completamente cubiertos entre sus recibos existentes, **When** el usuario ve su fila, **Then** no se ofrece ninguna acción para abrir un modal nuevo en ese periodo.
5. **Given** dos administradores tienen casi al mismo tiempo el modal abierto de la misma locación con un concepto en común todavía no cubierto, **When** ambos confirman, **Then** solo la primera confirmación en completarse tiene éxito para ese concepto; la segunda se rechaza indicando exactamente qué concepto(s) ya quedaron cubiertos por la otra, sin duplicar el cobro de ninguno.

---

### Edge Cases

- **Cambio de periodo en la pantalla**: al cambiar el periodo seleccionado, todos los montos sugeridos, contratos activos y conceptos ya cubiertos se recalculan para ese nuevo periodo — igual que ya ocurre en "Registro Masivo de Lecturas".
- **Locación sin lectura de medidor registrada en el periodo**: el monto sugerido de "luz" se muestra como S/ 0.00, editable, consistente con el comportamiento ya existente en el flujo individual de emisión de recibo.
- **Locación no alquilable (nodo organizativo)**: se muestra en el árbol como contexto jerárquico, sin fila de conceptos ni acción de generación, igual que en "Registro Masivo de Lecturas".
- **Todos los conceptos ya cubiertos**: la fila de esa locación no ofrece ninguna acción de generación para ese periodo, indicando claramente que ya está completo.
- **Modal sin ningún concepto marcado**: el sistema no permite confirmar el modal sin al menos un concepto seleccionado.
- **Concepto cubierto por otra confirmación mientras el modal seguía abierto**: si, entre que se abrió el modal y se confirmó, otro recibo cubrió alguno de los conceptos marcados (otra pestaña, otro administrador), el sistema rechaza la confirmación señalando exactamente cuáles ya quedaron cubiertos, sin generar un recibo parcial ni duplicar ningún concepto.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE mostrar, en una sola pantalla, todas las locaciones alquilables organizadas jerárquicamente (mismo árbol ya usado en "Registro Masivo de Lecturas"), para un periodo (mes) seleccionable.
- **FR-002**: Para cada locación con contrato activo en el periodo seleccionado, el sistema DEBE mostrar el monto sugerido de cada concepto (renta, luz, agua, pasadizo, seguridad) calculado con la misma lógica ya usada en la emisión individual de recibos: renta prorrateada según los días de vigencia del contrato en el periodo, luz según el total ya calculado de la lectura de medidor del periodo, y agua/pasadizo/seguridad según los montos fijos consignados en el contrato activo.
- **FR-003**: Para cada locación sin contrato activo en el periodo seleccionado, el sistema DEBE indicarlo claramente y no ofrecer ninguna acción de generación de recibo para esa locación en ese periodo.
- **FR-004**: El sistema DEBE mostrar, dentro del modal (u otro componente equivalente) de cada locación, únicamente sus conceptos todavía no cubiertos, cada uno con su monto sugerido editable antes de confirmar.
- **FR-005**: El sistema DEBE ofrecer, en la fila de cada locación con al menos un concepto disponible, una acción que abre su modal sin recargar toda la pantalla.
- **FR-006**: El sistema DEBE mostrar, para cada locación y periodo, qué conceptos ya están cubiertos por uno o más recibos existentes de ese mismo periodo (generados desde esta pantalla o por el flujo individual ya existente), y esos conceptos NO DEBEN ofrecerse como opción dentro del modal de esa locación.
- **FR-007**: El sistema DEBE permitir que una misma locación y periodo tengan más de un recibo: cada confirmación del modal de una locación genera un recibo independiente y nuevo, con exactamente los conceptos marcados en ese momento, sin que el conjunto de conceptos cubiertos entre todos los recibos de esa locación y periodo pueda superponerse.
- **FR-008**: El sistema DEBE rechazar, en el momento de confirmar el modal, cualquier intento de incluir un concepto ya cubierto por otro recibo de ese mismo periodo y locación — incluso si esa cobertura se produjo después de que el modal se abrió (condición de carrera entre dos confirmaciones casi simultáneas) — informando exactamente qué concepto(s) ya estaban cubiertos, sin generar un recibo parcial.
- **FR-009**: El sistema DEBE indicar, para cada locación cuyos conceptos disponibles del periodo ya están completamente cubiertos entre uno o más recibos, que ese periodo está completo para esa locación, sin ofrecer ninguna acción para abrir un modal nuevo.
- **FR-010**: Cada recibo generado desde esta pantalla DEBE quedar accesible desde la misma pantalla (referencia o enlace) para que el usuario pueda revisar o editar un recibo ya generado sin salir del flujo.
- **FR-011**: Un error de validación al confirmar el modal de una locación (ej. un monto negativo) NO DEBE afectar el estado de ninguna otra locación de la pantalla ni impedir seguir generando recibos de otras filas.
- **FR-012**: El sistema NO DEBE permitir confirmar el modal de una locación sin al menos un concepto marcado.

### Key Entities *(include if feature involves data)*

- **Recibo** (entidad existente, sin cambio de estructura): esta feature cambia la regla de negocio que hoy impide más de un recibo por locación y periodo (`ReciboDuplicadoPeriodoException`), reemplazándola por una regla a nivel de concepto — el conjunto de campos `incluye_*` en `true` de todos los recibos de una misma locación y periodo no puede superponerse. Cada confirmación del modal de esta pantalla crea un recibo nuevo e independiente.
- **Contrato** (existente): sigue siendo la fuente de los montos fijos por concepto (renta, agua, pasadizo, seguridad) y de la ventana de vigencia usada para el prorrateo de renta.
- **LecturaMedidor** (existente): sigue siendo la fuente del monto sugerido de luz, sin cambios.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador puede revisar la situación de cobro (contrato activo, montos sugeridos, conceptos ya cubiertos) de todas las locaciones de un periodo sin abrir más de una pantalla.
- **SC-002**: Un administrador puede generar recibos para 10 locaciones distintas del mismo periodo sin salir de la pantalla ni recargarla por completo entre una y otra, en menos tiempo que generarlos uno por uno con el flujo individual existente.
- **SC-003**: El 100% de los intentos de confirmar un modal incluyendo un concepto ya cubierto por otro recibo del mismo periodo y locación son rechazados antes de duplicar el cobro de ese concepto, incluso bajo confirmaciones casi simultáneas.
- **SC-004**: El 100% de las locaciones con todos sus conceptos ya cubiertos en un periodo se muestran como completas, sin requerir que el administrador abra cada una para descubrirlo.

## Assumptions

- **A-001**: Los "conceptos" a los que se refiere el pedido son exactamente los cinco ya existentes en el modelo de Recibo (`incluye_alquiler`, `incluye_agua`, `incluye_luz`, `incluye_pasadizo`, `incluye_seguridad`) — no se introduce ningún concepto nuevo ni una lista configurable.
- **A-002**: El periodo de esta pantalla es un único selector global (mes), igual que en "Registro Masivo de Lecturas" — no un rango de periodos ni un selector por locación.
- **A-003**: La edición de un recibo ya existente (flujo `recibos.edit`/`recibos.update`, fuera del alcance directo de esta pantalla) queda sujeta a la misma regla de no-superposición de conceptos de FR-007/FR-008 frente a los demás recibos de su mismo periodo y locación — no puede editarse para incluir un concepto que otro recibo del mismo periodo y locación ya cubre.
- **A-004**: Como en el flujo individual ya existente, la fecha de emisión de cada recibo generado desde esta pantalla se asume la fecha actual por defecto, sin exigir que el usuario la edite en cada modal.
- **A-005**: Cada confirmación del modal de una locación puede incluir cualquier subconjunto no vacío de sus conceptos todavía disponibles — no se exige que ningún recibo incluya "renta" ni ningún concepto en particular. El usuario puede reabrir el modal de la misma locación cuantas veces haga falta para cubrir los conceptos restantes, tanto en la misma visita a la pantalla como en una posterior; los conceptos ya cubiertos por una confirmación anterior se siguen bloqueando igual sin importar cuándo se generaron (FR-006).
- **A-006**: El componente de selección de conceptos es un modal (diálogo superpuesto) por defecto, consistente con el uso ya establecido del componente `Modal` nativo de Bootstrap 5 en el resto del proyecto (Principio VI de la constitución) para confirmaciones y formularios secundarios embebidos; un componente distinto (ej. un panel expandible en la misma fila) queda como alternativa de diseño a evaluar en `/speckit-plan` si el modal resulta poco práctico para el monto de campos por concepto.
