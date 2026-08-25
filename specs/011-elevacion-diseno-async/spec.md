# Feature Specification: Elevación de Diseño, Login como Entrada y Escritura Asíncrona

**Feature Branch**: `011-elevacion-diseno-async`

**Created**: 2026-08-21

**Status**: Draft

**Input**: User description: "Elevar el diseño visual de la interfaz Bootstrap 5 ya migrada (spec 010) manteniendo intacto el nivel de accesibilidad ya vigente, mejorando jerarquía visual, espaciado, profundidad de tarjetas, consistencia de iconografía y una paleta con más carácter, sin cambiar ninguna regla de negocio. Además, el login debe ser la primera vista que ve un visitante no autenticado, en vez de la página de bienvenida decorativa actual. Finalmente, las interacciones de escritura (crear/editar/eliminar) deben enviarse de forma asíncrona (fetch/AJAX) con actualización parcial de la página en vez de una redirección de servidor completa, preservando exactamente las mismas reglas de negocio y siguiendo funcionando por recarga completa de página si JavaScript no está disponible."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Diseño Visual Elevado (Priority: P1)

Como Administrador, quiero que la interfaz se vea más cuidada y profesional (mejor jerarquía visual, espaciado, profundidad de tarjetas, iconografía consistente y una paleta de colores con más carácter), sin que eso sacrifique el nivel de accesibilidad ya vigente, para trabajar en un sistema que se sienta confiable y agradable sin perder legibilidad ni facilidad de uso.

**Why this priority**: Es la motivación original de esta especificación y el cambio de menor riesgo (presentación pura, sin tocar lógica de negocio ni arquitectura de peticiones); establece la base visual sobre la que se apoyan las siguientes dos historias.

**Independent Test**: Se puede verificar navegando cualquier pantalla ya migrada a Bootstrap 5 (locaciones, contratos, recibos, etc.) y comprobando visualmente que mantiene contraste ≥4.5:1, mientras exhibe una jerarquía visual, espaciado y paleta de colores perceptiblemente más elaborados que el estado actual (colores genéricos, tarjetas planas sin profundidad).

**Acceptance Scenarios**:

1. **Given** cualquier pantalla de la aplicación ya migrada a Bootstrap 5, **When** el Administrador la visualiza, **Then** las tarjetas, botones y encabezados muestran una jerarquía visual clara (tamaños, pesos y espaciados diferenciados) y una paleta de colores distintiva del proyecto, con contraste ≥4.5:1 en todo texto y componente interactivo.
2. **Given** una pantalla con múltiples secciones de información (ej. el detalle de un contrato con costos, garantía, representantes y documentos), **When** el Administrador la revisa, **Then** cada sección se distingue visualmente de las demás (mediante espaciado, bordes o profundidad de tarjeta) sin necesidad de leer los encabezados para saber dónde termina una y empieza otra.
3. **Given** un ícono usado para representar una acción o estado (ej. eliminar, editar, pagado, pendiente), **When** aparece en distintas pantallas del sistema, **Then** el mismo concepto usa siempre el mismo ícono y color, de forma consistente en toda la aplicación.

---

### User Story 2 - Login como Vista de Entrada (Priority: P2)

Como visitante sin sesión iniciada, quiero que al entrar al sistema (por la dirección raíz o por cualquier pantalla que requiera estar autenticado) se me muestre directamente la pantalla de inicio de sesión, en vez de una página de bienvenida genérica que no tiene ninguna función real en este sistema, para poder autenticarme de inmediato sin pasos intermedios innecesarios.

**Why this priority**: Es un ajuste de enrutamiento acotado y de bajo riesgo, independiente del rediseño visual (aunque se beneficia de aplicarse después, para que el login ya luzca con el diseño elevado de la Historia 1).

**Independent Test**: Se puede verificar cerrando sesión y accediendo a la dirección raíz del sistema, comprobando que se muestra la pantalla de login (no una página de bienvenida), y que cualquier otra dirección protegida a la que se intente acceder sin sesión también redirige al login.

**Acceptance Scenarios**:

1. **Given** un visitante sin sesión iniciada, **When** accede a la dirección raíz del sistema, **Then** se le presenta la pantalla de inicio de sesión.
2. **Given** un visitante sin sesión iniciada, **When** intenta acceder directamente a cualquier pantalla protegida (ej. el listado de locaciones), **Then** se le redirige a la pantalla de inicio de sesión, y tras autenticarse exitosamente es llevado a la pantalla que originalmente intentaba visitar.
3. **Given** un Administrador con sesión ya iniciada, **When** accede a la dirección raíz del sistema, **Then** se le lleva directamente a su panel principal, sin mostrarle la pantalla de login ni la página de bienvenida.

