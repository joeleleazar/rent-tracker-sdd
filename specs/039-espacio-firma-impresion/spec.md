# Feature Specification: Más Espacio para la Firma en la Impresión del Comprobante de Pago

**Feature Branch**: `039-espacio-firma-impresion`

**Created**: 2026-08-27

**Status**: Draft

**Input**: User description: "Dale mas espacio para la firma en la impresion del comprobante de pago" —
esta vez confirmado y acotado explícitamente al comprobante de pago imprimible
(`pagos/comprobante.blade.php`, specs/035), a diferencia de specs/037 (retractada por aplicarse por error
sobre el documento equivocado).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Firmar cómodamente el comprobante impreso (Priority: P1)

Quien recibe el pago firma físicamente el comprobante de pago ya impreso. Hoy el espacio para la firma es
apenas una línea pegada al resto del texto, incómoda para firmar a mano. El administrador necesita que ese
espacio sea claramente más generoso al imprimir el comprobante, para que firmar el documento se sienta
natural y no forzado.

**Why this priority**: Es el único pedido de esta feature.

**Independent Test**: Abrir el comprobante de cualquier pago, activar la vista previa de impresión, y
confirmar que el espacio en blanco reservado para la firma es notoriamente mayor al que existía antes de
esta feature.

**Acceptance Scenarios**:

1. **Given** el comprobante de cualquier pago, **When** se activa la vista previa de impresión, **Then**
   existe un área en blanco de altura notoriamente mayor a la actual antes de la línea de firma.
2. **Given** esa misma vista previa de impresión, **When** se revisa el resto del documento, **Then** el
   resto del contenido (metadatos, monto de este pago, avance histórico, cierre) no cambia.

---

### Edge Cases

- ¿El espacio ampliado se ve también en pantalla (no solo al imprimir)? Sí — es el mismo bloque de firma en
  ambos casos, no hay una versión distinta solo para impresión.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El comprobante de un pago (`pagos/comprobante.blade.php`) DEBE reservar un área en blanco
  para la firma notoriamente más alta que la actual, tanto en pantalla como en la vista de impresión.
- **FR-002**: El resto del contenido del comprobante (metadatos, partes, monto de este pago, avance
  histórico de specs/036, cierre) NO DEBE cambiar de disposición ni de significado — el documento conserva
  su columna única (specs/037 quedó retractada, no se reintroduce el layout de dos columnas ni la lista de
  pagos del recibo).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El área en blanco reservada para la firma en el comprobante impreso es visiblemente mayor a
  la de antes de esta feature, en una comparación directa.

## Assumptions

- Este pedido es exactamente la User Story 1 de specs/037 (ya retractada) — se reutiliza la misma decisión
  de diseño ya validada entonces (área en blanco de altura fija antes de la línea de firma, no solo un
  margen mayor), esta vez confirmada explícitamente como el pedido correcto.
- No se reintroduce ningún otro cambio de specs/037 (el layout de dos columnas ni la lista de pagos del
  recibo) — el usuario ya aclaró que esos cambios correspondían a otra pantalla (specs/038).
