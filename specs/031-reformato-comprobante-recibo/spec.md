# Feature Specification: Reformato de Jerarquía Visual del Comprobante de Recibo

**Feature Branch**: `031-reformato-comprobante-recibo`

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "para el formato del recibo utiliza los siguientes lineamientos — Jerarquía general (de arriba hacia abajo): Encabezado breve (nombre del documento + logo), Metadatos del recibo (número, fecha de emisión, período, como pares etiqueta/valor), Datos de las partes (quién paga y quién recibe), Detalle de conceptos (lista/tabla de dos columnas concepto/monto, cada ítem en su propia línea, alquiler y cada gasto fijo por separado), Total (visualmente diferenciado, el único elemento que debe saltar a la vista), Cierre (firma y/o agradecimiento, opcional). Principios: una sola columna de lectura vertical; agrupar por bloques con separadores simples; jerarquía tipográfica mínima (2-3 tamaños); el total siempre más destacado que cualquier otro número; montos alineados a la derecha en columna fija."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Leer el comprobante de un vistazo, de arriba hacia abajo (Priority: P1)

Un propietario o un inquilino que recibe el comprobante de un pago (impreso o compartido por WhatsApp) lo
recorre en un único trayecto visual de arriba hacia abajo — encabezado, metadatos, partes, conceptos, total,
cierre — sin tener que buscar información salteada ni volver hacia atrás para entender a qué corresponde
cada dato.

**Why this priority**: Es la razón de ser del reformato — un comprobante que no se lee en un solo recorrido
vertical claro no cumple su propósito de documento de respaldo fácil de entender de un vistazo.

**Independent Test**: Abrir el comprobante de un recibo ya emitido y verificar que los seis bloques de
contenido aparecen en el orden especificado, cada uno claramente separado del siguiente, sin necesidad de
desplazamiento horizontal ni de releer el documento para ubicar un dato.

**Acceptance Scenarios**:

1. **Given** el comprobante de un recibo abierto, **When** se recorre de arriba hacia abajo, **Then**
   aparecen en orden: encabezado con el nombre del documento y el logotipo, metadatos del recibo (número,
   fecha de emisión, período), datos de las partes, detalle de conceptos, total destacado y cierre.
2. **Given** el comprobante abierto, **When** se observan los límites entre bloques, **Then** cada bloque
   está claramente separado del anterior y del siguiente mediante un separador simple (línea o espacio en
   blanco consistente), sin bloques pegados entre sí.
3. **Given** el comprobante abierto, **When** se revisa la tipografía usada en todo el documento, **Then**
   no se identifican más de tres tamaños o pesos de fuente distintos (título, etiquetas/detalle, total).

---

### User Story 2 - Identificar el monto total pagado de inmediato (Priority: P1)

La misma persona, al recibir el comprobante, busca primero una sola cosa: cuánto se pagó en total. Ese
número debe ser el elemento que más resalta en todo el documento, reconocible sin tener que leer el resto
del contenido.

**Why this priority**: Es el dato que la persona busca primero (Input del usuario) — si no resalta de
inmediato, el comprobante falla en su función más básica de confirmación de pago.

**Independent Test**: Abrir el comprobante y, sin leer el resto del contenido, confirmar que el monto total
es identificable de inmediato como el elemento visualmente más destacado del documento.

**Acceptance Scenarios**:

1. **Given** el comprobante abierto, **When** se le echa un vistazo rápido sin leer el detalle, **Then** el
   monto total es el primer número que capta la atención, por encima de cualquier monto individual de los
   conceptos o de los metadatos.
2. **Given** el comprobante abierto, **When** se compara el total con cualquier otro monto del documento,
   **Then** el total se distingue por una combinación de tamaño, peso de fuente y/o fondo o borde de
   resalte que ningún otro número del documento comparte.

---

### User Story 3 - Verificar el detalle exacto de qué se cobró (Priority: P2)

