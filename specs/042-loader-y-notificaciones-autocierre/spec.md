# Feature Specification: Loader de Carga de Página y Notificaciones de Respuesta con Autocierre

**Feature Branch**: `042-loader-y-notificaciones-autocierre`

**Created**: 2026-08-30

**Status**: Draft

**Input**: User description: "Revisa todo el proyecto y agrega dos cosas, un loader para representar la carga de una
pagina, y las notificaciones de respuesta despues de un crud no se quedan siempre en la pagina sino que se
autocierren despues de 8 segundos maximo o si es que hay un hover sobre ellas para que se detenga el tiempo
antes que se cierren"

## Contexto: conflicto con una regla vigente y su resolución

Hoy la Constitución del proyecto (sección "Restricciones Técnicas y Estándares de Interfaz > Mensajes de
Estado y Feedback") y `DESIGN.md` (sección "Mensaje / Alert") exigen explícitamente que las notificaciones de
éxito/error sean **persistentes, sin autocierre** — "el usuario la cierra actuando, no por un temporizador".
El comentario del propio componente `resources/views/components/mensaje-alerta.blade.php` lo repite: *"Mensaje
persistente (no se oculta automáticamente)"*.

Esta feature nace de un pedido explícito del usuario de **cambiar esa regla**: las notificaciones de respuesta
tras una operación CRUD ya no deben quedarse indefinidamente, sino autocerrarse a los 8 segundos como máximo,
con pausa del temporizador mientras el puntero (o el foco de teclado) esté sobre ellas. La spec asume esa
decisión como **confirmada** por el pedido, de forma análoga a la excepción confirmada y documentada de
`specs/041`, y registra como dependencia la enmienda correspondiente de la Constitución y la actualización de
`DESIGN.md` (ver Assumptions y Dependencies).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Las confirmaciones de una acción no se acumulan en pantalla (Priority: P1)

Un administrador realiza varias operaciones seguidas (crear un recibo, editar un pago, desactivar un usuario).
Antes, cada operación dejaba un banner de respuesta —de éxito o de error— que permanecía en la página hasta
que él lo cerraba a mano o navegaba a otra vista, por lo que tras varias acciones la parte superior del
contenido acumulaba banners viejos que ya no aportaban nada. Ahora toda notificación de respuesta (éxito o
error) se muestra, se puede leer con calma y desaparece sola tras un máximo de 8 segundos; si el administrador
deja el puntero encima (o la enfoca con el teclado) porque todavía la está leyendo —por ejemplo, un mensaje
de error que necesita revisar—, el temporizador se detiene y la notificación no se cierra hasta que retira el
puntero/foco. También puede cerrarla de inmediato con un control explícito de cierre.

**Why this priority**: es el problema concreto que el usuario pidió resolver; afecta a las 23 vistas que hoy
muestran mensajes de respuesta y es entregable de forma independiente del loader.

**Independent Test**: ejecutar una operación CRUD exitosa y otra que devuelva error de validación; en ambos
casos confirmar que la notificación aparece, que desaparece sola en 8 segundos o menos sin intervención, que
al mantener el puntero encima durante más de 8 segundos permanece visible, y que al retirar el puntero vuelve
a cerrarse; confirmar además que el control de cierre manual la oculta al instante.

**Acceptance Scenarios**:

1. **Given** una vista tras una operación CRUD (con notificación de éxito o de error), **When** se muestra la
   notificación de respuesta y el usuario no interactúa, **Then** la notificación se cierra automáticamente en
   8 segundos o menos con una transición visible.
2. **Given** una notificación de respuesta visible, **When** el usuario coloca el puntero sobre ella (o la
   enfoca con el teclado), **Then** el temporizador de autocierre se detiene y la notificación permanece
   visible mientras dure ese hover/foco, aunque supere los 8 segundos.
3. **Given** una notificación cuyo temporizador estaba pausado por hover/foco, **When** el usuario retira el
   puntero y el foco, **Then** el temporizador se reinicia y la notificación vuelve a cerrarse sola.
4. **Given** una notificación de respuesta visible, **When** el usuario activa su control de cierre explícito,
   **Then** la notificación se oculta de inmediato sin esperar al temporizador.
5. **Given** tres operaciones CRUD en rápida sucesión, **When** terminan las tres, **Then** la pantalla no
   queda con tres banners apilados de forma indefinida.
6. **Given** una notificación de error de validación visible, **When** el usuario la lee sin pasar el puntero
   por encima, **Then** se autocierra igual que una de éxito (el hover/foco es el único mecanismo para
   retenerla más de 8 segundos).

---

### User Story 2 - El usuario percibe que una página está cargando (Priority: P1)

El mismo administrador navega entre secciones (de "Locaciones" a "Registro de Pagos", de un listado al detalle
de un recibo). En conexiones lentas o con respuestas pesadas, hoy no hay ninguna señal de que la navegación
está en curso: la pantalla se queda igual unos segundos y parece que el clic no tuvo efecto. Ahora, mientras
la nueva sección se está cargando, aparece una barra de progreso fina en el borde superior de la ventana que
desaparece en cuanto el contenido está listo (o si la petición falla), de modo que el administrador siempre
sabe que el sistema está respondiendo. Los envíos de formulario conservan su retroalimentación actual —el
botón se deshabilita y muestra "Guardando…"— y la barra superior aparece recién en la navegación de redirección
que sigue a un envío exitoso.

**Why this priority**: es la segunda de las dos cosas pedidas explícitamente; mejora la percepción de
respuesta en toda la aplicación y es entregable de forma independiente de las notificaciones.

**Independent Test**: navegar entre dos secciones con la red limitada (throttling); confirmar que aparece la
barra de progreso superior dentro de ~1 segundo de iniciada la navegación y que desaparece al completarse la
carga; forzar un error de red durante una navegación y confirmar que la barra también desaparece (no queda
corriendo); enviar un formulario y confirmar que el botón muestra "Guardando…" sin que la barra superior se
dispare por el envío en sí.

**Acceptance Scenarios**:

1. **Given** el usuario en cualquier sección, **When** navega a otra sección y la respuesta tarda más que el
   umbral anti-parpadeo, **Then** aparece la barra de progreso superior hasta que el nuevo contenido queda
   listo.
2. **Given** una navegación en curso con la barra visible, **When** la carga termina correctamente, **Then**
   la barra se oculta.
3. **Given** una navegación en curso con la barra visible, **When** la petición falla (error de conexión o de
   servidor), **Then** la barra se oculta igualmente y no permanece visible indefinidamente.
4. **Given** una navegación cuya respuesta termina por debajo del umbral anti-parpadeo, **When** se completa,
   **Then** la barra no llega a mostrarse de forma perceptible (sin parpadeo).
5. **Given** un envío de formulario, **When** la petición está en curso, **Then** la retroalimentación es el
   botón deshabilitado con "Guardando…", sin que la barra de progreso superior se dispare por ese envío.

---

### Edge Cases

- **Notificaciones de error/validación**: se autocierran con el mismo comportamiento que las de éxito (máximo
  8 s, pausa por hover/foco, cierre manual). El hover/foco es el mecanismo previsto para que el usuario retenga
  un error mientras lo lee o lo corrige.
- **Varias notificaciones a la vez**: si una vista muestra una de éxito y una de error simultáneamente, cada
  una gestiona su propio temporizador de forma independiente.
- **Hover que entra y sale varias veces**: cada vez que el puntero/foco vuelve a salir, el temporizador se
  reinicia a la duración completa, dando tiempo de re-lectura.
- **`prefers-reduced-motion`**: tanto la transición de cierre de la notificación como la animación del loader
  deben degradar a un cambio no animado cuando el usuario pide movimiento reducido.
- **JavaScript no disponible**: sin JS, la notificación se sigue mostrando con el comportamiento persistente
  actual (no se pierde ninguna confirmación) y la barra de progreso simplemente no aparece; ninguna página se
  rompe (degradación elegante, coherente con specs/011).
- **Lectores de pantalla**: la notificación ya se anuncia vía `role="alert"`; el autocierre no debe retirarla
  del DOM antes de que alcance a anunciarse. El loader no debe capturar el foco ni interferir con la
  navegación por teclado más de lo imprescindible.
- **Barra colgada**: si la navegación se cancela (el usuario hace clic en otro enlace antes de que termine),
  la barra no debe quedar corriendo para la navegación abortada; el nuevo destino reinicia su propia barra.
- **Primera carga dura de página**: la entrada por URL directa o la recarga completa no llevan barra propia —
  el navegador ya muestra su indicador nativo de carga. La barra de esta feature aplica solo a las
  navegaciones sin recarga completa dentro de la aplicación.

## Requirements *(mandatory)*

### Functional Requirements

#### Notificaciones de respuesta (User Story 1)

- **FR-001**: Toda notificación de respuesta de una operación CRUD —de éxito o de error— DEBE cerrarse
  automáticamente tras un máximo de 8 segundos de visible, sin intervención del usuario.
- **FR-002**: Mientras el puntero del ratón esté sobre la notificación, o mientras la notificación (o su
  control de cierre) tenga el foco de teclado, el temporizador de autocierre DEBE detenerse; la notificación
  no puede cerrarse sola durante ese lapso.
- **FR-003**: Al retirarse el puntero y el foco de la notificación, el temporizador DEBE reiniciarse a la
  duración completa y la notificación DEBE volver a cerrarse sola al agotarse.
- **FR-004**: La notificación DEBE ofrecer un control explícito y accesible de cierre inmediato, además del
  autocierre.
- **FR-005**: El cierre de la notificación (automático o manual) DEBE ser una transición visible (no una
  desaparición instantánea) y DEBE degradar a un cambio no animado cuando el usuario tiene activado
  `prefers-reduced-motion`.
- **FR-006**: Las notificaciones de error y de validación DEBEN autocerrarse con el mismo comportamiento que
  las de éxito: mismo máximo de 8 segundos, misma pausa por hover/foco (FR-002/FR-003) y mismo control de
  cierre manual (FR-004). No existe un tipo de notificación de respuesta que quede persistente por defecto.
- **FR-007**: Si JavaScript no está disponible en el navegador, la notificación DEBE mostrarse igualmente con
  el comportamiento persistente actual, sin romper el resto de la página.
- **FR-008**: El comportamiento de autocierre DEBE aplicarse de forma consistente en todas las vistas que hoy
  muestran mensajes de respuesta (las 23 que usan el componente de mensaje), sin cambiar el texto, el tipo ni
  el disparador de cada mensaje.

#### Indicador de carga (User Story 2)

- **FR-009**: El sistema DEBE mostrar la barra de progreso de carga cuando una navegación entre vistas dentro
  de la aplicación (cambio de sección o de listado a detalle, sin recarga completa de página) tarde en
  responder más que un umbral corto anti-parpadeo.
- **FR-010**: La barra de progreso DEBE ocultarse cuando la carga se completa, cuando la petición falla (error
  de conexión o de servidor) y cuando la navegación se cancela — nunca puede quedar visible de forma
  indefinida.
- **FR-011**: La barra de progreso NO DEBE mostrarse de forma perceptible para navegaciones que terminan por
  debajo del umbral anti-parpadeo (evitar el parpadeo en respuestas rápidas).
- **FR-012**: La barra de progreso NO DEBE dispararse por el envío de un formulario en sí; el envío conserva
  su retroalimentación actual (botón deshabilitado con "Guardando…"). La navegación de redirección que sigue a
  un envío exitoso sí es una navegación y queda cubierta por FR-009. La primera carga dura de página (entrada
  por URL directa, recarga completa) NO lleva barra de esta feature: el navegador ya muestra su indicador
  nativo.
- **FR-013**: La barra de progreso DEBE presentarse como una barra fina fija en el borde superior de la
  ventana (posición fija, ancho completo, por encima de cualquier contenido), que avanza mientras la
  navegación está en curso y se retira al completarse.
- **FR-014**: La barra de progreso DEBE respetar `prefers-reduced-motion`, no capturar el foco del teclado, no
  bloquear la interacción con el resto de la interfaz y no impedir al usuario cancelar la navegación yendo a
  otro lado.

#### Transversal

- **FR-015**: Ambos elementos (notificación y barra de progreso) DEBEN construirse con los componentes y
  utilidades estándar de Bootstrap 5.3 y Bootstrap Icons, con contraste mínimo WCAG AA, sin introducir ninguna
  otra librería de interfaz ni Alpine.js (Constitución, Principio VI). La barra puede reutilizar el mecanismo
  de indicador de carga que ya trae la capa asíncrona del proyecto (`hx-boost`, specs/011).
- **FR-016**: Ninguna ruta, controlador, dato, mensaje flash de sesión ni comportamiento de negocio existente
  DEBE cambiar — esta feature es exclusivamente de presentación en el cliente.
- **FR-017**: La suite de pruebas automatizadas existente DEBE seguir pasando sin cambios de resultado.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Tras una operación CRUD (de éxito o de error) y sin que el usuario interactúe, la notificación
  deja de estar visible en 8 segundos o menos.
- **SC-002**: Manteniendo el puntero sobre una notificación durante más de 8 segundos, esta permanece visible
  todo ese tiempo y se cierra sola dentro de los 8 segundos siguientes a retirar el puntero.
- **SC-003**: Tras tres operaciones CRUD seguidas y sin interacción del usuario, la parte superior del
  contenido no conserva ninguna notificación pasados 8 segundos de la última.
- **SC-004**: En toda navegación entre secciones cuya respuesta supere el umbral anti-parpadeo, el usuario ve
  la barra de progreso superior dentro de 1 segundo de iniciada la navegación, y esa barra desaparece al
  terminar la carga.
- **SC-005**: En 0 de los casos probados (navegación exitosa, error de red, error de servidor, navegación
  cancelada) la barra de progreso permanece visible tras finalizar la acción.
- **SC-006**: Un envío de formulario no dispara la barra de progreso superior; su única retroalimentación
  durante el envío es el botón deshabilitado con "Guardando…".
- **SC-007**: Ninguna prueba automatizada existente cambia de resultado tras esta feature.
- **SC-008**: El comportamiento nuevo es idéntico en las vistas que muestran mensajes de respuesta, sin
  regresiones de contenido ni de tipo de mensaje en ninguna de ellas.

## Assumptions

- **Excepción confirmada a la regla de "notificaciones persistentes".** El usuario pidió explícitamente el
  autocierre; esta spec lo asume confirmado, de forma análoga a la excepción documentada de `specs/041`. La
  enmienda de la Constitución (sección "Mensajes de Estado y Feedback") y la actualización de `DESIGN.md`
  (sección "Mensaje / Alert" y el comentario del componente `mensaje-alerta`) se tratan como dependencia de
  esta feature, no como trabajo aparte.
- Se mantiene la **ubicación actual** del mensaje (banner al inicio del área de contenido); no se migra a un
  "toast" apilado en una esquina de la ventana, salvo que la revisión con el skill `impeccable` lo recomiende
  explícitamente.
- Al salir el puntero/foco, el temporizador se **reinicia a la duración completa** (8 s), en vez de reanudar
  solo el tiempo restante, para dar margen de re-lectura.
- Umbral anti-parpadeo de la barra de progreso: del orden de 150–200 ms; por debajo de ese tiempo la barra no
  se muestra.
- La barra de progreso se apoya en el mecanismo de indicador de carga que ya trae la capa asíncrona del
  proyecto (`hx-boost`, specs/011) para las navegaciones sin recarga completa. La primera carga dura de página
  no lleva barra propia (el navegador ya muestra su indicador nativo), y el envío de un formulario tampoco la
  dispara (conserva el botón "Guardando…" que ya implementa `resources/js/htmx.js`).
- htmx y el bundle JS de Bootstrap ya están cargados en el layout base; esta feature no agrega ninguna
  dependencia de terceros nueva.
- "Operación CRUD" abarca crear/editar/eliminar/anular/reactivar en locaciones, contratos, representantes,
  recibos, pagos, lecturas, conceptos de gasto fijo, usuarios y configuración — todas las secciones que hoy
  devuelven un mensaje flash de respuesta.
- El valor "8 segundos" es un máximo; una duración algo menor pero suficiente para leer un mensaje corto es
  aceptable siempre que respete FR-001.

## Dependencies

- **Enmienda constitucional**: `.specify/memory/constitution.md`, sección "Restricciones Técnicas y Estándares
  de Interfaz > Mensajes de Estado y Feedback" (hoy exige mensajes persistentes).
- **Actualización de documentación de diseño**: `DESIGN.md`, sección "Mensaje / Alert" (hoy dice "Persistent
  (no auto-dismiss)"), y el comentario del componente `resources/views/components/mensaje-alerta.blade.php`.
- **Reutiliza**: el componente Blade `mensaje-alerta`, la capa `hx-boost` del layout `app-bootstrap` y el
  archivo de mejora progresiva `resources/js/htmx.js` (bloqueo de doble envío y aviso de error de
  conectividad ya existentes).
