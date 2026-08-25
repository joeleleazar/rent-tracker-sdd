# Feature Specification: Estado de Recibos y Envío por WhatsApp o Impresión

**Feature Branch**: `007-estado-envio-recibo`

**Created**: 2026-08-19

**Status**: Ready

**Input**: User description: "Por cada recibo Generado se debe poder marcar si fue pagado, anulado, pendiente, tambien debe poder enviarse como imagen a whatsapp o imprimirse"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Marcar el Estado de Pago de un Recibo (Priority: P1)

Como Administrador, quiero marcar cada recibo generado como "pendiente", "pagado" o "anulado", de modo que pueda llevar un control claro de qué recibos ya fueron cobrados y cuáles siguen pendientes o fueron invalidados.

**Why this priority**: Es la funcionalidad central del negocio: sin un estado de pago visible, el administrador no puede saber qué inquilinos están al día ni identificar cobros pendientes o comprobantes anulados.

**Independent Test**: Se puede verificar generando un recibo (que inicia en estado "pendiente"), marcándolo como "pagado" desde su detalle, y comprobando que el estado se actualice y se refleje inmediatamente en el listado de recibos de la locación.

**Acceptance Scenarios**:

1. **Given** un recibo recién emitido, **When** el administrador consulta su detalle, **Then** el sistema muestra el estado "Pendiente" de forma visible con un indicador de alto contraste.
2. **Given** un recibo en estado "Pendiente", **When** el administrador presiona el botón "Marcar como Pagado" (mínimo 48x48px), **Then** el sistema actualiza el estado a "Pagado", registra la fecha en que se marcó el pago, y lo refleja de inmediato en el listado de recibos.
3. **Given** un recibo en estado "Pendiente" o "Pagado", **When** el administrador presiona el botón "Anular Recibo", **Then** el sistema solicita una confirmación explícita de alta visibilidad ("Sí, anular recibo" vs "No, cancelar") antes de cambiar el estado a "Anulado", incluso si el recibo ya estaba marcado como "Pagado".
4. **Given** un recibo en estado "Anulado", **When** el administrador presiona el botón "Revertir Anulación" y elige el nuevo estado ("Pendiente" o "Pagado"), **Then** el sistema solicita una confirmación explícita antes de aplicar el cambio, dado que revierte una anulación previa.

---

### User Story 2 - Envío del Recibo como Imagen por WhatsApp (Priority: P2)

Como Administrador, quiero enviar un recibo como imagen a través de WhatsApp directamente desde su detalle, de modo que pueda entregárselo rápidamente al inquilino sin tener que imprimirlo o escanearlo manualmente.

**Why this priority**: Agiliza la entrega del comprobante al inquilino usando un canal de comunicación ya habitual, reduciendo el tiempo administrativo y el uso de papel.

**Independent Test**: Se puede verificar abriendo el detalle de un recibo ya emitido, presionando el botón "Enviar por WhatsApp", y comprobando que el sistema genere una imagen legible del recibo y habilite su envío por WhatsApp.

**Acceptance Scenarios**:

1. **Given** el detalle de un recibo ya emitido, **When** el administrador presiona el botón "Enviar por WhatsApp" (mínimo 48x48px, etiqueta explícita), **Then** el sistema genera una imagen del recibo con todos sus conceptos y montos legibles, y habilita su envío por WhatsApp.
2. **Given** que la generación de la imagen del recibo fue exitosa, **When** el administrador confirma el envío, **Then** el sistema muestra un indicador de éxito persistente confirmando que la imagen quedó lista para compartirse por WhatsApp.

---

### User Story 3 - Impresión del Recibo (Priority: P2)

Como Administrador, quiero imprimir un recibo ya emitido, de modo que pueda entregar una copia física al inquilino cuando lo prefiera o lo requiera.

**Why this priority**: Muchos inquilinos prefieren o requieren un comprobante físico como respaldo del pago.

**Independent Test**: Se puede verificar abriendo el detalle de un recibo ya emitido, presionando el botón "Imprimir Recibo", y comprobando que se genere una vista de impresión legible con todos los conceptos, montos y el estado del recibo.

**Acceptance Scenarios**:

1. **Given** el detalle de un recibo ya emitido, **When** el administrador presiona el botón "Imprimir Recibo", **Then** el sistema muestra una vista de impresión clara y legible, incluyendo todos los conceptos, montos, el periodo facturado y el estado actual del recibo.

### Edge Cases

