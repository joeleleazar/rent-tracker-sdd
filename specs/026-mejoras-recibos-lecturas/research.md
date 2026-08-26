# Research: Mejoras al Flujo de Recibos y Lecturas

## Decisión 1 — Los recibos anulados dejan de "cubrir" un concepto

**Decisión**: `ServicioGeneracionReciboPeriodo` deja de considerar un recibo con `estado = 'anulado'` como
cobertura vigente en sus tres puntos de cálculo: `conceptosDisponibles()`/`conceptosDisponiblesDesde()`,
`reciboQueCubre()`/`reciboQueCubreDesde()` y `validarSinSuperposicion()`. Se agrega un scope
`Recibo::scopeVigente()` (`where('estado', '!=', 'anulado')`) reutilizado en los tres sitios, en vez de
repetir el `where` inline. `RegistroMasivoRecibosController::datosDelPeriodo()` ya excluye anulados de
`cantidadRecibosPorLocacion`/`totalFacturadoPorLocacion` (specs/024) — ese comportamiento no cambia, pero
ahora comparte el mismo scope para no divergir en el futuro.

**Rationale**: Es el origen exacto del defecto reportado (Local 101): las funciones `...Desde()` reciben
hoy TODOS los recibos de la locación/periodo sin filtrar por estado, así que un recibo anulado sigue
"tapando" sus conceptos tanto en los badges de la UI como en la validación que impediría generar un
recibo nuevo. Como las tres funciones ya comparten los mismos recibos pre-cargados (`with('conceptos')`),
un único scope aplicado en el origen de esas consultas corrige la vista y la lógica de negocio a la vez —
no son dos arreglos distintos.

**Alternativas consideradas**: Filtrar solo en la capa de presentación (Blade) dejando la lógica de
negocio intacta — rechazada porque entonces `validarSinSuperposicion()` seguiría bloqueando la
regeneración de un recibo ya anulado, que es precisamente el problema que el usuario reportó como
bloqueante, no solo cosmético.

## Decisión 2 — El conteo de "en uso" de un concepto de gasto fijo también excluye recibos anulados

**Decisión**: `ConceptoGastoFijoController::index()` (columna "En uso") y `destroy()` (chequeo que bloquea
la eliminación) cuentan `reciboConceptos()` filtrando por `whereHas('recibo', fn ($q) => $q->vigente())`,
en vez de contar todas las filas de `recibo_conceptos` sin importar el estado del recibo padre.
`valoresConcepto()->count()` (uso en contratos) no cambia — un contrato no tiene el concepto de "anulado".

**Rationale**: Comparte la misma causa raíz que la Decisión 1: una fila de `recibo_conceptos` que
pertenece a un recibo anulado no representa un uso activo del concepto, así que tampoco debería impedir su
eliminación. Sin este ajuste, un concepto usado únicamente en recibos ya anulados quedaría eliminable "a
medias" (fuera de contratos, pero nunca fuera de recibos), contradiciendo la User Story 5.

**Alternativas consideradas**: Ninguna — es la aplicación directa y única de la Decisión 1 a esta
pantalla; no hay una forma razonable de que un recibo anulado cuente como "en uso" aquí sin contradecir el
resto de esta feature.

**Hallazgo durante la implementación**: corregir el conteo no bastaba — `recibo_conceptos.concepto_gasto_fijo_id`
tenía `restrictOnDelete()` (specs/024), así que PostgreSQL seguía rechazando el `DELETE` aunque la
aplicación ya no lo bloqueara, con un error 500 en vez del mensaje explícito esperado. Se agregó una
migración adicional (`permitir_eliminar_concepto_gasto_fijo_en_uso`) que relaja esa FK a `nullOnDelete()`
— la fila histórica de `recibo_conceptos` (monto, recibo) se conserva para auditoría, solo pierde el
nombre del concepto ya eliminado; las 3 vistas que leen `reciboConcepto->conceptoGastoFijo->nombre`
(`locaciones/recibos/show.blade.php`, `comprobante.blade.php`,
`recibos/registro-masivo/recibos-del-periodo.blade.php`) se ajustaron a `?->nombre ?? 'Concepto eliminado'`.

