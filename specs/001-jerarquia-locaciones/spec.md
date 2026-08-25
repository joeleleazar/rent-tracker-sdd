# Feature Specification: Jerarquía de Locaciones Alquilables

**Feature Branch**: `001-jerarquia-locaciones`

**Created**: 2026-08-19

**Status**: Ready

**Input**: User description: "Cada locacion tiene sus caracteristicas como: tamaño, ubicacion, descripcion, ademas que una locacion puede estar dentro de otras, si bien no se alquila la locacion que las contiene si debe ser visible que existe esa jerarquia por ejemplo Galeria->piso->locacion a alquilar"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Visualización Accesible de Jerarquía (Priority: P1)

Como Administrador e Inquilino, quiero ver de manera clara e inequívoca la jerarquía completa de una locación que se ofrece en alquiler (por ejemplo, "Galería El Sol > Primer Piso > Local 12") con tipografía legible y alto contraste, para ubicar perfectamente el espacio sin confusión.

**Why this priority**: Es la necesidad central del negocio. Permite identificar la procedencia y ubicación exacta de la locación en alquiler, respetando el principio de diseño moderno e intuitivo definido en la Constitución del proyecto.

**Independent Test**: Se puede verificar accediendo al detalle de una locación marcada como alquilable y comprobando que se renderice su "ruta de navegación" (breadcrumbs) completa con contraste accesible (relación mínima 4.5:1).

**Acceptance Scenarios**:

1. **Given** que existe la jerarquía "Galería El Sol" (No Alquilable) -> "Piso 1" (No Alquilable) -> "Local A" (Alquilable), **When** un usuario visualiza el detalle de "Local A", **Then** el sistema muestra la cadena completa "Galería El Sol > Piso 1 > Local A" con contraste adecuado.
2. **Given** que un usuario visualiza la lista de locaciones disponibles para alquiler, **When** examina las opciones, **Then** solo se ofrecen para alquilar aquellas marcadas explícitamente como "alquilables", pero se visualiza de forma estática su locación contenedora asociada para dar contexto de ubicación.

---

### User Story 2 - Configuración de la Estructura de Locaciones (Priority: P2)

Como Administrador, quiero crear locaciones de cualquier nivel, registrando sus características obligatorias (tamaño en metros cuadrados, ubicación física y descripción) e indicando opcionalmente su locación contenedora (padre) y si es alquilable, para estructurar libremente el catálogo inmobiliario.

**Why this priority**: Permite alimentar el sistema con los datos y relaciones jerárquicas necesarias para que la visualización (P1) funcione correctamente.

**Independent Test**: Se puede probar creando una locación "Piso 2" cuyo padre sea "Galería El Sol", y luego un "Local B" cuyo padre sea "Piso 2", comprobando que los registros se guarden con las relaciones de clave foránea correctas en la base de datos PostgreSQL.

**Acceptance Scenarios**:

1. **Given** que existe una locación "Galería Central", **When** el administrador registra una nueva locación con nombre "Piso 1", tamaño "120.00", ubicacion "Sector Norte", descripción "Primer nivel de la galería", seleccionando "Galería Central" como locación padre y marcando "No Alquilable", **Then** el sistema guarda exitosamente la relación en la base de datos.
2. **Given** el formulario de creación de locación, **When** se intenta guardar una locación con el campo tamaño vacío o con un valor no numérico, **Then** el sistema detiene el proceso y muestra un mensaje de error explícito y persistente de alta visibilidad.

---

### User Story 3 - Prevención de Jerarquías Cíclicas (Priority: P3)

Como Administrador, quiero que el sistema me impida asignar una locación padre que resulte en un ciclo infinito (por ejemplo, que una Galería sea hija de un Piso que a su vez es hijo de esa misma Galería), para evitar fallos de desbordamiento de pila o bucles infinitos en la interfaz y reportes.

**Why this priority**: Garantiza la estabilidad técnica y la integridad referencial del sistema, evitando que la interfaz de usuario se bloquee o entre en bucle infinito al intentar renderizar la jerarquía.

**Independent Test**: Se puede validar intentando editar una locación padre para asignarle como su nuevo padre a una de sus propias locaciones hijas, y verificando que el sistema rechace la transacción y retorne un error de validación.

**Acceptance Scenarios**:

1. **Given** que "Piso 1" tiene como padre a "Galería El Sol", **When** el administrador edita "Galería El Sol" e intenta asignarle como padre a "Piso 1", **Then** el sistema bloquea la acción, muestra un mensaje de advertencia claro ("No se puede asignar una locación hija como padre") y no guarda los cambios.

### Edge Cases

