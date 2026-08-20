# Feature Specification: Representantes de Contrato

**Feature Branch**: `003-representantes-contrato`

**Created**: 2026-08-19

**Status**: Ready

**Input**: User description: "cada contrato debe tener un usuario representante el mismo tendra sus caracteristicas como apellidos ynombres, dni , fecha nacimiento podria haber mas de un representante pero por lo menos uno"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Asignación Obligatoria de Representante al Crear Contrato (Priority: P1)

Como Administrador, quiero asociar al menos un representante legal al registrar un contrato de alquiler, ingresando sus datos personales (apellidos, nombres, DNI y fecha de nacimiento), de modo que el sistema me impida guardar el contrato si no tiene ningún representante asignado.

**Why this priority**: Es la regla de negocio mandatoria y restrictiva más importante. Garantiza que todo contrato cuente con un respaldo jurídico identificable (mínimo una persona responsable) en todo momento.

**Independent Test**: Se puede verificar intentando guardar un contrato nuevo sin haber seleccionado ni registrado ningún representante, comprobando que el sistema muestre un error de validación persistente e impida guardar los datos.

**Acceptance Scenarios**:

1. **Given** el formulario de creación de contrato, **When** el administrador ingresa todos los datos del contrato pero deja la sección de representantes vacía e intenta guardar, **Then** el sistema bloquea el registro y muestra un mensaje en alto contraste con tamaño de fuente de 18px: "Debe asociar por lo menos un representante al contrato antes de guardar".
2. **Given** el formulario de creación de contrato, **When** el administrador agrega un representante con apellidos "Pérez Gómez", nombres "Juan Carlos", DNI "12345678" y fecha de nacimiento "1960-05-15", **Then** el sistema permite guardar el contrato exitosamente.

---

### User Story 2 - Soporte de Múltiples Representantes con Gestión Accesible (Priority: P2)

Como Administrador (incluyendo Adultos Mayores), quiero agregar múltiples representantes a un contrato de alquiler mediante controles táctiles simples y de alta visibilidad, visualizando el listado de personas asociadas con facilidad para editar o remover representantes con confirmación explícita.

**Why this priority**: Permite registrar estructuras contractuales complejas (por ejemplo, co-arrendatarios, avales o múltiples firmantes corporativos) manteniendo una experiencia de usuario sumamente sencilla y accesible para personas de la tercera edad.

**Independent Test**: Se puede verificar accediendo a un contrato, agregando secuencialmente dos representantes diferentes, y luego removiendo uno de ellos mediante el botón de eliminación, comprobando que se solicite confirmación de alta visibilidad y que los datos en base de datos se sincronicen correctamente.

**Acceptance Scenarios**:

1. **Given** un contrato con un representante ya asignado, **When** el administrador presiona el botón "Agregar Otro Representante" (mínimo 48x48px de área táctil), **Then** se abre un formulario secundario simple para registrar los datos del nuevo integrante.
2. **Given** un contrato con dos representantes asignados, **When** el administrador presiona el botón "Quitar Representante" de uno de ellos, **Then** el sistema muestra un modal de confirmación Senior-First con opciones grandes ("Sí, quitar representante" vs "No, cancelar") y, tras aceptar, remueve al representante de la vista y del registro del contrato de forma atómica.

---

### User Story 3 - Validación de Datos Personales del Representante (Priority: P3)

Como Administrador, quiero que el sistema valide que los datos ingresados para el representante sean correctos y legibles (DNI numérico de longitud válida, fecha de nacimiento lógica y apellidos/nombres no vacíos), para evitar errores de digitación en el documento contractual.

**Why this priority**: Evita la persistencia de datos inconsistentes, incompletos o erróneos que invalidarían el valor legal y de auditoría del contrato.

**Independent Test**: Se puede validar intentando ingresar un DNI con formato incorrecto o una fecha de nacimiento que indique que la persona es menor de edad, y comprobando que el sistema detenga el flujo y resalte los campos con errores de manera persistente.

**Acceptance Scenarios**:

1. **Given** el formulario de registro de representante, **When** se ingresa un DNI con formato incorrecto o una fecha de nacimiento que indica que el representante es menor de edad, **Then** el sistema muestra mensajes de error descriptivos y con colores de alto contraste ("El DNI debe tener formato válido", "El representante debe ser mayor de edad").

### Edge Cases

