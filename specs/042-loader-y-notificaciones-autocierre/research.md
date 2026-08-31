# Research: Loader de Carga de Página y Notificaciones de Respuesta con Autocierre

Feature de presentación en el cliente. No hay incógnitas de dominio ni de datos; las decisiones son de
mecánica de interfaz y de cómo encajar en el stack ya existente (`hx-boost`, Bootstrap 5.3, sin Alpine).

## §1. Autocierre de notificaciones con pausa por hover/foco

**Decisión**: convertir el componente `x-mensaje-alerta` en un `alert` de Bootstrap *dismissible*
(`alert-dismissible fade show` + `btn-close` con `data-bs-dismiss="alert"`) y añadir en `resources/js/bootstrap.js`
una función que, para cada notificación presente, arranca un temporizador de 8 000 ms; `mouseenter` y
`focusin` sobre la notificación **cancelan** el temporizador, `mouseleave` y `focusout` lo **reinician a la
duración completa**. Al agotarse, se cierra con `bootstrap.Alert.getOrCreateInstance(el).close()`, que dispara
la transición `fade` y retira el nodo.

**Rationale**:
- `bootstrap.js` ya está cargado por el layout y ya hace exactamente este patrón de escaneo
  (`DOMContentLoaded` + `htmx:afterSettle`) para autoabrir modales (`data-autoshow`). Reusar ese enganche
  evita una entrada nueva de Vite y cubre las navegaciones boosteadas (donde `DOMContentLoaded` no vuelve a
  dispararse, `specs/011`).
- `btn-close` + `data-bs-dismiss="alert"` es el cierre manual estándar de Bootstrap (FR-004); funciona con el
  `bootstrap.js` ya presente, independiente de nuestra lógica de temporizador.
- Reiniciar a la duración completa al salir el puntero (en vez de reanudar el remanente) da margen de
  re-lectura y es trivial de razonar y de probar (Assumptions de la spec).
- `role="alert"` se conserva: el nodo se renderiza en el HTML del servidor y está presente en el primer
  frame, así que el lector de pantalla lo anuncia antes de que el temporizador pueda cerrarlo (FR + Edge
  Case de lectores de pantalla).

**Alcance éxito + error (decisión Q1 = B)**: la misma lógica corre para `alert-success` y `alert-danger`. El
único mecanismo para retener un error es el hover/foco. El error de **validación de campo** no depende de
esto: sigue mostrándose junto al campo (`@error`) de forma persistente; lo que se autocierra es el *banner*
de resumen (`x-mensaje-alerta tipo="error"`).

**Alternativas consideradas**:
- *Toast de Bootstrap apilado en una esquina*: cambia la ubicación y el patrón visual de 23 vistas y
  DESIGN.md; se descartó salvo que `impeccable` lo pida (Assumptions).
- *Reanudar el tiempo restante en vez de reiniciar*: más código de estado (guardar `restante`), sin ventaja
  real para mensajes cortos.
- *Cierre sin transición*: descartado por FR-005; la transición `fade` de Bootstrap ya existe y respeta
  `prefers-reduced-motion` con una regla CSS extra.

## §2. Barra de progreso solo en navegaciones, no en envíos (decisión Q2 = C)

**Decisión**: en `resources/js/htmx.js`, escuchar `htmx:beforeRequest`; si el disparador es una **navegación**
(verbo `GET`), armar `setTimeout(150 ms)` que muestra la barra; si es un **envío de formulario** (verbo
`POST`/`PUT`/`PATCH`/`DELETE` o `evento.target` es un `<form>`), no hacer nada. Ocultar y limpiar el timeout
en `htmx:beforeSwap`, `htmx:afterRequest`, `htmx:sendError`, `htmx:responseError` y `htmx:abort`.

**Detección GET vs POST**: `evento.detail.requestConfig.verb` en htmx 2.x; como respaldo,
`evento.target.tagName === 'A'` o `!evento.target.closest('form')`. Se confirma el nombre exacto de la
propiedad al implementar (htmx 2.0.10); el archivo `htmx.js` actual ya distingue formularios por
`elemento.tagName === 'FORM'`, mismo criterio.

**Rationale**:
- Un envío boosteado por `hx-boost` es **una sola** petición XHR: el POST y el 302→GET de redirección los
  resuelve el navegador dentro del mismo XHR, htmx solo ve un ciclo con verbo POST. Por eso "la barra aparece
  en la redirección posterior" no es un evento observable por separado bajo `hx-boost`; y no hace falta,
  porque el botón «Guardando…» (ya implementado en `htmx.js`) cubre todo ese lapso y la vista destino
  renderiza su notificación flash. La spec (FR-012, US2) se lee en esa clave.
- Umbral de 150 ms: por debajo de eso la navegación se siente instantánea y mostrar la barra sería un
  parpadeo (FR-011). Se implementa como `setTimeout` que se cancela si la respuesta llega antes.
- `htmx:beforeSwap` como punto de ocultado (además de `afterRequest`) asegura que la barra no sobreviva al
  intercambio de contenido aunque un handler posterior falle.
- `htmx:abort` cubre el caso "el usuario hace clic en otro enlace antes de que termine": la navegación
  abortada oculta su barra; la nueva arma la suya (Edge Case "barra colgada").

