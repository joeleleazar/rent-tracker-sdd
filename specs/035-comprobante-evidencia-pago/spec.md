# Feature Specification: Comprobante de Pago Firmado y Evidencia de Pago

**Feature Branch**: `035-comprobante-evidencia-pago`

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "Considera que los pagos se hacen sobre los recibos, ademas agrega la opcion de poder subir una evidencia del pago ya que al hacer un adelanto de pago se debe poder exportar el recibo indicando el avance del pago y cuanto es y cuando queda pendiente, este se va imprimir y el que recibe el pago debe firmarlo y esto se debe subir como evidencia"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Exportar un comprobante del pago recién registrado (Priority: P1)

Un administrador acaba de registrar un pago (total o parcial) contra un recibo y necesita un documento
imprimible propio de ese pago — no del recibo completo — que muestre cuánto se pagó en esa transacción,
cuánto lleva pagado el recibo en total hasta ahora, y cuánto queda pendiente. Lo imprime para que la persona
que recibe el dinero lo firme como constancia de ese pago puntual.

**Why this priority**: Es el documento físico que ancla todo el resto de la feature — sin poder exportarlo,
no hay nada que firmar ni evidencia que subir después.

**Independent Test**: Registrar un pago parcial contra un recibo y exportar su comprobante, verificando que
muestra el monto de ese pago, el monto acumulado pagado y el saldo pendiente del recibo.

**Acceptance Scenarios**:

1. **Given** un pago recién registrado contra un recibo con saldo pendiente, **When** el administrador
   exporta su comprobante, **Then** el documento muestra el monto de ese pago, el monto total pagado hasta
   ahora (incluyendo pagos anteriores del mismo recibo) y el saldo que queda pendiente.
2. **Given** un pago que completa el total del recibo, **When** se exporta su comprobante, **Then** el
   documento indica que el recibo queda completamente pagado, con saldo pendiente en cero.
3. **Given** el comprobante de un pago exportado, **When** se envía a impresión, **Then** el documento se
   ve completo y legible en papel, con espacio para la firma de quien recibe el pago.

---

### User Story 2 - Subir la evidencia del pago firmado (Priority: P1)

El mismo administrador, ya con el comprobante impreso y firmado por quien recibió el pago, vuelve al
sistema y sube una foto o escaneo de ese documento firmado como evidencia, quedando asociada al pago
correspondiente para consulta posterior.

**Why this priority**: Es la otra mitad explícita del pedido — sin poder subir la evidencia, imprimir y
firmar el comprobante no deja ningún rastro dentro del sistema.

**Independent Test**: Sobre un pago ya registrado, subir un archivo como evidencia y verificar que queda
asociado a ese pago y puede volver a consultarse.

**Acceptance Scenarios**:

1. **Given** un pago ya registrado, **When** el administrador sube un archivo de imagen o PDF como
   evidencia, **Then** el archivo queda asociado a ese pago específico.
2. **Given** un pago con evidencia ya subida, **When** se revisa el detalle de ese pago, **Then** es
   posible abrir o descargar la evidencia subida.
3. **Given** un pago con evidencia subida por error, **When** el administrador sube un archivo nuevo para
   ese mismo pago, **Then** la evidencia anterior se reemplaza por la nueva.

---

### Edge Cases

- ¿Qué pasa si se intenta exportar el comprobante de un pago sobre un recibo que después fue anulado? El
  comprobante deja constancia del pago tal como fue registrado; la anulación del recibo es un estado
  posterior e independiente (ya cubierto por specs/032) y no impide seguir consultando el comprobante de un
  pago ya hecho.
- ¿Qué pasa si se edita el monto de un pago después de haber exportado y firmado su comprobante? El
  comprobante ya impreso y firmado no se actualiza retroactivamente (es un documento físico ya entregado);
  el sistema sigue mostrando el avance recalculado en pantalla, pero el archivo de evidencia sigue
  correspondiendo al comprobante tal como se firmó.
- ¿Qué pasa si se intenta subir un archivo de un tipo o tamaño no admitido como evidencia? El sistema
  rechaza la carga con un mensaje claro, sin afectar el pago ya registrado.
- ¿Qué pasa si un pago todavía no tiene evidencia subida? El sistema lo indica claramente como pendiente
  de evidencia, sin bloquear ninguna otra acción sobre ese pago.

