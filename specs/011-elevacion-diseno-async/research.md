# Research: Elevación de Diseño, Login como Entrada y Escritura Asíncrona

**Feature**: `011-elevacion-diseno-async` | **Date**: 2026-08-21

## 1. Refinamiento visual manteniendo Senior-First

**Decision**: Ampliar `resources/css/bootstrap.scss` (ya existente desde `specs/010`) con: (a) una escala de sombras propia (`$box-shadow-sm`/`$box-shadow`/`$box-shadow-lg` de Bootstrap, ajustadas a una opacidad sutil pero visible sobre el fondo claro actual) para dar profundidad a las `card`; (b) una paleta ampliada con un color de acento distintivo (ej. un tono secundario cálido para elementos destacados, sin reemplazar los colores semánticos `$primary`/`$success`/`$danger`/`$warning` ya validados por contraste); (c) una escala de espaciado (`$spacer` y el mapa `$spacers`) ligeramente más generosa entre secciones, ya que el espacio en blanco es la herramienta principal de jerarquía visual que no compromete legibilidad. Ninguna de estas variables afecta `$font-size-base`, `$input-btn-padding-*` ni los demás mínimos Senior-First ya fijados en `specs/010`.

**Rationale**: El mismo mecanismo (variables Sass antes del `@import` de Bootstrap) que ya se usó en 010 para hornear los mínimos de accesibilidad es el lugar correcto para agregar carácter visual, evitando volver a la trampa original (overrides de CSS dispersos por vista). Sombra y espaciado son, de las herramientas de jerarquía visual disponibles, las que menos tensionan contra Senior-First (no reducen tamaño de texto ni contraste).

**Alternatives considered**: Introducir un segundo framework/tema visual (ej. un theme de Bootstrap de terceros): rechazado, agrega una dependencia externa no auditada contra los criterios de contraste ya validados.

## 2. Login como vista de entrada

**Decision**: Cambiar la ruta `/` en `routes/web.php` de `return view('welcome')` a una redirección condicional: `auth()->check() ? redirect()->route('dashboard') : redirect()->route('login')`. Se retira `welcome.blade.php` de la ruta raíz (el archivo puede eliminarse o dejarse sin enrutar, ver `tasks.md`). No se requiere ningún cambio adicional para el resto de FR-003/FR-004: el middleware `auth` ya presente en el grupo de rutas protegidas redirige a `route('login')` con el mecanismo `intended()` de Laravel (confirmado en `AuthenticatedSessionController::store()`, que ya hace `redirect()->intended(route('dashboard'))`); y el middleware `guest` ya presente en `routes/auth.php` sobre la ruta `login` ya redirige a un usuario autenticado lejos del formulario de login (comportamiento por defecto de Laravel, sin necesidad de tocarlo).

**Rationale**: Los mecanismos de "volver a la página que se intentaba visitar tras loguearse" (FR-003, Acceptance Scenario 2) y "no mostrar login a un usuario ya autenticado" (Edge Case) ya existen en el scaffolding de autenticación de Laravel Breeze que trae el proyecto desde su commit inicial; solo faltaba que la ruta raíz participara de ese mismo esquema en vez de mostrar una vista estática ajena al sistema de sesiones.

**Alternatives considered**: Mover el contenido de `welcome.blade.php` a una landing pública informativa: rechazado, la Asunción A-002 de la especificación ya establece que esa página no cumple ninguna función de negocio en este sistema (es de uso interno para un único Administrador, no un producto con marketing público).

## 3. Motor de escritura asíncrona: htmx con `hx-boost`

**Decision**: Adoptar **htmx 2.x** (~14KB comprimido, sin dependencias), aplicando el atributo `hx-boost="true"` al contenedor raíz del layout compartido (`app-bootstrap.blade.php`). Esto convierte automáticamente todos los enlaces y formularios internos en peticiones AJAX que reemplazan el `<body>` (o el contenedor boosteado) sin una recarga completa de página, **sin requerir ningún cambio en controladores, rutas, Form Requests ni vistas** — el servidor sigue rindiendo exactamente el mismo HTML completo que hoy; htmx simplemente evita que el navegador ejecute una navegación de documento completa al recibirlo, y actualiza la URL vía `history.pushState`.

**Rationale — por qué esto resuelve FR-005 a FR-008 casi sin tocar el backend**:
- **FR-005/FR-006 (asíncrono, mismas reglas de negocio)**: htmx no cambia el método HTTP, la ruta, el cuerpo de la petición ni la respuesta — solo agrega la cabecera `HX-Request: true`. Todo el código de validación y reglas de negocio (Form Requests, servicios con `DB::transaction`, excepciones custom) se ejecuta exactamente igual sea la petición boosteada o clásica.
- **FR-007 (degradación sin JavaScript)**: es automática y no requiere lógica de "modo sin JS" — si htmx no carga o el navegador no lo soporta, los `<form>`/`<a>` conservan sus atributos `action`/`method`/`href` normales y el navegador hace la navegación clásica de siempre. No hay una rama de código distinta que mantener: es el comportamiento por defecto del HTML cuando htmx está ausente.
- **FR-008 (mismos mensajes de error/éxito)**: los errores de validación (`back()->withErrors()`, tanto los de Form Request como los de las excepciones custom ya capturadas en cada controlador) siguen siendo una respuesta 302 de redirección; htmx sigue redirecciones por defecto al bostear, por lo que el usuario ve la misma pantalla con los mismos mensajes, solo que sin el parpadeo de recarga completa.
- **SC-003 (suite de pruebas intacta)**: como el único cambio observable por el servidor es una cabecera HTTP que las pruebas Pest actuales no envían, la suite ejercita exactamente el mismo camino de código (el "modo clásico"), por lo que no se espera ningún ajuste de aserciones.

