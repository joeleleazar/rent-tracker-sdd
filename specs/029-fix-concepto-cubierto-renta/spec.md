# Feature Specification: Corregir Cobertura de Conceptos y Edición de Renta en Recibos

**Feature Branch**: `029-fix-concepto-cubierto-renta`

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "En la columna Conceptos de /recibos/registro-masivo se muestra 'Internet' como marcado/cubierto a pesar de no estar incluido en ningún recibo de esa locación y periodo. Además, al editar un recibo no se da la oportunidad de editar el monto de Renta (solo los demás conceptos)."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Editar el monto de Renta de un recibo ya emitido (Priority: P1)

Un administrador abre la edición de un recibo que ya incluye Renta y espera poder revisar o corregir su
monto (por ejemplo, un error de tipeo al emitirlo), igual que ya puede hacerlo con Agua, Luz u otros
conceptos. Hoy la pantalla de edición simplemente no ofrece el campo de Renta cuando el recibo ya la
incluye, así que no hay forma de corregirla sin anular el recibo y emitir uno nuevo.

**Why this priority**: Es un defecto que bloquea una operación básica y frecuente (corregir un monto ya
emitido) sin ninguna alternativa razonable dentro del sistema; su causa es puntual y autocontenida.

**Independent Test**: Abrir la edición de un recibo que incluye Renta y verificar que el campo "Incluir
Renta" con su monto aparece igual que los demás conceptos, editable y guardable.

**Acceptance Scenarios**:

1. **Given** un recibo ya emitido que incluye Renta, **When** el administrador abre su edición, **Then** el
   formulario muestra "Incluir Renta" marcado, con el monto actualmente registrado, editable.
2. **Given** esa misma pantalla, **When** el administrador cambia el monto de Renta y guarda, **Then** el
   recibo actualizado refleja el nuevo monto.
3. **Given** esa misma pantalla, **When** el administrador desmarca "Incluir Renta" y guarda, **Then** el
   recibo deja de incluir Renta (igual que ya ocurre hoy al desmarcar cualquier otro concepto).
4. **Given** un recibo que NO incluye Renta y ningún otro recibo del mismo periodo/locación la cubre,
   **When** el administrador abre su edición, **Then** "Incluir Renta" aparece disponible para agregarla
   (comportamiento ya correcto hoy, sin cambios).
5. **Given** un recibo que no incluye Renta pero otro recibo distinto del mismo periodo/locación sí la
   cubre, **When** el administrador abre la edición del primero, **Then** Renta NO aparece como opción
   (sigue perteneciendo al otro recibo — comportamiento ya correcto hoy, sin cambios).

---

### User Story 2 - Los badges de conceptos solo marcan lo realmente cubierto por un recibo vigente (Priority: P2)

Un administrador revisa la pantalla de emisión masiva de recibos para saber qué le falta emitir a cada
locación. Un concepto que nunca llegó a incluirse en ningún recibo vigente de ese periodo no debe
mostrarse como si ya estuviera cubierto — eso llevaría a omitir por error un concepto que en realidad sigue
pendiente.

**Why this priority**: Es una garantía de correctness ya exigida por specs/026 (los recibos anulados no
cuentan como cobertura); esta historia formaliza esa garantía con cobertura de prueba explícita para el
caso puntual reportado (un concepto sin ningún recibo vigente que lo incluya) y para Renta en particular,
cerrando cualquier vacío residual.

**Independent Test**: Para una locación y periodo donde ningún recibo vigente incluye un concepto dado,
verificar que ese concepto se muestra como disponible (no cubierto) en `/recibos/registro-masivo`.

**Acceptance Scenarios**:

1. **Given** una locación y periodo donde ningún recibo vigente incluye el concepto "Internet" (puede
   tener valor de referencia configurado en el contrato, o incluso figurar en un recibo ya anulado),
   **When** el administrador abre `/recibos/registro-masivo` para ese periodo, **Then** "Internet" se
   muestra como disponible, no como cubierto.
2. **Given** esa misma pantalla, **When** el administrador genera un recibo que incluye "Internet",
   **Then** "Internet" pasa a mostrarse como cubierto, enlazando a ese recibo.
3. **Given** el recibo del punto anterior es luego anulado, **When** el administrador vuelve a
   `/recibos/registro-masivo`, **Then** "Internet" vuelve a mostrarse como disponible (comportamiento ya
   exigido por specs/026 — se reafirma aquí con prueba explícita para este caso puntual).
