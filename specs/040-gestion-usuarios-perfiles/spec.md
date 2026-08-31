# Feature Specification: Gestión de Usuarios por Perfiles

**Feature Branch**: `040-gestion-usuarios-perfiles`

**Created**: 2026-08-27

**Status**: Draft

**Input**: User description: "Agrega la gestion de usuarios por perfiles, el master puede agregar nuevos usuarios y el administrador todo menos agregar usuarios"

## Clarifications

### Session 2026-08-27

- Q: ¿Qué alcance tiene el Administrador sobre el mantenimiento de usuarios (editar, restablecer contraseña, cambiar perfil, desactivar, eliminar)? → A: Sólo el Master tiene acceso al CRUD de usuarios. El Administrador no accede a la sección de gestión de usuarios en absoluto (ni siquiera para consultar el listado); toda alta, consulta, edición, restablecimiento de contraseña, cambio de perfil y cambio de estado de cuenta es exclusiva del Master.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - El Master da de alta a un nuevo usuario (Priority: P1)

Una persona con perfil **Master** necesita habilitar el acceso al sistema para un colaborador nuevo. Entra a la sección de gestión de usuarios, elige "Agregar usuario", completa nombre, correo electrónico, perfil (Master o Administrador) y una contraseña inicial, y guarda. El colaborador queda habilitado para iniciar sesión de inmediato con esas credenciales.

**Why this priority**: Es la razón de ser de la funcionalidad. Sin la capacidad de que el Master cree cuentas, no hay forma controlada de incorporar personas al sistema y la gestión por perfiles no aporta valor.

**Independent Test**: Se puede probar por completo iniciando sesión como Master, creando un usuario nuevo con perfil Administrador, cerrando sesión e iniciando sesión con la cuenta recién creada. Entrega valor porque habilita el alta controlada de personal.

**Acceptance Scenarios**:

1. **Given** una sesión iniciada con perfil Master, **When** el Master abre la sección de gestión de usuarios, **Then** ve el listado de usuarios existentes y una acción visible para agregar un usuario nuevo.
2. **Given** el formulario de alta de usuario abierto por un Master, **When** completa nombre, un correo electrónico no registrado previamente, un perfil válido y una contraseña que cumple la política mínima, **Then** el sistema crea el usuario, lo muestra en el listado y confirma la operación con un mensaje de éxito.
3. **Given** el formulario de alta de usuario abierto por un Master, **When** ingresa un correo electrónico que ya pertenece a otro usuario, **Then** el sistema rechaza el alta y muestra un mensaje indicando que el correo ya está en uso, sin crear el usuario.
4. **Given** el formulario de alta de usuario abierto por un Master, **When** deja vacío un campo obligatorio o ingresa un correo con formato inválido, **Then** el sistema no crea el usuario y muestra los errores de validación junto a cada campo.
5. **Given** un usuario recién creado por el Master, **When** esa persona inicia sesión por primera vez con la contraseña inicial, **Then** accede al sistema con los permisos correspondientes a su perfil.

---

### User Story 2 - El Administrador opera el sistema sin acceso a la gestión de usuarios (Priority: P1)

Una persona con perfil **Administrador** usa el sistema con acceso completo a la operación del negocio (locaciones, contratos, inquilinos, recibos, lecturas, pagos, conceptos de gasto fijo, configuración general), pero la sección de gestión de usuarios no existe para ella: no aparece en la navegación y cualquier intento directo de acceder a cualquiera de sus funciones (listar, crear, editar, restablecer contraseña, cambiar perfil, desactivar) es bloqueado.

**Why this priority**: Es la otra mitad de la regla de negocio solicitada. Define el límite del perfil Administrador y garantiza que toda la administración de cuentas quede reservada al Master. Sin esta restricción, los perfiles serían equivalentes.

**Independent Test**: Se puede probar iniciando sesión como Administrador y verificando (a) que todas las secciones operativas del sistema funcionan con normalidad y (b) que ninguna función de gestión de usuarios está visible ni accesible. Entrega valor porque materializa la separación de responsabilidades.

