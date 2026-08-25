# Feature Specification: Navegación a Contratos y Recibos desde las Vistas de Locación

**Feature Branch**: `014-vista-locacion-navegacion`

**Created**: 2026-08-24

**Status**: Draft

**Input**: User description: "Debo tener la opcion de ver los contratos, los recibos y editar desde la misma vista" — aclarado por el usuario: "En la vista /locaciones se debe poder ir a contratos el historial y el crud, ir al historial de recibos" — y precisado en una segunda ronda: "Me referia a la vista de locaciones donde se mostraban todas las areas es decir en fila-arbol-locacion" (la tabla jerárquica de `/locaciones`, no la vista de detalle individual `locaciones/show.blade.php`).

## Clarifications

### Session 2026-08-24

- Q: Ya existe un botón "Ver Recibos" agregado en la vista de detalle de la locación (`locaciones/show.blade.php`), implementado antes de esta aclaración, junto con el botón "Ver Contratos" ya existente. Ahora que se confirmó que el requisito real es sobre la fila de la tabla jerárquica (`fila-arbol-locacion.blade.php` en `/locaciones`), ¿qué debe pasar con ese acceso ya agregado en el detalle? → A: Se mantiene en ambas vistas — el detalle de la locación y la fila de la tabla jerárquica en `/locaciones` exponen ambas el acceso a contratos y recibos; ninguna reemplaza a la otra.
- Q: La fila de la tabla jerárquica ya tiene una columna "Acciones" con un botón "+" (solo ícono, crear locación hija) y "Editar" (ícono + texto). Al agregar "Ver Contratos" y "Ver Recibos", ¿cómo deben presentarse para no desbordar la columna en filas profundamente indentadas? → A: Menú desplegable "Acciones" (ícono ⋮) que agrupa Editar, Ver Contratos y Ver Recibos; el botón "+" (crear hija) permanece fuera del menú, siempre visible.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Acceder a recibos y contratos desde la fila de la locación en la vista general (Priority: P1) 🎯 MVP

Un usuario que está viendo la tabla jerárquica de todas las locaciones (`/locaciones`, donde se listan todas las áreas: galerías, pisos, locales) necesita, desde la fila de cualquier locación alquilable, navegar al historial de recibos y al historial/CRUD de contratos de esa locación, sin tener que abrir primero su detalle.

**Why this priority**: Es el requisito original del usuario — la vista donde gestiona todas las locaciones de un vistazo es la tabla jerárquica, no el detalle individual; tener que abrir el detalle primero para llegar a contratos o recibos añade un paso innecesario en el flujo más usado del sistema.

**Independent Test**: Puede probarse por completo abriendo `/locaciones`, desplegando el menú de acciones de la fila de una locación alquilable, y verificando que las opciones "Ver Recibos" y "Ver Contratos" llevan al historial correspondiente de esa locación específica.

**Acceptance Scenarios**:

1. **Given** la tabla jerárquica de locaciones con al menos una locación alquilable, **When** el usuario abre el menú de acciones de esa fila, **Then** ve las opciones "Ver Recibos" y "Ver Contratos" junto a "Editar".
2. **Given** el menú de acciones abierto en la fila de una locación alquilable, **When** el usuario selecciona "Ver Recibos", **Then** llega al historial de recibos de esa locación específica (no de otra).
3. **Given** el menú de acciones abierto en la fila de una locación alquilable, **When** el usuario selecciona "Ver Contratos", **Then** llega al historial de contratos de esa locación específica, desde donde puede crear o editar contratos.
4. **Given** una fila de locación no alquilable (galería, piso, sector, pasillo), **When** el usuario abre su menú de acciones, **Then** solo ve "Editar" — sin opciones de recibos ni contratos.

---

### User Story 2 - Acceder a recibos y contratos desde el detalle individual de la locación (Priority: P3)

Un usuario que está viendo el detalle de una única locación (tras entrar desde su fila u otro enlace) también puede, desde esa misma pantalla, navegar al historial de recibos y de contratos de esa locación — sin tener que volver a la tabla general para hacerlo.

**Why this priority**: Es un punto de entrada adicional y conveniente para cuando el usuario ya está dentro del detalle de una locación por otro motivo, pero no es el flujo principal que motivó este pedido (ver User Story 1). Ya se implementó como parte de una primera interpretación de este mismo requisito y se conserva porque no tiene costo mantenerlo (ver Clarifications).

**Independent Test**: Puede probarse por completo abriendo el detalle de una locación alquilable y verificando que las opciones "Ver Recibos" y "Ver Contratos" llevan al historial correspondiente.

**Acceptance Scenarios**:

1. **Given** una locación alquilable con al menos un recibo emitido, **When** el usuario abre el detalle de esa locación, **Then** ve una opción visible para ir al historial de recibos de esa locación.
2. **Given** una locación alquilable sin recibos emitidos todavía, **When** el usuario abre el detalle de esa locación y usa esa opción, **Then** llega al historial de recibos y ve un estado vacío claro (sin recibos), sin error.
3. **Given** una locación alquilable con contratos vigentes y finalizados, **When** el usuario abre el detalle de esa locación y sigue la opción de ver contratos, **Then** llega al historial completo de contratos de esa locación (vigentes y finalizados), desde donde puede crear o editar contratos.
4. **Given** una locación marcada como no alquilable, **When** el usuario abre su detalle, **Then** no se le ofrece ninguna de las dos opciones.

---

### Edge Cases

