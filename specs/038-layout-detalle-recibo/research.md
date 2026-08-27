# Research: Distribución en Dos Columnas del Detalle de Recibo

## Decisión 1: Grid Bootstrap `row`/`col-lg-7`/`col-lg-5`, no CSS a medida

**Decisión**: `row g-4` con `col-lg-7` (resumen del recibo) y `col-lg-5` (Pagos + Estado del Recibo),
apilándose por debajo de `992px`.

**Rationale**: Mismo mecanismo responsive que el resto del proyecto (Principio VI), resuelve FR-004 gratis.
Se usa el breakpoint `lg` en vez de `md` (a diferencia de specs/037, ya retractada, que usaba `md` para un
documento standalone más angosto) porque esta pantalla vive dentro del layout con sidebar fijo
(`app-bootstrap.blade.php`, 280px en desktop) — el espacio horizontal real disponible es menor que el de un
documento standalone a pantalla completa, así que apilar recién en `lg` (992px) evita que las dos columnas
se compriman demasiado en tablets o ventanas medianas.

**Alternatives considered**: `col-md-*`: descartado por la razón anterior — con el sidebar fijo restando
280px, el punto de quiebre `md` (768px) dejaría columnas demasiado angostas antes de apilarse.

## Decisión 2: No reproducir los elementos decorativos de la imagen de referencia

**Decisión**: Se adopta la distribución en dos columnas de la imagen, pero el contenido de cada columna
conserva exactamente los componentes ya usados en el proyecto (`dl`/`dt`/`dd`, `card`, `badge` con texto,
botones con ícono + etiqueta) — no las etiquetas en mayúsculas, los "chips" con ícono para
Locación/Período/Emisión, ni el menú de acciones de solo ícono ("⋮") que aparecen en la captura.

**Rationale**: Ya documentado como Assumption en spec.md — esos 3 elementos decorativos violan reglas ya
vigentes y aplicadas activamente en esta misma sesión: el "No-Decoration Rule" de `DESIGN.md` (contraste
directo con los 2 hallazgos de `text-uppercase` ya corregidos en specs/031 y specs/035) y el Principio VI de
la constitución sobre íconos que nunca reemplazan una etiqueta explícita. Adoptar la distribución espacial
sin sus decoraciones conflictivas mantiene esta pantalla visualmente coherente con el resto del sistema de
diseño ya establecido.

**Alternatives considered**: Reproducir la imagen literalmente (chips, mayúsculas, menú de ícono):
descartado por la razón anterior — requeriría documentar una excepción nueva a 2 reglas ya exigidas y
corregidas activamente en esta sesión, sin que el pedido del usuario ("una distribución como la de la
imagen") exigiera explícitamente esa fidelidad decorativa.

## Decisión 3: Ancho de página — se retira el `max-width: 42rem` de una sola columna

**Decisión**: El contenedor pasa de `class="col-12 col-lg-8" style="max-width: 42rem;"` a `class="col-12"`
sin límite de ancho adicional (el `container-xl` del layout ya acota el ancho máximo global).

**Rationale**: `max-width: 42rem` fue elegido en su momento (specs/007) para una sola columna angosta tipo
formulario — con dos columnas de tarjetas sustanciales lado a lado, ese límite dejaría la pantalla
innecesariamente comprimida. `DESIGN.md` ya distingue entre páginas de una sola columna (`42rem`–`48rem`) y
superficies que combinan varias tarjetas/columnas (ancho completo, ej. el árbol de locaciones) — esta
pantalla pasa a esa segunda categoría.

**Alternatives considered**: Mantener `max-width: 42rem` y comprimir las dos columnas dentro de ese ancho:
descartado — anularía el propósito mismo de la feature (ver resumen y pagos lado a lado con espacio
legible).
