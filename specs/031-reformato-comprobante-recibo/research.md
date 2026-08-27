# Research: Reformato de Jerarquía Visual del Comprobante de Recibo

## Decisión 1 — Reemplazar el `<dl>` plano por 6 bloques `<section>` separados con `<hr>` sutil

**Decisión**: La estructura actual (un único `<dl>` con todas las filas "Locación", "Inquilino", "Periodo",
"Fecha de emisión", "Estado", "Alquiler", cada concepto y el total, todas al mismo nivel) se reemplaza por 6
`<section class="bloque bloque--*">` (encabezado, metadatos, partes, conceptos, total, cierre), separadas
entre sí por un `<hr class="separador-bloque">` de línea fina (no un simple margen) para que el límite entre
bloques sea inequívoco incluso en escala de grises al imprimir.

**Rationale**: FR-001 y FR-009 piden explícitamente un orden fijo de bloques y una separación visible entre
cada uno — el `<dl>` plano actual no distingue "esto es un dato del recibo" de "esto es un dato de las
partes" de "esto es un concepto cobrado", todo se lee igual. Agrupar en `<section>` con nombres de bloque
en las clases (ver contracts/estructura-comprobante.md) también deja el documento fácil de recorrer para
`impeccable` y para los tests de Feature, que pueden anclarse a la presencia/orden de cada bloque.

**Alternativas consideradas**: Mantener el `<dl>` único y solo agregar más `margin-bottom` entre grupos de
filas — descartada porque un espacio en blanco por sí solo, sin una línea divisoria, es ambiguo entre "es un
salto de línea normal" y "es un límite de sección" (Principio III, claridad de jerarquía visual).

## Decisión 2 — Filas etiqueta/valor y conceptos como flex de dos columnas, montos en columna fija a la derecha

**Decisión**: Toda fila de metadatos (número de recibo, fecha de emisión, período), datos de pago y cada
línea de concepto usa `display: flex; justify-content: space-between;` con la etiqueta/nombre a la
izquierda y el valor/monto a la derecha en un `<span>` con `text-align: right` y
`font-variant-numeric: tabular-nums` (ya usado en `dd` actualmente) — todas las filas del documento
comparten el mismo padding horizontal del contenedor `.recibo`, así que sus valores de la derecha quedan
alineados en el mismo eje vertical entre bloques distintos.

**Rationale**: FR-007 y spec.md SC-003 exigen que todos los montos —metadatos si aplica, conceptos y
total— queden en una misma columna fija a la derecha. `flex` con `justify-content: space-between` dentro de
un contenedor de ancho consistente logra esto sin necesitar una tabla HTML real (que complicaría el ajuste
responsive de nombres de concepto largos, ya cubierto por el diseño actual con `flex-wrap` implícito en
texto).

**Alternativas consideradas**: Una tabla HTML (`<table>`) con columnas Concepto/Monto — descartada porque
las notas descriptivas de cada concepto (ya presentes hoy) necesitan su propia línea debajo del nombre, lo
que una tabla de filas simples no acomoda tan bien como un `flex` de dos columnas con contenido apilable en
la columna izquierda.

## Decisión 3 — Bloque de total con fondo `#1e40af` (mismo azul ya usado en `.btn-primario`), no un tono nuevo

**Decisión**: El bloque de total se destaca con un fondo de color sólido (`#1e40af`, el mismo azul ya usado
en `.btn-primario` de este mismo archivo) y texto blanco, en el tamaño de fuente más grande del documento
(tercer y último nivel de la jerarquía tipográfica, Decisión 4) — no solo negrita sobre fondo blanco como
hace la versión actual (`.fila-total`, borde superior + texto más grande).

**Rationale**: FR-008 exige que el total se distinga "mediante una combinación" de tamaño, peso y fondo/
borde — usar un color ya presente en el propio archivo (en vez de introducir un tono nuevo) mantiene la
paleta del documento consistente y evita tener que volver a verificar contraste WCAG AA desde cero: el
mismo `#1e40af` sobre blanco ya se usa hoy para el botón "Imprimir Recibo", con contraste suficiente para
texto, y blanco sobre `#1e40af` cumple igual de bien en el sentido inverso.

**Alternativas consideradas**: Mantener solo negrita + tamaño mayor sin fondo (como hoy) — descartada
explícitamente por spec.md FR-008/User Story 2: un total que compite en el mismo tratamiento visual (color
de texto, sin fondo) que el resto del documento no cumple "el único elemento que debería saltar a la vista
de inmediato".

## Decisión 4 — Jerarquía tipográfica de exactamente 3 niveles

**Decisión**:

