# Research: Lecturas de Medidor de Luz y Recibo por Periodo

**Feature**: `005-lecturas-medidor-recibo-periodo` | **Date**: 2026-08-20

## 1. Reconciliación: `Recibo` pasa de contrato-céntrico (specs/004) a locación-céntrico

**Decision**: Los puntos de entrada de generación e historial de `Recibo` definidos en `specs/004-condiciones-contrato-recibo/contracts/rutas-condiciones-contrato-recibo.md` (`GET/POST /contratos/{contrato}/recibos`, `GET /contratos/{contrato}/recibos/crear`) se sustituyen en esta especificación por rutas ancladas a `Locacion` + `periodo`: `GET /locaciones/{locacion}/recibos/crear?periodo=YYYY-MM` y `GET /locaciones/{locacion}/recibos` (historial, US3). Internamente, `ReciboController` resuelve el `Contrato` activo de esa locación vigente en el periodo solicitado (nuevo helper `Contrato::activoEnPeriodo()`); si no existe, bloquea la generación (FR-008) sin impedir el registro independiente de la lectura del medidor. El detalle `GET /recibos/{recibo}` (de 004) se mantiene sin cambios. `Recibo.contrato_id` se conserva (para saber qué contrato originó cada recibo histórico), y se agrega `Recibo.locacion_id` denormalizado.

**Rationale**: La especificación 004 modeló `Recibo` únicamente en función de un `Contrato` puntual, lo cual era razonable en el momento en que 004 se planificó de forma aislada (el recibo se generaba "para un contrato"). La especificación 005 exige explícitamente que el recibo se genere "por cada locación y periodo (mes)" (US2, FR-004) y que el historial se consulte "de una locación" (US3) — un concepto que debe sobrevivir a la renovación o sustitución del contrato de esa locación a lo largo de los meses. Si `Recibo` siguiera siendo accesible solo vía `/contratos/{contrato}/recibos`, el historial de una locación con 3 contratos sucesivos quedaría fragmentado en 3 listados distintos, contradiciendo directamente el Acceptance Scenario de US3 de esta especificación ("una locación con lecturas... Junio, Julio, Agosto"). Se opta por relocalizar el punto de entrada en vez de mantener ambos permanentemente, para no tener dos flujos de creación de recibo divergentes que puedan quedar desincronizados (ej. bloqueo de contrato activo solo implementado en uno de los dos). Esta reconciliación es análoga en espíritu a la de `specs/003-representantes-contrato/research.md` §1 (Inquilino/Representante): se documenta la decisión y su razón en la especificación que la requiere, sin reabrir ni reescribir los documentos ya completados de la especificación anterior.

**Alternatives considered**:
- Mantener ambas rutas (contrato-céntrico de 004 y locación-céntrico de 005) en paralelo indefinidamente: rechazado, duplicaría la lógica de bloqueo de contrato activo y de unicidad por periodo en dos controladores o dos ramas del mismo controlador, aumentando el riesgo de inconsistencia (Principio V).
- Mantener `Recibo` estrictamente contrato-céntrico y resolver el historial de locación mediante un JOIN a través de todos los contratos históricos de esa locación en cada consulta: rechazado, es más simple y más rápido de consultar con `locacion_id` denormalizado directamente en `Recibo`, y permite el índice `UNIQUE (locacion_id, periodo)` que exige FR-009 sin tener que pasar por la tabla `contratos`.

## 2. `Contrato::activoEnPeriodo()` — determinar el contrato vigente de un periodo

**Decision**: Nuevo helper en el modelo `Contrato`: `Locacion::contratoActivoEnPeriodo(Carbon $periodo): ?Contrato`, que busca entre los contratos de la locación aquel cuyo rango `fecha_inicio`/`fecha_fin` intersecta el mes calendario completo del periodo dado (`fecha_inicio <= fin_de_mes AND fecha_fin >= inicio_de_mes`), excluyendo estados `rescindido`/`cancelado` (mismo criterio de exclusión que `ServicioValidacionSolapamientoContrato` de `specs/002`). Si hay más de un contrato que cumple (no debería ocurrir dado que 002 impide solapamientos), se toma el de `fecha_inicio` más reciente como salvaguarda defensiva.

**Rationale**: La especificación exige bloquear la generación del recibo si no hay "un contrato activo vigente durante ese periodo" (FR-008), lo cual requiere una consulta explícita por rango de fechas contra el mes del periodo, no simplemente `estado = 'activo'` (un contrato podría estar en estado `activo` pero su vigencia real no cubrir completamente el mes solicitado, ej. si finalizó a mitad de mes — ver además `specs/008-prorrateo-alertas-pago` para el prorrateo de esos casos, que reutilizará este mismo helper).

**Alternatives considered**:
- Filtrar solo por `estado = 'activo'` sin comparar fechas: rechazado, no cubre el caso de un contrato ya vencido en la base de datos pero cuyo estado no fue actualizado manualmente a "vencido" (spec 002 no automatiza esa transición de estado).

## 3. Extensión de `Recibo` (specs/004): conceptos seleccionables y referencia a la lectura