- **Reutilización de Representantes**: ¿Se deben buscar y seleccionar representantes desde un catálogo global o cada contrato registra representantes de forma aislada y única? El sistema implementa un **Directorio Reutilizable (Catálogo Global)**. Se mantendrá un registro centralizado de representantes en la base de datos PostgreSQL. Al crear o editar un contrato, el administrador puede buscar a un representante existente por DNI o apellidos para asociarlo de inmediato, o registrar uno nuevo que se añadirá automáticamente al directorio para futuras consultas, previniendo duplicidades de datos.
- **Designación de Representante Principal**: Si un contrato tiene múltiples representantes, ¿es necesario designar a uno como "Principal" para fines de contacto o correspondencia, o todos se tratan por igual? Se define la **Designación de Representante Principal Obligatoria**. Si un contrato posee múltiples representantes asociados, el sistema exige que exactamente uno de ellos sea marcado como el "Representante Principal" (para ser el contacto primario para avisos de cobranzas y notificaciones automáticas). Al guardar el contrato, el sistema validará que exista un (y solo un) representante principal asignado.
- **Eliminación del Último Representante**: Si se intenta editar un contrato y remover a su único representante, el sistema debe bloquear esta acción automáticamente para evitar infringir la regla de "por lo menos uno".

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST permitir asociar uno o múltiples representantes legales a cada contrato de alquiler.
- **FR-002**: El sistema MUST requerir para cada representante los siguientes datos obligatorios: apellidos (texto), nombres (texto), DNI (texto/numérico) y fecha de nacimiento (fecha).
- **FR-003**: El sistema MUST validar que el contrato posea al menos un representante asociado antes de permitir guardar el registro o cambiar su estado a "Activo". En caso de múltiples representantes, el sistema MUST requerir que exactamente uno de ellos esté marcado como "Principal" (es_principal = true).
- **FR-004**: El sistema MUST impedir la remoción de un representante si este es el único asociado al contrato (garantizando el límite mínimo de uno).
- **FR-005**: La interfaz de usuario para buscar, agregar o quitar representantes MUST utilizar tipografía base de mínimo 18px, botones de tamaño táctil mínimo de 48x48px, y diálogos de confirmación explícitos para cualquier eliminación (Senior-First).
- **FR-006**: El registro de contratos con sus representantes asociados MUST persistirse de manera transaccional y atómica (`DB::transaction`) en la base de datos PostgreSQL para asegurar la consistencia relacional (Principio V de la Constitución).
- **FR-007**: El sistema MUST permitir la búsqueda y selección de representantes desde un directorio global unificado utilizando su DNI o apellidos como criterio de búsqueda (Senior-First UI con campos grandes y legibles).

### Key Entities *(include if feature involves data)*

- **Representante**: Representa a la persona natural que actúa como representante o firmante en un contrato de alquiler.
  - `id` (Entero, Auto-incremental, Clave Primaria)
  - `apellidos` (Cadena de caracteres, Obligatorio)
  - `nombres` (Cadena de caracteres, Obligatorio)
  - `dni` (Cadena de caracteres, Único en caso de catálogo reutilizable, Obligatorio)
  - `fecha_nacimiento` (Fecha, Obligatorio)
  - `timestamps` (Marcas de tiempo de creación y modificación)
- **ContratoRepresentante** (Tabla Pivote / Relación): Vincula un contrato con sus múltiples representantes.
  - `contrato_id` (Entero, Referencia a `Contrato.id`, Clave Foránea, Obligatorio)
  - `representante_id` (Entero, Referencia a `Representante.id`, Clave Foránea, Obligatorio)
  - `es_principal` (Booleano, por defecto falso)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El sistema bloquea el 100% de los intentos de crear o editar un contrato que no cuente con al menos un representante asignado y exactamente uno de ellos designado como Principal.
- **SC-002**: Un administrador adulto mayor puede buscar un representante existente, asociarlo al contrato y marcarlo como Principal en menos de 2 minutos utilizando la interfaz adaptada.
- **SC-003**: Todas las vistas de visualización de representantes cumplen con el contraste mínimo de WCAG AAA/AA (mínimo 4.5:1 para texto base de 18px).
- **SC-004**: Los diálogos de confirmación para eliminar representantes o deshacer cambios tienen botones explícitos de mínimo 48x48px y texto claro para prevenir errores accidentales.

## Assumptions

- **A-001**: Todos los representantes registrados deben ser mayores de edad legalmente (ej. 18 años o más), lo cual será validado al registrar la fecha de nacimiento.
- **A-002**: Los datos de nombres y apellidos de los representantes se registrarán tal como aparecen en su documento oficial de identidad (DNI).
- **A-003**: La relación entre Contratos y Representantes se almacena en una tabla relacional pivote en PostgreSQL, asegurando la flexibilidad de relaciones muchos-a-many.
