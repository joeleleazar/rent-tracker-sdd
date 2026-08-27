# Research: Más Espacio para Firmar y Aprovechamiento Horizontal en el Comprobante de Pago

## Decisión 1: Grid Bootstrap `row`/`col-md-7`/`col-md-5`, no CSS a medida

**Decisión**: El documento pasa de `max-width: 42rem` (columna única) a `max-width: 56rem`, con el
contenido principal y la lista de pagos dentro de un `row` de Bootstrap (`col-md-7` / `col-md-5`).

**Rationale**: Es el mecanismo responsive ya usado en absolutamente todo el resto del proyecto (Principio
VI) — se apila solo por debajo de `768px` sin escribir ningún media query propio, resolviendo FR-004/FR-005
gratis. `56rem` es un punto intermedio deliberado entre el `42rem`/`48rem` que usan las páginas de una sola
columna (formularios, detalle de recibo) y el ancho completo que usan las superficies tabulares (árbol de
locaciones) — este documento pasa a ser un híbrido (prosa + una lista corta), no una tabla densa.

**Alternatives considered**: Flexbox a medida (`d-flex flex-row`) con anchos fijos en `style`: descartado —
el grid de Bootstrap ya resuelve el apilamiento responsive sin código adicional; usar flexbox a medida
obligaría a reimplementar ese comportamiento a mano.

## Decisión 2: El pago actual se marca con badge + texto, no solo con color

**Decisión**: Dentro de la lista de pagos, el que corresponde a este comprobante lleva un
`<span class="badge bg-primary">Este pago</span>` junto a su fecha, además de negrita en su monto.

**Rationale**: FR-003 exige que se distinga "visualmente"; el principio de diseño del proyecto (Principio
III/VI) exige que ningún estado se comunique solo por color — un comprobante impreso en blanco y negro debe
seguir identificando cuál es "este pago" sin depender del color azul del badge. El texto "Este pago" resuelve
eso independientemente de cómo se imprima.

**Alternatives considered**: Solo resaltar con `bg-primary bg-opacity-10`/`text-primary` sin etiqueta de
texto: descartado — no comunica nada en una impresión en blanco y negro ni a un lector de pantalla.

## Decisión 3: Firma — área en blanco explícita, no solo un margen mayor

**Decisión**: Antes de la línea de firma se agrega un bloque vacío de altura fija (`height: 5rem`), además
de conservar el margen ya existente, en vez de simplemente aumentar la clase de margen (`mt-2` → `mt-5`).

**Rationale**: Un margen de Bootstrap (máximo `mt-5` = 3rem) por sí solo podría no sentirse "notoriamente
mayor" (SC-001) frente al espacio casi nulo actual, y su tamaño relativo depende de la escala de espaciado
del proyecto (`$spacer` personalizado, specs/010). Una altura fija en rem garantiza un área de firma
predecible tanto en pantalla como en la impresión (FR-001, Acceptance Scenario 2), sin depender de cuánto
contenido haya alrededor.

**Alternatives considered**: Aumentar solo a `mt-5`: descartado por la razón anterior — no garantiza una
altura mínima predecible, y developer sky/ escala de espaciado del proyecto ya multiplica los pasos base por
1.75, así que `mt-5` no es necesariamente "notoriamente mayor" a simple vista.

## Decisión 4: Orden de la lista de pagos — cronológico ascendente (mismo criterio que specs/036)

**Decisión**: La lista de pagos se ordena por `id` ascendente (orden de registro), igual que el criterio ya
establecido en specs/036 para "hasta ese pago inclusive".

**Rationale**: Consistencia con la decisión ya tomada y documentada en specs/036 (orden de registro, no
`fecha_pago`, para no depender de una fecha retroactiva) — reutilizar el mismo criterio en toda la vista
evita que dos partes del mismo documento ordenen los pagos de forma distinta.

**Alternatives considered**: Orden descendente (más reciente primero), como ya usa la lista de pagos del
detalle de recibo (`recibos/show.blade.php`): descartado para esta lista en particular — un orden
cronológico ascendente narra mejor la secuencia de pagos como "historia" de este recibo, que es el propósito
de mostrarla aquí (contexto), a diferencia de la lista operativa del detalle de recibo (donde lo más
reciente al frente es más útil para gestionar).
