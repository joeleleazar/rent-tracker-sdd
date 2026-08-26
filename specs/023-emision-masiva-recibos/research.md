# Research: Emisión Masiva de Recibos por Periodo

## Decisión 1: reemplazo de la regla "un solo recibo por locación y periodo"

**Decision**: `ServicioGeneracionReciboPeriodo::generar()` deja de chequear "¿ya existe algún recibo para esta
locación y periodo?" y pasa a chequear "¿alguno de los conceptos que quiero incluir ya está cubierto por un
recibo existente de esta locación y periodo?". La excepción `ReciboDuplicadoPeriodoException` se reemplaza
por `ConceptosReciboYaCubiertosException`, que lleva la lista de claves de concepto superpuestas (ej.
`['incluye_alquiler']`) y la colección de recibos existentes que las cubren (para poder enlazarlos en el
mensaje/UI, FR-008/FR-010).

**Rationale**: es el cambio de fondo pedido por spec.md FR-007/FR-008. No hay ninguna constraint de base de
datos que lo impida hoy (ver Decisión 2), así que el cambio es puramente de lógica de aplicación.

**Alternatives considered**: mantener `ReciboDuplicadoPeriodoException` intacta y agregar una excepción
nueva en paralelo solo para el flujo masivo — descartada porque duplicaría la regla de negocio en dos
lugares (Assumption A-003 exige que el flujo individual de edición también la respete), violando la misma
lección ya aprendida en specs anteriores de este proyecto sobre no duplicar lógica de cálculo entre
controladores.

## Decisión 2: sí hace falta una migración — había una constraint `UNIQUE` que no se había detectado

**Decision**: se agrega la migración `2026_08_26_000000_quitar_unique_locacion_periodo_de_recibos` que quita
la constraint `UNIQUE(locacion_id, periodo)` de `recibos` y la reemplaza por un índice no único equivalente.

**Actualización (descubierta al ejecutar T002, no en la planificación original)**: la primera versión de
esta decisión afirmaba que no hacía falta ninguna migración, basada en revisar solo
`create_recibos_table.php` y `agregar_indices_llaves_foraneas.php`. Esa revisión fue incompleta: la migración
`2026_08_21_042852_add_conceptos_a_recibos_table.php` (specs/004, FR-009) sí agrega
`$table->unique(['locacion_id', 'periodo'])` explícitamente. El primer test que intentó crear un segundo
recibo válido para la misma locación y periodo con conceptos distintos falló con
`SQLSTATE[23505]: Unique violation` antes de llegar siquiera a evaluar la lógica de aplicación — confirmando
que la restricción era real y activa en la base de datos, no solo una regla de `ServicioGeneracionReciboPeriodo`
como se había asumido. Se corrige aquí la decisión y se documenta la migración correctiva.

**Rationale**: la constraint `UNIQUE(locacion_id, periodo)` fue exactamente la implementación elegida en
specs/004 para "un solo recibo por locación y periodo" (FR-009 de esa spec) — un requisito que esta feature
reemplaza deliberadamente (spec.md FR-007). Levantarla es, por lo tanto, un cambio de esquema necesario, no
opcional.

**Alternatives considered**: agregar una constraint `EXCLUDE` de PostgreSQL sobre los 5 booleanos para que la
propia base de datos garantice la no-superposición — descartada por complejidad desproporcionada (una
`EXCLUDE` constraint sobre columnas booleanas para modelar "conjuntos disjuntos" no es un patrón estándar de
Postgres) frente a resolverlo con `lockForUpdate()` dentro de una transacción (Decisión 3), que ya es
suficiente para el volumen de uso de este sistema (panel administrativo de una sola organización, no un
sistema de alta concurrencia).

## Decisión 3: condición de carrera (FR-008) resuelta con `lockForUpdate()`

**Decision**: dentro de `DB::transaction()`, antes de insertar el nuevo recibo, `generar()` hace
`Recibo::where('locacion_id', ...)->where('periodo', ...)->lockForUpdate()->get()` para releer el estado
real y recalcular los conceptos ya cubiertos con un bloqueo de fila que serializa confirmaciones concurrentes
de la misma locación y periodo. Si el conjunto recién leído se superpone con los conceptos solicitados,
lanza `ConceptosReciboYaCubiertosException` y la transacción se revierte sin insertar nada.