Un propietario que necesita confirmar qué conceptos específicos se incluyeron en un cobro (por ejemplo,
para resolver una consulta del inquilino) revisa la lista de conceptos y encuentra cada ítem — alquiler y
cada gasto fijo — en su propia línea, con su monto alineado en una misma columna, fácil de recorrer y sumar
visualmente.

**Why this priority**: Es el contenido central del documento (Input del usuario: "la parte central"), pero
depende de que el encabezado y los metadatos ya hayan anclado el contexto del documento (User Story 1),
por lo que es un peldaño después de la lectura general.

**Independent Test**: Abrir el comprobante de un recibo con varios conceptos (alquiler + gastos fijos) y
verificar que cada uno aparece como una línea independiente con su propio monto, y que todos los montos del
detalle quedan alineados en una misma columna a la derecha.

**Acceptance Scenarios**:

1. **Given** un recibo con alquiler y cuatro gastos fijos (agua, luz, internet, mantenimiento), **When** se
   revisa el detalle de conceptos, **Then** aparecen cinco líneas independientes, cada una con su propio
   monto — ninguna combina dos o más conceptos en una sola cifra.
2. **Given** el detalle de conceptos visible, **When** se observan los montos de cada línea, **Then** todos
   quedan alineados en una misma columna fija a la derecha, en el mismo eje vertical que los montos de
   metadatos y el total.

---

### Edge Cases

- ¿Qué pasa con un recibo que no incluye monto de alquiler (solo conceptos de gasto fijo)? El detalle de
  conceptos no debe mostrar una línea vacía de "Alquiler" — solo los conceptos efectivamente cobrados.
- ¿Qué pasa con un recibo que tiene muchos conceptos (más de cinco o seis)? El documento se alarga
  verticalmente, pero la jerarquía tipográfica y la separación entre bloques se mantienen iguales — no se
  comprimen para intentar que "quepa" en menos espacio.
- ¿Qué pasa con el comprobante de un recibo anulado? La marca "Anulado" ya existente (specs/004) debe
  seguir mostrándose, sin superponerse con el nuevo bloque de total destacado ni con ningún otro bloque de
  forma que pierda legibilidad.
- ¿Qué pasa si el nombre del inquilino o el nombre de la locación son inusualmente largos? Deben ajustarse
  con salto de línea dentro de su propio bloque, sin desalinear la columna de montos ni desbordar el
  documento.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El comprobante DEBE organizarse en bloques verticales en este orden fijo: encabezado,
  metadatos del recibo, datos de las partes, detalle de conceptos, total y cierre.
- **FR-002**: El bloque de encabezado DEBE mostrar el nombre del documento ("Recibo de Pago") junto con el
  logotipo de la marca ya incorporado en el sistema (specs/030).
- **FR-003**: El bloque de metadatos DEBE mostrar, como pares etiqueta/valor, el número de recibo, la fecha
  de emisión y el período (mes/año) al que corresponde el pago.
- **FR-004**: El bloque de datos de las partes DEBE identificar claramente quién realiza el pago (el
  inquilino) y a qué locación/inmueble corresponde.
- **FR-005**: El bloque de datos de las partes DEBE identificar además a quién recibe el pago, usando un
  nombre de propietario/administrador configurable por el usuario (no ligado al usuario autenticado que
  emite el recibo ni al nombre interno de la aplicación) — el sistema DEBE permitir definir y editar ese
  nombre desde la pantalla de Configuración existente.
- **FR-005a**: Si el nombre del propietario/administrador todavía no fue configurado, el comprobante DEBE
  seguir siendo válido y legible (omitiendo esa línea o mostrando un valor por defecto claro), sin error ni
  bloqueo de la emisión o visualización del recibo.
- **FR-006**: El detalle de conceptos DEBE mostrar cada ítem cobrado (el alquiler, cuando aplique, y cada
  gasto fijo) en su propia línea con su monto correspondiente — nunca como un monto agregado que combine
  varios conceptos en una sola cifra.
