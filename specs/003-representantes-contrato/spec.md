# Feature Specification: Inquilinos de Contrato (Inquilino Principal)

**Feature Branch**: `003-representantes-contrato`

**Created**: 2026-08-19

**Updated**: 2026-08-23

**Status**: Ready

**Input**: User description original: "cada contrato debe tener un usuario representante el mismo tendra sus caracteristicas como apellidos y nombres, dni, fecha nacimiento podria haber mas de un representante pero por lo menos uno"

**Corrección del usuario (2026-08-23)**: "Cuando me referia a representante era el inquilino, no hace falta tenerlo por separado el inquilino es el representante principal podrian haber mas inquilinos para un mismo local pero debe haber uno principal a eso me referia"

**Nota de alcance**: Esta corrección reemplaza el concepto de "Representante" como entidad separada del contrato. No existe un rol de "representante legal" distinto del inquilino: **el inquilino es, en sí mismo, el representante del contrato**. Un contrato (local) puede tener varios inquilinos, pero exactamente uno debe estar marcado como **Inquilino Principal**. Todos los requisitos, entidades y criterios de esta especificación se reinterpretan bajo esta unificación; no se debe mantener un catálogo o tabla separada de "Representante".

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Asignación Obligatoria de al Menos un Inquilino al Contrato (Priority: P1)

Como Administrador, quiero asociar al menos un inquilino a un contrato de alquiler, ingresando sus datos personales (apellidos, nombres, DNI y fecha de nacimiento), de modo que el sistema me impida guardar el contrato si no tiene ningún inquilino asignado.

**Why this priority**: Es la regla de negocio mandatoria y restrictiva más importante. Garantiza que todo contrato cuente con al menos una persona identificable y responsable en todo momento.

**Independent Test**: Se puede verificar intentando guardar un contrato nuevo sin haber seleccionado ni registrado ningún inquilino, comprobando que el sistema muestre un error de validación persistente e impida guardar los datos.

**Acceptance Scenarios**:

1. **Given** el formulario de creación de contrato, **When** el administrador ingresa todos los datos del contrato pero deja la sección de inquilinos vacía e intenta guardar, **Then** el sistema bloquea el registro y muestra un mensaje persistente y de alto contraste: "Debe asociar por lo menos un inquilino al contrato antes de guardar".
2. **Given** el formulario de creación de contrato, **When** el administrador agrega un inquilino con apellidos "Pérez Gómez", nombres "Juan Carlos", DNI "12345678" y fecha de nacimiento "1960-05-15", **Then** el sistema permite guardar el contrato exitosamente y marca automáticamente a este inquilino como Principal si es el único.

---

### User Story 2 - Soporte de Múltiples Inquilinos con Gestión Accesible (Priority: P2)

Como Administrador, quiero agregar múltiples inquilinos a un mismo contrato (local) mediante controles simples y de alta visibilidad, visualizando el listado de personas asociadas con facilidad para editar o remover inquilinos con confirmación explícita.

**Why this priority**: Permite registrar estructuras de alquiler compartidas (por ejemplo, co-inquilinos o grupos familiares) manteniendo una experiencia de usuario sencilla e intuitiva.

**Independent Test**: Se puede verificar accediendo a un contrato, agregando secuencialmente dos inquilinos diferentes, y luego removiendo uno de ellos mediante el botón de eliminación, comprobando que se solicite confirmación de alta visibilidad y que los datos en base de datos se sincronicen correctamente.

**Acceptance Scenarios**:

1. **Given** un contrato con un inquilino ya asignado (Principal), **When** el administrador presiona el botón "Agregar Otro Inquilino", **Then** se abre un formulario secundario simple para registrar los datos del nuevo integrante, sin alterar la designación de Principal existente.
2. **Given** un contrato con dos inquilinos asignados, **When** el administrador presiona el botón "Quitar Inquilino" de uno de ellos (no Principal), **Then** el sistema muestra un modal de confirmación con opciones claras ("Sí, quitar inquilino" vs "No, cancelar") y, tras aceptar, remueve al inquilino de la vista y del registro del contrato de forma atómica.

---

### User Story 3 - Validación de Datos Personales del Inquilino (Priority: P3)

Como Administrador, quiero que el sistema valide que los datos ingresados para el inquilino sean correctos y legibles (DNI numérico de longitud válida, fecha de nacimiento lógica y apellidos/nombres no vacíos), para evitar errores de digitación en el documento contractual.

**Why this priority**: Evita la persistencia de datos inconsistentes, incompletos o erróneos que invalidarían el valor legal y de auditoría del contrato.

**Independent Test**: Se puede validar intentando ingresar un DNI con formato incorrecto o una fecha de nacimiento que indique que la persona es menor de edad, y comprobando que el sistema detenga el flujo y resalte los campos con errores de manera persistente.

**Acceptance Scenarios**:

1. **Given** el formulario de registro de inquilino, **When** se ingresa un DNI con formato incorrecto o una fecha de nacimiento que indica que el inquilino es menor de edad, **Then** el sistema muestra mensajes de error descriptivos y con colores de alto contraste ("El DNI debe tener formato válido", "El inquilino debe ser mayor de edad").

### Edge Cases