- **Locaciones Huérfanas por Eliminación**: ¿Qué ocurre si se elimina una locación contenedora que tiene sub-locaciones asociadas? El sistema aplica una **Restricción Estricta (Bloqueo)**. Se impedirá la eliminación de cualquier locación que posea sub-locaciones asociadas en la base de datos. El administrador debe eliminar o desvincular/reasignar las locaciones hijas manualmente antes de poder proceder.
- **Profundidad Excesiva de la Jerarquía**: ¿Existe un límite de profundidad para evitar problemas visuales o de rendimiento en pantallas de tamaño estándar? A nivel de base de datos la profundidad es **ilimitada**. Sin embargo, para mantener la ruta legible sin abrumar la interfaz, la interfaz de usuario **truncará la visualización** a un máximo de los últimos 3 niveles (ej. "... > Piso 1 > Local 10"), asegurando que la ruta quepa holgadamente en pantalla sin requerir desplazamiento horizontal.
- **Cambio de Atributo Alquilable**: Si una locación pasa de "Alquilable" a "No Alquilable" pero tiene contratos de alquiler activos o históricos, el sistema debe bloquear el cambio o manejar la transición de manera segura para preservar la integridad transaccional (Principio V de la Constitución).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST permitir el registro de locaciones con los campos obligatorios: nombre (texto), tamaño (numérico exacto decimal), ubicación física (texto), descripción (texto), locación_padre_id (entero, opcional/nullable) y es_alquilable (booleano).
- **FR-002**: El sistema MUST admitir relaciones reflexivas (padre-hijo) de modo que cualquier locación pueda tener cero o una locación padre.
- **FR-003**: El sistema MUST validar antes de guardar que no se generen dependencias circulares directas o indirectas en la jerarquía de locaciones.
- **FR-004**: La interfaz de usuario MUST presentar la jerarquía de locaciones en un formato plano lineal con contraste WCAG AA. Si la jerarquía supera los 3 niveles, la visualización se truncará mostrando un indicador de omisión y únicamente los últimos 3 niveles (ej. "... > Padre > Hijo > Local") para preservar la legibilidad.
- **FR-005**: El sistema MUST permitir filtrar las búsquedas para mostrar únicamente locaciones marcadas como `es_alquilable = true`, pero mostrando siempre su contexto jerárquico truncado asociado.
- **FR-006**: Las operaciones de creación y edición de locaciones jerárquicas MUST ejecutarse bajo transacciones atómicas de base de datos (`DB::transaction`) para garantizar la consistencia relacional (Principio V de la Constitución).
- **FR-007**: El sistema MUST bloquear la eliminación de una locación que tenga sub-locaciones hijas asociadas, lanzando una confirmación explícita con explicación clara.

### Key Entities *(include if feature involves data)*

- **Locacion**: Representa cualquier espacio físico gestionable en el sistema. Puede ser un contenedor organizativo o una unidad de alquiler final.
  - `id` (Entero, Auto-incremental, Clave Primaria)
  - `nombre` (Cadena de caracteres, Obligatorio)
  - `tamaño` (Decimal con precisión exacta de 2 decimales, Obligatorio)
  - `ubicacion_fisica` (Texto libre, Obligatorio)
  - `descripcion` (Texto libre, Obligatorio)
  - `locacion_padre_id` (Entero, Referencia a `Locacion.id`, Nullable, Clave Foránea)
  - `es_alquilable` (Booleano, Obligatorio, por defecto falso)
  - `timestamps` (Marcas de tiempo de creación y modificación)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Los usuarios administradores de cualquier edad pueden registrar una nueva locación con su jerarquía en menos de 2 minutos sin requerir asistencia externa.
- **SC-002**: La interfaz de visualización de la jerarquía en pantallas de detalle de locación cumple con el estándar de accesibilidad del proyecto (tamaño mínimo de fuente de 18px, área táctil de botones de mínimo 48x48px, y contraste mínimo 4.5:1).
- **SC-003**: El tiempo de respuesta para renderizar la jerarquía completa de una locación en el backend no excede los 200 milisegundos bajo una carga de 100 consultas simultáneas.
- **SC-004**: El sistema previene el 100% de los intentos de crear dependencias circulares mediante validación estricta a nivel de modelo y transacción en el servidor.

## Assumptions

- **A-001**: Las locaciones contenedoras (las que tienen `es_alquilable = false`) no tienen montos de alquiler asociados ni pueden vincularse a contratos de arrendamiento.
- **A-002**: Se asume que el volumen típico de locaciones para un único cliente no superará las 1,000 unidades en total, por lo que la carga recursiva en PostgreSQL se puede manejar eficientemente con consultas estándar o expresiones de tabla común (CTE).
- **A-003**: La base de datos relacional PostgreSQL del proyecto gestionará de forma nativa la restricción de clave foránea reflexiva con las políticas de integridad adecuadas.
