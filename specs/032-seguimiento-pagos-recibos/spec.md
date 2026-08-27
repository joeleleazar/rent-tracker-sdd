# Feature Specification: Registro y Seguimiento de Pagos de Recibos

**Feature Branch**: `032-seguimiento-pagos-recibos`

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "Una vez Generado los recibos se debe poder agregar pagos en base al monto de los recibos, se permiten pagos parciales y se debe poder visualizar el avance de estos pagos o si ya esta pagado o no en una nueva vista que copie la estructura de jerarquia de locales que se ve en emision de recibos"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Registrar un pago contra un recibo emitido (Priority: P1)

Un administrador recibe dinero de un inquilino (total o parcial) correspondiente a un recibo ya emitido y
registra ese pago en el sistema, indicando el monto recibido. El sistema no exige que el pago cubra el
recibo completo de una sola vez.

**Why this priority**: Es la capacidad central de la feature — sin poder registrar un pago, no hay nada que
hacer un seguimiento.

**Independent Test**: Abrir un recibo ya emitido, registrar un pago por un monto menor a su total, y
verificar que el sistema lo acepta y lo refleja como pago parcial.

**Acceptance Scenarios**:

1. **Given** un recibo emitido de S/ 960.75 sin pagos previos, **When** el administrador registra un pago
   de S/ 500.00, **Then** el sistema lo acepta y el recibo queda con S/ 500.00 pagados y S/ 460.75
   pendientes.
2. **Given** un recibo con S/ 500.00 ya pagados de un total de S/ 960.75, **When** el administrador
   registra un segundo pago de S/ 460.75, **Then** el sistema lo acepta y el recibo queda completamente
   pagado.
3. **Given** un recibo con S/ 500.00 ya pagados de un total de S/ 960.75, **When** el administrador intenta
   registrar un pago de S/ 600.00, **Then** el sistema rechaza el pago y muestra el monto máximo que
   todavía puede registrarse (S/ 460.75).
4. **Given** un recibo emitido, **When** el administrador intenta registrar un pago de S/ 0.00 o de un
   monto negativo, **Then** el sistema rechaza el pago con un mensaje claro.

---

### User Story 2 - Ver el avance de pago de un período en la jerarquía de locales (Priority: P1)

Un administrador abre una nueva pantalla, organizada con la misma jerarquía de locales (galería, piso,
local) que ya usa la pantalla de emisión de recibos, y para un período elegido ve de un vistazo qué
locaciones tienen sus recibos completamente pagados, cuáles tienen un pago parcial y cuáles todavía no
registran ningún pago.

**Why this priority**: Es la otra mitad explícita del pedido — sin esta vista, saber el estado de cobranza
del mes exige abrir recibo por recibo.

**Independent Test**: Con varios recibos de un mismo período en distintos estados de pago (sin pagos,
parcial, completo), abrir la nueva vista para ese período y verificar que cada locación muestra el estado
correcto sin tener que entrar a cada recibo.

**Acceptance Scenarios**:

1. **Given** un período con recibos en distintos estados de pago, **When** el administrador abre la nueva
   vista para ese período, **Then** ve la misma jerarquía de galería/piso/local que en la emisión de
   recibos, y cada locación con recibo muestra su avance de pago (monto pagado / monto total, o "pagado
   en su totalidad").
2. **Given** la nueva vista abierta, **When** el administrador cambia el período con el mismo selector que
   ya existe en la emisión de recibos, **Then** la vista se actualiza mostrando el avance de pago de los
   recibos del nuevo período.
3. **Given** una locación sin ningún recibo emitido en el período, **When** se muestra en la jerarquía,
   **Then** no se le atribuye ningún estado de pago (se distingue claramente de una locación con recibo
   pero sin pagos registrados).

---

### User Story 3 - Corregir un pago registrado por error (Priority: P2)

Un administrador que registró un pago con un monto equivocado lo corrige (edita el monto) o lo elimina,
y el avance de pago del recibo se recalcula de inmediato.

**Why this priority**: Es soporte para el error humano inevitable al capturar montos — importante, pero
secundario frente a poder registrar y ver pagos correctamente desde el principio.

**Independent Test**: Registrar un pago con un monto incorrecto, corregirlo (o eliminarlo), y verificar que
el avance de pago del recibo refleja el cambio.

**Acceptance Scenarios**:

1. **Given** un recibo con un pago de S/ 500.00 registrado por error (debía ser S/ 300.00), **When** el
   administrador corrige el monto del pago a S/ 300.00, **Then** el avance de pago del recibo se recalcula
   usando el nuevo monto.
2. **Given** un recibo con un pago registrado por error, **When** el administrador elimina ese pago,
   **Then** el sistema pide una confirmación explícita antes de eliminarlo, y una vez confirmado el avance
   de pago del recibo vuelve a calcularse sin ese pago.

---

### Edge Cases

- ¿Qué pasa si un recibo no tiene ningún pago registrado? Debe distinguirse claramente como "sin pagos",
  no confundirse visualmente con un pago parcial de monto cero.
- ¿Qué pasa si se anula un recibo que ya tenía pagos registrados? Los pagos ya registrados se conservan
  como historial; el recibo deja de contarse en el seguimiento de avance de pago (igual que ya queda
  excluido de otros cálculos de cobertura al anularse).
- ¿Qué pasa si una misma locación tiene más de un recibo emitido en el mismo período? El avance de pago se
  muestra por cada recibo individual, no agregado a nivel de locación, para no ocultar cuál recibo
  específico sigue pendiente.
- ¿Qué pasa si se intenta editar un pago de un recibo que ya se anuló? No debe permitirse — el recibo
  anulado no admite nuevos movimientos de pago (edición, eliminación o registro).

## Clarifications

### Session 2026-08-26

- Q: El recibo ya tiene un control manual de "Estado de Pago" (Pendiente / Pagado / Anulado) que el
  administrador cambia a mano. Al introducir pagos individuales y parciales, ¿cómo debe relacionarse ese
  control existente con el nuevo estado de avance calculado a partir de los pagos registrados? → A: Se
  calcula automáticamente a partir de los pagos registrados (sin pagos o pago parcial → Pendiente,
  mostrando el avance; suma de pagos = total → Pagado); el control manual de Pendiente/Pagado se retira.
  La transición manual hacia/desde Anulado se mantiene igual que hoy, con su misma confirmación explícita.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE permitir registrar uno o más pagos contra un recibo ya emitido, cada uno con
  un monto y una fecha.
- **FR-002**: El sistema DEBE permitir que la suma de los pagos registrados contra un recibo sea menor a su
  monto total (pago parcial), sin exigir que se cubra el total en un solo pago.
- **FR-003**: El sistema DEBE impedir registrar un pago que haga que la suma de los pagos supere el monto
  total del recibo, indicando el monto máximo todavía disponible para pagar.
- **FR-004**: El sistema DEBE rechazar el registro de un pago con monto cero o negativo.
- **FR-005**: El sistema DEBE calcular, para cada recibo, su estado de avance de pago a partir de la suma
  de sus pagos registrados: sin pagos, parcialmente pagado (mostrando el monto pagado y el monto
  pendiente), o pagado en su totalidad.
- **FR-006**: El estado "Pendiente"/"Pagado" de un recibo DEBE derivarse automáticamente de FR-005 (sin
  pagos o pago parcial → Pendiente, mostrando el avance; suma de pagos igual al total → Pagado) — el
  control manual existente para elegir directamente entre Pendiente y Pagado se retira. La transición
  manual hacia o desde "Anulado" se mantiene sin cambios, incluyendo su confirmación explícita ya
  existente.
- **FR-007**: El sistema DEBE ofrecer una nueva vista que organice los recibos de un período en la misma
  jerarquía de locales (galería, piso, local) que ya usa la pantalla de emisión de recibos, mostrando el
  avance de pago de cada locación con recibo emitido en ese período.
- **FR-008**: La nueva vista DEBE permitir cambiar de período con el mismo mecanismo de selector y
  navegación anterior/siguiente que ya usa la pantalla de emisión de recibos.
- **FR-009**: El sistema DEBE permitir acceder, desde la nueva vista, al detalle de pagos de un recibo
  específico, tanto para registrar un pago nuevo como para revisar los ya registrados.
- **FR-010**: El sistema DEBE permitir editar el monto de un pago ya registrado, y eliminarlo (con
  confirmación explícita previa), recalculando el avance de pago del recibo correspondiente en ambos
  casos.
- **FR-011**: El sistema DEBE excluir a los recibos anulados del cálculo y de la visualización del avance
  de pago, y DEBE impedir registrar, editar o eliminar pagos sobre un recibo anulado.
- **FR-012**: Cada pago registrado DEBE conservar quién lo registró y cuándo, para trazabilidad.

### Key Entities *(include if feature involves data)*

- **Pago**: un movimiento de pago registrado contra un recibo — monto, fecha, quién lo registró. Varios
  pagos pueden corresponder a un mismo recibo.
- **Recibo** (ya existente): su estado de avance de pago (sin pagos / parcial / pagado en su totalidad)
  pasa a derivarse de la suma de sus pagos en vez de fijarse manualmente; conserva su transición manual
  hacia/desde "Anulado".

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador puede registrar un pago (total o parcial) contra un recibo emitido en menos
  de 30 segundos desde que abre ese recibo.
- **SC-002**: Al abrir la nueva vista para un período, el 100% de las locaciones con al menos un recibo
  emitido en ese período muestran su avance de pago sin necesidad de abrir cada recibo individualmente.
- **SC-003**: El sistema rechaza el 100% de los intentos de registrar un pago que exceda el saldo pendiente
  de un recibo.
- **SC-004**: Un administrador puede identificar qué locaciones de un período todavía tienen saldo
  pendiente de pago en menos de 10 segundos, sin sumar montos manualmente.
- **SC-005**: Para cualquier recibo, el monto pagado más el monto pendiente mostrados coinciden siempre
  exactamente con su monto total.

## Assumptions

- El monto máximo que pueden sumar los pagos de un recibo es su total ya calculado (renta + conceptos);
  esta feature no introduce mora, recargos ni descuentos.
- Los pagos se registran manualmente por un administrador — no hay integración con pasarelas de pago,
  bancos ni conciliación automática.
- La nueva vista de avance de pagos es una pantalla adicional (consulta + punto de entrada para registrar
  pagos); no reemplaza ni modifica la pantalla ya existente de emisión masiva de recibos.
- Un recibo anulado no admite pagos nuevos ni cambios sobre los que ya tenía — un recibo se anula como una
  operación independiente de su historial de pagos, que se conserva para auditoría.