- **Reutilización de Inquilinos**: ¿Se deben buscar y seleccionar inquilinos desde un catálogo global o cada contrato registra inquilinos de forma aislada y única? El sistema implementa un **Directorio Reutilizable (Catálogo Global)**. Se mantendrá un registro centralizado de inquilinos en la base de datos PostgreSQL. Al crear o editar un contrato, el administrador puede buscar a un inquilino existente por DNI o apellidos para asociarlo de inmediato, o registrar uno nuevo que se añadirá automáticamente al directorio para futuras consultas, previniendo duplicidades de datos.
- **Designación de Inquilino Principal**: Si un contrato tiene múltiples inquilinos, el sistema exige que exactamente uno de ellos sea marcado como el "Inquilino Principal" (para ser el contacto primario para avisos de cobranzas y notificaciones automáticas, y para figurar como titular del local). Al guardar el contrato, el sistema validará que exista un (y solo un) inquilino principal asignado.
- **Eliminación del Último Inquilino**: Si se intenta editar un contrato y remover a su único inquilino, el sistema debe bloquear esta acción automáticamente para evitar infringir la regla de "por lo menos uno".
- **Eliminación del Inquilino Principal cuando hay otros**: Si se intenta remover al inquilino marcado como Principal mientras existen otros inquilinos en el contrato, el sistema debe exigir que se designe primero a un nuevo Principal entre los restantes antes de permitir la remoción.
- **Migración de datos existentes**: El sistema ya contaba con un modelo simple de "Inquilino" (con solo el campo `nombre`, un inquilino por contrato) proveniente de una feature anterior, y con una entidad "Representante" separada (con apellidos, nombres, DNI, fecha de nacimiento) introducida por esta misma feature antes de la corrección. Ambos conceptos se unifican en una única entidad **Inquilino** con los campos completos; los datos existentes deben conservarse y consolidarse sin pérdida de información al eliminar la duplicidad.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST permitir asociar uno o múltiples inquilinos a cada contrato de alquiler.
- **FR-002**: El sistema MUST requerir para cada inquilino los siguientes datos obligatorios: apellidos (texto), nombres (texto), DNI (texto/numérico) y fecha de nacimiento (fecha).
- **FR-003**: El sistema MUST validar que el contrato posea al menos un inquilino asociado antes de permitir guardar el registro o cambiar su estado a "Activo". En caso de múltiples inquilinos, el sistema MUST requerir que exactamente uno de ellos esté marcado como "Principal" (es_principal = true).
- **FR-004**: El sistema MUST impedir la remoción de un inquilino si este es el único asociado al contrato (garantizando el límite mínimo de uno).
- **FR-005**: La interfaz de usuario para buscar, agregar o quitar inquilinos MUST ofrecer diálogos de confirmación explícitos para cualquier eliminación.
- **FR-006**: El registro de contratos con sus inquilinos asociados MUST persistirse de manera transaccional y atómica (`DB::transaction`) en la base de datos PostgreSQL para asegurar la consistencia relacional (Principio V de la Constitución).
- **FR-007**: El sistema MUST permitir la búsqueda y selección de inquilinos desde un directorio global unificado utilizando su DNI o apellidos como criterio de búsqueda.
- **FR-008**: El sistema MUST unificar el concepto de "Representante" e "Inquilino" en una única entidad; no se debe mantener una tabla o catálogo separado de representantes distinto al de inquilinos.
- **FR-009**: El sistema MUST impedir la remoción del inquilino marcado como Principal mientras existan otros inquilinos en el contrato, salvo que se designe simultáneamente a un nuevo Principal.

### Key Entities *(include if feature involves data)*

- **Inquilino**: Representa a la persona natural que arrienda un local y que actúa, a su vez, como representante del contrato de alquiler.
  - `id` (Entero, Auto-incremental, Clave Primaria)
  - `apellidos` (Cadena de caracteres, Obligatorio)
  - `nombres` (Cadena de caracteres, Obligatorio)
  - `dni` (Cadena de caracteres, Único en el catálogo reutilizable, Obligatorio)
  - `fecha_nacimiento` (Fecha, Obligatorio)
  - `timestamps` (Marcas de tiempo de creación y modificación)
- **ContratoInquilino** (Tabla Pivote / Relación): Vincula un contrato con sus múltiples inquilinos.
  - `contrato_id` (Entero, Referencia a `Contrato.id`, Clave Foránea, Obligatorio)
  - `inquilino_id` (Entero, Referencia a `Inquilino.id`, Clave Foránea, Obligatorio)
  - `es_principal` (Booleano, por defecto falso; exactamente uno en `true` por contrato)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El sistema bloquea el 100% de los intentos de crear o editar un contrato que no cuente con al menos un inquilino asignado y exactamente uno de ellos designado como Principal.
- **SC-002**: Un administrador adulto mayor puede buscar un inquilino existente, asociarlo al contrato y marcarlo como Principal en menos de 2 minutos utilizando la interfaz adaptada.
- **SC-003**: Todas las vistas de visualización de inquilinos cumplen con el contraste mínimo WCAG AA (4.5:1 para texto normal, 3:1 para componentes interactivos grandes).
- **SC-004**: Los diálogos de confirmación para eliminar inquilinos o deshacer cambios usan botones y texto claramente diferenciados ("Sí, quitar inquilino" vs "No, cancelar") para prevenir errores accidentales.

## Assumptions

- **A-001**: Todos los inquilinos registrados deben ser mayores de edad legalmente (ej. 18 años o más), lo cual será validado al registrar la fecha de nacimiento.
- **A-002**: Los datos de nombres y apellidos de los inquilinos se registrarán tal como aparecen en su documento oficial de identidad (DNI).
- **A-003**: La relación entre Contratos e Inquilinos se almacena en una tabla relacional pivote en PostgreSQL, asegurando la flexibilidad de relaciones muchos-a-muchos.
- **A-004**: "Local" e "Inquilino Principal del contrato" se usan como sinónimos de "locación arrendada" y "representante del contrato" respectivamente, según la terminología del usuario.
- **A-005**: La entidad "Representante" y el modelo simplificado previo de "Inquilino" (solo con campo `nombre`) quedan obsoletos y deben consolidarse en la entidad Inquilino unificada descrita aquí; esta consolidación de datos e implementación se detallará en la etapa de planificación técnica (`/speckit.plan`).