**FR-009 (bloqueo de doble envío) y FR-010 (error de red explícito)** sí requieren un pequeño script propio (`resources/js/htmx.js`) que escuche los eventos nativos de htmx: `htmx:beforeRequest` para deshabilitar el botón de envío del formulario disparador, `htmx:afterRequest`/`htmx:afterSettle` para rehabilitarlo, y `htmx:sendError`/`htmx:responseError` para mostrar un `x-mensaje-alerta` persistente de error de conectividad (distinto de un error de validación de negocio, que ya llega renderizado dentro del HTML intercambiado por htmx).

**Alternatives considered**:
- **Reescribir cada formulario con `fetch()` a mano, devolviendo JSON desde cada controlador**: rechazado, multiplicaría el trabajo por cada uno de los ~15 controladores/acciones de escritura, obligaría a mantener dos formatos de respuesta (JSON y redirect clásico) en paralelo, y aumentaría el riesgo de romper la suite de pruebas existente (que asume respuestas de redirect/sesión). htmx boost logra el mismo resultado observable sin ninguna de esas duplicaciones.
- **Alpine.js + `fetch` manual por vista**: rechazado, Alpine.js fue retirado deliberadamente en `specs/010`; reintroducirlo solo para esto iría en contra de esa decisión sin necesidad, cuando htmx cubre el caso de uso de forma más directa (está diseñado específicamente para aplicaciones server-rendered como esta).
- **Inertia.js (SPA con Vue/React sobre Laravel)**: rechazado explícitamente, ya que el usuario descartó la opción de "rearquitectura completa a SPA/API JSON" al definir el alcance de esta spec; Inertia exigiría reescribir cada vista Blade como componente de un framework JS, muy por encima del alcance elegido.
- **Turbo (Hotwire)**: alternativa funcionalmente similar a htmx (mismo principio de boosting), descartada solo por preferir la superficie de API más simple y granular de htmx para los casos puntuales de FR-009/FR-010 (eventos JS más directos de enganchar).

## 4. Indicador de carga y prevención de doble envío

**Decision**: `resources/js/htmx.js` escucha `htmx:beforeRequest` sobre cualquier `form`/`a` boosteado: agrega `disabled` al botón de tipo `submit` dentro del elemento disparador (o al propio enlace, agregándole una clase `disabled` visual) y le agrega temporalmente un texto de estado ("Guardando…"), revertido en `htmx:afterRequest`. Esto se implementa una sola vez, de forma centralizada, en vez de repetirse en cada formulario.

**Rationale**: Cumple FR-009 sin requerir que cada vista implemente su propio manejo de estado de envío; htmx expone estos eventos globalmente en `document`, por lo que un único listener cubre toda la aplicación presente y futura.

**Alternatives considered**: Usar el atributo `hx-disabled-elt` de htmx (deshabilita un elemento automáticamente durante la petición): considerado y complementario, se usa como mecanismo principal donde aplique directamente sobre el botón de envío, con el listener JS como respaldo para el texto de estado ("Guardando…"), ya que `hx-disabled-elt` por sí solo no cambia el texto visible del botón.

## 5. Mensaje de error de conectividad

**Decision**: Los eventos `htmx:sendError` (fallo de red antes de recibir respuesta) y `htmx:responseError` con código de estado 5xx (error de servidor no relacionado a validación) disparan la inserción de un `<x-mensaje-alerta tipo="error">` persistente en la parte superior del contenedor boosteado, con el texto "No se pudo completar la acción por un problema de conexión. Intente nuevamente.". Los errores 422 (validación de negocio) NO disparan este mensaje genérico, ya que htmx los sigue como una redirección normal que ya trae sus propios mensajes específicos renderizados por el servidor.

**Rationale**: Cumple FR-010 distinguiendo explícitamente "falla de red/servidor" de "error de validación de negocio", tal como exige el Edge Case correspondiente de la especificación.

**Alternatives considered**: Un `toast` que desaparece automáticamente: rechazado, contradice el Principio III (mensajes persistentes, no efímeros) ya aplicado a todas las alertas del proyecto.

## 6. Framework de pruebas para el nuevo comportamiento

**Decision**: Se agrega `tests/Feature/RutaRaizTest.php` (Pest) con dos casos: visitante sin sesión → redirect a `route('login')`; usuario autenticado → redirect a `route('dashboard')`. No se agregan pruebas Pest para el comportamiento de htmx en sí (es una librería de terceros ya probada, y su efecto observable por el servidor es nulo, ver §3), consistente con el principio de no probar el framework, solo el código propio.

**Rationale**: Es el único comportamiento genuinamente nuevo a nivel de servidor introducido por esta feature (US2); el resto (US1 visual, US3 htmx) no introduce lógica de servidor nueva que requiera pruebas de negocio adicionales.

**Alternatives considered**: Pruebas de navegador end-to-end (Dusk) para verificar la ausencia de recarga completa: rechazado, el proyecto no usa Dusk ni tiene esa infraestructura; la verificación de "sin recarga perceptible" se hace manualmente en navegador (ver `quickstart.md`), consistente con cómo se verificaron los aspectos visuales de `specs/010`.
