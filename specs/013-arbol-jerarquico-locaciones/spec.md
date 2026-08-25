# Feature Specification: Árbol Jerárquico Horizontal de Locaciones

**Feature Branch**: `013-arbol-jerarquico-locaciones`

**Created**: 2026-08-23

**Status**: Ready

**Input**: User description: "Requiero que la vista donde se muestran los locales y sus locales padres se muestren de forma jerarquizada como arbol horizontal para una mejor gestion, no se requiere 2 vistas por separado"

**Contexto**: Actualmente existen dos vistas planas separadas que muestran locaciones sin representar su jerarquía real: el listado general de locaciones (página de inicio) y el listado de locaciones alquilables (que solo muestra la ruta jerárquica como texto plano truncado, sin visualizar el árbol completo ni las locaciones contenedoras como nodos gestionables). Esta especificación las reemplaza por una única vista de árbol jerárquico.

**Revisión 2026-08-23 (estilo de presentación)**: Una primera iteración de esta feature se implementó como un diagrama de tarjetas horizontales conectadas por líneas (estilo organigrama). El usuario pidió reemplazar ese estilo por una **tabla jerárquica indentada** (una fila por locación, con columnas Nombre/Locación, Estado, Tipo y Acciones, controles de expandir/contraer por fila e íconos por tipo de locación), y agregó el requisito de clasificar cada locación con un **Tipo** (de una lista fija: Galería, Piso, Sector, Pasillo, Local) y una acción rápida para agregar una locación hija directamente desde su fila. Esta revisión reemplaza las referencias a "árbol horizontal tipo organigrama" de la versión anterior por este nuevo estilo tabular (ver Assumption A-002 actualizada).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Visualización Unificada del Árbol de Locaciones (Priority: P1)

Como Administrador, quiero ver en una sola vista todas las locaciones (tanto las alquilables como sus locaciones contenedoras) organizadas como una tabla jerárquica indentada (por ejemplo, "Galería El Sol" con "Piso 1" y "Piso 2" indentados debajo, y cada piso con sus locales indentados un nivel más), con columnas de Nombre/Locación, Estado, Tipo y Acciones, para entender de un vistazo la estructura completa del inmueble sin tener que consultar pantallas separadas.

**Why this priority**: Es el objetivo central de la solicitud: eliminar la necesidad de navegar entre dos vistas distintas para entender la estructura jerárquica, reemplazándolas por una representación visual unificada, ordenada y de mejor gestión.

**Independent Test**: Se puede verificar accediendo a la vista unificada de locaciones y comprobando que se renderiza una tabla jerárquica con todas las locaciones registradas (contenedoras y alquilables), cada una indentada según su nivel de profundidad respecto a su locación padre, sin necesidad de visitar ninguna otra pantalla para ver dicha estructura.

**Acceptance Scenarios**:

1. **Given** que existen las locaciones "Galería El Sol" (No Alquilable) → "Piso 1" (No Alquilable) → "Local A" (Alquilable) y "Local B" (Alquilable), **When** el administrador abre la vista unificada de locaciones, **Then** el sistema muestra una fila para "Galería El Sol", con "Piso 1" indentado debajo, y "Local A"/"Local B" indentados un nivel más debajo de "Piso 1", todo dentro de la misma pantalla.
2. **Given** que existen múltiples locaciones raíz (sin locación padre), **When** el administrador visualiza la tabla, **Then** cada locación raíz encabeza su propio grupo de filas indentadas dentro de la misma vista, sin mezclarse entre sí.

---

### User Story 2 - Gestión Directa desde el Árbol (Priority: P2)

Como Administrador, quiero acceder a las acciones de gestión de cualquier locación (ver detalle, editar, o agregar una nueva locación hija) directamente desde su fila en la tabla jerárquica, para administrar el inmueble sin salir de la vista jerárquica.

**Why this priority**: Convierte la visualización en una herramienta de gestión real, no solo informativa, cumpliendo con el objetivo de "mejor gestión" indicado por el usuario.

**Independent Test**: Se puede verificar haciendo clic en las acciones de una fila (tanto de una locación alquilable como de una contenedora) y comprobando que se accede correctamente a las acciones de gestión correspondientes a esa locación, incluyendo agregar una locación hija con el padre ya preseleccionado.

**Acceptance Scenarios**:

1. **Given** la tabla jerárquica visible, **When** el administrador presiona "Editar" en la fila de "Piso 1" (No Alquilable), **Then** el sistema le permite editar "Piso 1" (una locación contenedora no tiene contratos propios).
2. **Given** la tabla jerárquica visible, **When** el administrador presiona "Editar" en la fila de "Local A" (Alquilable), **Then** el sistema le permite acceder a la edición de "Local A" y a sus contratos asociados.
3. **Given** la tabla jerárquica visible, **When** el administrador presiona la acción rápida "Agregar" (ícono "+") en la fila de "Piso 1", **Then** el sistema navega al formulario de creación de una nueva locación con "Piso 1" ya preseleccionada como locación padre.

---

### User Story 3 - Manejo de Árboles Grandes (Priority: P3)

Como Administrador, quiero poder contraer y expandir ramas del árbol cuando el inmueble tiene muchas locaciones, para mantener la vista legible y manejable sin perder de vista la estructura general.

**Why this priority**: Sin esta capacidad, un portafolio con cientos de locaciones (ver Asunción de `specs/001-jerarquia-locaciones`) volvería la tabla jerárquica visualmente inmanejable, contradiciendo el objetivo de "mejor gestión".

**Independent Test**: Se puede verificar contrayendo la fila de una locación con varias locaciones hijas y comprobando que sus filas descendientes se ocultan sin afectar al resto de la tabla, y que expandirla nuevamente las vuelve a mostrar.

**Acceptance Scenarios**:

1. **Given** una locación con varias locaciones hijas visibles en la tabla, **When** el administrador contrae su fila, **Then** las filas de sus locaciones descendientes dejan de mostrarse y el control de expandir/contraer indica visualmente que tiene contenido oculto.
2. **Given** una fila previamente contraída, **When** el administrador la expande, **Then** sus filas hijas vuelven a mostrarse en su posición jerárquica correcta, respetando la indentación.

### Edge Cases

- **Locación raíz sin hijas**: Una locación sin padre y sin locaciones hijas se muestra como una única fila sin control de expandir/contraer.
- **Jerarquías muy profundas**: Si una locación tiene muchos niveles de profundidad, la indentación acumulada de las filas más profundas se contiene dentro de un área de desplazamiento horizontal propia de la tabla, sin producir scroll horizontal a nivel de página completa (Restricciones Técnicas de la Constitución).
- **Locaciones con muchas hijas directas**: Se muestran como filas adicionales apiladas verticalmente bajo su padre (la tabla crece verticalmente, con scroll de página normal), sin afectar el ancho de la tabla.
- **Prevención de ciclos al renderizar**: Aunque `specs/001-jerarquia-locaciones` (FR-003) ya impide guardar jerarquías cíclicas, el recorrido para construir la tabla jerárquica MUST aplicar la misma salvaguarda defensiva de límite de profundidad ya usada en el sistema, para no producir un bucle infinito si los datos llegaran a corromperse.
- **Volumen alto de locaciones**: Con el volumen típico esperado (hasta 1,000 locaciones, Asunción A-002 de `specs/001-jerarquia-locaciones`), la tabla debe seguir siendo navegable mediante la capacidad de contraer/expandir filas (US3).
- **Locación sin Tipo asignado**: Las locaciones registradas antes de esta revisión no tienen un valor de Tipo; se muestran con una etiqueta neutra ("Sin tipo") y un ícono genérico hasta que el administrador la edite y seleccione uno.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST mostrar todas las locaciones registradas (alquilables y contenedoras) en una única vista unificada, organizadas como una tabla jerárquica indentada (una fila por locación, indentada según su nivel de profundidad) que refleje visualmente las relaciones padre-hijo reales, con columnas de Nombre/Locación, Estado, Tipo y Acciones.
- **FR-002**: Esta vista unificada MUST reemplazar a las dos vistas planas previas usadas para este propósito (el listado general de locaciones y el listado de locaciones alquilables con ruta de texto truncada); el administrador ya no MUST necesitar consultar dos pantallas distintas para entender la estructura jerárquica.
- **FR-003**: Cada fila MUST distinguir visualmente si la locación es alquilable o contenedora (no alquilable) en su columna Estado, usando las convenciones de color semántico ya establecidas en el proyecto (Principio VI de la Constitución).
- **FR-004**: El sistema MUST admitir y representar correctamente múltiples locaciones raíz (sin padre), cada una encabezando su propio grupo de filas indentadas dentro de la misma tabla.
- **FR-005**: Cada fila MUST ofrecer acceso directo a las acciones de gestión de esa locación (editar, y ver contratos cuando aplique) sin necesidad de abandonar la vista jerárquica para localizarla primero.
- **FR-006**: Las filas con locaciones hijas MUST poder contraerse y expandirse individualmente, para mantener manejable la visualización en jerarquías con muchas locaciones.
- **FR-007**: La tabla MUST evitar el scroll horizontal a nivel de página completa; cualquier desbordamiento horizontal (por indentación acumulada en jerarquías profundas) MUST contenerse dentro de un área de desplazamiento propia.
- **FR-008**: El recorrido de construcción de la tabla jerárquica MUST aplicar un límite defensivo de profundidad para prevenir bucles infinitos ante datos jerárquicos corruptos, de forma consistente con la salvaguarda ya existente en el sistema.
- **FR-009**: La vista MUST cumplir con el contraste mínimo WCAG AA (4.5:1) en el texto, íconos y badges de cada fila.
- **FR-010**: El sistema MUST permitir clasificar cada locación con un campo "Tipo" de una lista fija predefinida (Galería, Piso, Sector, Pasillo, Local), mostrado en su propia columna junto con un ícono distintivo por tipo.
- **FR-011**: Cada fila MUST incluir una acción rápida "Agregar" que navegue al formulario de creación de una nueva locación con esa fila ya preseleccionada como locación padre.

