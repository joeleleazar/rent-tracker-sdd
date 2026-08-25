# Feature Specification: Gestión de Contratos de Locación

**Feature Branch**: `002-gestion-contratos`

**Created**: 2026-08-19

**Status**: Ready

**Input**: User description: "Cada locacion puede tener multiples contratos, secuenciales no deberia haber 2 contratos al mismo tiempo corriendo y se debe poder subir el archivo en pdf o las fotos del mismo contrato firmado notarialmente"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Registro de Contrato y Regla de No Solapamiento (Priority: P1)

Como Administrador, quiero registrar un contrato de alquiler para una locación específica indicando el inquilino, el monto, y el rango de fechas (desde/hasta), de modo que el sistema impida guardar si existe otro contrato activo o programado que se solape en el tiempo para esa misma locación.

**Why this priority**: Es la restricción central del negocio y garantiza la integridad de las operaciones de alquiler. Previene el error crítico de alquilar un mismo espacio a dos inquilinos diferentes al mismo tiempo.

**Independent Test**: Se puede verificar intentando guardar un nuevo contrato cuyas fechas de inicio o fin coincidan, parcial o totalmente, con el rango de un contrato ya existente en una locación, comprobando que el sistema rechace la transacción y muestre una alerta de error explícita, persistente y de alta legibilidad en el servidor.

**Acceptance Scenarios**:

1. **Given** que la "Locación A" tiene un contrato activo del "2026-01-01" al "2026-12-31", **When** el administrador registra un contrato para "Locación A" del "2026-06-01" al "2027-05-31", **Then** el sistema bloquea el registro, muestra una alerta detallada en alto contraste y no persiste datos en PostgreSQL.
2. **Given** que la "Locación A" tiene un contrato del "2026-01-01" al "2026-06-30", **When** el administrador registra un contrato para "Locación A" con fecha de inicio "2026-07-01", **Then** el sistema guarda el contrato exitosamente ya que es secuencial y no existe solapamiento temporal.

---

### User Story 2 - Carga Accesible del Contrato Notariado (Priority: P2)

Como Administrador, quiero adjuntar el archivo PDF o las fotos de las páginas del contrato firmado notarialmente de manera simple (mediante botones claros, sin interfaces confusas), para respaldar jurídicamente el alquiler.

**Why this priority**: Permite digitalizar y archivar el documento de respaldo del contrato directamente en el sistema, lo cual es fundamental para auditorías y referencias rápidas de contratos firmados.

**Independent Test**: Se puede verificar accediendo al formulario del contrato, seleccionando un archivo PDF o tres fotos JPG del contrato firmado, guardando el registro y comprobando que los archivos se carguen correctamente y se puedan previsualizar en pantalla con controles simples de zoom.

**Acceptance Scenarios**:

1. **Given** un nuevo contrato, **When** el administrador presiona el botón "Seleccionar PDF del Contrato" y selecciona un documento PDF, **Then** el sistema carga el archivo, muestra un indicador de éxito persistente con el nombre del archivo y permite previsualizar la primera página del documento.
2. **Given** un nuevo contrato, **When** el administrador presiona el botón "Subir Foto de Página" y carga múltiples imágenes JPG/PNG (hasta un límite de 10 fotos), **Then** el sistema asocia las imágenes secuencialmente al contrato y muestra una galería simple y de alta legibilidad con miniaturas grandes para su revisión.

---

### User Story 3 - Consulta de Historial de Contratos Secuenciales (Priority: P3)

Como Administrador, quiero ver el listado histórico de contratos que ha tenido una locación ordenados cronológicamente, diferenciando claramente el contrato activo de los contratos pasados o futuros programados.

**Why this priority**: Facilita el seguimiento administrativo de la ocupación histórica del inmueble, ayudando a planificar renovaciones y a consultar históricos de pagos y arrendatarios de manera secuencial.

**Independent Test**: Se puede validar ingresando a la sección "Historial de Contratos" de una locación y verificando que el listado muestre las relaciones cronológicas de manera lineal, destacando visualmente el contrato activo (si existe) utilizando un indicador de color de alto contraste.

**Acceptance Scenarios**:

1. **Given** que una locación ha tenido 3 contratos históricos (uno vencido en 2025, uno activo en 2026, uno reservado para 2027), **When** se consulta el historial de la locación, **Then** se muestran los 3 registros en orden cronológico inverso, destacando el del 2026 con una etiqueta explícita de "Contrato Activo actual".

### Edge Cases