**Acceptance Scenarios**:

1. **Given** una sesión iniciada con perfil Administrador, **When** navega por las secciones operativas del sistema, **Then** puede consultar y modificar la información del negocio igual que hoy, sin pérdida de funcionalidad respecto al comportamiento previo.
2. **Given** una sesión iniciada con perfil Administrador, **When** revisa la navegación de la aplicación, **Then** no se muestra ninguna entrada ni acción que lleve a la sección de gestión de usuarios.
3. **Given** una sesión iniciada con perfil Administrador, **When** intenta acceder directamente a cualquier función de gestión de usuarios (listado, alta, edición, restablecimiento de contraseña, cambio de perfil o cambio de estado) reutilizando un enlace o dirección conocida, **Then** el sistema rechaza la solicitud con un aviso de permiso insuficiente y no se produce ningún cambio.

---

### User Story 3 - Mantenimiento de cuentas existentes (Priority: P2)

El **Master** necesita mantener actualizadas las cuentas: corregir el nombre o el correo de un usuario, restablecer su contraseña cuando la olvida, cambiar su perfil, o desactivar el acceso de alguien que deja de colaborar (sin borrar su historial). El listado de usuarios, accesible sólo para el Master, permite realizar estas acciones sobre cada cuenta.

**Why this priority**: Complementa el alta con el ciclo de vida completo de la cuenta. Es necesario para operar de forma sostenible, pero el sistema ya aporta valor con las historias P1 aunque el mantenimiento llegue después.

**Independent Test**: Se puede probar, con sesión de Master, editando un usuario existente, restableciendo su contraseña, desactivándolo, verificando que ya no puede iniciar sesión, reactivándolo y confirmando que vuelve a acceder.

**Acceptance Scenarios**:

1. **Given** el listado de usuarios abierto por un Master, **When** edita el nombre o el correo de un usuario con datos válidos, **Then** el sistema guarda los cambios y los refleja en el listado.
2. **Given** el listado de usuarios abierto por un Master, **When** restablece la contraseña de un usuario, **Then** el sistema aplica la nueva contraseña y el usuario puede iniciar sesión con ella.
3. **Given** el listado de usuarios abierto por un Master, **When** desactiva una cuenta, **Then** esa cuenta no puede iniciar sesión y aparece marcada como inactiva, conservando su información y su historial asociado.
4. **Given** una cuenta desactivada, **When** un Master la reactiva, **Then** la cuenta vuelve a poder iniciar sesión.
5. **Given** el sistema con un único usuario Master activo, **When** se intenta desactivar, eliminar o cambiar de perfil a ese último Master, **Then** el sistema rechaza la operación e informa que debe existir al menos un Master activo.

---

### Edge Cases

