# Research: Lectura Anterior por Defecto y Total Editable y Persistido

## Contexto

El Technical Context del plan no dejó `NEEDS CLARIFICATION` de stack — las 3 preguntas de alcance
(Q1-Q3 de `/speckit-specify`) ya se resolvieron. Esta investigación documenta cómo implementar esas
decisiones reutilizando el código real ya existente, leído directamente del repositorio (no de
memoria de sesiones anteriores, dado que specs/018-optimizacion-esquema-postgresql modificó
`RegistroMasivoLecturasController`, `ConfiguracionGeneral` y el esquema entre medio).

**Nota de entorno**: igual que specs/016/017, usar el binario de PHP de Herd
(`C:\Users\joel5\.config\herd\bin\php.bat`) para `artisan`/`pest` en esta máquina.

## Decisión 1: dónde vive la generación del monto de luz de un recibo

- **Decision**: El único punto de lectura de "monto de luz sugerido" en todo el sistema es
  `ServicioGeneracionReciboPeriodo::calcularMontoLuzSugerido()`
  (`app/Services/ServicioGeneracionReciboPeriodo.php:104-113`), llamado únicamente desde
  `ReciboController` (línea 58) para prellenar el formulario de generación de recibo. Hoy calcula
  `consumo_calculado × tarifa_vigente_actual`; pasa a leer `lectura->total` directamente, sin
  multiplicar nada.
- **Rationale**: Confirmado por búsqueda en todo `app/` y `resources/` — no hay otro lugar que
  recalcule el monto de luz. El monto final de un recibo (`monto_luz`) ya era, desde antes de esta
  feature, un valor que el usuario podía editar en el formulario de recibo (`generar()`/
  `actualizar()` reciben `$datos['monto_luz']` del propio formulario, no lo calculan) — esta
  feature solo cambia cuál es el valor *sugerido* que prellena ese formulario, no agrega una
  edición nueva ahí.
- **Alternatives considered**: Ninguna — es un cambio de una sola línea en un único método ya
  identificado, no hay ambigüedad de dónde aplicarlo.

## Decisión 2: el total se guarda tal cual llega, con fallback en servidor si no llega

- **Decision**: `RegistroMasivoLecturasController::store()` ya prefetch-ea locaciones/lecturas del
  periodo/lecturas anteriores antes del `foreach` (specs/018, líneas 74-91) — se agrega, por fila:
  `$lecturaAnterior = $lecturaAnterior ?? 0.0` (FR-001) y `$total = is_numeric($datosFila['total']
  ?? null) ? (float) $datosFila['total'] : round($consumo * $tarifa, 2)` (FR-003/FR-004). El
  servidor no necesita distinguir "el usuario lo editó" de "lo dejó como estaba" — el campo del
  formulario ya llega con el valor final (editado o no, porque el input siempre tiene un valor
  precargado por JS), y si llega vacío o no numérico (JS deshabilitado), el servidor recalcula el
  mismo valor sugerido que el navegador habría mostrado.
- **Rationale**: Mismo principio de "degradación elegante sin JavaScript" ya documentado para el
  resto del registro masivo (Principio VI de la constitución, specs/011). Evita un estado
  intermedio ambiguo ("¿está vacío porque el usuario lo borró a propósito, o porque JS no corrió?")
  tratando ambos casos igual: sin un valor numérico utilizable, se usa el sugerido.
- **Alternatives considered**: Agregar un campo oculto `total_editado_manualmente` (booleano) para
  que el servidor sepa si debe recalcular o respetar el valor enviado — descartado por
  innecesariamente complejo: el servidor nunca necesita "recalcular y descartar lo que el usuario
  escribió", solo necesita un valor numérico usable, que siempre está en `total` salvo que JS haya
  fallado.

## Decisión 3: el input de Total no se sobrescribe una vez que el usuario lo tocó

- **Decision**: En `resources/js/registro-masivo-lecturas.js`, `recalcularTotales()` deja de
  escribir directamente sobre el elemento `total-fila-{id}` cuando ese elemento es un `<input>`
  (fila pendiente) que el usuario ya editó a mano. Se agrega un listener `input` sobre el propio
  campo de total que marca `dataset.editadoManualmente = 'true'` la primera vez que su valor
  cambia por tipeo del usuario; `recalcularTotales()` solo sobrescribe `.value` de ese input si
  `dataset.editadoManualmente` no está presente. Para una fila ya completada (el elemento sigue
  siendo un `<div>` de solo lectura, sin input), el comportamiento no cambia.
- **Rationale**: Sin esto, cada vez que el usuario tipea en "Lectura Actual" de *cualquier* fila,
  `recalcularTotales()` recorre *todas* las filas y sobrescribiría el total que el usuario ya había
  editado a mano en otra fila — perdiendo su edición sin que lo note. Es el mismo tipo de defecto
  de sincronización que motivó specs/016, así que se resuelve explícitamente aquí en vez de
  descubrirse después.
