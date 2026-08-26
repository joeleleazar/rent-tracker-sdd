# Feature Specification: Correcciones de Auditoría Impeccable (Edición de Locación y Theming del Sidebar)

**Feature Branch**: `025-correcciones-auditoria-impeccable`

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "Correcciones derivadas de la auditoría con el skill 'impeccable' (audit) sobre 3 vistas Blade que nunca habían pasado por revisión de diseño (app-bootstrap.blade.php, error-modal-recibo.blade.php, estado-recibo-locacion.blade.php) y 3 rutas verificadas en vivo (/recibos/1, /locaciones/crear?locacion_padre_id=1, /locaciones/1/editar). La auditoría encontró: (1) [P0, bloqueante] el formulario de editar locación no puede guardarse para ninguna locación que no tenga 'tipo' asignado, porque la validación exige 'tipo' como campo requerido tanto al crear como al editar, sin excepción para datos legacy — verificado que 8 de 8 locaciones de la base de demo no tienen tipo asignado; (2) [P2] el color y dimensiones del sidebar están hardcodeados en un <style> embebido en app-bootstrap.blade.php, duplicando la variable Sass $dark ya definida en bootstrap.scss; (3) cierre formal de la revisión de diseño pendiente sobre las 3 vistas nunca revisadas. El comportamiento de crear una locación nueva (tipo obligatorio) no debe cambiar."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Editar una locación existente sin verse forzado a clasificarla (Priority: P1)

Un operador que necesita corregir el nombre, el tamaño o cualquier otro dato de una locación que fue creada antes de que existiera el campo "tipo" (o que por cualquier motivo no tiene tipo asignado) espera poder guardar esa corrección sin que el sistema le exija clasificar la locación como parte de un cambio que no tiene relación con eso.

**Why this priority**: Es un bloqueo total y verificado (8 de 8 locaciones de la base de demo no pueden editarse hoy) de una operación básica de mantenimiento de datos — la prioridad más alta posible porque impide una tarea, no solo la dificulta.

**Independent Test**: Puede probarse editando una locación sin tipo asignado, cambiando solo el nombre y dejando el campo tipo sin seleccionar, y verificando que el cambio se guarda sin errores de validación.

**Acceptance Scenarios**:

1. **Given** una locación existente sin tipo asignado, **When** el operador edita otro campo (por ejemplo, el nombre) y guarda sin seleccionar un tipo, **Then** el sistema guarda el cambio exitosamente sin exigir un tipo.
2. **Given** una locación existente sin tipo asignado, **When** el operador sí elige explícitamente un tipo válido al editar, **Then** el sistema guarda la locación con ese tipo, igual que hoy.
3. **Given** una locación existente que ya tiene un tipo asignado, **When** el operador intenta guardar el formulario de edición sin ningún tipo seleccionado, **Then** el sistema sigue rechazando el guardado y exige un tipo válido (no se permite quitar un tipo ya asignado).
4. **Given** el formulario de creación de una locación nueva, **When** el operador intenta guardarlo sin seleccionar un tipo, **Then** el sistema sigue rechazando el guardado exactamente igual que hoy (sin cambios en este flujo).

---

### User Story 2 - Sidebar con color y layout mantenibles desde un solo lugar (Priority: P2)

Un desarrollador que en el futuro necesite ajustar la paleta de colores del sistema (por ejemplo, el tono oscuro del sidebar) espera poder hacerlo modificando un único valor, sin tener que recordar que ese mismo color también está escrito de forma separada en otro archivo.

**Why this priority**: Es deuda técnica de mantenibilidad, no un defecto visible hoy para el usuario final — de menor urgencia que el bloqueo de edición, pero con costo creciente cada vez que alguien toca la paleta de colores sin saber del duplicado.

**Independent Test**: Puede probarse inspeccionando el código: el color y las dimensiones base del sidebar deben existir en un solo lugar (la hoja de estilos compilada del proyecto), reutilizando el token de color ya definido, sin ningún `<style>` embebido redundante en la plantilla de layout.

**Acceptance Scenarios**:

1. **Given** la plantilla de layout principal, **When** se inspecciona su código, **Then** ya no contiene una regla de estilo embebida que defina el color de fondo o las dimensiones del sidebar.
2. **Given** la hoja de estilos compilada del proyecto, **When** se inspecciona la regla del sidebar, **Then** el color de fondo referencia el mismo token de color ya usado en el resto del sistema, no un valor hexadecimal repetido.
3. **Given** la aplicación ya migrada, **When** un usuario visualiza cualquier pantalla con el sidebar, **Then** el aspecto visual (color, ancho, comportamiento responsivo) es idéntico al que tenía antes del cambio.

---

### User Story 3 - Cierre formal de la revisión de diseño pendiente (Priority: P3)

