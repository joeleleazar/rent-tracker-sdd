# Research: Catálogo Dinámico de Conceptos de Gastos Fijos, Periodo Ágil y Totales por Locación

## Decisión 1: identificar "Renta" y "Luz" por una clave estable, no por el nombre

**Decision**: `conceptos_gasto_fijo` tiene una columna `clave` (string, nullable, única) — `'renta'` y `'luz'`
para los dos conceptos especiales, `null` para cualquier concepto regular (Agua, Luz de Pasadizo, Seguridad,
y cualquiera que el administrador agregue después). Toda la lógica que necesita distinguir "es Renta" o "es
Luz" (protección contra eliminación, exclusión de `contrato_valores_concepto`, fuente del monto sugerido)
compara contra `clave`, nunca contra `nombre`.

**Rationale**: FR-001 permite renombrar cualquier concepto (incluida "Renta" o "Luz", si el administrador
decidiera hacerlo por una razón de redacción); si el comportamiento especial dependiera del nombre exacto,
renombrar "Renta" a "Alquiler" rompería silenciosamente el prorrateo. Una clave estable, invisible para el
usuario final, desacopla el nombre visible del comportamiento del sistema — mismo patrón que ya usa este
proyecto para no acoplar lógica a texto editable por el usuario.

**Alternatives considered**: comparar por `id` fijo (ej. "el concepto con id=1 siempre es Renta") — descartado
por ser frágil ante un backfill/seed que no garantice IDs específicos entre entornos (desarrollo, testing,
producción); una `clave` string explícita es autodescriptiva y no depende del orden de inserción.

## Decisión 2: `Contrato` y `Recibo` dejan de tener columnas fijas por concepto

**Decision**: se elimina `costo_agua`/`costo_luz`/`costo_pasadizo`/`costo_seguridad` de `contratos`, y
`incluye_alquiler`/`incluye_agua`/`incluye_luz`/`incluye_pasadizo`/`incluye_seguridad`/`monto_agua`/
`monto_luz`/`monto_pasadizo`/`monto_seguridad` de `recibos`. En su lugar:
- `contrato_valores_concepto(contrato_id, concepto_gasto_fijo_id, valor)` — un valor de referencia por
  contrato y concepto (nunca para "Renta" ni "Luz", FR-004/FR-006).
- `recibo_conceptos(recibo_id, concepto_gasto_fijo_id, monto)` — un monto por recibo y concepto que ese
  recibo efectivamente incluye. `monto_renta` se conserva como columna propia de `recibos` (no entra en esta
  tabla) porque "Renta" siempre está presente en el primer recibo que la cubre y su prorrateo ya depende de
  columnas propias del recibo (`dias_activos_periodo`/`dias_totales_periodo`); moverla a la tabla de detalle
  no aportaría nada y complicaría `Recibo::total()`.

**Rationale**: es la traducción directa de FR-001 al esquema — sin columnas fijas, agregar un concepto nuevo
al catálogo ya alcanza para poder usarlo en cualquier contrato o recibo, sin migración de esquema.

**Alternatives considered**: guardar los conceptos de un recibo como una columna `jsonb` (`{concepto_id:
monto}`) en vez de una tabla de detalle — descartado porque el Principio I de la constitución exige aprovechar
las capacidades relacionales de PostgreSQL (claves foráneas, tipos específicos) en vez de bypassear el ORM
con una bolsa de JSON; una tabla de detalle también permite la regla de no-superposición de conceptos
(specs/023) y la agregación de totales (Historia 4) con consultas SQL normales, no lógica de aplicación sobre
JSON.

## Decisión 3: se abandona el patrón "concepto excluido pero con monto recordado"

**Decision**: en el modelo nuevo, un concepto que un recibo no incluye simplemente no tiene fila en
`recibo_conceptos` — no existe ningún lugar donde se guarde un monto "recordado por si se vuelve a incluir".

**Rationale**: ese patrón (specs/005, columnas `monto_*` que sobrevivían aunque `incluye_*` fuera `false`)
ya había quedado semánticamente obsoleto desde specs/023: hoy, un concepto no incluido en un recibo queda
"disponible" para que un *recibo distinto* de la misma locación y periodo lo cubra más adelante — no hay
ningún escenario donde el mismo recibo necesite "recordar" un monto que decidió no incluir. Mantener el
patrón viejo solo agregaría columnas nunca leídas.

**Alternatives considered**: conservar el patrón agregando una columna `activo`/`incluido` a
`recibo_conceptos` — descartada por no tener ningún caso de uso real que la justifique tras specs/023.

## Decisión 4: migración de datos existentes, en 3 pasos dentro de una sola migración por tabla