**Rationale**: como no hay una constraint de BD (Decisión 2), la única forma de blindar la regla ante dos
confirmaciones casi simultáneas (spec.md FR-008, Historia 3 Escenario 5) es serializarlas explícitamente con
un bloqueo de fila dentro de la transacción — mismo patrón (`DB::transaction` + relectura antes de insertar)
que ya usa este mismo servicio para otras invariantes, y consistente con el Principio V de la constitución.

**Alternatives considered**: confiar solo en la relectura sin `lockForUpdate()` (ventana de carrera entre el
`SELECT` y el `INSERT` de dos transacciones concurrentes) — descartada por ser exactamente el escenario que
FR-008 pide blindar explícitamente.

## Decisión 4: el modal se carga por locación vía htmx, no se pre-renderizan N modales

**Decision**: la pantalla tiene un único contenedor de modal compartido (`<div id="modal-recibo">`, usando
`<x-modal-bootstrap>`); el botón "Generar Recibo" de cada fila dispara `hx-get` a
`recibos.registroMasivo.modal` (con `locacion` y `periodo`), que devuelve la parcial `modal-recibo.blade.php`
con los conceptos disponibles de esa locación y sus montos sugeridos, y la swapea dentro de ese contenedor
compartido antes de abrirlo (vía un pequeño listener JS en `htmx:afterSwap`, igual de acotado al patrón ya
usado en `resources/js/registro-masivo-lecturas.js`).

**Rationale**: pre-renderizar un modal Bootstrap completo por cada locación (decenas de nodos, como en
"Registro Masivo de Lecturas") infla el HTML de la página proporcionalmente al número de locaciones aunque
la mayoría nunca se abra, y el contenido de cada modal (montos sugeridos, conceptos disponibles) puede
quedar desactualizado si el usuario genera un recibo de otra fila y vuelve a esa locación sin recargar. Un
modal compartido cargado bajo demanda evita ambos problemas y es coherente con la "Excepción de
interactividad asíncrona" del Principio VI (htmx, no Alpine.js).

**Alternatives considered**: un modal estático por fila (como el único modal ya existente de
`locaciones/show.blade.php`, que es de un solo ítem) — descartado por no escalar a una lista de decenas de
locaciones.

## Decisión 5: la confirmación del modal actualiza la fila vía swap de htmx, no redirect

**Decision**: el `POST` del modal (`recibos.registroMasivo.store`) responde con la parcial de la fila
actualizada de esa locación (mismos datos que ya usa `fila-registro-masivo-recibos.blade.php`), apuntada por
`hx-target` a la fila correspondiente (`id="fila-recibo-{locacion}"`), y un pequeño script cierra el modal
tras el swap exitoso (`htmx:afterRequest` sobre el formulario del modal, análogo a como
`registro-masivo-lecturas.js` ya reacciona a eventos de htmx para actualizar totales en vivo).