---

### User Story 3 - Escritura Asíncrona con Degradación Elegante (Priority: P3)

Como Administrador, quiero que al crear, editar o eliminar cualquier registro (locaciones, contratos, representantes, lecturas de medidor, recibos, configuración general, resoluciones de garantía) la página se actualice de inmediato sin una recarga completa perceptible, para que el sistema se sienta más ágil y fluido en el uso diario, sin perder ninguna de las validaciones o reglas de negocio ya vigentes, y sin dejar de funcionar (aunque con recarga completa, como hoy) si mi navegador no puede ejecutar JavaScript.

**Why this priority**: Es el cambio de mayor alcance (toca la forma en que se envían y responden todas las escrituras del sistema) y el que más se beneficia de apoyarse en una base visual ya estable (Historia 1) y en un punto de entrada ya correcto (Historia 2).

**Independent Test**: Se puede verificar creando, editando y eliminando un registro de cada tipo (por ejemplo, una locación y un contrato) con JavaScript habilitado, comprobando que la página se actualiza sin una recarga completa visible y que los mensajes de error/éxito aparecen igual de persistentes y legibles que hoy; y repitiendo la misma acción con JavaScript deshabilitado, comprobando que el formulario sigue guardando correctamente mediante una recarga completa de página, con el mismo resultado final.

**Acceptance Scenarios**:

1. **Given** el formulario de creación de un contrato con datos válidos, **When** el Administrador lo envía con JavaScript habilitado, **Then** el contrato se guarda y la pantalla se actualiza para reflejar el resultado sin una recarga completa de página perceptible.
2. **Given** un intento de crear un contrato que se solapa con uno existente, **When** el Administrador lo envía con JavaScript habilitado, **Then** el sistema muestra el mismo mensaje de error explícito y persistente que hoy, sin recargar la página completa, resaltando los campos correspondientes.
3. **Given** el mismo formulario de creación de contrato, **When** el Administrador lo envía con JavaScript deshabilitado o no disponible en su navegador, **Then** el sistema sigue procesando la solicitud mediante una recarga completa de página (como ocurre actualmente) y produce exactamente el mismo resultado final (guardado exitoso o mensaje de error), sin ninguna pérdida de funcionalidad.
4. **Given** la eliminación de un registro con confirmación explícita (ej. una locación sin sub-locaciones), **When** el Administrador confirma la eliminación con JavaScript habilitado, **Then** el registro desaparece de la vista sin una recarga completa de página.

### Edge Cases

