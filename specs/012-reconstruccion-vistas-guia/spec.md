# Feature Specification: Reconstrucción de Vistas según la Guía de Referencia Bootstrap

**Feature Branch**: `012-reconstruccion-vistas-guia`

**Created**: 2026-08-21

**Status**: Draft

**Input**: User description: "Reconstruir desde cero todas las vistas Blade de la interfaz de administración (features 001-009), reemplazando la implementación visual actual (de specs 010 y 011) por una que siga literalmente los wireframes, snippets de componentes y estructura de contenido descritos en los documentos de referencia que ahora forman parte del Principio VI de la constitución (docs/referencias-diseno-bootstrap/), respetando sin excepción las 3 reconciliaciones ya fijadas como vinculantes (sidebar, htmx, paleta de colores propia)."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Componentes de Contrato Fieles a la Guía de Referencia (Priority: P1)

Como Administrador, quiero que la pantalla de contrato (creación, detalle e historial) presente sus componentes exactamente con la estructura descrita en la guía de referencia del proyecto (dropzone de documentos con las dos opciones de carga, modal de solapamiento con las secciones "Contrato Existente"/"Nuevo Contrato" separadas, timeline de historial con indicador de fecha y badge de estado, panel de costos en grid de 2 columnas con total calculado de solo lectura), para tener la experiencia más completa y detallada que el proyecto definió como referencia, sin perder ninguna validación ni comportamiento ya vigente.

**Why this priority**: Es la pantalla más compleja y con más componentes descritos en la guía (documentos, solapamiento, costos, timeline); establecerla primero valida el patrón de reconstrucción antes de extenderlo al resto de las pantallas.

**Independent Test**: Se puede verificar creando un contrato con documentos, provocando un solapamiento de fechas, revisando el historial de una locación con contratos previos, y registrando costos de referencia, comprobando en cada caso que el componente visual coincide con la estructura descrita en la guía y que el resultado de negocio (validaciones, persistencia) es idéntico al actual.

**Acceptance Scenarios**:

1. **Given** el detalle de un contrato sin documentos cargados, **When** el Administrador visualiza la sección de documentos, **Then** se presenta un área de carga con las dos opciones "Seleccionar PDF del Contrato" y "Subir Fotos de Páginas" descritas en la guía, indicando los límites de tamaño y cantidad.
2. **Given** un intento de crear un contrato que se solapa con uno existente, **When** el sistema detecta el conflicto, **Then** el modal de advertencia muestra el contrato existente y el nuevo contrato en dos bloques de alerta claramente diferenciados, cada uno con sus propios datos (fechas, inquilino, monto).
3. **Given** una locación con 3 o más contratos históricos, **When** el Administrador visualiza su historial, **Then** se presenta como una línea de tiempo con un indicador de fecha y un badge de estado (activo/vencido/rescindido) por cada contrato, no como una lista plana de tarjetas idénticas.
4. **Given** el formulario de costos de referencia de un contrato, **When** el Administrador lo visualiza, **Then** los campos se organizan en una cuadrícula de 2 columnas con el símbolo "S/" integrado en cada campo, y un campo adicional de solo lectura muestra el total calculado de la suma.

---

### User Story 2 - Paneles de Representantes y Recibos Fieles a la Guía (Priority: P2)

Como Administrador, quiero que el panel de representantes (tarjetas individuales con búsqueda en directorio) y el panel de estado de recibo (las 3 opciones de estado visibles simultáneamente) sigan la estructura exacta de la guía de referencia, para gestionar estos dos flujos frecuentes con la misma experiencia detallada que el resto de la aplicación.

**Why this priority**: Son los dos flujos operativos de mayor frecuencia de uso después del contrato (P1); se benefician de que el patrón de reconstrucción ya esté validado.

**Independent Test**: Se puede verificar agregando dos representantes a un contrato y comprobando que cada uno se presenta en su propia tarjeta con ancho mínimo consistente y un botón de búsqueda en el directorio global; y cambiando el estado de un recibo comprobando que las 3 opciones de estado (Pendiente/Pagado/Anulado) están visibles y seleccionables simultáneamente en un solo control, sin necesidad de un formulario de acción separado por estado.

**Acceptance Scenarios**:

