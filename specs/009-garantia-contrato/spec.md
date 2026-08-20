# Feature Specification: Registro de Garantía Entregada por Contrato

**Feature Branch**: `009-garantia-contrato`

**Created**: 2026-08-19

**Status**: Ready

**Input**: User description: "Cada Contrato tiene tambien una garantia entregada se debe almacenar esa información"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Registro de la Garantía Entregada al Firmar el Contrato (Priority: P1)

Como Administrador, quiero registrar en cada contrato el monto de la garantía entregada por el inquilino y la fecha en que se recibió, de modo que quede constancia formal de ese depósito y pueda consultarlo en cualquier momento durante la vigencia del contrato.

**Why this priority**: Sin este registro no existe respaldo alguno de cuánto dinero se recibió como garantía por cada locación, lo cual genera riesgo de disputas con el inquilino al momento de finalizar el contrato y devolver (o retener) el depósito.

**Independent Test**: Se puede verificar creando o editando un contrato, ingresando el monto de garantía "S/ 1500.00" y la fecha de entrega "2026-08-19", guardando el registro y comprobando que la información quede asociada correctamente a ese contrato y visible en su detalle.

**Acceptance Scenarios**:

1. **Given** el formulario de registro de un contrato, **When** el administrador ingresa el monto de garantía "S/ 1500.00", la fecha de entrega "2026-08-19" y selecciona el medio de entrega "Efectivo", **Then** el sistema guarda esta información asociada al contrato y la muestra en su detalle.
2. **Given** un contrato ya guardado sin garantía registrada (por ejemplo, un contrato antiguo migrado), **When** el administrador consulta su detalle, **Then** el sistema indica claramente "Sin garantía registrada" en lugar de mostrar un monto vacío o confuso.

---

### User Story 2 - Consulta de la Garantía desde el Detalle del Contrato (Priority: P2)

Como Administrador, quiero ver de forma clara y destacada el monto y la fecha de la garantía entregada al consultar el detalle de un contrato, de modo que pueda verificar rápidamente esta información sin tener que buscarla en otro lugar.

**Why this priority**: Facilita la consulta rápida al momento de atender preguntas del inquilino o al preparar la devolución de la garantía al finalizar el contrato.

**Independent Test**: Se puede verificar consultando el detalle de un contrato con garantía registrada y comprobando que el monto, la fecha de entrega y el medio de entrega se muestren con tipografía de al menos 18px y alto contraste, sin necesidad de navegar a otra pantalla.

**Acceptance Scenarios**:

1. **Given** un contrato con garantía registrada de "S/ 1500.00" entregada en efectivo el "2026-08-19", **When** el administrador consulta el detalle del contrato, **Then** el sistema muestra esta información de forma destacada junto a los demás datos del contrato (costo de renta, fechas de vigencia).

---

### User Story 3 - Registro de la Devolución o Retención de la Garantía al Finalizar el Contrato (Priority: P2)

Como Administrador, quiero registrar cómo se resolvió la garantía de un contrato al finalizarlo (devuelta totalmente, devuelta parcialmente con una retención, o retenida totalmente), indicando el monto devuelto, el monto retenido y el motivo de la retención cuando corresponda, de modo que quede constancia clara y auditable de ese cierre económico frente al inquilino.

**Why this priority**: Es habitual que se retenga parte o toda la garantía por daños, deudas pendientes u otros motivos justificados; sin este registro no hay forma de sustentar esa decisión ante el inquilino ni de llevar un control preciso del dinero que efectivamente se devolvió.

**Independent Test**: Se puede verificar abriendo un contrato con garantía de "S/ 1500.00" ya entregada, registrando su resolución con "S/ 1200.00" devueltos y "S/ 300.00" retenidos por el motivo "Reparación de puerta dañada", guardando el registro y comprobando que el contrato refleje el detalle completo de la resolución.

**Acceptance Scenarios**:

1. **Given** un contrato con garantía entregada de "S/ 1500.00", **When** el administrador registra la resolución de la garantía indicando devolución total de "S/ 1500.00" sin retención, **Then** el sistema guarda el registro, marca la garantía como "Resuelta" y muestra la fecha de devolución.
2. **Given** un contrato con garantía entregada de "S/ 1500.00", **When** el administrador registra "S/ 1200.00" como monto devuelto, "S/ 300.00" como monto retenido y el motivo "Reparación de puerta dañada", **Then** el sistema valida que la suma de ambos montos (S/ 1500.00) coincida exactamente con el monto de garantía entregada, guarda el registro y muestra el detalle completo (devuelto, retenido y motivo) en el contrato.
3. **Given** el formulario de resolución de garantía con un monto retenido mayor a cero, **When** el administrador intenta guardar sin ingresar un motivo de retención, **Then** el sistema bloquea el guardado y muestra un mensaje de error explícito y de alto contraste indicando que el motivo es obligatorio cuando existe retención.
4. **Given** un contrato cuya garantía ya fue marcada como "Resuelta", **When** el administrador presiona "Corregir Resolución de Garantía", **Then** el sistema solicita una confirmación explícita de alta visibilidad antes de permitir editar los montos ya registrados de devolución y retención.

### Edge Cases

- **Contrato sin garantía**: Si un contrato no tiene garantía asociada (por ejemplo, se pactó sin depósito), el sistema permite guardar el contrato sin este dato, sin bloquear el registro ni mostrar errores de validación.
- **Edición del monto de garantía después de registrado**: Si el administrador corrige el monto o la fecha de garantía ya guardada, el sistema permite la edición y conserva el valor más reciente, sin mantener un historial de cambios previos en esta iteración.
- **Garantía con monto igual a cero**: Si el administrador ingresa "S/ 0.00" como monto de garantía, el sistema lo trata como "sin garantía entregada" para efectos de visualización, evitando mostrar un registro de garantía vacío como si fuera válido.
- **Suma de montos devuelto y retenido distinta al monto de garantía**: Si la suma del monto devuelto y el monto retenido no coincide exactamente con el monto de garantía entregada, el sistema bloquea el guardado y muestra un mensaje explícito indicando la diferencia, para evitar que se "pierda" o se "invente" dinero en el registro.
- **Retención total de la garantía**: Si el administrador registra "S/ 0.00" como monto devuelto y el monto total de la garantía como retenido, el sistema exige igualmente un motivo de retención antes de guardar.
- **Resolución de garantía sin garantía previamente registrada**: Si el contrato no tiene un monto de garantía mayor a cero, el sistema no ofrece la opción de registrar su resolución, dado que no existe garantía que devolver o retener.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST permitir registrar, para cada contrato, el monto de la garantía entregada por el inquilino como un campo numérico decimal exacto, opcional.
- **FR-002**: El sistema MUST permitir registrar la fecha en que se recibió la garantía, cuando se haya registrado un monto de garantía mayor a cero.
- **FR-003**: El sistema MUST permitir registrar el medio de entrega de la garantía (por ejemplo, efectivo, transferencia o cheque) como un dato adicional opcional del contrato.
- **FR-004**: El sistema MUST mostrar la información de garantía (monto, fecha y medio de entrega) de forma visible en el detalle del contrato, indicando explícitamente "Sin garantía registrada" cuando no exista un monto mayor a cero.
- **FR-005**: El sistema MUST permitir editar la información de garantía de un contrato ya guardado en cualquier momento durante su vigencia.
- **FR-006**: El sistema MUST permitir registrar la resolución de la garantía de un contrato con monto de garantía mayor a cero, indicando el monto devuelto y el monto retenido.
- **FR-007**: El sistema MUST exigir un motivo de retención (texto obligatorio) cuando el monto retenido registrado sea mayor a cero.
- **FR-008**: El sistema MUST validar que la suma del monto devuelto y el monto retenido sea exactamente igual al monto de garantía entregada, bloqueando el guardado y mostrando un mensaje explícito en caso de discrepancia.
- **FR-009**: El sistema MUST registrar la fecha en que se guarda la resolución de la garantía y marcar el estado de la garantía como "Resuelta" una vez registrada.
- **FR-010**: El sistema MUST solicitar una confirmación explícita de alta visibilidad (Senior-First) antes de permitir editar una resolución de garantía ya registrada.
- **FR-011**: El sistema MUST registrar de forma transaccional y atómica (`DB::transaction`) el guardado de la información de garantía (entrega y resolución) junto con el resto de los datos del contrato.
- **FR-012**: La interfaz de registro y consulta de la garantía del contrato MUST cumplir los estándares Senior-First del proyecto (tipografía mínima de 18px, alto contraste y etiquetas explícitas como "Monto de Garantía Entregada", "Fecha de Entrega de Garantía", "Registrar Resolución de Garantía").

