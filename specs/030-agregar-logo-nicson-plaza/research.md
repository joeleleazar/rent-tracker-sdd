# Research: Incorporar el Logo de Nicson Plaza a la Interfaz

## Decisión 1 — Ubicación y formato del archivo: usar el `.jpg` entregado tal cual, servido desde `public/`

**Decisión**: Copiar el archivo entregado a `public/images/logo-nicson-plaza.png` (sin recortar,
redimensionar ni convertir de formato) y referenciarlo desde Blade con `asset('images/logo-nicson-plaza.png')`.
No se agrega a la lista de entradas de `vite.config.js`.

**Rationale**: El entorno no tiene ninguna herramienta de conversión/edición de imágenes instalada
(`magick`/`convert` de ImageMagick, `cwebp`, `ffmpeg` — verificado con `which` antes de planificar); la
única vía de PHP disponible en este proyecto (extensión `gd`) serviría para redimensionar pero no aporta
nada que CSS no resuelva ya para las 3 vistas donde se usa el logo como imagen. `vite.config.js` en este
proyecto solo lista entradas JS/CSS que Vite procesa (fuentes, Sass, scripts) — nunca imágenes estáticas;
el patrón ya establecido para un asset binario servido tal cual es `public/favicon.ico`, colocado
directamente en `public/` sin pasar por Vite. Replicar ese mismo patrón para el logo es la opción más
simple y consistente con el proyecto.

**Alternativas consideradas**: Redimensionar/comprimir el archivo antes de copiarlo (son 2048×2048,
~193 KB) — descartada por no tener herramienta disponible para hacerlo de forma confiable, y porque a los
tamaños de despliegue reales (un ícono de sidebar de unos pocos rem, un logo de login de unos 5rem) el
navegador ya redimensiona la imagen sin problema visual ni de rendimiento perceptible para un archivo de
este peso.

**Hallazgo posterior a la implementación**: el usuario reemplazó manualmente el archivo entregado por una
versión propia en `public/images/logo-nicson-plaza.png` — ya no el mismo `.jpg` de 2048×2048 sino un `.png`
de 1769×962 (RGBA, con transparencia real, ~864 KB) — y editó a mano las referencias `.jpg`→`.png` en el
código. Esa edición manual dejó dos defectos: el `<link rel="icon">` de ambos layouts quedó con
`type="image/jpeg"` apuntando a un archivo `.png` (ver Decisión 3), y los 3 lugares donde se muestra el
`<img>` seguían usando una caja cuadrada fija con `object-fit: contain` (ver Decisión 2) — dimensionada
para la proporción 1:1 del `.jpg` original, que con el nuevo archivo 1.84:1 lo encogía mucho más de lo
previsto. Se corrigieron ambos defectos sin volver a copiar ningún archivo (el usuario ya lo había
reemplazado) — ver el detalle en Decisión 2 y Decisión 3.

## Decisión 2 — El logo se enmarca en una tarjeta blanca donde el fondo es oscuro (sidebar)

**Decisión**: En el encabezado del sidebar (`app-bootstrap.blade.php`, fondo oscuro), el `<img>` del logo
se envuelve en un contenedor pequeño con fondo blanco y esquinas redondeadas (mismo lenguaje visual que
`card` ya exige el Principio VI), en vez de mostrarlo suelto directamente sobre el fondo oscuro. En el
login (`guest-bootstrap.blade.php`, fondo claro) y en el comprobante de recibo (documento blanco), el
`<img>` se muestra directamente, sin envoltorio adicional.

**Rationale**: El archivo es un `.jpg` — no admite transparencia — y su fondo es blanco sólido. Colocado
directamente sobre el fondo oscuro del sidebar (`$dark`, ver `resources/css/bootstrap.scss`), se vería como
un rectángulo blanco desprolijo alrededor de la marca. Como no hay forma de quitar ese fondo sin
herramientas de edición de imagen (Decisión 1), la solución que sí está al alcance de este proyecto es
tratar ese rectángulo blanco como un elemento de diseño intencional — una pequeña tarjeta/badge, patrón ya
establecido y exigido por el Principio VI de la constitución para "presentar un registro individual", que
aquí se reutiliza para presentar la marca. En el login y el comprobante, el fondo del propio documento ya
es claro, así que el fondo blanco del archivo se funde sin necesidad de ningún tratamiento adicional.

**Alternativas consideradas**:
- Aplicar un filtro CSS (`mix-blend-mode`) para intentar que el blanco del `.jpg` se funda con el fondo
  oscuro — descartada porque con una imagen JPEG (compresión con artefactos) el resultado es impredecible
  y no controlable de forma confiable sin poder inspeccionar/editar los píxeles reales.