**Primera carga dura / back-forward**: una carga completa de página (URL directa, F5) no pasa por
`htmx:beforeRequest`; el navegador ya muestra su indicador nativo, así que no se añade barra propia (FR-012).
`htmx:historyRestore` (volver atrás con caché) es instantáneo y tampoco arma la barra.

**Alternativas consideradas**:
- *Usar `hx-indicator` / clase `.htmx-request` de htmx*: sirve para indicadores locales por elemento, no para
  una barra global de página; además se activa también en formularios. Se prefiere el manejo explícito por
  evento.
- *Librería "nprogress" o similar*: la constitución (Principio VI, FR-015) prohíbe introducir librerías de
  interfaz nuevas. La barra se arma con `progress`/`progress-bar` de Bootstrap.
- *Overlay centrado con spinner (opción Q3-B)*: descartada por el usuario; además oscurece el contenido y hay
  que cuidar que no atrape el foco.

## §3. Construcción de la barra con primitivas de Bootstrap

**Decisión**: contenedor `div.barra-carga-navegacion` de posición **fija** (`top:0; left:0; right:0`), alto
~3 px, `z-index` alto, oculto por defecto (`d-none` o `opacity:0`), con un `div.progress-bar` interno de
color `$primary`. Mientras la navegación está en curso, el ancho del `progress-bar` anima de 0 % a ~90 % con
una transición CSS (efecto "cargando"); al completarse salta a 100 % y la barra se desvanece. Con
`prefers-reduced-motion`, la barra se muestra/oculta sin animar el ancho (barra sólida indeterminada o
simplemente visible→oculta).

**Rationale**: `progress` + `progress-bar` son componentes estándar de Bootstrap 5.3 (FR-015). El único
CSS bespoke es la posición fija y el alto reducido — análogo al único componente a medida ya aceptado en
`DESIGN.md` ("Estado Vacío"), que se documenta ahí igual. El color sale de la paleta vinculante del proyecto
(`resources/css/bootstrap.scss`), con contraste ya verificado.

**Alternativas consideradas**: barra con `@keyframes` propia de translate (más control del "shimmer", más
CSS); se deja la animación de `width` por transición, más simple y suficiente.

## §4. `prefers-reduced-motion` y accesibilidad

**Decisión**:
- Regla `@media (prefers-reduced-motion: reduce)` en `bootstrap.scss` que pone `transition: none` en
  `.barra-carga-navegacion .progress-bar` y en `.alert.fade` (el cierre del alert se vuelve instantáneo).
- La barra no recibe foco (`tabindex` ausente) y no cubre contenido interactivo (3 px en el borde superior);
  no necesita `role` — es decorativa respecto al contenido. Opcionalmente `aria-hidden="true"`.
- El `btn-close` del alert lleva `aria-label="Cerrar"` (Bootstrap lo trae por defecto en inglés; se fija en
  español, Constitución Principio II).

**Rationale**: FR-005 y FR-014 lo exigen explícitamente; el proyecto ya respeta WCAG AA como piso
(Constitución Principio III).

## §5. Enmienda constitucional y actualización de `DESIGN.md`

**Decisión**: enmendar `.specify/memory/constitution.md`, sección "Restricciones Técnicas y Estándares de
Interfaz → Mensajes de Estado y Feedback", reemplazando la exigencia de "mensajes persistentes … no por un
temporizador" por: *notificaciones de respuesta efímeras que se autocierran tras un máximo de 8 s, con el
temporizador en pausa mientras el puntero o el foco están sobre la notificación, y con control de cierre
manual; los errores de validación por campo siguen mostrándose de forma persistente junto a su campo*.
Bump SemVer **MINOR** (2.1.1 → 2.2.0) con su Sync Impact Report. En `DESIGN.md`, sección "Mensaje / Alert":
quitar "Persistent (no auto-dismiss)" y añadir la subsección de la barra de carga de navegación.

**Rationale**: es un cambio de un lineamiento normativo de interfaz pedido explícitamente por el usuario, no
un principio central ni un cambio de stack → MINOR, no MAJOR. Documentarlo como enmienda (y no aplicarlo en
silencio) es el mismo criterio que `specs/041` usó con el "No-Decoration Rule" de `DESIGN.md`.

**Alternativas consideradas**: PATCH (solo aclaración) — descartada, se cambia el sentido de la regla, no su
redacción. MAJOR — descartada, no es un principio central ni incompatible con el resto.

## §6. Riesgo sobre la suite de pruebas existente

**Análisis**: las pruebas Feature verifican el flash con `assertSee('<texto>')` sobre el HTML renderizado y
con `assertSessionHas('mensaje')`. El texto sigue estando en el slot del componente y la sesión no cambia, así
que añadir `alert-dismissible fade show` y un `btn-close` no altera ningún aserto existente. Ninguna prueba
afirma el markup exacto del alert ni la ausencia de un botón de cierre. El autocierre y la barra son 100 %
cliente y no se ejercitan en pruebas HTTP.

**Mitigación**: correr `php artisan test` completo (433 pruebas) como tarea de cierre (SC-007) y `npm run
build` (SC — build limpio), más la prueba Feature nueva del contrato del componente.