**Decision**: Migración de alteración sobre `recibos` (de `specs/004`) que agrega `locacion_id` (FK → `locaciones.id`, `restrictOnDelete()`, obligatorio, backfill desde `contrato.locacion_id` para filas ya existentes), `lectura_medidor_id` (FK opcional → `lecturas_medidor.id`, `nullOnDelete()`), y los booleanos `incluye_alquiler`/`incluye_luz`/`incluye_agua`/`incluye_seguridad`/`incluye_pasadizo` (todos `boolean`, por defecto `true` para no romper la semántica de los recibos ya creados en 004, que siempre incluían todos los conceptos). Se agrega el índice único compuesto `(locacion_id, periodo)` (FR-009).

**Rationale**: Los campos de 004 (`monto_renta`/`monto_agua`/`monto_luz`/`monto_pasadizo`/`monto_seguridad`) ya cubren "cuánto se cobra"; a esta especificación le falta "qué conceptos se decidió incluir" (FR-005), que es una decisión distinta de si el monto es cero o no (Edge Case de 004: "el campo se registra en S/ 0.00 y ese concepto se sigue mostrando... sin ocultarse" vs. esta especificación: "los conceptos excluidos NO aparecen en el detalle ni en el total"). Son reglas de negocio diferentes que coexisten: un concepto puede tener monto S/ 0.00 y estar incluido (se muestra en 0), o puede estar excluido directamente (no se muestra). Por eso se requieren columnas booleanas independientes de los montos.

**Alternatives considered**:
- Usar `monto_x = null` para representar "excluido" en vez de un booleano dedicado: rechazado, generaría ambigüedad con el comportamiento ya establecido en 004 donde `0.00` es un valor válido y visible, no equivalente a "excluido"; un booleano explícito es más claro y más fácil de testear.

## 4. Cálculo de consumo y detección de lectura menor a la anterior (FR-002, Edge Case)

**Decision**: `LecturaMedidor.consumo_calculado` se calcula en `ServicioCalculoConsumoMedidor::calcular(Locacion $locacion, string $periodo, float $lectura): array`, que busca la lectura del periodo cronológicamente inmediato anterior de esa locación (`LecturaMedidor::where('locacion_id', ...)->where('periodo', '<', $periodo)->orderByDesc('periodo')->first()`), calcula `lectura - lectura_anterior->lectura` si existe, o devuelve `null` ("sin dato anterior") si no. Si el resultado es negativo, el Service devuelve un indicador de advertencia (`requiere_confirmacion: true`) que el controlador traduce en un mensaje de alto contraste exigiendo confirmación explícita antes de guardar (Edge Case "Lectura menor a la del periodo anterior"), sin bloquear el guardado de forma permanente.

**Rationale**: Centralizar el cálculo en un Service (no en un accessor del modelo) permite reutilizar la misma lógica desde `LecturaMedidorController@store`/`@update` y desde la vista previa de consumo (si se muestra antes de guardar vía un endpoint de cálculo), y facilita probarlo unitariamente sin necesidad de HTTP (Principio IV). Nota: `specs/006-historial-lectura-medidor` reemplaza este cálculo implícito (comparación contra el registro anterior) por dos columnas explícitas (`lectura_anterior`/`lectura_actual`) en el mismo `LecturaMedidor`; esta especificación (005) se implementa primero con el modelo más simple (una sola columna `lectura` + comparación contra el periodo anterior), que 006 refina — ver `specs/006/research.md` §1 para la migración de datos correspondiente.

**Alternatives considered**:
- Bloquear permanentemente el guardado si el consumo es negativo: rechazado, el Edge Case exige explícitamente permitir continuar tras una confirmación explícita (posible cambio de medidor), no un bloqueo total.

## 5. Tarifa de luz por unidad de consumo (FR-006, FR-007)

**Decision**: `ConfiguracionGeneral.tarifa_luz_por_unidad` (`decimal(12,4)`, mayor precisión que los montos monetarios para admitir tarifas con más de 2 decimales por unidad, ej. "S/ 0.6850 por kWh"), agregada a la tabla ya existente `configuracion_general` (de `specs/004`) mediante migración de alteración, editable desde la misma pantalla `/configuracion` (se extiende `ConfiguracionGeneralController`/`SolicitudActualizarConfiguracionGeneral` de 004). El monto sugerido de luz se calcula como `consumo_calculado * tarifa_luz_por_unidad` en `ServicioGeneracionReciboPeriodo`, redondeado a 2 decimales solo al momento de precargar el campo editable del formulario (el valor final que persiste en `Recibo.monto_luz` es el que confirme el administrador, editado o no).

**Rationale**: Consistente con el patrón de fila única ya establecido en `specs/004/research.md` §2, que anticipó explícitamente esta columna. `decimal(12,4)` evita perder precisión en el cálculo intermedio (consumo × tarifa) antes de redondear al monto final en soles, aunque el monto final del recibo (`monto_luz`) sigue siendo `decimal(12,2)` como el resto de montos monetarios (Principio V).

**Alternatives considered**:
- Tarifa con precisión `decimal(12,2)` igual que los montos: rechazado, tarifas por unidad de consumo eléctrico suelen expresarse con más de 2 decimales en la práctica (ej. tarifas reguladas de electricidad), y truncar a 2 decimales introduciría un error sistemático acumulado en consumos altos.

## 6. Framework de pruebas

**Decision**: Pest, consistente con `specs/001-004`.

**Rationale**: Ya adoptado por el proyecto.

**Alternatives considered**: Ninguna — decisión ya tomada a nivel de proyecto.