- **FR-007**: Todos los montos del documento (metadatos si corresponde, detalle de conceptos y total)
  DEBEN alinearse en una misma columna fija a la derecha.
- **FR-008**: El monto total DEBE distinguirse visualmente de cualquier otro número del documento mediante
  una combinación de mayor tamaño, mayor peso de fuente y/o un fondo o borde de resalte, de forma que sea
  el elemento que más capta la atención al ver el documento.
- **FR-009**: Cada uno de los seis bloques DEBE separarse del siguiente mediante un separador simple (una
  línea o un espacio en blanco consistente) — ningún bloque debe aparecer pegado al anterior sin ninguna
  distinción visual.
- **FR-010**: La jerarquía tipográfica de todo el documento DEBE limitarse a un máximo de tres
  tamaños/pesos de fuente diferenciados (título, etiquetas de sección/texto de detalle, total).
- **FR-011**: El documento DEBE incluir un bloque de cierre con una frase breve de agradecimiento; un
  espacio de firma es opcional dentro de ese mismo bloque.
- **FR-012**: El comprobante de un recibo anulado DEBE seguir mostrando la marca "Anulado" ya existente,
  sin que se superponga con el bloque de total ni oculte información de ningún otro bloque.
- **FR-013**: El comprobante DEBE seguir siendo compatible con sus dos formas de distribución ya
  existentes — impresión y captura de imagen para compartir por WhatsApp — conservando su legibilidad en
  ambas.

### Key Entities *(include if feature involves data)*

- **Recibo**: número (identificador), fecha de emisión, período, estado (pagado/anulado), monto de
  alquiler (si aplica), monto total.
- **Concepto de recibo**: nombre del concepto, monto — cada gasto fijo cobrado en el recibo, uno por línea.
- **Locación**: nombre/identificador del inmueble al que corresponde el pago.
- **Inquilino**: nombre de quien realiza el pago.
- **Configuración general**: se le agrega el nombre del propietario/administrador que recibe el pago — un
  dato de configuración global editable por el usuario, no ligado a un recibo individual ni al usuario que
  lo emite.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Al ver el comprobante, una persona puede identificar el monto total pagado en menos de 2
  segundos, sin necesidad de leer el resto del documento.
- **SC-002**: El 100% de los conceptos cobrados en un recibo (alquiler, cuando aplique, más cada gasto
  fijo) aparecen como líneas individuales con su propio monto — ninguno aparece agregado a otro.
- **SC-003**: El 100% de los montos visibles del documento (metadatos, detalle de conceptos y total) quedan
  alineados en una misma columna a la derecha.
- **SC-004**: El documento se puede leer de principio a fin siguiendo un único recorrido vertical, sin que
  el lector necesite desplazarse horizontalmente ni saltar entre secciones fuera de orden.
- **SC-005**: El comprobante sigue produciendo una impresión y una captura de imagen para WhatsApp legibles
  y completas, sin regresiones respecto al comportamiento ya validado en specs anteriores (026, 030).

## Assumptions

- El reformato se aplica únicamente a la vista de comprobante ya existente
  (`resources/views/locaciones/recibos/comprobante.blade.php`), reutilizando el logotipo ya incorporado
  (specs/030) y los datos reales del recibo — no se trata de un documento standalone ni de datos de
  ejemplo, a diferencia del intento anterior de esta misma idea que no se implementó como se esperaba.
- La vista de gestión del recibo dentro de la aplicación (con las acciones de editar/anular, distinta del
  comprobante imprimible/compartible) no está en el alcance de esta feature.
- Esta feature reorganiza y restiliza la presentación visual del comprobante; el único cambio de datos que
  introduce es el nuevo nombre de propietario/administrador en Configuración general (FR-005) — no toca el
  modelo de datos del Recibo ni el de sus conceptos.