4. **Given** una locación y periodo donde ningún recibo vigente incluye Renta, **When** el administrador
   abre `/recibos/registro-masivo`, **Then** Renta se muestra como disponible, no como cubierta.

---

### Edge Cases

- Un recibo con estado "pendiente" o "pagado" sigue contando como cobertura vigente; solo "anulado" no
  cuenta — sin cambios respecto a specs/026.
- Editar un recibo y desmarcar Renta cuando esa era la única razón por la que el recibo existía (sin ningún
  otro concepto marcado) sigue sujeto a la regla ya existente de que un recibo debe cubrir al menos un
  concepto — sin cambios de comportamiento aquí.
- Un concepto con un valor de referencia configurado en el contrato (para sugerir su monto) pero sin
  ningún recibo que lo incluya nunca debe mostrarse como cubierto — tener un valor configurado no es lo
  mismo que estar facturado.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Al abrir la edición de un recibo que ya incluye Renta (`monto_renta` no nulo), el sistema
  DEBE ofrecer el campo "Incluir Renta" con su monto actual, editable, en el mismo formulario donde se
  editan los demás conceptos.
- **FR-002**: Guardar el formulario de edición con un monto de Renta distinto DEBE actualizar el recibo con
  el nuevo monto.
- **FR-003**: Guardar el formulario de edición con "Incluir Renta" desmarcado DEBE quitar la Renta de ese
  recibo, igual que ya ocurre al desmarcar cualquier otro concepto.
- **FR-004**: El sistema NO DEBE mostrar un concepto como cubierto en `/recibos/registro-masivo` para una
  locación y periodo dados a menos que exista al menos un recibo con estado distinto de "anulado" de esa
  locación y periodo que efectivamente lo incluya (para Renta: con `monto_renta` no nulo; para el resto:
  con una fila de detalle asociada a ese concepto).
- **FR-005**: Que un concepto tenga un valor de referencia configurado en el contrato de la locación NO
  DEBE, por sí solo, hacer que se muestre como cubierto.

### Key Entities

*(No aplica — no se introducen ni modifican entidades de datos.)*

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador puede corregir el monto de Renta de un recibo ya emitido sin necesidad de
  anularlo y emitir uno nuevo, en el 100% de los casos donde el recibo ya incluye Renta.
- **SC-002**: En `/recibos/registro-masivo`, el 100% de los conceptos mostrados como "cubiertos" corresponden
  a un recibo vigente real que efectivamente los incluye — verificable siguiendo el enlace de cada badge
  cubierto hasta ese recibo.
- **SC-003**: Ningún concepto sin ningún recibo vigente que lo incluya se muestra como cubierto, incluso si
  tiene un valor de referencia configurado en el contrato o figuró en un recibo ya anulado.

## Assumptions

- La causa raíz de la User Story 1 ya fue identificada por inspección de código: `ReciboController::edit()`
  arma la lista de conceptos ofrecidos como "los disponibles + los que este recibo ya incluye
  (`$recibo->conceptos`)", pero Renta nunca vive en `$recibo->conceptos` (se guarda aparte, como
  `monto_renta` directamente en el recibo) — así que ese "agregar de vuelta" nunca la reincorpora cuando ya
  está cubierta por el propio recibo que se está editando. La vista de edición ya sabe renderizar Renta
  correctamente (usa el mismo `esRenta()` que el resto de conceptos); falta que el controlador se la
  entregue en la lista.
- La User Story 2 no se logró reproducir contra los datos actuales del entorno de desarrollo al momento de
  especificar esta corrección (la exclusión de recibos anulados de specs/026 ya está vigente y, verificada
  en el HTML servido en vivo, "Internet" no aparece marcado hoy). Se documenta y se cubre con pruebas
  automatizadas explícitas de todas formas, tanto porque el reporte del usuario puede reflejar un estado
  transitorio ya resuelto como para blindar el invariante contra cualquier regresión futura — el costo de
  esa cobertura adicional es bajo y el reporte original queda satisfecho igual.
- Esta corrección es independiente de specs/028 (quitar el botón "Ir" de `/recibos/registro-masivo`, en
  curso en paralelo) — no toca el formulario de navegación de periodo, solo los badges de conceptos y el
  formulario de edición de un recibo individual.
