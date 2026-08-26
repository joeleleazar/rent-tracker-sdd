# Feature Specification: Simplificar Navegación de Periodo en Registro Masivo de Lecturas

**Feature Branch**: `027-simplificar-navegacion-periodo`

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "Simplificar la navegación de periodo en /lecturas/registro-masivo: quitar el botón 'Ir' del selector de periodo, dejando como único comportamiento oficial de navegación las flechas ‹ › (que ya navegan directo al periodo anterior/siguiente) y el cambio de valor del campo de fecha/mes (que ya se autoenvía vía htmx al detectar 'change'). El botón 'Ir' existía como fallback de degradación elegante si JavaScript falla, pero el usuario, tras conocer ese contexto (documentado en un test de specs/024 y en un comentario de specs/026), decidió explícitamente retirarlo de todas formas."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Navegar de periodo solo con flechas o cambiando la fecha (Priority: P1)

Un operador que revisa el registro masivo de lecturas espera moverse entre periodos usando únicamente las flechas de anterior/siguiente o escribiendo/seleccionando directamente un mes en el campo de fecha — sin un botón adicional de confirmación que hoy no aporta nada en el uso normal (con JavaScript activo, que es el entorno en el que se usa esta aplicación).

**Why this priority**: Es el único objetivo de este feature — una simplificación de interfaz ya decidida explícitamente por el usuario tras evaluar el contraste con la degradación elegante sin JavaScript.

**Independent Test**: Abrir `/lecturas/registro-masivo`, hacer clic en una flecha de navegación y verificar que el periodo cambia; cambiar el valor del campo de fecha y verificar que el periodo también cambia; confirmar que no aparece ningún botón de confirmación adicional junto al selector.

**Acceptance Scenarios**:

1. **Given** la pantalla de registro masivo de lecturas en un periodo dado, **When** el operador hace clic en la flecha "periodo anterior" o "periodo siguiente", **Then** la pantalla navega directamente a ese periodo, igual que hoy.
2. **Given** la pantalla de registro masivo de lecturas, **When** el operador cambia el valor del campo de fecha (mes), **Then** la pantalla navega directamente al periodo seleccionado sin necesitar ninguna acción adicional, igual que hoy.
3. **Given** la pantalla de registro masivo de lecturas, **When** se inspecciona el selector de periodo, **Then** no existe ningún botón de confirmación visible junto a las flechas y el campo de fecha.

---

### Edge Cases

- ¿Qué ocurre si JavaScript no está disponible en el navegador del operador? El campo de fecha deja de autoenviarse y las flechas dejan de disparar la navegación acotada (`hx-select`/`hx-target`), pero al ser enlaces `<a href>` reales, siguen funcionando como navegación clásica de página completa. Se pierde específicamente la posibilidad de saltar a un periodo arbitrario tecleado en el campo de fecha sin recorrer los periodos intermedios con las flechas — pérdida aceptada explícitamente por el usuario como parte de esta decisión.
- ¿Qué pasa con el formulario que envuelve el selector de periodo? Sigue existiendo como elemento separado del resto de controles (tarifa, exportar), para que el autoenvío del campo de fecha nunca incluya esos otros campos — esa separación ya no depende de la existencia de un botón "Ir".

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE eliminar el botón de confirmación ("Ir") del selector de periodo en la pantalla de registro masivo de lecturas.
- **FR-002**: El sistema DEBE seguir permitiendo navegar al periodo anterior o siguiente mediante las flechas, sin cambio de comportamiento respecto a hoy.
- **FR-003**: El sistema DEBE seguir navegando automáticamente al periodo seleccionado cuando el operador cambia el valor del campo de fecha, sin cambio de comportamiento respecto a hoy.
- **FR-004**: El sistema DEBE mantener el envío del periodo aislado de los demás controles de la misma fila (tarifa por kWh, exportar), sin que un cambio de periodo dispare accidentalmente esos otros campos.
- **FR-005**: El sistema NO DEBE conservar ninguna prueba automatizada que exija la existencia del botón "Ir" — la prueba existente que verificaba su presencia y su atributo `type="submit"` debe eliminarse o reemplazarse por una que refleje el nuevo comportamiento (solo flechas + autoenvío).

### Key Entities

*(No aplica — este feature no involucra entidades de datos, solo una simplificación de interacción en una vista ya existente.)*

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las navegaciones de periodo mediante flechas siguen funcionando exactamente igual que antes del cambio.
- **SC-002**: El 100% de los cambios de valor en el campo de fecha siguen navegando automáticamente al periodo correspondiente.
- **SC-003**: El botón "Ir" no aparece en el marcado de la pantalla bajo ninguna condición.
- **SC-004**: El resto de la suite de pruebas automatizadas del registro masivo de lecturas sigue pasando sin modificar sus aserciones de resultado esperado, salvo la prueba específica del botón "Ir" (FR-005).

## Assumptions

- Esta aplicación se usa en la práctica siempre con JavaScript activo (entorno de operadores conocidos, no público general), por lo que la pérdida del fallback de degradación elegante para el caso específico de "saltar a un periodo arbitrario sin JavaScript" es una decisión de producto aceptada, no un descuido — las flechas siguen ofreciendo una vía de navegación clásica (aunque menos directa) incluso sin JavaScript.
- Ningún otro control de esa misma fila (tarifa por kWh, exportar) cambia de comportamiento; solo se retira el botón "Ir" y se ajusta el comentario/prueba que lo referenciaban.