## Clarifications

### Session 2026-08-26

- Q: El comprobante que se va a imprimir y firmar para dejar constancia de un pago, ¿debe ser un documento
  nuevo propio de cada pago individual, o el comprobante del recibo completo (specs/031) actualizado para
  mostrar el último pago? → A: Un documento nuevo, propio de cada pago — distinto del comprobante del
  recibo completo — porque lo que se firma es esa transacción puntual, no el recibo entero.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE ofrecer, para cada pago ya registrado, un comprobante exportable/imprimible
  propio de ese pago, independiente del comprobante del recibo completo (specs/031).
- **FR-002**: El comprobante de un pago DEBE mostrar, como mínimo: a qué recibo corresponde (locación,
  inquilino, período), el monto de ese pago específico, la fecha del pago, el monto total pagado del recibo
  hasta ese momento (acumulado, incluyendo pagos anteriores), y el saldo que queda pendiente.
- **FR-003**: El comprobante de un pago DEBE incluir un espacio para la firma de quien recibe el pago.
- **FR-004**: El comprobante de un pago DEBE verse completo y legible al imprimirse, igual que ya se exige
  para el comprobante del recibo completo (specs/031 FR-013).
- **FR-005**: El sistema DEBE permitir subir un archivo (imagen o PDF) como evidencia de un pago ya
  registrado, asociado específicamente a ese pago.
- **FR-006**: El sistema DEBE permitir consultar (abrir o descargar) la evidencia ya subida de un pago.
- **FR-007**: El sistema DEBE permitir reemplazar la evidencia de un pago subiendo un archivo nuevo,
  sustituyendo la anterior.
- **FR-008**: El sistema DEBE indicar claramente, para cada pago, si todavía no tiene evidencia subida.
- **FR-009**: El sistema DEBE rechazar la carga de un archivo de evidencia de un tipo o tamaño no admitido,
  mostrando un mensaje claro, sin afectar el registro del pago.
- **FR-010**: Subir evidencia de un pago NO DEBE ser obligatorio para registrar el pago en sí — el pago
  puede registrarse y usarse normalmente (contar para el avance del recibo) sin tener todavía una evidencia
  cargada.

### Key Entities *(include if feature involves data)*

- **Pago** (ya existente, specs/032): se le agrega la posibilidad de tener asociado un archivo de
  evidencia (imagen o PDF) del comprobante firmado.
- **Comprobante de pago**: el documento exportable/imprimible propio de un pago — no una entidad
  persistida nueva, se genera a partir de los datos ya existentes del pago y del recibo al que pertenece.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador puede exportar el comprobante de un pago recién registrado en menos de 10
  segundos desde que lo registra.
- **SC-002**: El 100% de los comprobantes de pago exportados muestran montos consistentes: el monto de ese
  pago más el saldo pendiente que indican coincide siempre con el total del recibo correspondiente.
- **SC-003**: Un administrador puede subir la evidencia de un pago y volver a consultarla después en menos
  de 3 pasos desde el detalle de ese pago.
- **SC-004**: El 100% de los intentos de subir un archivo de evidencia de un tipo o tamaño no admitido son
  rechazados sin afectar el pago al que correspondían.

## Assumptions

- El comprobante de un pago se genera a partir de datos ya existentes (el pago y el recibo al que
  pertenece) en el momento de exportarlo — no es un documento que se guarda con un valor fijo en el
  tiempo; si se abre de nuevo más adelante, refleja el estado más reciente del recibo.
- Un pago admite una única evidencia a la vez (subir una nueva reemplaza la anterior) — no se administra un
  historial de múltiples archivos de evidencia por pago.
- Los tipos de archivo admitidos como evidencia son imagen (foto o escaneo del comprobante firmado) o PDF,
  siguiendo el mismo criterio ya usado en el sistema para adjuntar documentos (specs de gestión documental
  de contratos); el límite de tamaño sigue una práctica estándar razonable para este tipo de archivo.
- Subir la evidencia es una acción manual posterior a imprimir y firmar el comprobante — el sistema no
  imprime ni escanea nada por sí mismo, solo genera el documento a exportar y recibe el archivo ya firmado
  de vuelta.
