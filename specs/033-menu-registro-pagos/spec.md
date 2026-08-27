# Feature Specification: Menú de Registro de Pagos en la Jerarquía de Locales

**Feature Branch**: `033-menu-registro-pagos`

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "Los pagos se deben registrar en nuevo menu que diga 'Registro de Pagos', que repita la estructura de filas que ya hay en la vista de 'Emitir Recibos'"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Llegar al registro de pagos desde el menú principal (Priority: P1)

Un administrador que necesita registrar el cobro de un inquilino abre el menú principal del sistema y
encuentra un acceso directo llamado "Registro de Pagos", junto al resto de accesos operativos (Locaciones,
Registrar Lecturas, Emitir Recibos), sin tener que recordar una dirección o llegar ahí desde el detalle de
un recibo puntual.

**Why this priority**: Es el problema concreto que motiva esta feature — el registro de pagos ya existe
como funcionalidad, pero no es alcanzable desde el menú de navegación del sistema.

**Independent Test**: Desde cualquier pantalla del sistema, abrir el menú principal, hacer clic en
"Registro de Pagos" y verificar que se llega a la pantalla correspondiente en un solo paso.

**Acceptance Scenarios**:

1. **Given** un administrador en cualquier pantalla del sistema, **When** abre el menú principal, **Then**
   ve un ítem llamado "Registro de Pagos" junto al resto de accesos operativos.
2. **Given** el administrador hace clic en "Registro de Pagos", **When** la pantalla carga, **Then** su
   título visible es "Registro de Pagos" y el ítem de menú correspondiente queda señalado como la sección
   activa.

---

### User Story 2 - Registrar un pago directamente desde la fila de una locación (Priority: P1)

El mismo administrador, ya dentro de "Registro de Pagos", ve la misma jerarquía de locales (galería, piso,
local) que ya conoce de "Emitir Recibos". En la fila de una locación con saldo pendiente, encuentra una
acción "Registrar Pago" que lo lleva directo a ingresar el monto cobrado, del mismo modo directo en que
"Generar Recibo" ya lo lleva a emitir un recibo nuevo.

**Why this priority**: Es la razón de ser de la pantalla — reconocer visualmente la misma estructura de
filas no sirve de nada si la acción de registrar el pago no está igual de accesible desde ahí.

**Independent Test**: Con una locación que tiene un recibo vigente con saldo pendiente en el período, hacer
clic en "Registrar Pago" desde su fila y verificar que se llega directo a la pantalla donde se ingresa el
pago para ese recibo.

**Acceptance Scenarios**:

1. **Given** una locación con un único recibo vigente y saldo pendiente en el período, **When** el
   administrador hace clic en "Registrar Pago" en su fila, **Then** llega directo a la pantalla donde puede
   ingresar el monto del pago para ese recibo.
2. **Given** una locación con más de un recibo vigente en el período, **When** el administrador hace clic en
   "Registrar Pago" en su fila, **Then** el sistema lo lleva primero a elegir a cuál de esos recibos
   corresponde el pago, igual que ya ocurre hoy al revisar recibos de una locación con más de uno.
3. **Given** una locación cuyo(s) recibo(s) del período ya están completamente pagados, **When** se revisa
   su fila, **Then** no se ofrece la acción "Registrar Pago" (no hay saldo pendiente contra el cual
   registrar un pago nuevo).

---

### User Story 3 - Revisar los pagos ya registrados sin registrar uno nuevo (Priority: P2)

El administrador, sin necesidad de registrar un pago nuevo, quiere confirmar qué se pagó ya en una
locación — por ejemplo, para resolver una consulta de un inquilino. Encuentra una acción separada para
revisar el historial, sin que registrar un pago sea la única forma de acceder a esa información.

**Why this priority**: Es un complemento necesario de la historia principal, pero de menor urgencia — el
sistema ya resuelve la revisión de pagos hoy, esta historia solo confirma que sigue disponible desde la
nueva pantalla.

**Independent Test**: Sobre una locación con al menos un recibo vigente en el período (tenga o no saldo
pendiente), verificar que existe una acción para revisar sus pagos ya registrados, independiente de
"Registrar Pago".

**Acceptance Scenarios**:

1. **Given** una locación con recibos vigentes en el período, **When** se revisa su fila, **Then** existe
   una acción para ver los pagos ya registrados, visible tanto si todavía tiene saldo pendiente como si ya
   está completamente pagada.