- **Cambio de estado con confirmación**: Cualquier cambio de estado hacia o desde "Anulado" MUST solicitar confirmación explícita de alta visibilidad, dado que es una acción con impacto sobre la validez del comprobante.
- **Reversión de un recibo anulado**: Dado que las transiciones son libres, un recibo "Anulado" puede revertirse a "Pendiente" o "Pagado"; al hacerlo, el sistema limpia la fecha de anulación previamente registrada y aplica la fecha correspondiente al nuevo estado.
- **Envío o impresión de un recibo anulado**: Si el administrador intenta enviar por WhatsApp o imprimir un recibo en estado "Anulado", el sistema genera igualmente la imagen o vista de impresión, pero incluye de forma visible y destacada la marca "ANULADO" sobre el documento para evitar confusión con un comprobante válido.
- **Recibo sin conexión a WhatsApp instalado**: Si el dispositivo del administrador no tiene WhatsApp disponible para recibir la imagen generada, el sistema informa de forma clara que no se pudo completar el envío directo, pero permite descargar o guardar la imagen generada para compartirla por otro medio.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST asignar automáticamente el estado "Pendiente" a todo recibo al momento de su emisión.
- **FR-002**: El sistema MUST permitir al Administrador cambiar el estado de un recibo entre "Pendiente", "Pagado" y "Anulado" desde su vista de detalle.
- **FR-003**: El sistema MUST registrar la fecha y hora en que un recibo cambia a estado "Pagado" o "Anulado".
- **FR-004**: El sistema MUST solicitar una confirmación explícita de alta visibilidad antes de cambiar el estado de un recibo hacia o desde "Anulado" (es decir, tanto al anular un recibo como al revertir un recibo anulado a otro estado).
- **FR-005**: El sistema MUST permitir todas las transiciones de estado libremente y en cualquier momento entre "Pendiente", "Pagado" y "Anulado" (por ejemplo, anular un recibo ya "Pagado", o revertir un recibo "Anulado" de vuelta a "Pendiente" o "Pagado"), sin restricciones de secuencia obligatoria.
- **FR-006**: El sistema MUST permitir generar, a partir del detalle de un recibo ya emitido, una imagen legible que incluya todos sus conceptos, montos, periodo facturado y estado actual.
- **FR-007**: El sistema MUST habilitar el envío de la imagen generada del recibo mediante el mecanismo nativo de compartir del dispositivo o navegador del Administrador, permitiéndole elegir manualmente WhatsApp (u otra aplicación disponible) y el destinatario en cada envío, sin requerir que el sistema almacene o gestione números de teléfono.
- **FR-008**: El sistema MUST permitir generar, a partir del detalle de un recibo ya emitido, una vista de impresión legible con los mismos datos que la imagen (conceptos, montos, periodo, estado).
- **FR-009**: El sistema MUST incluir de forma visible la marca "ANULADO" en la imagen o vista de impresión de cualquier recibo cuyo estado sea "Anulado".
- **FR-010**: La interfaz de cambio de estado, envío por WhatsApp e impresión de recibos MUST usar alto contraste y etiquetas explícitas ("Marcar como Pagado", "Anular Recibo", "Enviar por WhatsApp", "Imprimir Recibo").

### Key Entities *(include if feature involves data)*

- **Recibo** (extensión de la entidad introducida en las funcionalidades de condiciones del contrato y lecturas de medidor): Se agrega el estado de pago y su trazabilidad.
  - `estado` (Cadena de caracteres/Enum, valores: "pendiente", "pagado", "anulado"; por defecto "pendiente"; transiciones libres entre los tres valores)
  - `fecha_pago` (Marca de tiempo, se asigna al entrar en "pagado" y se limpia a nulo si el recibo sale de ese estado)
  - `fecha_anulacion` (Marca de tiempo, se asigna al entrar en "anulado" y se limpia a nulo si el recibo sale de ese estado)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los recibos emitidos inician en estado "Pendiente" y su estado se refleja correctamente en el listado y detalle inmediatamente después de cualquier cambio.
- **SC-002**: Un administrador puede generar y dejar lista para compartir por WhatsApp la imagen de un recibo en menos de 30 segundos desde su detalle.
- **SC-003**: Un administrador puede generar la vista de impresión de un recibo en menos de 15 segundos desde su detalle.
- **SC-004**: El 100% de las imágenes o vistas de impresión de recibos en estado "Anulado" muestran la marca "ANULADO" de forma visible y de alto contraste.

## Assumptions

- **A-001**: Marcar un recibo como "Pagado" es una acción binaria (pagado o no pagado); no se contempla el registro de pagos parciales o abonos progresivos en esta iteración.
- **A-002**: La imagen generada del recibo reutiliza la misma información y formato que la vista de impresión, adaptada a un formato de imagen apto para compartir por mensajería.
- **A-003**: El envío por WhatsApp no requiere que el sistema almacene ni gestione credenciales de una cuenta de WhatsApp Business; se apoya en la app de WhatsApp ya instalada en el dispositivo del administrador o en su versión web.