- **Terminación Anticipada para Nuevo Contrato**: ¿Cómo gestiona el sistema el caso donde se desea registrar un nuevo contrato para una locación pero el actual debe ser cancelado o modificado antes de su vencimiento planificado? El sistema implementa **Rescisión Manual Obligatoria**. El administrador debe editar de manera explícita el contrato actual, cambiar su estado a "Rescindido" (o "Cancelado") y ajustar su `fecha_fin` para dejar libre el rango de fechas. Solo tras esta acción manual el sistema permitirá registrar el nuevo contrato secuencial. Esto evita acortamientos accidentales y mantiene la trazabilidad jurídica de las rescisiones.
- **Soporte de Cargas de Archivos**: ¿Qué formatos específicos y cantidad máxima de archivos de fotos de páginas se permiten subir simultáneamente para un solo contrato? Se define una **Carga Flexible con Límite Estándar**. El sistema permite asociar exactamente un único archivo en formato PDF (con un límite de 15MB) **O** hasta un máximo de 10 fotos individuales de las páginas del contrato (en formatos JPG o PNG, con un límite de 5MB por imagen). El sistema paginará secuencialmente estas imágenes en el visualizador lineal.
- **Eliminación o Cambio de Documento Adjunto**: El sistema debe solicitar confirmación de alta visibilidad antes de eliminar un archivo digitalizado del contrato, asegurando que no se pierdan respaldos sin advertencia explícita.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST permitir crear, editar y consultar contratos de alquiler asociados a una única locación.
- **FR-002**: El sistema MUST requerir para cada contrato: inquilino (nombre/referencia), fecha_inicio (fecha), fecha_fin (fecha), monto_renta (numérico exacto decimal) y estado (borrador, activo, vencido, rescindido).
- **FR-003**: El sistema MUST validar a nivel de modelo y base de datos que ninguna locación tenga dos contratos activos o programados cuyas fechas de vigencia se solapen. Si se detecta solapamiento, el sistema bloqueará el registro requiriendo la rescisión manual y ajuste del contrato previo.
- **FR-004**: El sistema MUST permitir asociar un archivo en formato PDF (máximo 15MB) O hasta un máximo de 10 imágenes (JPG/PNG, máximo 5MB cada una) como respaldo digitalizado del contrato firmado notarialmente.
- **FR-005**: La interfaz de usuario para cargar documentos MUST ofrecer botones descriptivos y claros, con textos unívocos ("Seleccionar PDF del Contrato", "Subir Foto de Página").
- **FR-006**: Todos los registros y cálculos relacionados con los contratos y el almacenamiento de documentos MUST gestionarse de forma atómica bajo transacciones (`DB::transaction`) en la base de datos PostgreSQL.

### Key Entities *(include if feature involves data)*

- **Contrato**: Representa el acuerdo de arrendamiento legal de una locación para un periodo específico.
  - `id` (Entero, Auto-incremental, Clave Primaria)
  - `locacion_id` (Entero, Referencia a `Locacion.id`, Clave Foránea, Obligatorio)
  - `inquilino_id` (Entero, Referencia al Inquilino, Clave Foránea, Obligatorio)
  - `fecha_inicio` (Fecha, Obligatorio)
  - `fecha_fin` (Fecha, Obligatorio)
  - `monto_renta` (Decimal exacto, Obligatorio)
  - `estado` (Cadena de caracteres/Enum, ej. "activo", "vencido", "cancelado")
- **DocumentoContrato**: Representa los archivos digitales de respaldo (PDF o fotos) adjuntos al contrato.
  - `id` (Entero, Auto-incremental, Clave Primaria)
  - `contrato_id` (Entero, Referencia a `Contrato.id`, Clave Foránea, Obligatorio)
  - `nombre_archivo` (Cadena de caracteres, Obligatorio)
  - `ruta_archivo` (Cadena de caracteres, Obligatorio)
  - `tipo_archivo` (Cadena de caracteres, ej. "pdf", "imagen")
  - `secuencia` (Entero, para ordenar las fotos de las páginas, por defecto 1)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El sistema detecta y previene el 100% de los intentos de solapamiento de fechas de contratos antes de persistir los datos.
- **SC-002**: Un administrador puede adjuntar y guardar un PDF de contrato en menos de 1 minuto desde el panel de gestión.
- **SC-003**: Todas las vistas de carga y visualización de documentos de contrato cumplen con el contraste WCAG AA.
- **SC-004**: Los archivos adjuntos de tipo imagen se muestran en una galería de visualización lineal de un solo toque (adelante/atrás) optimizada para la lectura sin esfuerzo.

## Assumptions

- **A-001**: Los archivos subidos se almacenarán de forma local en el servidor o mediante el sistema de archivos configurado en Laravel (Storage) de forma segura y no pública directamente.
- **A-002**: Un contrato firmado notarialmente digitalizado validará en el servidor el cumplimiento de límites de peso (15MB por PDF, 5MB por foto), mostrando advertencias legibles si se excede.
- **A-003**: Se asume que los contratos solo se pueden asociar a locaciones que tengan habilitado el marcador `es_alquilable = true`. El sistema impedirá asociar un contrato a una locación no alquilable.