**Rationale**: es el comportamiento pedido explícitamente en el `/speckit-clarify` de esta spec ("se genera
al confirmar el modal... la fila se actualiza sola"), y reutiliza el mismo patrón ya probado en
`RegistroMasivoLecturasController::actualizarInline()` (responde con la parcial, no con un redirect).

**Alternatives considered**: redirigir a `recibos.show` tras generar — descartada explícitamente por la
Historia 2 (Acceptance Scenario 3: "sin haber salido de la pantalla").

## Decisión 6: montos sugeridos reutilizan los servicios ya existentes, sin duplicar cálculo

**Decision**: el endpoint del modal reutiliza `ServicioCalculoProrrateoContrato::calcular()` (renta) y
`ServicioGeneracionReciboPeriodo::calcularMontoLuzSugerido()` (luz), y lee `costo_agua`/`costo_pasadizo`/
`costo_seguridad` directamente del contrato activo — exactamente la misma lógica que ya usa
`locaciones/recibos/create.blade.php` (flujo individual), sin reimplementarla.

**Rationale**: evita una tercera fuente de verdad para "cuánto sugerir por concepto" (ya hay dos: el flujo
individual y, antes de esta decisión, ninguna otra) — consistente con la lección ya aplicada en specs/022
(Decisión 1) de reutilizar cálculos existentes en vez de duplicarlos.

**Alternatives considered**: N/A — no hay otra fuente de este cálculo en el proyecto.

## Decisión 7 (descubierta al ejecutar T013): orden de rutas — `/recibos/registro-masivo` debe registrarse antes de `/recibos/{recibo}`

**Decision**: las 3 rutas `recibos.registroMasivo.*` se registran en `routes/web.php` inmediatamente después
de `locaciones.recibos.store` y **antes** de `Route::get('/recibos/{recibo}', ...)->name('recibos.show')`.

**Rationale**: Laravel resuelve rutas en el orden en que se registran, no por especificidad. Con
`/recibos/{recibo}` registrada primero (como estaba, más abajo en el archivo, junto a las demás rutas de
recibos), una petición a `/recibos/registro-masivo` calzaba con ese patrón primero, y Laravel intentaba
bindear la cadena literal `"registro-masivo"` como si fuera el `{recibo}` (un id numérico) — el primer test
de T011 falló con `SQLSTATE[22P02]: Invalid text representation... bigint` antes de llegar siquiera al nuevo
controlador. Se corrigió moviendo el bloque de rutas nuevas por delante, con un comentario explícito en el
archivo advirtiendo por qué el orden importa ahí.

**Alternatives considered**: cambiar el patrón de `/recibos/{recibo}` a `/recibos/{recibo:id}` (constraint de
tipo) — descartado por ser un cambio más amplio a una ruta ya estable y usada en todo el proyecto, cuando
reordenar dos bloques de rutas nuevas resuelve el problema sin tocar nada existente.

## Decisión 8 (descubierta al ejecutar T031, validación manual): faltaba el enlace de navegación

**Decision**: se agrega un ítem "Emitir Recibos" (`bi-receipt`) al menú lateral
(`resources/views/components/layouts/app-bootstrap.blade.php`), junto a "Registrar Lecturas".

**Rationale**: ninguna tarea de `tasks.md` incluía explícitamente agregar el enlace de navegación — la
pantalla nueva solo era alcanzable escribiendo la URL a mano. Se detectó recién al validar manualmente
quickstart.md Escenario 1 en el navegador (T031), no en ningún test automatizado (ninguno de los tests de
`RegistroMasivoRecibosControllerTest` verifica la navegación global). Se corrige siguiendo exactamente el
mismo patrón ya usado por "Registrar Lecturas" (mismo tipo de `<li>`, mismo criterio de estado activo vía
`request()->routeIs(...)`).

**Alternatives considered**: N/A — omisión a corregir, no una decisión de diseño con alternativas.

## Decisión 9 (corregida tras revisión, validación manual T031): "Luz de Pasadizo" es el nombre correcto del concepto — no un bug

**Nota de proceso**: esta decisión reemplaza una nota anterior que señalaba erróneamente
`locaciones/recibos/show.blade.php` (línea 63, "Monto de Luz de Pasadizo") como un bug de copiar-pegar
ajeno a esta feature. Al revisar el resto de la app antes de "corregirlo" a pedido del usuario, se encontró
que **"Luz de Pasadizo" es el nombre ya establecido del concepto** en 3 de los 4 lugares donde aparece
(`locaciones/recibos/create.blade.php` y `edit.blade.php`: "Incluir Luz de Pasadizo";
`locaciones/recibos/comprobante.blade.php`: "Luz de Pasadizo"; `show.blade.php`: "Monto de Luz de Pasadizo")
— solo el campo de costo del contrato (`contratos/partials/costos-fijos-contrato.blade.php`,
`contratos/show.blade.php`) usa la forma corta "Pasadizo". `show.blade.php` estaba correcto; la nota
original fue un falso positivo.

**Decision**: se corrige `ConceptosReciboYaCubiertosException::ETIQUETAS['incluye_pasadizo']` de `'pasadizo'`
a `'luz de pasadizo'`, alineando las etiquetas nuevas de esta feature (badges de
`estado-recibo-locacion.blade.php`, checkbox de `modal-recibo.blade.php`, mensaje de
`ConceptosReciboYaCubiertosException`) con el nombre ya establecido en el resto del sistema, en vez de tocar
`show.blade.php` (que no tenía ningún defecto).

**Rationale**: la inconsistencia real estaba en el código nuevo de esta spec, no en el código preexistente —
corregirla ahí es lo correcto, y evita introducir una segunda forma de nombrar el mismo concepto en el
sistema.