---

### Edge Cases

- ¿Qué pasa con una locación que no tiene ningún recibo emitido en el período? No se ofrece ni "Registrar
  Pago" ni la acción de revisión — no hay nada contra qué registrar ni qué revisar, igual que "Emitir
  Recibos" no ofrece "Generar Recibo" sin un contrato activo.
- ¿Qué pasa con una locación cuyo único recibo del período está anulado? No se ofrece "Registrar Pago" — un
  recibo anulado no admite pagos nuevos (ya establecido por specs/032).
- ¿Qué pasa si una locación tiene varios recibos vigentes, unos con saldo pendiente y otros ya pagados?
  "Registrar Pago" sigue disponible en la fila (basta con que uno de ellos tenga saldo pendiente), y lleva a
  la misma pantalla de elección entre recibos que ya usa "Ver Pagos"/"Ver Recibos".

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE agregar un ítem de menú "Registro de Pagos" a la navegación principal, visible
  junto al resto de accesos operativos ya existentes (Locaciones, Gestionar Locaciones, Registrar Lecturas,
  Emitir Recibos, Conceptos de Gasto Fijo, Configuración).
- **FR-002**: Al seleccionar "Registro de Pagos", el sistema DEBE mostrar una pantalla organizada con la
  misma jerarquía de locales (galería, piso, local) que ya usa la pantalla de "Emitir Recibos".
- **FR-003**: El título visible de esta pantalla DEBE ser "Registro de Pagos", consistente con el nombre
  del ítem de menú que lleva a ella.
- **FR-004**: El ítem de menú "Registro de Pagos" DEBE señalarse visualmente como la sección activa cuando
  el administrador se encuentra en esta pantalla, igual que ya ocurre con el resto de los ítems del menú.
- **FR-005**: Cada locación con al menos un recibo vigente del período que todavía tenga saldo pendiente
  DEBE mostrar una acción "Registrar Pago" que lleve directo a la pantalla donde se ingresa el monto del
  pago, replicando el mismo patrón de acceso directo que ya ofrece "Generar Recibo" en "Emitir Recibos".
- **FR-006**: Cada locación con al menos un recibo vigente del período DEBE seguir ofreciendo, por separado
  de "Registrar Pago", una acción para revisar los pagos ya registrados, replicando el mismo patrón que ya
  ofrece "Ver Recibos" en "Emitir Recibos".
- **FR-007**: La pantalla DEBE conservar el mismo selector de período (mes/año, con navegación anterior/
  siguiente) ya usado en "Emitir Recibos".

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador puede llegar a la pantalla de registro de pagos desde cualquier otra pantalla
  del sistema en un solo clic, sin necesidad de conocer una dirección directa.
- **SC-002**: Desde la fila de una locación con saldo pendiente, un administrador llega a la pantalla donde
  puede ingresar un pago en un solo clic, igual que ya ocurre hoy para emitir un recibo.
- **SC-003**: El 100% de las locaciones con recibos vigentes en el período siguen ofreciendo una forma de
  revisar sus pagos ya registrados, tengan o no saldo pendiente.
- **SC-004**: Un administrador ya familiarizado con "Emitir Recibos" reconoce de inmediato la misma
  estructura de filas al abrir "Registro de Pagos", sin necesitar explicación adicional.

## Assumptions

- Esta feature reutiliza y renombra la pantalla de avance de pagos ya entregada por specs/032 (misma
  jerarquía de locales, mismos datos de avance por locación) — no se crea una segunda pantalla paralela con
  la misma estructura bajo un nombre distinto.
- El ítem de menú se ubica junto al resto de accesos operativos del sistema, en un lugar consistente con el
  flujo de trabajo habitual (emitir un recibo → cobrarlo).
- "Registrar Pago" y la acción de revisión pueden coexistir en la misma fila cuando corresponda (saldo
  pendiente y pagos ya registrados a la vez), igual que "Generar Recibo" y "Ver Recibos" ya coexisten hoy en
  "Emitir Recibos".
- Dónde exactamente vive la pantalla para "ingresar el monto del pago" (por ejemplo, el detalle del recibo
  ya existente) no cambia por esta feature — lo que cambia es cómo se llega ahí desde la jerarquía de
  locales.