### Key Entities *(include if feature involves data)*

- **Locacion**: Entidad ya existente (`specs/001-jerarquia-locaciones`), extendida en esta revisión con:
  - `tipo` (uno de: Galería, Piso, Sector, Pasillo, Local; nullable — las locaciones existentes antes de esta revisión no tienen un valor asignado hasta que se editen).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador puede comprender la estructura jerárquica completa del inmueble consultando una única vista, sin necesidad de navegar entre pantallas separadas (reducción de 2 vistas a 1 para este propósito).
- **SC-002**: Un administrador puede localizar cualquier locación específica (alquilable o contenedora) dentro de la tabla y llegar a sus acciones de gestión en un máximo de 2 interacciones desde la vista unificada.
- **SC-003**: La vista se mantiene legible y sin degradación visual con portafolios de hasta 1,000 locaciones (volumen típico ya asumido por el proyecto), gracias a la capacidad de contraer/expandir filas.
- **SC-004**: El 100% de las locaciones registradas (alquilables y contenedoras) aparecen representadas como filas de la tabla, con relaciones padre-hijo visualmente correctas (indentación) respecto a los datos almacenados.
- **SC-005**: Un administrador puede agregar una nueva locación hija de cualquier fila visible en un máximo de 2 interacciones (acción rápida "Agregar" + completar el formulario ya preseleccionado).

## Assumptions

- **A-001**: La vista unificada reemplaza tanto al listado general de locaciones de la página de inicio como al listado de locaciones alquilables; ambas rutas existentes pueden seguir apuntando a esta misma vista consolidada en vez de eliminarse, siempre que el contenido mostrado sea uno solo (no dos listados distintos).
- **A-002** (revisada 2026-08-23): La jerarquía se presenta como una **tabla indentada** (una fila por locación, indentada según profundidad, con columnas Nombre/Locación, Estado, Tipo y Acciones, y control de expandir/contraer por fila), reemplazando el diseño de tarjetas horizontales conectadas por líneas de la iteración anterior de esta feature.
- **A-003**: No se requiere mostrar el estado de los contratos (vigente/vencido) directamente en las filas de la tabla; esa información sigue disponible a través de la acción "Ver Contratos" de cada fila alquilable, sin duplicarse visualmente en la tabla.
- **A-004**: El estado de expansión/contracción de las filas es una preferencia de visualización en la sesión del navegador del administrador y no necesita persistirse en el servidor entre sesiones.
- **A-005**: El campo "Tipo" es una lista fija predefinida (Galería, Piso, Sector, Pasillo, Local) en vez de texto libre, para poder asociar un ícono consistente por tipo y evitar variantes inconsistentes (ej. "piso" vs "Piso" vs "PISO"). Es nullable a nivel de base de datos (las locaciones existentes antes de esta revisión no tienen un valor), pero obligatorio en el formulario para locaciones nuevas o editadas.
