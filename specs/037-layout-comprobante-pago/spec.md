# Feature Specification: Más Espacio para Firmar y Aprovechamiento Horizontal en el Comprobante de Pago

**Feature Branch**: `037-layout-comprobante-pago`

**Created**: 2026-08-27

**Status**: RETRACTED — malinterpretación del objetivo

**Retraction note (2026-08-27)**: Esta spec asumió que "la firma"/"mostrar los pagos" se referían al
comprobante de pago imprimible (specs/035), pero el usuario confirmó que ese documento ya estaba bien y
NO debía tocarse — el pedido real era una distribución de dos columnas para la vista del **detalle del
recibo** (`resources/views/locaciones/recibos/show.blade.php`), donde se gestionan los pagos, siguiendo una
imagen de referencia. Todo el código de esta spec (layout de 2 columnas y firma ampliada en
`pagos/comprobante.blade.php`, más sus tests) fue revertido. El pedido correcto se retoma en
specs/038-layout-detalle-recibo.

**Input**: User description: "dale mas espacio vertical para la firma, ademas aprovecha el espacio
horizontal para mostrar los pagos"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Firmar cómodamente el comprobante impreso (Priority: P1)

Quien recibe el pago firma físicamente el comprobante de pago ya impreso (specs/035). Hoy el espacio para
la firma es apenas una línea pegada al resto del texto, incómoda para firmar a mano. El administrador
necesita que ese espacio sea claramente más generoso, para que firmar el documento impreso se sienta natural
y no forzado.

**Why this priority**: Es el problema concreto reportado — el comprobante ya cumple su función de evidencia,
pero el espacio de firma actual es insuficiente para uso físico real.

**Independent Test**: Abrir el comprobante de un pago, activar la vista previa de impresión, y confirmar que
el espacio en blanco reservado para la firma es notoriamente mayor al que existía antes de esta feature.

**Acceptance Scenarios**:

1. **Given** el comprobante de cualquier pago, **When** se revisa la sección de firma, **Then** existe un
   área en blanco de altura notoriamente mayor a la actual antes de la línea de firma.
2. **Given** la vista de impresión del comprobante, **When** se imprime, **Then** el espacio de firma
   ampliado se conserva en el documento impreso (no es un efecto solo de pantalla).

---

### User Story 2 - Ver el historial de pagos del recibo junto al comprobante (Priority: P2)

El mismo administrador (o quien firma el comprobante) quiere ver, sin salir del documento, qué otros pagos
tiene registrados ese recibo — no solo el pago que este comprobante puntual documenta. Hoy el documento usa
una sola columna angosta que deja espacio horizontal sin aprovechar en pantallas anchas; ese espacio se
destina a mostrar el resto de los pagos del recibo a modo de contexto.

**Why this priority**: Complementa a la historia principal — usa el espacio horizontal liberado para dar
contexto útil (specs/036 ya resolvió que el avance mostrado es histórico; esta historia lo refuerza
mostrando la lista completa), pero no es el problema urgente reportado.

**Independent Test**: Abrir el comprobante de un pago de un recibo con más de un pago registrado, y
confirmar que se muestra, junto al contenido principal, una lista de todos los pagos de ese recibo, con el
pago que corresponde a este comprobante identificable dentro de esa lista.

**Acceptance Scenarios**:

1. **Given** el comprobante de un pago de un recibo con 2 o más pagos registrados, **When** se abre el
   comprobante, **Then** se muestra una lista con todos los pagos de ese recibo (fecha y monto de cada uno),
   distinta del bloque de "monto de este pago" ya existente.
2. **Given** esa misma lista, **When** se revisa, **Then** el pago que corresponde a este comprobante en
   particular se distingue visualmente del resto (por ejemplo, resaltado).
3. **Given** el comprobante de un pago de un recibo con un único pago registrado, **When** se abre, **Then**
   la lista muestra ese único pago (comportamiento consistente, sin caso especial de "lista vacía").

---

### Edge Cases

- ¿Qué pasa en una pantalla angosta (celular)? El diseño de dos columnas debe apilarse en una sola columna
  legible, sin perder ningún contenido — mismo criterio responsive ya usado en el resto del proyecto.
- ¿Qué pasa al imprimir? El espacio de firma ampliado y la lista de pagos deben verse correctamente en la
  vista de impresión, no solo en pantalla.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El comprobante de un pago DEBE reservar un área en blanco para la firma notoriamente más alta
  que la actual, tanto en pantalla como en la vista de impresión.
- **FR-002**: El comprobante de un pago DEBE mostrar, además del contenido ya existente (specs/035/036), una
  lista de todos los pagos registrados en el recibo al que pertenece, con su fecha y monto.
- **FR-003**: En esa lista, el pago correspondiente a este comprobante específico DEBE distinguirse
  visualmente de los demás.
- **FR-004**: El documento DEBE aprovechar un ancho mayor al actual en pantallas suficientemente anchas, de
  forma que el contenido principal y la lista de pagos se muestren lado a lado, no uno debajo del otro.
- **FR-005**: En una pantalla angosta, el documento DEBE apilar el contenido principal y la lista de pagos
  en una sola columna, sin perder ningún dato.
- **FR-006**: El monto de este pago, el avance histórico (specs/036) y el resto del contenido ya existente
  del comprobante NO DEBEN cambiar de significado ni de cálculo — esta feature es un cambio de layout, no
  de datos.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El área en blanco reservada para la firma en el comprobante impreso es visiblemente mayor a
  la de antes de esta feature, en una comparación directa.
- **SC-002**: Al abrir el comprobante de cualquier pago de un recibo con más de un pago, se puede identificar
  a qué pago corresponde ese comprobante dentro de la lista completa de pagos, sin ambigüedad.
- **SC-003**: En una pantalla angosta, el 100% del contenido del comprobante (incluida la lista de pagos)
  sigue siendo legible sin scroll horizontal.

## Assumptions

- Como el usuario no estará disponible para responder preguntas de aclaración durante esta sesión, las
  siguientes decisiones de diseño se toman como supuestos razonables documentados, no como
  [NEEDS CLARIFICATION]:
  - "La firma" se refiere al bloque de firma ya existente en `pagos/comprobante.blade.php` (specs/035) — el
    único comprobante de este proyecto que tiene una sección de firma (el comprobante del recibo completo,
    specs/007/031, no tiene una).
  - "Mostrar los pagos" (en plural) se interpreta como mostrar la lista completa de los pagos del recibo al
    que pertenece este comprobante puntual — no una repetición del monto de este pago ya destacado.
  - El ancho máximo del documento se amplía lo necesario para acomodar dos columnas en pantallas medianas o
    más anchas (contenido principal + lista de pagos), replegándose a una columna en pantallas angostas —
    mismo criterio responsive ya usado en el resto del sistema (Bootstrap `row`/`col-*`).
  - La lista de pagos no repite ni reemplaza el cálculo histórico de "Pagado hasta ahora"/"Saldo pendiente"
    (specs/036) — es un listado informativo adicional, no un nuevo cálculo.
- Esta feature es un cambio de layout sobre una vista ya existente (specs/035, ajustada en specs/036) — no
  introduce entidades, columnas ni rutas nuevas.