## Decisión 3 — La generación de recibo desde el registro masivo reutiliza la página individual existente

**Decisión**: La acción "Generar Recibo" de `recibos/registro-masivo/index.blade.php` deja de abrir un
modal (`RegistroMasivoRecibosController::modal()`/`store()`, `modal-recibo.blade.php`,
`error-modal-recibo.blade.php`) y en su lugar navega (enlace normal, no `hx-get`) a la página ya existente
`locaciones.recibos.create` (`GET /locaciones/{locacion}/recibos/crear`), pasando el periodo visible como
`?periodo=YYYY-MM` (esa página ya sabe leerlo — `ReciboController::create()` ya llama a
`resolverPeriodo(request()->query('periodo'))`). El envío usa el `POST` ya existente
`locaciones.recibos.store`, que ya redirige a `recibos.show` al terminar. Las rutas, controlador y vistas
específicas del modal masivo (`recibos.registroMasivo.modal`, `recibos.registroMasivo.store`) se retiran
por quedar sin ningún llamador.

**Rationale**: La página individual (`locaciones/recibos/create.blade.php`) ya implementa, como página
completa (no modal), exactamente lo que la User Story 2 pide: contrato activo, conceptos disponibles con
monto sugerido, conceptos ya cubiertos con enlace a su recibo, prorrateo de renta y consumo de luz — es
funcionalmente equivalente al modal, con la única diferencia de que hoy no expone `?periodo=` de forma tan
prominente en su UX de entrada. Construir una segunda página nueva para el flujo masivo duplicaría esa
lógica y sus pruebas sin ningún beneficio: ambos flujos generan el mismo tipo de recibo para la misma
locación. Reutilizar el flujo ya maduro reduce superficie de código a mantener y automáticamente hereda
cualquier corrección futura (como la Decisión 1) en un solo lugar.

**Alternativas consideradas**: Construir una página nueva específica para el contexto masivo (con un
"volver a la lista" que preserve el periodo) — rechazada por duplicar ~90% de `locaciones/recibos/create`
sin una necesidad funcional real; el botón "Cancelar" ya existente (vuelve al historial de recibos de la
locación) es un destino razonable también cuando se llega desde el registro masivo, y `recibos.show` tras
guardar ya dejaba clara la confirmación en el flujo individual.

## Decisión 4 — Borrador de recibo: mismo patrón que el borrador de lecturas (specs/015), con conceptos en JSONB

**Decisión**: Nueva tabla `borradores_recibo` (modelo `BorradorRecibo`), con una fila por combinación de
`usuario_id` + `periodo` + `locacion_id` (`unique`), igual que `borradores_lectura_medidor`. Columnas:
`incluye_alquiler` (boolean), `monto_renta` (decimal 12,2 nullable), `fecha_emision` (date nullable) y
`conceptos` (`jsonb`, mapa `concepto_gasto_fijo_id => monto` — la presencia de una clave significa
"incluido", igual que ya hace el payload real del formulario, ver
`specs/024-conceptos-gastos-fijos-dinamicos/contracts/recibo-conceptos-dinamico.md`). La página de
generación agrega, además del autoguardado pasivo cada 120s (`hx-trigger="every 120s"`, mismo mecanismo
que lecturas), un botón explícito "Guardar Borrador" que dispara el mismo POST bajo demanda — la
diferencia respecto al patrón de lecturas es intencional: aquí el usuario pidió explícitamente poder
guardar el borrador, no solo confiar en un guardado silencioso. Al cargar la página, si existe un borrador
para ese usuario/locación/periodo, sus valores prellenan el formulario (sobre los sugeridos por defecto).
Al confirmar la emisión exitosamente, el borrador correspondiente se elimina (mismo patrón que
`RegistroMasivoLecturasController::store()` hace con `BorradorLecturaMedidor` al terminar sin errores).