1. **Given** un contrato con dos representantes asociados, **When** el Administrador visualiza el panel de representantes, **Then** cada representante se muestra en su propia tarjeta individual (ancho mínimo consistente en pantallas grandes), con sus acciones de marcar Principal/Quitar dentro de esa misma tarjeta.
2. **Given** el formulario para agregar un representante, **When** el Administrador busca por DNI o apellido, **Then** la búsqueda se realiza dentro de un modal dedicado de directorio, mostrando los resultados como una lista seleccionable.
3. **Given** el detalle de un recibo, **When** el Administrador visualiza su estado, **Then** las 3 opciones de estado (Pendiente/Pagado/Anulado) se presentan simultáneamente como un único control de selección, resaltando la opción actual, en vez de un botón de acción por cada posible transición.

---

### User Story 3 - Vista de Impresión y Detalle de Consistencia Restante (Priority: P3)

Como Administrador, quiero que la vista de impresión del recibo use las reglas de impresión descritas en la guía de referencia, y que el resto de vistas menores (locaciones, lecturas de medidor, configuración) también reflejen el nivel de detalle de componentes de la guía donde aplique, para que toda la aplicación sea consistente con el mismo estándar de referencia, no solo las pantallas más complejas.

**Why this priority**: Cierra la reconstrucción con las pantallas de menor complejidad/frecuencia, una vez validado el patrón en P1 y P2.

**Independent Test**: Se puede verificar imprimiendo (o generando la vista previa de impresión de) un recibo y comprobando que el diseño se adapta a un formato imprimible legible; y revisando las vistas de locaciones/lecturas/configuración para confirmar que usan los mismos componentes ya estandarizados (cards, badges, input-groups) sin inconsistencias visuales respecto al resto de la aplicación.

**Acceptance Scenarios**:

1. **Given** un recibo emitido, **When** el Administrador solicita su impresión, **Then** el documento se adapta a un formato de impresión limpio y legible, ocultando los elementos de navegación e interacción que no corresponden a un documento impreso.
2. **Given** cualquier pantalla restante no cubierta explícitamente por P1/P2, **When** el Administrador la visualiza, **Then** usa los mismos componentes ya estandarizados (cards, badges de estado, input-groups monetarios, breadcrumbs) sin mezclarlos con patrones visuales distintos entre pantallas.

### Edge Cases

- **Conflicto entre el detalle de la guía y una decisión ya fijada como vinculante**: Si un componente descrito en la guía de referencia (ej. navegación, interactividad de escritura, colores exactos) contradice una de las 3 reconciliaciones ya fijadas en el Principio VI de la constitución (sidebar, htmx, paleta propia), la reconciliación de la constitución MUST prevalecer sin excepción; el detalle de la guía se sigue únicamente donde no entra en conflicto con esas 3 reconciliaciones.
- **Preservación de reglas de negocio durante la reconstrucción visual**: Ningún componente reconstruido puede alterar una regla de validación o de negocio ya implementada (ej. el modal de solapamiento sigue bloqueando el guardado exactamente en los mismos casos que hoy, solo cambia cómo se presenta la información del conflicto).
- **Componentes de la guía no aplicables al dominio real del proyecto**: Donde un snippet de la guía use datos de ejemplo genéricos (nombres, montos) que no correspondan a los campos reales del sistema, se adapta la estructura visual del componente (grid, tarjeta, modal, badge) a los campos reales, sin inventar datos ni campos que no existan en el modelo actual.
- **Vistas fuera del alcance de la guía**: Las pantallas de autenticación (`auth/*`), perfil de usuario y la vista de bienvenida (ya retirada en `specs/011`) no están cubiertas por ningún documento de la guía y quedan fuera del alcance de esta reconstrucción, salvo que requieran un ajuste menor de consistencia visual con el resto de la aplicación ya reconstruida.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST presentar la sección de documentos del contrato como un área de carga con dos opciones explícitas de selección ("Seleccionar PDF del Contrato", "Subir Fotos de Páginas"), indicando los límites de tamaño y cantidad ya vigentes, en vez de los controles de carga actuales.
- **FR-002**: El sistema MUST presentar el conflicto de solapamiento de contratos como un modal con dos bloques de alerta separados y claramente rotulados ("Contrato Existente" y el contrato que se intenta registrar), cada uno mostrando sus propios datos relevantes (fechas, inquilino, monto).
- **FR-003**: El sistema MUST presentar el historial de contratos de una locación como una línea de tiempo, con un indicador de fecha y un badge de estado por cada contrato listado.
- **FR-004**: El sistema MUST presentar los costos de referencia del contrato en una cuadrícula de 2 columnas, cada campo monetario con el símbolo "S/" integrado, más un campo adicional de solo lectura con el total calculado de la suma de todos los costos.
- **FR-005**: El sistema MUST presentar cada representante asociado a un contrato en su propia tarjeta individual con un ancho mínimo consistente, incluyendo dentro de esa tarjeta sus acciones de marcar Principal y quitar.
- **FR-006**: El sistema MUST realizar la búsqueda de representantes en el directorio global dentro de un modal dedicado, mostrando los resultados como una lista seleccionable.
- **FR-007**: El sistema MUST presentar las 3 opciones de estado de un recibo (Pendiente/Pagado/Anulado) simultáneamente visibles y seleccionables en un único control, resaltando la opción vigente.
- **FR-008**: El sistema MUST aplicar reglas de estilo de impresión a la vista de comprobante de recibo, ocultando los elementos de navegación e interacción no relevantes para un documento impreso.
- **FR-009**: El sistema MUST mantener, en toda vista reconstruida, la navegación por sidebar fijo, la interactividad de escritura vía htmx, y la paleta de colores exacta ya definida en el proyecto — ninguna de estas 3 reconciliaciones se reemplaza por lo que sugiera la guía de referencia.
- **FR-010**: El sistema MUST preservar exactamente las mismas rutas, controladores, modelos, servicios, reglas de validación y comportamiento de negocio ya implementados en las especificaciones 001 a 011; esta reconstrucción es exclusivamente de la capa de presentación.
- **FR-011**: El sistema MUST mantener, en toda vista reconstruida, los mínimos de accesibilidad Senior-First ya vigentes (tipografía ≥18px, contraste ≥4.5:1, botones/áreas táctiles ≥48x48px, confirmación explícita en acciones destructivas).

