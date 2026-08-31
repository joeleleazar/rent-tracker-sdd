# Feature Specification: Distribución en Dos Columnas del Detalle de Recibo

**Feature Branch**: `038-layout-detalle-recibo`

**Created**: 2026-08-27

**Status**: Draft

**Input**: User description: "Hubo un error, el ultimo cambio que aplicaste lo hiciste sobre la version
imprimible del comprobante de pago ese ya estaba bien no debiste tocarlo, yo me referia a una distribucion
como la de la imagen adjunta [...] para especificamente la vista del recibo donde se agregan los pagos" —
corrección de specs/037 (retractada), que había aplicado por error el cambio de layout sobre
`pagos/comprobante.blade.php` en vez de `locaciones/recibos/show.blade.php`.

## Contexto de la imagen de referencia

El usuario adjuntó una captura de un diseño con dos columnas para esta misma pantalla: a la izquierda, el
resumen del recibo (locación, período, emisión, desglose de conceptos y total); a la derecha, apiladas, la
tarjeta de Pagos (con barra de progreso, avance y lista de pagos) y la tarjeta de Estado del Recibo. Hoy
ambas viven en una sola columna angosta, una debajo de la otra.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ver el resumen del recibo y sus pagos lado a lado (Priority: P1)

Un administrador abre el detalle de un recibo para revisar sus datos y gestionar sus pagos. Hoy debe
desplazarse verticalmente por una columna angosta para pasar del resumen del recibo a la sección de Pagos.
Con esta feature, en una pantalla suficientemente ancha, ve el resumen del recibo y la gestión de pagos uno
junto al otro, sin tener que hacer scroll para relacionar ambos.

**Why this priority**: Es el pedido explícito del usuario, con una imagen de referencia concreta — la
distribución en dos columnas para esta pantalla específica.

**Independent Test**: Abrir el detalle de cualquier recibo en una pantalla ancha y confirmar que el resumen
del recibo se muestra en una columna y la gestión de pagos (más el estado del recibo) en otra, lado a lado.

**Acceptance Scenarios**:

1. **Given** el detalle de un recibo, **When** se abre en una pantalla ancha, **Then** el resumen del recibo
   (estado, locación, período, fecha de emisión, desglose de conceptos, total) se muestra en una columna a
   la izquierda.
2. **Given** la misma pantalla, **When** se revisa la columna derecha, **Then** se muestran, apiladas, la
   tarjeta de Pagos (avance, barra de progreso, lista de pagos, formulario de registrar pago) y la tarjeta
   de Estado del Recibo (Anular/Reactivar).
3. **Given** un recibo anulado (sin tarjeta de Pagos, specs/032), **When** se revisa la columna derecha,
   **Then** solo se muestra la tarjeta de Estado del Recibo, sin dejar un hueco vacío donde iría Pagos.

---

### User Story 2 - Seguir usando la pantalla normalmente en un celular (Priority: P2)

El mismo administrador abre el detalle de un recibo desde un celular. La distribución en dos columnas no
tiene sentido en una pantalla angosta — necesita que todo el contenido se siga viendo apilado, en el mismo
orden lógico que antes (resumen primero, pagos después, estado del recibo al final).

**Why this priority**: Complementa a la historia principal — asegura que el cambio no rompa la experiencia ya
funcional en pantallas angostas.

**Independent Test**: Abrir el mismo detalle de recibo en una pantalla angosta y confirmar que todo el
contenido se sigue viendo apilado en una sola columna, en el mismo orden que antes de esta feature.

**Acceptance Scenarios**:

1. **Given** el detalle de un recibo, **When** se abre en una pantalla angosta, **Then** el resumen del
   recibo, la tarjeta de Pagos y la tarjeta de Estado del Recibo se apilan verticalmente en ese orden, sin
   scroll horizontal ni contenido cortado.

---

### Edge Cases

