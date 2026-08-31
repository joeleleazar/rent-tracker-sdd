# Feature Specification: Fichas Visuales, Rótulos de Sección y Menú de Acciones en el Detalle de Recibo

**Feature Branch**: `041-fichas-visuales-detalle-recibo`

**Created**: 2026-08-27

**Status**: Implemented

**Input**: User description: adjuntó una captura de referencia de `/recibos/{id}` (fichas con ícono para
Locación/Período/Emisión, rótulos de sección en mayúsculas, menú de acciones "⋮" por pago, línea de Total
destacada) y pidió: *"puedes modificar para que tenga la siguiente estructura pero sin romper la estética
existente"*.

## Contexto: conflicto con specs/038 y resolución

Esta captura es la **misma imagen de referencia** que originó `specs/038-layout-detalle-recibo` (distribución
en dos columnas, ya implementada). La sesión que redactó specs/038 la recibió también y decidió, en su
sección "Assumptions", **no** reproducir sus detalles decorativos (fichas con ícono, mayúsculas, menú
solo-ícono) por conflicto directo con el "No-Decoration Rule" de `DESIGN.md` y el Principio VI de la
constitución ("los íconos nunca reemplazan una etiqueta explícita") — implementó en su lugar `dl`/`dt`/`dd`
plano y botones con ícono + etiqueta visible, sin fichas ni mayúsculas.

En esta sesión, al recibir la misma captura con el pedido explícito de "esa estructura", se construyó
primero una versión fiel a la imagen (fichas, mayúsculas, botón "⋮" solo-ícono) sin conocer todavía la
decisión de specs/038. Al descubrirse el conflicto, se consultó al usuario explícitamente — ver
`specs/038-layout-detalle-recibo/spec.md`, sección Assumptions, entrada marcada `[SUPERSEDIDO]` — y el
usuario **confirmó** que quería mantener las fichas, los rótulos en mayúsculas y el menú de acciones. Esta
spec documenta esa decisión, ya tomada con esa confirmación explícita, y la excepción resultante:

- El botón de menú por pago **no** quedó solo-ícono: lleva el texto visible "Más" junto al ícono `⋮`, para
  seguir cumpliendo el Principio VI (el ícono refuerza la etiqueta, no la reemplaza).
- Los rótulos en mayúsculas se formalizaron como una excepción confirmada y documentada al "No-Decoration
  Rule" de `DESIGN.md` (token `typography.seccion`, clase `.titulo-seccion`), no como una violación silenciosa
  de esa regla.
- La distribución en dos columnas de specs/038 (FR-001 a FR-005 de ese spec) **no cambia** — esta feature
  solo agrega detalle visual dentro de esas dos columnas ya existentes.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ubicar los datos clave del recibo de un vistazo (Priority: P1)

Un administrador abre el detalle de un recibo para confirmar rápidamente a qué locación, período y fecha de
emisión corresponde, sin tener que leer una lista de `dt`/`dd`. Ve tres fichas con ícono (Locación, Período,
Emisión) al principio de la tarjeta de resumen, y el total del recibo destacado visualmente al final de la
lista de conceptos.

**Why this priority**: es el contenido que el usuario confirmó explícitamente que quería ver primero, tal
como en la imagen de referencia.

**Independent Test**: abrir el detalle de cualquier recibo y confirmar que las tres fichas (Locación,
Período, Emisión) se muestran con su ícono, etiqueta y valor, y que la fila de Total tiene un fondo
diferenciado del resto de las líneas de concepto.

**Acceptance Scenarios**:

1. **Given** el detalle de un recibo, **When** se abre la tarjeta "Resumen del Recibo", **Then** se muestran
   tres fichas con ícono — Locación, Período, Emisión — antes de la lista de conceptos.
2. **Given** la misma tarjeta, **When** se llega al final de la lista de conceptos, **Then** la fila de Total
   se distingue visualmente (fondo propio) del resto de las líneas.

---

### User Story 2 - Gestionar las acciones de un pago sin que compitan visualmente con su monto (Priority: P1)

El mismo administrador revisa la lista de pagos de un recibo con varios pagos registrados. Antes, cada pago
mostraba 3-4 botones sueltos (Ver Comprobante, Editar, Subir/Reemplazar Evidencia, Eliminar) que competían
visualmente con el monto del pago, sobre todo con varios pagos en pantalla. Ahora esas acciones quedan
agrupadas en un menú "Más" por pago, y "Registrar Pago" — antes un formulario siempre visible al pie de la
tarjeta — es un botón que abre un modal, como el resto de las acciones secundarias del proyecto.

**Why this priority**: reduce el ruido visual de la sección con más variabilidad de contenido (la cantidad de
pagos por recibo no tiene tope), sin ocultar ninguna acción.