1. **Título del documento** (encabezado): el tamaño más grande después del total, en negrita.
2. **Texto base** (etiquetas de bloque, etiquetas de fila, valores, notas de concepto): un único tamaño
   base para todo el cuerpo del documento; dentro de este nivel, las etiquetas de bloque (ej. "DETALLE DE
   CONCEPTOS") se distinguen únicamente por mayúsculas, `letter-spacing` y un color más tenue — nunca por un
   tamaño de fuente distinto — y las etiquetas de fila se distinguen de sus valores únicamente por peso de
   fuente (semibold vs. regular), tampoco por tamaño.
3. **Total**: el tamaño más grande de todo el documento.

**Rationale**: FR-010 limita la jerarquía a "un máximo de tres tamaños/pesos de fuente diferenciados". La
lectura de esta regla que permite cumplir simultáneamente con el resto de requisitos (etiquetas de bloque
distinguibles del texto de detalle, FR-001; etiquetas de fila distinguibles de sus valores, ya un patrón
existente en el archivo) es tratar "tamaño" como el eje que sí está limitado a 3 escalones (título, base,
total), y usar mayúsculas/`letter-spacing`/color/peso como variaciones *dentro* del nivel base para lograr
distinción sin agregar una cuarta escala de tamaño — el mismo truco tipográfico ya usado hoy en el archivo
(`dt` en semibold vs. `dd` en regular, mismo `font-size: 1rem` ambos).

**Alternativas consideradas**: Un cuarto tamaño intermedio dedicado a las etiquetas de bloque (más grande
que las etiquetas de fila, más chico que el título) — descartada por violar literalmente el límite de 3
tamaños que pide FR-010, y por ser innecesaria dado que mayúsculas + `letter-spacing` ya logran la misma
distinción sin sumar una escala.

## Decisión 5 — El encabezado agrupa logo + título; el número de recibo se muda al bloque de metadatos

**Decisión**: El `<h1>Recibo #{{ $recibo->id }}</h1>` actual se reemplaza por un encabezado que agrupa el
logo (ya existente, specs/030) junto al título genérico "Recibo de Pago"; el número específico del recibo
(`#{{ $recibo->id }}`) se muestra como su propia fila etiqueta/valor ("N.° de recibo") dentro del bloque de
metadatos, junto a fecha de emisión y período.

**Rationale**: Spec.md FR-002/FR-003 separan explícitamente "nombre del documento" (qué tipo de documento
es, siempre igual) de "metadatos del recibo" (número, fecha, período — lo que varía por cada recibo
emitido) como dos bloques distintos con propósitos distintos; mezclar el número dentro del título (como
hoy) confunde ambos propósitos en un solo elemento.

**Alternativas consideradas**: Mantener "Recibo #1" como título y agregar además una fila redundante de
"N.° de recibo" en metadatos — descartada por duplicar el mismo dato dos veces sin necesidad, cuando
separar completamente ambos roles ya resuelve el requisito con una sola fuente de verdad por dato.

## Decisión 6 — Nuevo campo `nombre_propietario` en `ConfiguracionGeneral`, sin migración de esquema

**Decisión**: Se agrega `'nombre_propietario'` a `ConfiguracionGeneral::$fillable` y a
`valoresPorDefecto()` (valor por defecto: `null`), siguiendo exactamente el mismo patrón clave-valor que
las 4 claves ya existentes (specs/018) — sin crear ninguna migración de esquema, solo una fila nueva en
`configuracion_general` la primera vez que se guarda un valor. El formulario de Configuración
(`configuracion/edit.blade.php`) gana un campo de texto nuevo, y `SolicitudActualizarConfiguracionGeneral`
gana su regla de validación (`nullable`, `string`, `max:255` — no `required`, para no romper instalaciones
existentes que todavía no lo configuraron).

**Rationale**: Es exactamente el caso de uso que motivó el rediseño clave-valor de specs/018 ("agregar un
parámetro nuevo en el futuro es insertar una fila, no una migración de esquema") — usarlo tal como está
documentado evita cualquier migración nueva. `nullable` en vez de `required` resuelve directamente FR-005a
(el comprobante debe seguir siendo válido si todavía no se configuró): el comprobante omite la fila
"Recibido por" cuando el valor es `null`/vacío, en vez de mostrar un valor por defecto engañoso (un nombre
inventado) o bloquear la emisión del recibo.

**Alternativas consideradas**: Un valor por defecto no vacío (ej. `config('app.name')`) — descartada por la
propia respuesta del usuario a la aclaración de spec.md (Q1): el nombre de quien recibe el pago debe ser un
dato de negocio configurado explícitamente, no una inferencia del nombre interno del sistema.

## Decisión 7 — Cierre: solo frase de agradecimiento, sin espacio de firma en esta iteración

**Decisión**: El bloque de cierre incluye únicamente una frase breve de agradecimiento en cursiva; no se
agrega una línea de firma física.

**Rationale**: Spec.md marca el espacio de firma como explícitamente opcional (FR-011) y la propia sección
de Assumptions de spec.md ya resuelve esa opcionalidad a favor de omitirlo, dado que el comprobante se
comparte mayormente de forma digital (impresión y WhatsApp) donde una línea de firma en blanco no cumple
ninguna función real. Mantiene el bloque de cierre simple, consistente con Decisión 4 (no sumar elementos
tipográficos nuevos sin necesidad).

**Alternativas consideradas**: Agregar una línea de firma junto a la frase de cierre "por si acaso" —
descartada por no aportar valor verificable (nadie firma sobre una imagen capturada por html2canvas ni
sobre la mayoría de las impresiones que terminan compartidas digitalmente) y por sumar un elemento visual
extra sin un requisito que lo pida de forma obligatoria.