- Pedirle al usuario una versión con fondo transparente — descartado por ahora: el pedido fue incorporar
  el archivo entregado, y la solución de tarjeta blanca ya resuelve el problema sin bloquear la feature a
  la espera de un archivo distinto; queda como mejora futura si el usuario provee una versión en PNG con
  transparencia.

**Hallazgo posterior a la implementación**: el usuario efectivamente proveyó esa versión con transparencia
(ver Decisión 1) — un `.png` de 1769×962 sin fondo sólido. Con eso, el fondo blanco alrededor de la marca
en el sidebar dejó de ser un artefacto del archivo (ya no lo hay) y la tarjeta blanca se conserva por una
razón distinta: el trazo "nicson" del logo es azul oscuro, con poco contraste directo sobre el fondo casi
negro del sidebar (`$dark: #111827`, `resources/css/bootstrap.scss`) — la tarjeta blanca sigue siendo
necesaria para que la marca se siga leyendo bien ahí (Principio III), aunque ya no para tapar un fondo no
deseado. En el login y el comprobante, donde el fondo del documento ya es claro, el envoltorio se retira
(nunca hizo falta salvo para tapar el fondo del `.jpg`, que ya no existe).

Además, el ancho real del archivo dejó de ser cuadrado (1769×962, ~1.84:1) mientras las 3 vistas seguían
dimensionando el `<img>` con una caja cuadrada fija (`width`/`height` iguales) y `object-fit: contain` —
pensada para encajar el `.jpg` 1:1 original. Con la proporción nueva, `contain` ajusta por el lado más
angosto de la caja (el ancho, en una caja cuadrada), encogiendo el logo mucho más de lo previsto y
volviéndolo ilegible. Se reemplazó ese patrón en el sidebar, el login y el comprobante por
`height: Xrem; width: auto;` — el `<img>` se dimensiona por su alto real y el ancho se ajusta solo a la
proporción del archivo, sin recortarlo ni forzarlo a un cuadrado.

## Decisión 3 — Favicon vía `<link rel="icon">`, sin reemplazar `public/favicon.ico`

**Decisión**: Agregar `<link rel="icon" type="image/png" href="{{ asset('images/logo-nicson-plaza.png') }}">`
al `<head>` de ambos layouts (`guest-bootstrap.blade.php` y `app-bootstrap.blade.php`). El archivo
`public/favicon.ico` existente se deja sin tocar.

**Rationale**: Todos los navegadores actuales (Chrome, Edge, Firefox, Safari) aceptan un `.jpg`/`.png`
como ícono de pestaña vía `<link rel="icon">`, y ese enlace explícito tiene prioridad sobre la solicitud
implícita a `/favicon.ico` que hace el navegador cuando no encuentra ningún `<link>` — así que agregar el
`<link>` alcanza para cumplir FR-006 sin necesitar generar un `.ico` real (formato que sí requeriría una
herramienta de conversión no disponible, Decisión 1). Mantener `favicon.ico` intacto preserva el
comportamiento para cualquier consumidor que ignore el `<link>` y pida ese archivo directamente
(crawlers antiguos, algunas extensiones de navegador), sin costo adicional.

**Alternativas consideradas**: Sobrescribir `favicon.ico` con un archivo `.ico` generado a mano — descartada
por no ser factible de forma confiable sin herramientas de conversión de imagen en este entorno.

**Hallazgo posterior a la implementación**: al reemplazar el archivo por la versión `.png` (Decisión 1), la
edición manual del usuario actualizó la URL del `<link rel="icon">` pero no su atributo `type`, que quedó
en `image/jpeg` apuntando a un archivo `.png` — una discrepancia MIME-type/extensión. Se corrigió a
`type="image/png"` en ambos layouts, junto con los asserts correspondientes en
`tests/Feature/LogoInstitucionalTest.php` (US4).

## Decisión 4 — El logo del sidebar sigue siendo un enlace al inicio

**Decisión**: El `<a href="{{ url('/') }}">` que hoy envuelve el texto "Rent Tracker" pasa a envolver el
logo (dentro de su tarjeta blanca, Decisión 2), conservando exactamente el mismo destino y comportamiento
de enlace.

**Rationale**: FR-003 exige explícitamente que el encabezado siga funcionando como enlace al inicio — es
el comportamiento ya existente hoy con el texto, y cambiar el contenido visual de un enlace no requiere
ningún cambio de lógica adicional.

**Alternativas consideradas**: Ninguna — es la aplicación directa del requisito.