**Independent Test**: abrir un recibo con al menos un pago y confirmar que las acciones del pago (Ver
Comprobante, Editar, Subir/Reemplazar Evidencia, Eliminar) están agrupadas en un menú "Más" con ícono y
etiqueta visible, y que "Registrar Pago" abre un modal en vez de mostrar un formulario siempre visible.

**Acceptance Scenarios**:

1. **Given** un recibo con al menos un pago, **When** se abre su menú "Más", **Then** se listan Ver
   Comprobante, Editar, Subir Evidencia o Reemplazar Evidencia (según corresponda) y Eliminar, cada una con
   ícono y etiqueta de texto visible.
2. **Given** un recibo no pagado por completo, **When** se hace clic en "Registrar Pago", **Then** se abre un
   modal con el formulario (monto, fecha), en vez de un formulario embebido siempre visible.
3. **Given** un error de validación al registrar un pago (monto o fecha inválidos), **When** la página se
   recarga, **Then** el modal de "Registrar Pago" se reabre automáticamente mostrando el error — no se pierde
   silenciosamente por estar dentro de un modal cerrado.

---

### Edge Cases

- ¿Qué pasa si un pago no tiene evidencia? El menú "Más" ofrece "Subir Evidencia" en vez de "Ver
  Evidencia"/"Reemplazar Evidencia" — mismo comportamiento condicional que ya existía, solo reubicado dentro
  del menú.
- ¿Qué pasa con el botón "Más" en lectores de pantalla? Además del texto visible "Más", conserva su
  `aria-label` descriptivo ("Más acciones para este pago") para que el anuncio siga siendo específico por
  pago, no solo "Más" repetido en cada fila.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: La tarjeta "Resumen del Recibo" DEBE mostrar tres fichas con ícono (Locación, Período, Emisión)
  antes de la lista de conceptos, sin cambiar el dato ni el formato ya usados para cada uno.
- **FR-002**: La fila de Total DEBE distinguirse visualmente (fondo propio) del resto de las líneas de
  concepto de la tarjeta de resumen.
- **FR-003**: Las acciones de cada pago (Ver Comprobante, Editar, Subir/Reemplazar Evidencia, Eliminar) DEBEN
  agruparse en un menú de acciones por pago, con ícono y etiqueta de texto visible en el control que lo abre
  (nunca solo un ícono), conforme al Principio VI de la constitución.
- **FR-004**: "Registrar Pago" DEBE presentarse como un modal (consistente con el resto de acciones
  secundarias del proyecto), no como un formulario siempre visible; DEBE reabrirse automáticamente si la
  página vuelve con errores de validación de ese formulario.
- **FR-005**: Los rótulos de sección en mayúsculas ("Resumen del Recibo", "Pagos", "Estado del Recibo", las
  etiquetas de las fichas) DEBEN usar el token de tipografía documentado (`typography.seccion` en
  `DESIGN.md`), no un tamaño o tratamiento ad hoc por vista.
- **FR-006**: Ninguna ruta, acción ni dato existente (registrar/editar/eliminar pago, subir evidencia,
  anular/reactivar recibo, la distribución en dos columnas de specs/038) DEBE cambiar de comportamiento —
  esta feature es de presentación visual dentro de esa distribución ya existente.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador identifica la locación, el período y la fecha de emisión de un recibo sin leer
  una lista de pares etiqueta-valor genérica.
- **SC-002**: Con 3 o más pagos registrados en un recibo, ningún pago necesita más de un control visible
  (el menú "Más") para acceder a todas sus acciones.
- **SC-003**: Ninguna de las 69 pruebas automatizadas ya existentes sobre recibos y pagos
  (`ReciboControllerTest`, `PagoReciboControllerTest`, `EvidenciaPagoControllerTest`,
  `ComprobantePagoControllerTest`) cambia de resultado tras esta feature.

## Assumptions

- **Excepción confirmada al "No-Decoration Rule" de `DESIGN.md`.** El usuario confirmó explícitamente
  mantener los rótulos en mayúsculas de esta pantalla pese al conflicto señalado por specs/038; `DESIGN.md`
  documenta esta excepción como deliberada y acotada al rol "Título de Sección" (`.titulo-seccion`), no como
  una habilitación general de mayúsculas en el resto del sistema.
- **El menú "Más" no es un botón solo-ícono.** A diferencia de la captura de referencia (que mostraba "⋮" sin
  texto), se agregó la etiqueta visible "Más" junto al ícono para cumplir el Principio VI sin necesidad de
  otra excepción confirmada.
- Esta feature no introduce funcionalidad nueva — reorganiza visualmente contenido ya existente (specs/007,
  032, 034, 035, 038) dentro de la distribución en dos columnas que specs/038 ya estableció.
