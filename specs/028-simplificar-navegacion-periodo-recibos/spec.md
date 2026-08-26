# Feature Specification: Simplificar Navegación de Periodo en Registro Masivo de Recibos

**Feature Branch**: `028-simplificar-navegacion-periodo-recibos`

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "lo mismo para recibos/registro-masivo" — aplicar a `/recibos/registro-masivo` la misma simplificación ya decidida y aplicada en specs/027 para `/lecturas/registro-masivo`: quitar el botón "Ir" del selector de periodo, dejando como único comportamiento oficial las flechas ‹ › y el autoenvío del campo de fecha vía htmx.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Navegar de periodo solo con flechas o cambiando la fecha (Priority: P1)

Un administrador que emite recibos de forma masiva espera moverse entre periodos usando únicamente las flechas de anterior/siguiente o cambiando el campo de fecha — sin un botón adicional de confirmación, igual que ya se decidió y aplicó para el registro masivo de lecturas (specs/027).

**Why this priority**: Único objetivo de este feature; misma decisión ya tomada por el usuario, aplicada aquí por consistencia entre las dos pantallas de registro masivo (lecturas y recibos), que comparten el mismo patrón de selector de periodo (`specs/024`).

**Independent Test**: Abrir `/recibos/registro-masivo`, hacer clic en una flecha y verificar que el periodo cambia; cambiar el campo de fecha y verificar que también navega; confirmar que no aparece ningún botón de confirmación.

**Acceptance Scenarios**:

1. **Given** la pantalla de registro masivo de recibos en un periodo dado, **When** el administrador hace clic en la flecha "periodo anterior" o "periodo siguiente", **Then** la pantalla navega directamente a ese periodo, igual que hoy.
2. **Given** la pantalla de registro masivo de recibos, **When** el administrador cambia el valor del campo de fecha (mes), **Then** la pantalla navega directamente al periodo seleccionado sin acción adicional, igual que hoy.
3. **Given** la pantalla de registro masivo de recibos, **When** se inspecciona el selector de periodo, **Then** no existe ningún botón de confirmación visible.

---

### Edge Cases

- Mismos edge cases ya documentados y aceptados en specs/027 (pérdida del salto directo a un periodo arbitrario sin JavaScript; las flechas siguen funcionando como navegación clásica por ser enlaces reales).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE eliminar el botón de confirmación ("Ir") del selector de periodo en la pantalla de registro masivo de recibos.
- **FR-002**: El sistema DEBE seguir permitiendo navegar al periodo anterior o siguiente mediante las flechas, sin cambio de comportamiento.
- **FR-003**: El sistema DEBE seguir navegando automáticamente al periodo seleccionado cuando el administrador cambia el campo de fecha, sin cambio de comportamiento.
- **FR-004**: El sistema NO DEBE conservar ninguna referencia (comentario o prueba) que exija la existencia del botón "Ir" en esta vista.

### Key Entities

*(No aplica.)*

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las navegaciones de periodo mediante flechas siguen funcionando exactamente igual que antes.
- **SC-002**: El 100% de los cambios de valor en el campo de fecha siguen navegando automáticamente.
- **SC-003**: El botón "Ir" no aparece en el marcado de la pantalla bajo ninguna condición.
- **SC-004**: El resto de la suite de pruebas de registro masivo de recibos sigue pasando sin modificar sus aserciones de resultado esperado.

## Assumptions

- Misma decisión y mismas razones ya documentadas en specs/027 — se aplica aquí por consistencia entre pantallas gemelas, sin necesidad de reevaluar el trade-off de degradación elegante.