Un responsable de calidad de diseño que revisa el cumplimiento del Principio VI de la constitución del proyecto espera que toda vista Blade modificada quede con evidencia de haber pasado por la revisión de diseño exigida, sin huecos sin documentar.

**Why this priority**: Es un requisito de proceso/documentación, no un defecto funcional o visual — se resuelve al final, una vez aplicadas las correcciones de las historias anteriores, para que la revisión final evalúe el estado ya corregido.

**Independent Test**: Puede verificarse revisando que las 3 vistas señaladas por la auditoría (app-bootstrap.blade.php, error-modal-recibo.blade.php, estado-recibo-locacion.blade.php) tengan su revisión de diseño documentada.

**Acceptance Scenarios**:

1. **Given** las 3 vistas señaladas como nunca revisadas, **When** se aplican las correcciones de las historias 1 y 2, **Then** cada una pasa por una revisión de diseño formal y el resultado queda documentado en el sistema de diseño del proyecto.

---

### Edge Cases

- ¿Qué ocurre si una locación sin tipo se edita y el usuario selecciona un tipo por error y luego quiere revertir a "sin tipo"? Fuera de alcance: este feature no agrega una forma de "quitar" un tipo ya asignado, solo evita que el sistema fuerce asignar uno a una locación que nunca lo tuvo.
- ¿Qué ocurre con el resto de reglas de validación del formulario de edición (nombre, tamaño, ubicación, etc.)? No cambian — solo se ajusta la exigencia del campo "tipo".
- ¿Qué ocurre si, tras consolidar el estilo del sidebar, la aplicación se ve en un navegador con caché de la hoja de estilos anterior? Fuera de alcance de este feature (comportamiento estándar de invalidación de caché de assets ya manejado por el proceso de build existente).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE permitir guardar la edición de una locación que no tenía ningún tipo asignado antes del cambio, aunque el campo tipo se deje sin seleccionar.
- **FR-002**: El sistema DEBE seguir exigiendo un tipo válido al editar una locación que ya tenía un tipo asignado (no se permite dejarlo vacío).
- **FR-003**: El sistema DEBE seguir exigiendo un tipo válido al crear una locación nueva, sin excepción, sin cambio de comportamiento respecto a hoy.
- **FR-004**: El sistema DEBE definir el color de fondo y las dimensiones base del sidebar en un único lugar (la hoja de estilos compilada del proyecto), eliminando la regla de estilo embebida duplicada en la plantilla de layout.
- **FR-005**: La regla de color del sidebar DEBE reutilizar el token de color ya definido para ese propósito en el sistema de diseño del proyecto, en vez de repetir su valor hexadecimal.
- **FR-006**: El sistema DEBE mantener el mismo aspecto visual del sidebar (color, ancho, comportamiento responsivo) antes y después de esta consolidación.
- **FR-007**: Las 3 vistas señaladas por la auditoría (app-bootstrap.blade.php, error-modal-recibo.blade.php, estado-recibo-locacion.blade.php) DEBEN quedar con su revisión de diseño documentada conforme al Principio VI de la constitución, una vez aplicadas las correcciones de FR-001 a FR-006.

### Key Entities

- **Locación**: entidad ya existente; este feature no cambia su forma de datos, solo la regla de validación aplicada a su atributo "tipo" durante la edición.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las locaciones existentes sin tipo asignado pueden editarse y guardarse sin ningún error de validación relacionado con el campo tipo.
- **SC-002**: El formulario de creación de una locación nueva sigue rechazando el 100% de los intentos de guardado sin tipo seleccionado, sin excepción.
- **SC-003**: El color y las dimensiones base del sidebar quedan definidos en un único archivo fuente, verificable por inspección de código (cero valores duplicados).
- **SC-004**: Las 3 vistas auditadas quedan registradas como revisadas en la documentación de diseño del proyecto.
- **SC-005**: El 100% de las pruebas automatizadas existentes para locaciones (creación y edición) siguen pasando sin modificar sus aserciones de resultado esperado, salvo la incorporación de casos nuevos para el escenario de edición sin tipo.

## Assumptions

- La intención original de hacer "tipo" nullable en la base de datos (documentada en su propia migración) era precisamente permitir locaciones sin clasificar por compatibilidad con datos anteriores a ese campo — este feature simplemente alinea la validación con esa intención ya existente, no introduce una regla de negocio nueva.
- No se requiere una forma de "desasignar" un tipo ya asignado a una locación — eso queda fuera de alcance por no haber sido parte del hallazgo de la auditoría ni solicitado explícitamente.
- La consolidación del estilo del sidebar es puramente interna (mantenibilidad); no se espera ni se permite ningún cambio perceptible de apariencia para el usuario final.