- ¿Qué pasa con los modales (subir evidencia, editar pago, eliminar pago, anular/reactivar recibo)? No
  cambian — siguen siendo modales de Bootstrap independientes de la distribución en columnas, ya que no
  forman parte del flujo visual de la página en sí.
- ¿Qué pasa con los mensajes de éxito/error (`session('mensaje')`, errores de validación)? Se muestran igual
  que antes, por encima de las dos columnas, sin quedar atrapados dentro de una de ellas.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: En una pantalla suficientemente ancha, el detalle de un recibo DEBE mostrar el resumen del
  recibo y la gestión de pagos en dos columnas, lado a lado.
- **FR-002**: La columna del resumen del recibo DEBE conservar todo su contenido actual (estado, locación,
  período, fecha de emisión, desglose de conceptos, total, y las acciones Editar Recibo / Ver Comprobante /
  Ver Historial de Recibos) sin cambios de significado.
- **FR-003**: La columna de pagos DEBE mostrar, en este orden, la tarjeta de Pagos (cuando el recibo no está
  anulado) y la tarjeta de Estado del Recibo, con todo su contenido y acciones actuales sin cambios de
  significado.
- **FR-004**: En una pantalla angosta, las dos columnas DEBEN apilarse verticalmente en el mismo orden
  lógico (resumen, luego pagos, luego estado del recibo), sin perder ningún contenido ni generar scroll
  horizontal.
- **FR-005**: Ningún dato, cálculo, ruta ni acción existente (registrar/editar/eliminar pago, subir
  evidencia, anular/reactivar recibo) DEBE cambiar de comportamiento — esta feature es un cambio de
  distribución visual, no de funcionalidad.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: En una pantalla ancha, un administrador puede ver el total del recibo y el avance de sus pagos
  al mismo tiempo, sin necesidad de hacer scroll.
- **SC-002**: En una pantalla angosta, el 100% del contenido de la pantalla sigue siendo accesible y legible,
  sin scroll horizontal.
- **SC-003**: Ninguna de las acciones ya existentes en esta pantalla (registrar pago, editar/eliminar pago,
  subir evidencia, anular/reactivar recibo) cambia su comportamiento tras esta feature.

## Assumptions

- **[SUPERSEDIDO por specs/041-fichas-visuales-detalle-recibo, 2026-08-27 — ver ese spec.]** ~~La imagen de
  referencia se adopta como guía de distribución espacial (dos columnas: resumen | pagos + estado), no como
  especificación visual literal. Algunos detalles decorativos de la captura (etiquetas en mayúsculas, "chips"
  con ícono para Locación/Período/Emisión, menú de acciones de solo ícono tipo "⋮") entran en conflicto
  directo con reglas ya establecidas y aplicadas en esta misma sesión: el "No-Decoration Rule" de `DESIGN.md`
  (ninguna etiqueta en mayúsculas) y el Principio VI de la constitución ("los íconos nunca reemplazan una
  etiqueta explícita"). Por eso esta feature reproduce la distribución en columnas de la imagen usando los
  componentes y la tipografía ya establecidos del sistema de diseño del proyecto (tarjetas, `dl`/`dt`/`dd`,
  badges con texto, botones con ícono + etiqueta), no una reestilización nueva.~~ En una sesión posterior el
  usuario confirmó explícitamente que sí quería las fichas con ícono, las etiquetas en mayúsculas y el menú
  de acciones — specs/041 documenta esa decisión y la excepción resultante al "No-Decoration Rule". La
  distribución en dos columnas en sí (FR-001 a FR-005 de este spec) sigue vigente sin cambios.
- El ancho máximo actual de la página (`col-lg-8` con `max-width: 42rem`, pensado para una sola columna
  angosta) se amplía para acomodar las dos columnas — mismo criterio que otras pantallas del proyecto que
  combinan tarjetas anchas (ej. el árbol de locaciones), en vez de mantener el límite pensado para
  formularios de una sola columna.
- Esta feature no introduce ninguna funcionalidad nueva — reordena contenido ya existente (specs/007, 032,
  034, 035) en un layout de dos columnas.
