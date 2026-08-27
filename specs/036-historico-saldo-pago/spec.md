# Feature Specification: Saldo Histórico en el Comprobante de Pago

**Feature Branch**: `036-historico-saldo-pago`

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "debe quedar como historico cuanto era la deuda a pagar al momento del pago
porque cuando vea historico y el pago ya esta hecho me saldra como total pagado pero al momento del pago
habia un pendiente"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ver el avance de pago tal como era al momento de ese pago, no el actual (Priority: P1)

Un administrador registró dos pagos parciales de un recibo en momentos distintos; hoy el recibo ya está
completamente pagado. Al abrir el comprobante del **primer** pago (por ejemplo, para reimprimirlo o
verificar qué se le entregó al inquilino en ese momento), el administrador necesita ver el acumulado y el
saldo pendiente **tal como estaban justo después de ese primer pago** — no el acumulado y saldo actuales del
recibo, que ya reflejan el segundo pago y muestran todo pagado.

**Why this priority**: Es el defecto concreto reportado — el comprobante de un pago es, por diseño
(specs/035), un documento que se imprime y se firma como evidencia de lo pagado hasta ese momento. Si
recalcula su acumulado/saldo con datos actuales en vez de históricos, dos comprobantes impresos en momentos
distintos para el mismo recibo terminan mostrando la misma cifra de "saldo pendiente" (la actual, casi
siempre S/ 0.00 una vez el recibo se completa), lo cual contradice la realidad al momento en que cada uno se
firmó.

**Independent Test**: Registrar dos pagos parciales sobre un mismo recibo en momentos distintos hasta
completarlo; abrir el comprobante del primer pago y confirmar que su acumulado y saldo pendiente
corresponden únicamente a ese primer pago (no incluyen el segundo), aunque el recibo ya esté completamente
pagado.

**Acceptance Scenarios**:

1. **Given** un recibo con dos pagos parciales registrados en momentos distintos, que entre ambos completan
   el total, **When** se abre el comprobante del primer pago, **Then** el acumulado mostrado corresponde
   solo al primer pago y el saldo pendiente mostrado es el que quedaba después de ese primer pago (no cero).
2. **Given** el mismo recibo, **When** se abre el comprobante del segundo pago, **Then** el acumulado
   mostrado corresponde a la suma de ambos pagos y el saldo pendiente mostrado es cero (o el que
   corresponda), reflejando que ese segundo pago sí completó el total.
3. **Given** un recibo con un único pago registrado, **When** se abre su comprobante, **Then** el acumulado y
   el saldo pendiente mostrados no cambian respecto al comportamiento ya existente (specs/035) — esta
   historia no afecta el caso de un solo pago.

---

### Edge Cases

- ¿Qué pasa si se elimina un pago anterior a otro ya registrado? El acumulado/saldo histórico del pago
  posterior debe recalcularse para excluir el pago eliminado — refleja la secuencia real de pagos que
  efectivamente existe, no la que existía en un momento pasado que ya no es válida.
- ¿Qué pasa si se edita el monto de un pago anterior a otro? El acumulado/saldo histórico del pago posterior
  debe recalcularse con el monto corregido del pago anterior — mismo criterio que el caso anterior.
- ¿Qué pasa con el comprobante de un pago sobre un recibo ya anulado? Sigue disponible (specs/035) y su
  acumulado/saldo histórico se calcula con el mismo criterio, sin verse afectado por la anulación.

## Clarifications

### Session 2026-08-26

- Q: Para calcular el acumulado/saldo histórico de un pago, ¿qué determina cuáles pagos anteriores se
  incluyen "hasta ese pago inclusive"? → A: Orden de registro en el sistema (orden de creación) — editar la
  `fecha_pago` que ingresa el administrador nunca reordena el historial ya calculado ni afecta comprobantes
  de otros pagos ya impresos/firmados.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El comprobante de un pago individual DEBE mostrar el monto acumulado pagado calculado
  únicamente a partir de los pagos de ese recibo registrados hasta ese pago inclusive, en el orden real en
  que cada pago fue registrado en el sistema (no la fecha de pago que ingresa el administrador, que puede
  ser retroactiva — ver Clarifications), no a partir de todos los pagos actuales del recibo.
- **FR-002**: El comprobante de un pago individual DEBE mostrar el saldo pendiente calculado como el total
  del recibo menos ese acumulado histórico (FR-001), no el saldo pendiente actual del recibo.
- **FR-003**: El sistema DEBE recalcular el acumulado/saldo histórico de un pago automáticamente cuando
  cambie la composición de los pagos anteriores a él en la misma secuencia (se edita o elimina un pago
  anterior) — nunca debe quedar una cifra histórica inconsistente con los pagos que efectivamente existen.
- **FR-004**: El monto propio de ese pago (lo que ese pago en particular aportó) DEBE seguir mostrándose sin
  cambios respecto al comportamiento ya existente (specs/035) — esta feature no modifica esa cifra, solo el
  acumulado y el saldo pendiente que la acompañan.
- **FR-005**: Un recibo con un único pago DEBE seguir comportándose exactamente igual que hoy (specs/035) —
  su acumulado histórico es, por definición, igual a ese único pago.

### Key Entities

- **Pago**: sin nuevos atributos persistentes propios en esta feature — lo que cambia es cómo se calcula, al
  mostrar el comprobante de un pago puntual, el acumulado y el saldo pendiente que lo acompañan: a partir de
  los pagos del recibo hasta ese pago inclusive, no de todos los pagos actuales del recibo.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Al abrir el comprobante de cualquier pago de un recibo con más de un pago registrado, el
  acumulado y el saldo pendiente mostrados corresponden exactamente a la situación de ese recibo
  inmediatamente después de ese pago — nunca a una situación posterior.
- **SC-002**: Dos comprobantes impresos para dos pagos distintos del mismo recibo, en momentos distintos,
  muestran cifras de acumulado/saldo pendiente diferentes entre sí siempre que efectivamente lo eran en la
  realidad (es decir, dejan de coincidir artificialmente en el saldo actual del recibo).
- **SC-003**: El 100% de los comprobantes de pago de recibos con un único pago no cambian su comportamiento
  respecto a antes de esta feature.

## Assumptions

- Esta feature es una corrección de cálculo sobre el comprobante de pago ya entregado por specs/035 — no
  introduce una pantalla, ruta ni entidad nueva.
- El resto de la aplicación donde hoy se muestra el avance de pago **actual** de un recibo (el detalle del
  recibo, "Registro de Pagos"/specs/033, la barra de progreso/specs/034) sigue mostrando el estado **actual**
  sin cambios — esos lugares son, por diseño, indicadores en vivo del estado presente del recibo, no
  documentos históricos; el ajuste de esta feature se limita al comprobante de un pago individual
  (specs/035), que sí está pensado como constancia de un momento puntual.
- El total del recibo (`Recibo::total()`) no se historiza en esta feature — se asume que el total de un
  recibo no cambia después de haber recibido pagos sobre él en el uso normal del sistema; si el total
  llegara a editarse después de registrar pagos, ese escenario queda fuera del alcance de esta feature.