- ¿Qué pasa si una locación alquilable no tiene ningún contrato todavía? El acceso al historial de contratos debe seguir disponible (en ambas vistas) y mostrar un estado vacío claro, en vez de ocultarse o fallar.
- ¿Qué pasa si el usuario cambia el estado "alquilable" de una locación mientras la tiene abierta en otra pestaña? Al recargar cualquiera de las dos vistas, las opciones de navegación a contratos/recibos deben reflejar el estado actual.
- ¿Qué pasa si una locación no tiene sub-locaciones ni es alquilable (nodo puramente organizativo)? No debe ofrecerse ninguna opción de contratos/recibos en ninguna de las dos vistas.
- ¿Qué pasa con el menú de acciones de la fila en una locación muy indentada (varios niveles de profundidad) en pantallas angostas? El menú desplegable no debe forzar scroll horizontal en la página ni recortarse fuera del viewport — debe abrirse hacia el lado con espacio disponible.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: La vista de detalle de una locación DEBE ofrecer, cuando la locación es alquilable, una opción visible para navegar al historial de recibos de esa locación.
- **FR-002**: La vista de detalle de una locación DEBE ofrecer, cuando la locación es alquilable, una opción visible para navegar al historial de contratos de esa locación.
- **FR-003**: Desde el historial de contratos alcanzado desde cualquiera de las dos vistas (detalle de la locación o fila de la tabla jerárquica), el usuario DEBE poder crear un contrato nuevo para esa locación.
- **FR-004**: Desde el historial de contratos alcanzado desde cualquiera de las dos vistas, el usuario DEBE poder editar cualquier contrato existente de esa locación.
- **FR-005**: Cuando una locación no es alquilable, ninguna de las dos vistas DEBE ofrecer las opciones de navegación a contratos ni a recibos.
- **FR-006**: El historial de recibos alcanzado desde cualquiera de las dos vistas DEBE mostrar todos los recibos emitidos para esa locación, incluyendo un estado vacío claro cuando no existe ninguno todavía.
- **FR-007**: El historial de contratos alcanzado desde cualquiera de las dos vistas DEBE mostrar tanto los contratos vigentes como los finalizados de esa locación.
- **FR-008**: Las opciones de navegación a contratos y a recibos DEBEN presentarse como acciones distintas y claramente diferenciadas (no combinadas en una sola opción ambigua), en ambas vistas.
- **FR-009**: La fila de cada locación alquilable en la tabla jerárquica de `/locaciones` DEBE ofrecer, mediante un menú desplegable de acciones, navegación al historial de recibos y al historial de contratos de esa locación, además de la acción "Editar" ya existente.
- **FR-010**: El menú desplegable de acciones de la fila DEBE excluir la acción de crear locación hija ("+"), que permanece como control independiente y siempre visible fuera del menú.
- **FR-011**: El menú desplegable de acciones de la fila NO DEBE producir scroll horizontal en la página ni recortarse fuera del viewport, sin importar la profundidad de indentación de la fila.

### Key Entities *(include if feature involves data)*

- **Locación**: la propiedad o unidad sobre la que se navega, ya sea desde su fila en la tabla jerárquica o desde su vista de detalle; determina si corresponde ofrecer acceso a contratos/recibos según su condición de alquilable.
- **Contrato**: acuerdo de alquiler asociado a una locación; tiene un historial (vigentes y finalizados) y admite creación y edición.
- **Recibo**: comprobante de cobro periódico asociado a una locación; se consulta como historial de solo lectura desde esta vista (su creación/edición ya ocurre dentro de su propio flujo, fuera del alcance de esta funcionalidad).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Desde la fila de cualquier locación alquilable en `/locaciones`, un usuario llega al historial de recibos de esa locación en dos acciones (abrir el menú de acciones + seleccionar la opción).
- **SC-002**: Desde la fila de cualquier locación alquilable en `/locaciones`, un usuario llega al historial de contratos de esa locación en dos acciones.
- **SC-003**: Desde el detalle de cualquier locación alquilable, un usuario llega al historial de recibos o de contratos de esa locación en una sola acción.
- **SC-004**: El 100% de las filas y de los detalles de locaciones alquilables muestran ambas opciones de navegación (contratos y recibos) operativas; el 100% de las locaciones no alquilables no las muestran, en ninguna de las dos vistas.
- **SC-005**: Un usuario puede completar el ciclo de "ver la tabla general de locaciones → revisar historial de contratos de una locación → crear o editar un contrato" sin encontrar callejones sin salida ni tener que volver a buscar la locación por otra vía.

## Assumptions

- La condición "alquilable" (`es_alquilable`) de una locación sigue siendo el criterio que determina si aplica mostrar navegación a contratos y recibos, tanto en la fila de la tabla jerárquica como en el detalle.
- La creación y edición de recibos ya cuenta con su propio flujo (alcanzado desde el historial de recibos); esta funcionalidad solo asegura que ese historial sea alcanzable desde ambas vistas, sin rediseñar el flujo de recibos en sí.
- "Editar desde la misma vista" se resuelve como navegación consolidada y directa (sin pasos intermedios innecesarios) hacia las pantallas de creación/edición ya existentes de contratos, no como formularios de edición incrustados dentro de ninguna de las dos vistas.
- No se requiere un enlace de navegación equivalente hacia "crear/editar recibo" directamente desde ninguna de las dos vistas, ya que para recibos alcanza con llegar al historial (el CRUD de recibos ya es accesible desde ese historial).
- El punto de entrada principal para este requisito es la tabla jerárquica de `/locaciones` (User Story 1); el acceso equivalente desde el detalle individual de la locación (User Story 2) ya fue implementado como parte de una interpretación previa de este mismo pedido y se conserva como punto de entrada adicional, no se retira (ver Clarifications).