**Rationale**: El proyecto ya resolvió exactamente este problema (retener un formulario largo, de varias
filas/campos, sin perder avances) para el registro masivo de lecturas, con un modelo transitorio dedicado
en vez de introducir un estado "borrador" sobre la entidad final. Reutilizar el mismo patrón evita
inventar una segunda forma de resolver el mismo problema y mantiene consistencia de codebase. Se usa
`jsonb` para los conceptos (en vez de una tabla hija `borrador_recibo_conceptos`) porque cada guardado de
borrador reemplaza la fila completa de forma atómica (nunca se filtra, ordena ni relaciona por concepto
individual mientras es un borrador) — un mapa `jsonb` es el tipo de dato específico de PostgreSQL
apropiado para ese caso de uso (Principio I de la constitución permite explícitamente tipos de datos
específicos de PostgreSQL), evitando la sobre-ingeniería de una tabla relacional para datos que se
descartan por completo en la siguiente escritura o al confirmar el recibo.

**Alternativas consideradas**:
- Tabla hija `borrador_recibo_conceptos` (una fila por concepto marcado) — rechazada por ser una
  relación real innecesaria para datos que nunca se consultan individualmente ni sobreviven más que unos
  minutos/horas.
- Solo autoguardado pasivo sin botón explícito (copiando el patrón de lecturas al pie de la letra) —
  rechazada porque el pedido original es explícito ("que ... te permita guardar el borrador"): se agrega
  el botón como capa adicional de claridad/control del usuario, sin quitar el autoguardado pasivo que ya
  demostró funcionar bien en lecturas.

## Decisión 5 — "Ver Recibos" del periodo: redirección directa o lista, según cantidad

**Decisión**: Nueva ruta `GET recibos.registroMasivo.recibosDelPeriodo`
(`/recibos/registro-masivo/{locacion}/recibos?periodo=YYYY-MM`). Cuenta los recibos de esa locación y
periodo **sin filtrar por estado** (a diferencia de las Decisiones 1/2, aquí SÍ se listan también los
anulados, porque este es un lugar para *auditar qué ocurrió*, no para calcular disponibilidad — ver Edge
Case de spec.md sobre preservar visibilidad histórica). Si hay exactamente uno, redirige a `recibos.show`.
Si hay más de uno, renderiza una vista simple de lista (nombre/estado/total de cada recibo, enlazando a su
detalle). La fila de cada locación en `recibos/registro-masivo/index.blade.php` muestra esta acción
("Ver Recibos") siempre que exista al menos un recibo (de cualquier estado) para esa locación y periodo,
además — no en reemplazo — de "Generar Recibo" (que sigue apareciendo si aún quedan conceptos
disponibles).

**Rationale**: Es la lectura más directa del pedido ("la vista debe mostrar una lista de recibos en caso
sean varios... y solo es uno directamente ese"), acotada al contexto natural de la pantalla donde se pide
(el registro masivo, que ya está filtrado a un periodo). No se introduce una pantalla global de búsqueda
de recibos de todos los periodos — eso ya existe, sin cambios, como el historial completo por locación
(`locaciones.recibos.index`).

**Alternativas consideradas**: Reutilizar `locaciones.recibos.index` (historial completo) agregando un
filtro de periodo por query string — rechazada porque esa pantalla está pensada para navegación libre por
todo el historial (breadcrumb, ordenado por periodo descendente) y mezclar ahí un modo "acotado a un
periodo con redirección automática si hay uno solo" la haría confusa para su uso original.

## Decisión 6 — Barra de herramientas de lecturas en una sola fila

**Decisión**: Fusionar los dos `<div class="card">` de
`resources/views/lecturas/registro-masivo/index.blade.php` (navegación de periodo y tarifa/exportar) en
un único `card` con un solo `card-body d-flex flex-wrap align-items-end gap-3` que contiene los tres
grupos (navegación de periodo, tarifa por kWh, botones de exportar). Cambio puramente de disposición
visual — ningún atributo `hx-*`, ruta ni validación cambia.

**Rationale**: `d-flex flex-wrap` ya es el patrón usado en ambos bloques por separado; combinarlos en un
solo contenedor los pone en la misma fila en pantallas anchas y los apila de forma legible en pantallas
angostas (comportamiento `flex-wrap` estándar, cumpliendo FR-018/Principio VI de diseño responsive sin
scroll horizontal) sin necesitar CSS a medida.

**Alternativas consideradas**: Ninguna — es un reacomodo directo de marcado ya existente, sin ambigüedad
de diseño.