- **Reglas de negocio idénticas independientemente del canal**: Toda regla de validación o de negocio ya implementada (solapamiento de contratos, mínimo un representante con exactamente uno Principal, cuadre exacto de montos de garantía, bloqueo de eliminación de locaciones con hijas, etc.) MUST comportarse de forma idéntica sea que la petición llegue de forma asíncrona o mediante el envío clásico de formulario — el canal de transporte nunca puede relajar ni endurecer una regla de negocio.
- **Pérdida de conexión a mitad de una escritura asíncrona**: Si la petición asíncrona falla por un problema de red (no por una regla de negocio), el sistema MUST informarlo con un mensaje explícito y persistente e invitar a reintentar, en vez de fallar silenciosamente o dejar el formulario en un estado ambiguo (ej. botón de guardar sin respuesta).
- **Doble envío accidental**: Mientras una escritura asíncrona está en curso, el sistema MUST evitar que un segundo clic sobre el mismo botón de guardar dispare un envío duplicado.
- **Enlaces directos a pantallas protegidas ya autenticado**: Si un Administrador con sesión ya iniciada visita la pantalla de login directamente (ej. por un enlace guardado), el sistema MUST llevarlo a su panel principal en vez de mostrarle el formulario de login nuevamente.
- **Usuarios con tecnología asistiva**: La degradación a recarga completa de página ante una falla de JavaScript no es solo una red de seguridad técnica: es un requisito de accesibilidad, ya que parte de la población de usuarios puede usar navegadores desactualizados o software asistivo que interfiera con JavaScript.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST mantener, en toda pantalla ya migrada a Bootstrap 5, un contraste ≥4.5:1 mientras se eleva su jerarquía visual, espaciado, profundidad de tarjetas y paleta de colores.
- **FR-002**: El sistema MUST usar iconografía consistente (mismo ícono y color para el mismo concepto de acción o estado) en todas las pantallas.
- **FR-003**: El sistema MUST redirigir a cualquier visitante sin sesión iniciada que acceda a la dirección raíz o a cualquier pantalla protegida hacia la pantalla de inicio de sesión.
- **FR-004**: El sistema MUST llevar a un Administrador con sesión iniciada a su panel principal si accede a la dirección raíz o a la pantalla de login directamente, sin mostrarle el formulario de login ni ninguna página de bienvenida intermedia.
- **FR-005**: El sistema MUST enviar de forma asíncrona las interacciones de creación, edición y eliminación de todos los registros de las features ya implementadas (locaciones, contratos, representantes, lecturas de medidor, recibos, configuración general, resoluciones de garantía), actualizando la pantalla sin una recarga completa de página perceptible cuando JavaScript esté disponible.
- **FR-006**: El sistema MUST preservar, para cada una de esas interacciones, exactamente las mismas reglas de validación y de negocio ya implementadas, sin ninguna diferencia de comportamiento entre el envío asíncrono y el envío clásico de formulario.
- **FR-007**: El sistema MUST seguir funcionando correctamente mediante un envío clásico de formulario con recarga completa de página (igual al comportamiento actual) cuando JavaScript no esté disponible o falle en el navegador del Administrador, produciendo el mismo resultado final que el envío asíncrono.
- **FR-008**: El sistema MUST mostrar los mismos mensajes de error y de éxito, con el mismo nivel de persistencia y contraste ya exigido por el Principio III de la Constitución, tanto en el envío asíncrono como en el clásico.
- **FR-009**: El sistema MUST impedir el envío duplicado de una misma acción de escritura mientras una petición asíncrona anterior todavía está en curso.
- **FR-010**: El sistema MUST informar de forma explícita y persistente cualquier falla de conectividad durante una escritura asíncrona, distinguiéndola de un error de validación de negocio.

### Key Entities

*No aplica: esta feature no introduce ni modifica entidades de datos; reutiliza exactamente el mismo modelo de datos de las features 001 a 010.*

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las pantallas ya migradas a Bootstrap 5 cumplen el contraste WCAG AA (≥4.5:1) tras el rediseño visual, verificado pantalla por pantalla.
- **SC-002**: El 100% de las interacciones de escritura del sistema (crear/editar/eliminar, en las 9 features de negocio) funcionan tanto con JavaScript habilitado (actualización sin recarga completa) como deshabilitado (recarga completa), produciendo el mismo resultado final en ambos casos.
- **SC-003**: El 100% de las pruebas automatizadas existentes (191 al momento de esta especificación) continúan pasando sin modificar ninguna aserción de regla de negocio.
- **SC-004**: Un visitante sin sesión iniciada llega a la pantalla de login en el 100% de los intentos de acceso a la dirección raíz o a cualquier pantalla protegida, sin pasar por ninguna página intermedia.
- **SC-005**: Un Administrador percibe el sistema como visiblemente más ágil al completar una acción de escritura habitual (crear un contrato, registrar una lectura), sin el parpadeo de una recarga completa de página, y sin necesitar más tiempo del que le toma hoy completar esa misma acción.

## Assumptions

- **A-001**: "Panel principal" (FR-004, US2) se refiere al listado de locaciones (`dashboard`/`locaciones.index` ya existentes), no a una pantalla nueva creada por esta especificación.
- **A-002**: La página de bienvenida decorativa actual (`welcome.blade.php`, scaffolding por defecto de Laravel) no cumple ninguna función de negocio en este sistema y puede retirarse o dejar de estar en la ruta raíz sin impacto, ya que ningún flujo de las especificaciones 001-010 depende de ella.
- **A-003**: "Actualización parcial de la página" (US3) no implica convertir la aplicación en una SPA ni exponer una API JSON pública de uso general: el mecanismo técnico exacto (ej. envío asíncrono con respuesta JSON consumida por JavaScript para actualizar el DOM) se decide en la fase de planificación, siempre que preserve el comportamiento de degradación a formulario clásico (FR-007).
- **A-004**: El volumen de datos y el modelo de usuario único (Administrador, sin RBAC) siguen siendo los mismos que en las especificaciones 001-010; esta feature no introduce roles ni permisos nuevos.
- **A-005**: Las pruebas automatizadas existentes, que ejercitan el envío clásico de formulario (sin cabeceras de petición asíncrona), siguen siendo válidas como verificación del comportamiento de degradación elegante exigido por FR-007, sin necesitar reescribirse.