- **Último Master**: el sistema debe impedir que la última cuenta Master activa quede sin poder crear usuarios (desactivación, eliminación o cambio de perfil del último Master bloqueados).
- **Autodesactivación / autodegradación**: una persona no puede desactivar su propia cuenta ni quitarse a sí misma el perfil Master mientras sea el único Master.
- **Correo duplicado por diferencias de mayúsculas/espacios**: `Juan@Correo.com ` y `juan@correo.com` deben tratarse como el mismo correo y no permitir duplicados.
- **Sesión activa de un usuario recién desactivado**: si una cuenta se desactiva mientras esa persona tiene una sesión abierta, deja de tener acceso a las secciones protegidas en su siguiente acción.
- **Cambio de perfil de un usuario con sesión abierta**: los nuevos permisos aplican, a más tardar, en su siguiente inicio de sesión.
- **Contraseña inicial débil**: el alta rechaza contraseñas que no cumplen la longitud mínima de la política.
- **Usuarios preexistentes al implementar la funcionalidad**: las cuentas que ya existían deben quedar con un perfil asignado y no perder el acceso.
- **Acceso directo por dirección conocida**: un Administrador que conoce la ruta de cualquier función de gestión de usuarios (listado, alta, edición, cambio de perfil, cambio de estado) no debe poder ejecutarla llamándola directamente.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST asociar a cada usuario exactamente un perfil, con dos valores posibles: **Master** y **Administrador**.
- **FR-002**: El sistema MUST permitir que un usuario con perfil Master cree nuevos usuarios, indicando al menos nombre, correo electrónico, perfil y contraseña inicial.
- **FR-003**: El sistema MUST impedir que un usuario con perfil Administrador acceda a cualquier función de gestión de usuarios (listar, crear, editar, restablecer contraseña, cambiar perfil, desactivar/reactivar, eliminar), tanto ocultando toda entrada a esa sección en la interfaz como rechazando cualquier solicitud directa a esas funciones con un aviso de permiso insuficiente.
- **FR-004**: El sistema MUST conceder a los perfiles Master y Administrador acceso completo a todas las funciones operativas del negocio existentes antes de esta funcionalidad (locaciones, contratos, inquilinos, recibos, lecturas de medidor, pagos, conceptos de gasto fijo, configuración general), sin introducir restricciones nuevas sobre ellas.
- **FR-005**: El sistema MUST restringir el acceso a la sección de gestión de usuarios (todas sus vistas y acciones) exclusivamente a usuarios autenticados con perfil Master, y negarlo a los usuarios con perfil Administrador y a cualquier visitante no autenticado.
- **FR-006**: El sistema MUST mostrar al Master un listado de todos los usuarios con, como mínimo, su nombre, su correo electrónico, su perfil y si la cuenta está activa o inactiva.
- **FR-007**: El sistema MUST validar, al crear o editar un usuario, que el correo electrónico tenga formato válido y sea único entre todos los usuarios, comparándolo sin distinguir mayúsculas de minúsculas y sin espacios sobrantes.
- **FR-008**: El sistema MUST exigir que toda contraseña asignada a un usuario (en alta o restablecimiento) cumpla una política mínima de al menos 8 caracteres.
- **FR-009**: El sistema MUST almacenar las contraseñas de forma protegida (no recuperables en texto claro) y nunca mostrarlas en pantalla ni en el listado.
- **FR-010**: El sistema MUST permitir a un Master editar el nombre y el correo electrónico de un usuario existente.
- **FR-011**: El sistema MUST permitir a un Master restablecer la contraseña de un usuario existente.
- **FR-012**: El sistema MUST permitir a un Master cambiar el perfil de un usuario existente entre Master y Administrador. Ningún usuario con perfil Administrador puede cambiar perfiles, ni el propio ni el de otros.
- **FR-013**: El sistema MUST permitir a un Master desactivar y reactivar una cuenta de usuario; una cuenta desactivada no puede iniciar sesión ni acceder a secciones protegidas, pero conserva toda su información y su historial asociado.
- **FR-014**: El sistema MUST impedir desactivar, eliminar o cambiar de perfil a la última cuenta Master activa, informando que debe existir siempre al menos un Master activo.
- **FR-015**: El sistema MUST impedir que un Master desactive su propia cuenta o se quite a sí mismo el perfil Master cuando es el único Master activo.
- **FR-016**: El sistema MUST asignar un perfil a todas las cuentas de usuario que existan al momento de implementar la funcionalidad, de modo que ninguna quede sin perfil ni pierda el acceso; al menos una de ellas MUST quedar con perfil Master.
- **FR-017**: El sistema MUST dejar de ofrecer el auto-registro público de cuentas: la única vía de creación de usuarios es a través de un Master dentro de la sección de gestión de usuarios.
- **FR-018**: El sistema MUST registrar en el historial de eventos de seguridad las altas, cambios de perfil, restablecimientos de contraseña, desactivaciones y reactivaciones de usuarios, indicando quién realizó la acción y sobre qué cuenta.
- **FR-019**: El sistema MUST confirmar mediante un paso explícito (pantalla o diálogo de confirmación con lenguaje claro) toda acción destructiva o de alto impacto que un Master ejecute sobre una cuenta: desactivación, eliminación y cambio de perfil.
- **FR-020**: El sistema MUST presentar mensajes de éxito y de error claros y persistentes tras cada operación de gestión de usuarios.
- **FR-021**: El sistema MUST mostrar la entrada de navegación hacia la sección de gestión de usuarios únicamente a los usuarios con perfil Master.