### Key Entities

*No aplica: esta feature no introduce ni modifica entidades de datos; reutiliza exactamente el mismo modelo de datos de las especificaciones 001 a 011.*

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los componentes descritos explícitamente en la guía de referencia para las pantallas de contrato, representantes y recibos (dropzone, modal de solapamiento, timeline, grid de costos, tarjetas de representante, selector de estado) están implementados según su estructura descrita, verificado componente por componente.
- **SC-002**: El 100% de las pruebas automatizadas existentes (193 al momento de esta especificación) continúan pasando sin modificar ninguna aserción de regla de negocio.
- **SC-003**: El 100% de las vistas reconstruidas mantienen las 3 reconciliaciones vinculantes (sidebar, htmx, paleta propia) y los 4 mínimos de accesibilidad Senior-First, verificado pantalla por pantalla.
- **SC-004**: Un Administrador puede completar los mismos flujos de negocio (registrar contrato con documentos y costos, resolver un solapamiento, gestionar representantes, cambiar estado de un recibo, imprimir un comprobante) sin ninguna pérdida de funcionalidad respecto al estado actual del sistema.

## Assumptions

- **A-001**: "Literalmente" (Input del usuario) se interpreta como seguir la estructura, agrupación y tipo de componente descritos en la guía (ej. "grid de 2 columnas", "modal de directorio", "timeline"), adaptando nombres de campos/datos de ejemplo a los reales del sistema — no copiar textualmente marcadores de posición o datos ficticios de los snippets de la guía.
- **A-002**: Donde la guía de referencia sugiere una tecnología o color específico que ya fue reconciliado en el Principio VI de la constitución (sidebar vs. navbar, htmx vs. Alpine.js, paleta propia vs. genérica), esa reconciliación ya resuelta no vuelve a discutirse en esta spec; se documenta únicamente si aparece un caso nuevo no cubierto por esas 3 reconciliaciones.
- **A-003**: El volumen de datos y el modelo de usuario único (Administrador, sin RBAC) siguen siendo los mismos que en las especificaciones anteriores.
- **A-004**: Las vistas de autenticación, perfil y cualquier pantalla fuera de las features 001-009 no requieren reconstrucción exhaustiva componente por componente, solo consistencia visual básica si ya heredan el mismo layout compartido.