### Key Entities *(include if feature involves data)*

- **Contrato** (extensión de la entidad existente): Se agregan los campos de garantía entregada y su resolución.
  - `monto_garantia` (Decimal exacto, opcional, nulo o 0.00 si no aplica)
  - `fecha_entrega_garantia` (Fecha, opcional, obligatoria solo si `monto_garantia` es mayor a cero)
  - `medio_entrega_garantia` (Cadena de caracteres/Enum, ej. "efectivo", "transferencia", "cheque"; opcional)
  - `estado_garantia` (Cadena de caracteres/Enum, valores: "entregada", "resuelta"; por defecto "entregada" cuando `monto_garantia` es mayor a cero)
  - `monto_devuelto_garantia` (Decimal exacto, nulo hasta que se registre la resolución)
  - `monto_retenido_garantia` (Decimal exacto, nulo hasta que se registre la resolución; por defecto 0.00)
  - `motivo_retencion_garantia` (Texto, obligatorio solo si `monto_retenido_garantia` es mayor a cero)
  - `fecha_resolucion_garantia` (Marca de tiempo, nula hasta que se registre la resolución)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los contratos nuevos permiten registrar el monto, la fecha y el medio de entrega de la garantía sin errores, en menos de 1 minuto adicional al tiempo de registro del contrato.
- **SC-002**: El 100% de los contratos con garantía registrada muestran esta información de forma visible y legible (mínimo 18px, alto contraste) en su detalle, sin necesidad de navegación adicional.
- **SC-003**: El 100% de los contratos sin garantía registrada muestran el mensaje "Sin garantía registrada" en lugar de un campo vacío ambiguo.
- **SC-004**: El 100% de los intentos de registrar una resolución de garantía donde la suma de monto devuelto y monto retenido no coincide con el monto entregado son bloqueados antes de guardarse.
- **SC-005**: El 100% de las resoluciones de garantía con monto retenido mayor a cero incluyen un motivo de retención registrado, consultable en el detalle del contrato.

## Assumptions

- **A-001**: Un contrato tiene, como máximo, un único registro de garantía (un monto y una fecha de entrega); no se contempla en esta iteración el registro de garantías entregadas en múltiples partes o cuotas.
- **A-002**: El registro de garantía no requiere adjuntar un comprobante o documento digitalizado (a diferencia del contrato notariado ya cubierto en la funcionalidad de gestión de contratos); esto podrá tratarse como una funcionalidad futura si se requiere.
- **A-003**: No se mantiene un historial de auditoría de cambios sobre el monto o fecha de la garantía en esta iteración; el sistema conserva únicamente el valor vigente más reciente.
- **A-004**: La resolución de la garantía (devolución/retención) puede registrarse en cualquier momento mientras el contrato tiene un monto de garantía mayor a cero; no se exige que el contrato esté "vencido" o "rescindido" para permitir este registro, dado que en la práctica algunas devoluciones pueden gestionarse antes del cierre formal del contrato.
- **A-005**: El motivo de retención es un campo de texto libre (sin catálogo predefinido de motivos) en esta iteración.