- **Alternatives considered**: Solo recalcular el total sugerido al cargar la página (no en cada
  tipeo de "Lectura Actual") — descartado porque el usuario todavía necesita ver el total sugerido
  actualizarse mientras completa la lectura por primera vez, antes de decidir si lo edita; la
  distinción correcta no es "cuándo recalcular" sino "una vez que el usuario editó este campo en
  particular, dejar de tocarlo".

## Decisión 4: el borrador (autoguardado) también protege un total editado a mano

- **Decision**: `borradores_lectura_medidor` agrega una columna `total` (nullable, sin índice
  nuevo). `guardarBorrador()` incluye `total` en el `map()`/`upsert()` ya existente (mismo patrón
  que `lectura_actual`, filtrando solo si es numérico); `campo-lectura-registro-masivo.blade.php` /
  `fila-registro-masivo.blade.php` prellenan el input de total con `old($clave, $borrador?->total)`
  al reabrir la pantalla, igual que ya hace hoy "Lectura Actual" con `$borrador?->lectura_actual`.
- **Rationale**: El `hx-include="#formulario-registro-masivo"` del autoguardado (specs/015
  Decisión 4) ya envía el valor del nuevo input de total al servidor sin cambios de marcado — el
  campo llega en cada ciclo de autoguardado aunque el backend todavía no lo procese. No guardarlo
  dejaría un hueco idéntico en espíritu al que motivó specs/016 (un dato que el usuario ya escribió
  y que el sistema podría perder sin avisar) apenas se soltara esta feature. El costo de incluirlo
  es mínimo: una columna más en una tabla ya diseñada exactamente para este propósito.
- **Alternatives considered**: No tocar `borradores_lectura_medidor` y dejar que el total editado
  se pierda si el usuario cierra sin guardar el lote — descartado porque es un defecto previsible y
  barato de evitar, no una ampliación de alcance: el propio dato ya viaja en cada petición de
  autoguardado sin ningún cambio adicional, solo falta persistirlo del lado del servidor.

## Decisión 5: `consumo_calculado` no se toca (FR-007 no implica eliminarlo)