**Decision**: el orden de migraciones es: (1) crear `conceptos_gasto_fijo` y sembrar las 5 filas
(`clave='renta'` Renta orden 1, `clave=null` Agua orden 2, `clave='luz'` Luz orden 3, `clave=null` "Luz de
Pasadizo" orden 4, `clave=null` Seguridad orden 5) dentro de la propia migración (`up()`), no en un seeder
aparte — así el catálogo mínimo existe en cualquier entorno donde corran las migraciones, incluido
producción; (2) crear `contrato_valores_concepto` y hacer backfill leyendo `costo_agua`/`costo_luz`/
`costo_pasadizo`/`costo_seguridad` de cada contrato existente hacia una fila por concepto (excepto Luz, que
no se migra a esta tabla — ver Decisión 5); (3) crear `recibo_conceptos` y hacer backfill leyendo
`incluye_*`/`monto_*` de cada recibo existente hacia una fila por concepto efectivamente incluido. Recién en
una migración posterior, tras confirmar el backfill, se eliminan las columnas viejas.

**Rationale**: mismo criterio ya usado en specs/019 (backfill primero, columna `NOT NULL`/eliminación
después, nunca en el mismo paso) — permite verificar el backfill antes del punto sin retorno de perder las
columnas originales.

**Alternatives considered**: una única migración gigante que crea las 3 tablas, migra los datos y elimina las
columnas viejas de un solo golpe — descartada por ser mucho más difícil de revisar y de revertir
parcialmente si algo sale mal a mitad de camino.

## Decisión 5: `costo_luz` existente no se migra a `contrato_valores_concepto`

**Decision**: el valor histórico de `costo_luz` (columna que existe en `contratos` pero cuyo valor nunca se
usó para sugerir el monto de luz de un recibo — specs/019 ya estableció que "Luz" siempre sugiere el `total`
de la lectura de medidor, no un costo fijo del contrato) se descarta al eliminar la columna, sin backfill.

**Rationale**: consistente con FR-006 — "Luz" no tiene valor de referencia configurado a mano en el
contrato bajo el modelo nuevo, así que no hay ningún lugar al que migrar ese dato. Se verificó (ver
`quickstart.md`) que descartarlo no cambia ningún monto sugerido real, porque ya era un campo que ningún
flujo de generación de recibo leía.

**Alternatives considered**: migrar `costo_luz` a un campo informativo sin uso funcional — descartado por
agregar un campo "de adorno" sin ningún consumidor, contrario a la disciplina de no dejar código/datos
muertos ya aplicada varias veces en este proyecto (ver specs/016, specs/021).

## Decisión 6: formularios de conceptos dinámicos, mismo patrón ya usado en el modal de registro masivo

**Decision**: todo formulario que antes tenía campos fijos (`incluye_agua`, `monto_agua`, ...) pasa a
iterar los conceptos disponibles/activos y nombrar sus campos por id:
`conceptos[{concepto_gasto_fijo_id}][monto]` (presencia de la clave = concepto incluido, ausencia = no
incluido) — igual que `modal-recibo.blade.php` de specs/023 ya hace con `conceptosDisponibles` (un array de
claves, no una lista fija de 5 nombres). El valor de referencia por contrato usa el mismo criterio:
`valores[{concepto_gasto_fijo_id}]`.

**Rationale**: reutiliza un patrón ya implementado y probado en la misma sesión (specs/023) en vez de
inventar uno nuevo, y es la única forma de que un formulario funcione para un catálogo cuyo tamaño no se
conoce en tiempo de compilación.

**Alternatives considered**: N/A — es la extensión directa y necesaria de un patrón que ya existe.

## Decisión 7: periodo ágil — flechas como enlaces `hx-get`, selector con `hx-trigger="change"`

**Decision**: las flechas «anterior»/«siguiente» son elementos con `hx-get` apuntando a la misma ruta de
índice de la pantalla (`lecturas.registroMasivo.index` / `recibos.registroMasivo.index`) con el query param
`periodo` ya calculado en el servidor (mes actual ±1, vía Blade) al momento de renderizar, apuntando a
`hx-target` sobre el contenedor de la tabla completa (`hx-swap="outerHTML"` o `"innerHTML"` según
corresponda) — sin recargar el layout completo. El selector de mes deja de depender de un botón "Cambiar
Periodo" y pasa a disparar `hx-get` en su propio evento `change`, mismo patrón ya usado por el input de
tarifa de `lecturas/registro-masivo/index.blade.php` (`hx-trigger="change"`).

**Rationale**: reutiliza mecanismos htmx ya presentes y probados en el propio proyecto (Principio VI,
"Excepción de interactividad asíncrona") en vez de introducir JavaScript de navegación a mano; calcular el
periodo ±1 en el servidor (no en JS) evita duplicar la lógica de "mes calendario anterior/posterior" que ya
vive en PHP (`Carbon`).

**Alternatives considered**: recalcular el periodo ±1 en JavaScript en el cliente antes de armar la URL del
`hx-get` — descartado por duplicar en JS una lógica de fechas (cruce de año, días por mes) que `Carbon` ya
resuelve correctamente del lado del servidor sin ese riesgo.

## Decisión 8: total y cantidad de recibos por locación, agregados en la misma consulta agrupada existente

**Decision**: `RegistroMasivoRecibosController::datosDelPeriodo()` (ya existente desde specs/023) agrega, a
la misma colección de recibos por locación que ya trae agrupada, dos valores derivados por locación:
`->where('estado', '!=', 'anulado')->count()` y `->where('estado', '!=', 'anulado')->sum(...)` sobre esa
misma colección ya cargada en memoria — sin ninguna consulta adicional a la base de datos.

**Rationale**: los recibos de cada locación y periodo ya se traen agrupados en una sola consulta (specs/023,
research.md Decisión 8 original); calcular total y cantidad sobre esa misma colección en memoria es gratis en
cuanto a consultas y preserva el criterio anti-N+1 ya establecido.

**Alternatives considered**: una consulta `GROUP BY locacion_id` agregada aparte (`COUNT`/`SUM` a nivel de
SQL) — descartada por ser una consulta adicional innecesaria cuando los mismos datos ya están en memoria tras
la consulta agrupada existente.