### Key Entities *(include if feature involves data)*

- **Usuario**: persona con acceso al sistema. Atributos relevantes: nombre, correo electrónico (único), contraseña protegida, perfil asignado, estado (activa / inactiva), fecha de alta. Un usuario tiene exactamente un perfil.
- **Perfil**: nivel de acceso de un usuario. Conjunto cerrado de dos valores: **Master** (acceso operativo completo más acceso exclusivo a todo el CRUD de usuarios) y **Administrador** (acceso operativo completo, sin ningún acceso a la sección de gestión de usuarios). No se crean perfiles nuevos desde la interfaz.
- **Evento de seguridad de usuarios**: registro de auditoría de una acción sobre una cuenta (alta, cambio de perfil, restablecimiento de contraseña, desactivación, reactivación). Atributos: quién la ejecutó, cuenta afectada, tipo de acción, momento en que ocurrió.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un Master puede dar de alta a un usuario nuevo y dejarlo operativo (capaz de iniciar sesión) en menos de 2 minutos, sin ayuda de soporte técnico.
- **SC-002**: El 100% de los intentos de un Administrador de acceder a cualquier función de gestión de usuarios —listar, crear, editar, restablecer contraseña, cambiar perfil o cambiar estado, por interfaz o por acceso directo a la dirección— son bloqueados.
- **SC-003**: Tras implementar la funcionalidad, el 100% de las cuentas de usuario existentes conservan su acceso y tienen un perfil asignado.
- **SC-004**: El 100% de las cuentas desactivadas quedan impedidas de iniciar sesión en el intento inmediatamente posterior a la desactivación.
- **SC-005**: En ningún escenario el sistema queda sin al menos una cuenta Master activa.
- **SC-006**: El 100% de las altas, cambios de perfil, restablecimientos de contraseña y cambios de estado de cuenta quedan registrados en el historial de eventos de seguridad con su autor y la cuenta afectada.
- **SC-007**: Ningún flujo de la operación del negocio existente pierde funcionalidad para los perfiles Master y Administrador respecto al comportamiento previo a esta funcionalidad.

## Assumptions

- El sistema ya cuenta con autenticación por correo electrónico y contraseña, y con infraestructura de restablecimiento de contraseña; esta funcionalidad se apoya en ella en lugar de reemplazarla.
- Sólo se requieren dos perfiles (Master y Administrador). No se pide un sistema de permisos granular ni la creación de perfiles adicionales desde la interfaz.
- "Todo menos agregar usuarios" se interpreta, según la clarificación del 2026-08-27, como que el Administrador tiene acceso operativo completo al negocio pero ningún acceso a la sección de gestión de usuarios: todo el CRUD de usuarios (listar, crear, editar, restablecer contraseña, cambiar perfil, desactivar/reactivar, eliminar) es exclusivo del Master.
- La contraseña inicial de un usuario nuevo la define el Master en el momento del alta; no se requiere un flujo de invitación por correo con enlace de activación para la primera versión.
- La política mínima de contraseña es de 8 caracteres, alineada con la práctica estándar del stack actual; no se piden reglas de complejidad adicionales.
- La verificación de correo electrónico no es un requisito para que un usuario creado por el Master pueda iniciar sesión.
- Las cuentas se desactivan en lugar de eliminarse de forma permanente como mecanismo por defecto para retirar el acceso, de modo de preservar la trazabilidad del historial; si se permite la eliminación definitiva, queda sujeta a las mismas salvaguardas del último Master (FR-014).
- Al implementar la funcionalidad, las cuentas preexistentes se consideran Master (por tratarse de las cuentas fundadoras del sistema), salvo indicación distinta durante la planificación.
- El número de usuarios del sistema es pequeño (decenas como máximo), por lo que el listado no requiere paginación ni búsqueda avanzada en su primera versión.
- La interfaz de gestión de usuarios se integra en la navegación autenticada existente y sigue el sistema de componentes visuales vigente del proyecto.