- **Decision**: La columna `consumo_calculado` de `lecturas_medidor` y su escritura en
  `store()`/`actualizarInline()` se mantienen exactamente como están hoy. FR-007 ("no es necesario
  almacenar el consumo") se interpreta como "esta feature no necesita agregar un nuevo mecanismo de
  almacenamiento de consumo para calcular el total" — no como un mandato de eliminar la columna ya
  usada por specs/006 (`discrepanciaConSiguiente()` no la usa directamente, pero sí la columna
  Consumo del registro masivo de specs/017 y la exportación Excel/PDF de specs/015).
  `actualizarInline()` (edición en línea de una lectura ya guardada) tampoco toca `total`: por Q2,
  la edición de total después de guardado queda fuera de alcance, así que ese flujo solo sigue
  actualizando `lectura_actual` y `consumo_calculado` como ya hace.
- **Rationale**: Eliminar `consumo_calculado` sería un cambio de esquema mucho más amplio (toca
  vistas y exportaciones de specs/006/015/017 que hoy leen esa columna directamente) para un
  beneficio que el spec no pide — FR-007 es una aclaración de alcance ("no hace falta guardar nada
  nuevo para esto"), no un requisito de limpieza de columnas existentes.
- **Alternatives considered**: Eliminar `consumo_calculado` y calcular el consumo siempre al vuelo
  desde `lectura_actual`/`lectura_anterior` — descartado por alcance: ninguna Acceptance Scenario
  de la spec pide eliminar nada, y hacerlo tocaría código de 3 specs anteriores sin necesidad.
- **Actualización (specs/021)**: esta decisión quedó superada — `specs/021-derivar-consumo-calculado`
  eliminó la columna y la reemplazó por un accessor de Eloquent, exactamente la alternativa
  descartada acá. Se documenta en su momento (2026-08-25) que ya no era "fuera de alcance": el
  propio patrón de duplicación que esta Decisión 5 decidió no tocar terminó siendo la motivación
  explícita de esa feature posterior.

## Decisión 6: backfill de `total` para lecturas históricas (FR-008)

- **Decision**: La migración que agrega `total` a `lecturas_medidor` completa, en la misma
  migración (mismo patrón que
  `2026_08_21_044219_refine_lecturas_medidor_anterior_actual.php`, que ya hizo un backfill fila por
  fila dentro de `up()`), el total de cada fila existente:
  `total = round((consumo_calculado ?? lectura_actual) * tarifa_vigente_actual, 2)` — usando
  `consumo_calculado` si ya existe (la mayoría de las filas), o `lectura_actual` completo si esa
  fila nunca tuvo lectura anterior (mismo criterio de "anterior = 0" de FR-001, aplicado
  retroactivamente para que ninguna fila quede sin total, SC-002). La tarifa usada es la vigente al
  momento de correr la migración, leída de `ConfiguracionGeneral::actual()->tarifa_luz_por_unidad`
  (specs/018, interfaz pública sin cambios) — no existe una tarifa histórica guardada (ver
  Assumptions de spec.md), así que no hay una alternativa más precisa disponible con los datos
  actuales.
- **Rationale**: Es la única fuente de tarifa que el sistema tiene. Documentar esta limitación en
  el propio comentario de la migración dispensa cualquier interpretación futura ambigua sobre por
  qué el total histórico puede no coincidir con lo que el inquilino realmente pagó en su momento.
- **Alternatives considered**: Dejar `total` nullable para filas históricas en vez de forzar un
  backfill — descartado porque contradice explícitamente FR-008/SC-002 (respuesta del usuario a
  Q3: backfill completo).

## Decisión 7: un total negativo no se rechaza aparte — hereda la confirmación de consumo negativo

- **Decision**: `store()` no valida el signo de `total` de forma independiente. El total sugerido
  que JS precarga en el input puede ser legítimamente negativo cuando el consumo de esa fila es
  negativo y el usuario ya lo confirmó (`confirmar_consumo_negativo`, specs/015 FR-008) — ese
  chequeo ya existe y ya rechaza la fila (`ConsumoNegativoSinConfirmarException`) antes de llegar a
  persistir nada, si el consumo es negativo y no está confirmado. Una vez que la fila superó ese
  chequeo, el total que llegue (editado o no, negativo o no) se persiste tal cual — no hay una
  regla de signo adicional específica para `total`.
- **Rationale**: El Edge Case de `spec.md` ("un total negativo debe rechazarse con el mismo
  criterio ya usado para los demás campos monetarios") se satisface mediante el mecanismo que ya
  existe para `lectura_actual`/consumo, no agregando un segundo chequeo de signo sobre `total` en
  paralelo — un total negativo válido (consumo negativo ya confirmado × tarifa) es exactamente el
  escenario que ese mecanismo ya está diseñado para permitir explícitamente. Lo único que sí se
  valida sobre `total` es que sea *numérico*; si no lo es (o falta), se usa el fallback calculado
  (Decisión 2) — nunca se rechaza la fila por esa causa.
- **Alternatives considered**: Agregar `min:0` a `total` igual que a `lectura_actual` —
  descartado porque rompería el caso ya soportado de consumo negativo confirmado (un total negativo
  ahí es correcto, no un error).

## Nota de implementación sobre FR-001: 0 es solo el insumo del cálculo, no lo que se persiste

FR-001 dice que el *consumo* se calcula con 0 como lectura anterior cuando no hay ninguna
registrada — no dice que la columna `lectura_anterior` de la fila nueva deba guardarse como `0` en
vez de `null`. Se persiste `null` igual que siempre (0.0 se usa únicamente como argumento de
`ServicioCalculoConsumoMedidor::calcularConsumo()`), porque `LecturaMedidor::
discrepanciaConSiguiente()` (specs/006) ya trata `lectura_anterior === null` como "sin dato, no hay
discrepancia que evaluar" — si se guardara `0` ahí, una lectura futura de esa misma locación
compararía su `lectura_actual` contra `0` y casi siempre reportaría una discrepancia falsa. La
columna "Lectura Periodo Anterior" de la pantalla tampoco se ve afectada por esta distinción: ya
resuelve "sin dato" consultando la lectura previa real de la locación, no releyendo esta columna de
la fila que se acaba de crear.

## Decisión 8 (descubierta al ejecutar T007): `LecturaMedidorController::store()` también necesita completar `total`

- **Decision**: Al correr la migración de T002 (`total` `NOT NULL`) contra la suite completa, no
  solo fallaron pruebas de registro masivo (esperado, se resuelve en la Fase 4/US2) sino también 3
  pruebas de `LecturaMedidorControllerTest` — el flujo **individual** de registro de una lectura
  (`LecturaMedidorController::store()`) también hace un `LecturaMedidor::create()` sin `total`, y
  al ser la misma tabla, la columna `NOT NULL` lo rompe igual. Se agrega un método privado
  `calcularTotal()` en ese controlador (consumo × tarifa vigente, `0.0` si no hay consumo
  calculable) — la misma fórmula que ya usaba `calcularMontoLuzSugerido()` antes de esta feature —
  sin agregar ningún campo de edición nuevo a la UI individual (Q1 de `/speckit-clarify`: FR-001,
  el default de 0 para lectura anterior, es exclusivo del registro masivo; esto no lo contradice,
  es una consecuencia distinta de que `total` sea `NOT NULL` en una tabla compartida).
- **Rationale**: No es una ampliación de alcance elegida — es la única forma de que el flujo
  individual (specs/005/006) siga funcionando después de que `total` pase a ser obligatorio en la
  tabla que ambos flujos comparten. `update()` no necesita el mismo ajuste: solo modifica una fila
  ya existente (que ya tiene un `total` persistido), sin volver a insertar.
- **Alternatives considered**: Dejar `total` nullable indefinidamente para no tocar el flujo
  individual — descartado porque contradice FR-008/SC-002 (ninguna lectura sin total) y porque el
  flujo individual también alimenta recibos vía `calcularMontoLuzSugerido()`, así que también se
  beneficia de tener su propio total ya fijado en vez de depender de la tarifa vigente al generar
  el recibo.

## Decisión 9 (descubierta en la verificación manual de T025): `calcularConsumoDeCampo()` también necesita el default de 0

- **Decision**: `resources/js/registro-masivo-lecturas.js` calculaba el consumo en vivo devolviendo
  `null` cuando `data-lectura-anterior` venía vacío (sin lectura previa) — el mismo criterio que
  specs/016/017 ya usaban correctamente para ESE caso, pero que specs/019 FR-001 cambia
  específicamente para el registro masivo. Sin este ajuste, la fila que motivó FR-001 (una
  locación sin ninguna lectura anterior) nunca mostraba un consumo ni un total sugerido mientras el
  usuario tipeaba — quedaban vacíos hasta guardar, aunque el servidor sí calculaba y persistía el
  valor correcto. Se cambia `leerNumero(campo.dataset.lecturaAnterior)` por
  `leerNumero(campo.dataset.lecturaAnterior) ?? 0`, espejando exactamente el cambio ya hecho en
  `RegistroMasivoLecturasController::store()` (T011).
- **Rationale**: Encontrado al verificar manualmente el Escenario 1 de `quickstart.md` en el
  navegador — Pest no puede detectar esto (no hay Dusk/Playwright, ver Technical Context de
  `plan.md`), que es precisamente por qué `quickstart.md` incluye este escenario como paso manual
  obligatorio antes de cerrar la feature.
- **Alternatives considered**: Ninguna — es la misma regla de negocio (FR-001) aplicada donde
  faltaba, no una decisión de diseño nueva.

## Decisión 10 (descubierta post-implementación, 2026-08-25): dos escrituras más sin `total`

- **Decision**: `app/Console/Commands/ImportarLecturasMedidorHistoricas.php` y
  `database/seeders/DatabaseSeeder.php` también hacen `LecturaMedidor::create()` sin `total` — a
  diferencia de la Decisión 8 (encontrada corriendo la suite durante T007), estos dos no tienen
  ninguna prueba automatizada que los ejercite, así que quedaron sin detectar hasta una revisión
  manual posterior al cierre de la feature. Se les agrega el mismo cálculo
  (`consumo × tarifa_vigente`, mismo criterio que `LecturaMedidorController::calcularTotal()`): el
  comando de importación usa `ConfiguracionGeneral::actual()->tarifa_luz_por_unidad` (única tarifa
  disponible, misma limitación ya documentada en la Decisión 6 de backfill); el seeder usa `0.85`,
  la misma tarifa que él mismo ya configura al principio de `run()`.
- **Rationale**: Sin este ajuste, ambos fallarían con un error de base de datos (`NOT NULL`) la
  próxima vez que alguien los corriera — un defecto real, aunque de bajo impacto inmediato (ninguno
  se ejecuta en el flujo normal de la aplicación, solo en importación puntual o al poblar un
  entorno nuevo). Confirma el patrón ya señalado en la Decisión 8: mapear "todo punto de escritura"
  por búsqueda estática (`grep`) es más confiable que depender de que la suite de pruebas los
  ejercite a todos — ninguno de estos dos archivos tiene cobertura de test propia.
- **Alternatives considered**: Ninguna — mismo tipo de corrección mecánica que la Decisión 8, no
  una decisión de diseño nueva.

## Brecha de pruebas a cerrar en tasks.md

Ninguna prueba actual cubre: (a) que una locación sin lectura anterior registre consumo = lectura
actual (FR-001); (b) que el total enviado por el formulario se persista tal cual (FR-003/FR-004);
(c) que el total persistido no cambie si la tarifa general cambia después (FR-005); (d) que
`calcularMontoLuzSugerido()` devuelva el total persistido en vez de recalcular consumo × tarifa
(FR-006); (e) que el backfill de la migración complete el total de filas históricas con y sin
`consumo_calculado` (FR-008).
