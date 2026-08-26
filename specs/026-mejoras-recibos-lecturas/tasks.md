---

description: "Task list for 026-mejoras-recibos-lecturas"
---

# Tasks: Mejoras al Flujo de Recibos y Lecturas

**Input**: Design documents from `/specs/026-mejoras-recibos-lecturas/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/*, quickstart.md

**Tests**: incluidas — Principio IV de la constitución; esta feature toca lógica de facturación ya
probada (specs/005/018/023/024) y retira rutas/vistas existentes (el modal de generación masiva), por lo
que el riesgo de regresión es alto sin cobertura explícita.

**Organization**: 5 historias de usuario en orden de prioridad (US1 P1, US2 P2, US3 P3, US4 P4, US5 P5),
más una fase Foundational mínima (el scope `Recibo::vigente()` que comparten US1 y US5).

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan`/`pest` en esta máquina.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

## Phase 2: Foundational — scope compartido de recibos vigentes

**Propósito**: un único punto (`Recibo::vigente()`) que defina "no anulado", reutilizado por US1 (donde
se aplica a los cálculos de cobertura) y US5 (donde se aplica al conteo de "en uso" de un concepto).
Bloquea US1 y US5; no bloquea US2, US3 ni US4.

- [X] T002 En `app/Models/Recibo.php`, agregar `public function scopeVigente(Builder $query): Builder` que aplique `where('estado', '!=', 'anulado')` (research.md Decisión 1).
- [X] T003 [P] Test unitario en `tests/Unit/ReciboTest.php`: `Recibo::vigente()` excluye los recibos con `estado='anulado'` e incluye `pendiente`/`pagado`.

**Checkpoint**: el scope existe y está probado — US1 y US5 pueden empezar.

---

## Phase 3: User Story 1 - Un recibo anulado deja de bloquear su periodo (Priority: P1)

**Goal**: los conceptos cubiertos por un recibo anulado vuelven a mostrarse como disponibles en el
registro masivo y pueden volver a cubrirse con un recibo nuevo, sin romper el conteo/total por locación
que ya excluye anulados (specs/024).

**Independent Test**: anular el único recibo de una locación/periodo y verificar que sus conceptos
vuelven a aparecer disponibles en `/recibos/registro-masivo` y que se puede generar un recibo nuevo que
los cubra (quickstart.md Escenario 1).

### Tests for User Story 1 ⚠️

- [X] T004 [P] [US1] Extender `tests/Unit/ServicioGeneracionReciboPeriodoTest.php`: `conceptosDisponibles()` no excluye un concepto cuyo único recibo cubridor está anulado; `reciboQueCubre()` no lo mapea a ese recibo anulado; `generar()` (vía `validarSinSuperposicion()`) permite crear un recibo nuevo que cubra un concepto antes cubierto por un recibo ahora anulado, sin lanzar `ConceptosReciboYaCubiertosException`.
- [X] T005 [P] [US1] Extender `tests/Feature/RegistroMasivoRecibosControllerTest.php`: para una locación cuyo único recibo del periodo está anulado, el HTML de `recibos.registroMasivo.index` muestra sus conceptos como disponibles (no como cubiertos) y ofrece "Generar Recibo"; el conteo/total de la columna "Total del Periodo" sigue excluyendo ese recibo anulado (regresión de specs/024).

### Implementation for User Story 1

- [X] T006 [US1] En `ServicioGeneracionReciboPeriodo::conceptosDisponibles()` (`app/Services/ServicioGeneracionReciboPeriodo.php`), agregar `->vigente()` a la consulta de recibos de la locación/periodo.
- [X] T007 [US1] En `ServicioGeneracionReciboPeriodo::reciboQueCubre()`, agregar `->vigente()` a su consulta de recibos.
- [X] T008 [US1] En `ServicioGeneracionReciboPeriodo::validarSinSuperposicion()`, agregar `->vigente()` a la consulta de recibos con `lockForUpdate()`.
- [X] T009 [US1] En `RegistroMasivoRecibosController::datosDelPeriodo()` (`app/Http/Controllers/RegistroMasivoRecibosController.php`, dentro del `foreach ($idsAlquilables as $id)`), calcular `$recibosVigentes = $recibosDeLaLocacion->where('estado', '!=', 'anulado')` **antes** de llamar a `conceptosDisponiblesDesde()`/`reciboQueCubreDesde()` y pasarles esa colección filtrada en vez de `$recibosDeLaLocacion` sin filtrar (causa raíz exacta del defecto reportado en Local 101); reutilizar la misma variable para `cantidadRecibosPorLocacion`/`totalFacturadoPorLocacion` en vez de recalcularla.

**Checkpoint**: un recibo anulado ya no bloquea ningún cálculo de disponibilidad en ninguna pantalla.

---

## Phase 4: User Story 2 - Generar un recibo en una vista propia, con borrador guardable (Priority: P2)

**Goal**: "Generar Recibo" desde el registro masivo navega a la página individual ya existente (en vez de
abrir un modal), con un borrador guardable/recuperable por usuario+locación+periodo.

**Independent Test**: desde `/recibos/registro-masivo`, iniciar la generación de un recibo, verificar que
navega a una página propia con la locación/periodo evidentes, guardar un borrador, salir, volver y
verificar que el borrador se recupera; confirmar la emisión y verificar que el borrador se descarta
(quickstart.md Escenario 2).

### Tests for User Story 2 ⚠️

- [X] T010 [P] [US2] Test unitario `tests/Unit/BorradorReciboTest.php`: `$fillable`, casts (`periodo` date, `monto_renta` decimal:2, `fecha_emision` date, `conceptos` array), relaciones `usuario()`/`locacion()`.
- [X] T011 [P] [US2] Extender `tests/Feature/ReciboControllerTest.php`: `POST locaciones.recibos.borrador` crea/actualiza (upsert) el borrador del usuario autenticado para esa locación y periodo; `GET locaciones.recibos.create` prellena conceptos/montos/`incluye_alquiler`/`fecha_emision` desde un borrador existente; `POST locaciones.recibos.store` exitoso elimina el borrador correspondiente; si `store()` falla por `ConceptosReciboYaCubiertosException`, el borrador NO se elimina; dos usuarios distintos con borradores de la misma locación/periodo no se pisan entre sí.
- [X] T012 [P] [US2] Reescribir en `tests/Feature/RegistroMasivoRecibosControllerTest.php` los tests que hoy ejercitan `modal()`/`store()` (rutas retiradas en esta historia): el botón "Generar Recibo" del índice es un `<a href>` hacia `locaciones.recibos.create` con `?periodo=` (ya no `hx-get` a `recibos.registroMasivo.modal`); las rutas `recibos.registroMasivo.modal` y `recibos.registroMasivo.store` ya no existen.

### Implementation for User Story 2

- [X] T013 [US2] Migración `database/migrations/..._create_borradores_recibo_table.php`: `usuario_id` (FK `users`, `cascadeOnDelete()`), `periodo` (date), `locacion_id` (FK `locaciones`, `cascadeOnDelete()`), `incluye_alquiler` (boolean, default `false`), `monto_renta` (decimal 12,2, nullable), `fecha_emision` (date, nullable), `conceptos` (`jsonb`, default `'{}'`), timestamps, `unique(['usuario_id', 'periodo', 'locacion_id'])` (data-model.md).
- [X] T014 [US2] Modelo `app/Models/BorradorRecibo.php`: `$table = 'borradores_recibo'`, `$fillable`, `casts()` (`periodo` → date, `monto_renta` → decimal:2, `fecha_emision` → date, `conceptos` → array), `usuario(): BelongsTo`, `locacion(): BelongsTo`.
- [X] T015 [US2] Ruta `POST locaciones.recibos.borrador` (`/locaciones/{locacion}/recibos/borrador`) en `routes/web.php`, junto al resto del grupo `locaciones.recibos.*`.
- [X] T016 [US2] `ReciboController::guardarBorrador(Request $solicitud, Locacion $locacion)`: `upsert` de `BorradorRecibo` para (`Auth::id()`, `periodo`, `$locacion->id`) con `incluye_alquiler`/`monto_renta`/`fecha_emision`/`conceptos` recibidos, sin validación estricta de completitud (mismo criterio permisivo que `RegistroMasivoLecturasController::guardarBorrador()`); responde con un texto breve de confirmación ("Borrador guardado a las HH:MM.").
- [X] T017 [US2] `ReciboController::create()`: buscar `BorradorRecibo` de (usuario autenticado, locación, periodo resuelto) y, si existe, usar sus valores para prellenar `incluye_alquiler`/`monto_renta`/`fecha_emision` y los montos/marcas de `conceptos` en vez de los sugeridos por defecto (contracts/borrador-recibo.md).
- [X] T018 [US2] `ReciboController::store()`: tras un `generar()` exitoso, eliminar el `BorradorRecibo` de (usuario autenticado, locación, periodo) si existe, antes de redirigir a `recibos.show`.
- [X] T019 [US2] Vista `resources/views/locaciones/recibos/create.blade.php`: agregar botón "Guardar Borrador" (`hx-post` a `locaciones.recibos.borrador`, `hx-include` del formulario, `hx-trigger="click"`) con texto de confirmación visible tras guardar, más un elemento de autoguardado pasivo (`hx-trigger="every 120s"`, mismo mecanismo que `lecturas/registro-masivo/index.blade.php`); prellenar cada checkbox/monto/fecha desde el borrador cuando `ReciboController::create()` lo provea.
- [X] T020 [US2] `resources/views/recibos/registro-masivo/partials/estado-recibo-locacion.blade.php`: cambiar el botón "Generar Recibo" de `hx-get` hacia `recibos.registroMasivo.modal` a un enlace normal `<a href="{{ route('locaciones.recibos.create', ['locacion' => $locacion, 'periodo' => $periodo->format('Y-m')]) }}">`.
- [X] T021 [US2] Retirar el flujo de modal ya sin llamador: método `modal()`/`store()` de `RegistroMasivoRecibosController`, rutas `recibos.registroMasivo.modal`/`recibos.registroMasivo.store` en `routes/web.php`, vistas `resources/views/recibos/registro-masivo/partials/modal-recibo.blade.php` y `error-modal-recibo.blade.php`, el contenedor `#contenido-modal-recibo` en `resources/views/recibos/registro-masivo/index.blade.php`, y `app/Http/Requests/SolicitudGuardarReciboRegistroMasivo.php`.

**Checkpoint**: la generación de recibo desde el registro masivo es una página propia con borrador; el
flujo individual y el masivo comparten el mismo código.

---

## Phase 5: User Story 3 - Ver los recibos ya generados de una locación y periodo (Priority: P3)

**Goal**: cada fila del registro masivo con al menos un recibo (de cualquier estado) en el periodo
visible ofrece una acción "Ver Recibos" que redirige directo si hay uno solo, o lista si hay varios.

**Independent Test**: para una locación con un recibo en el periodo visible, "Ver Recibos" lleva directo a
su detalle; con dos recibos, muestra una lista para elegir (quickstart.md Escenario 3).

### Tests for User Story 3 ⚠️

- [X] T022 [P] [US3] Feature test en `tests/Feature/RegistroMasivoRecibosControllerTest.php`: `GET recibos.registroMasivo.recibosDelPeriodo` con 0 recibos redirige a `recibos.registroMasivo.index`; con 1 recibo redirige a `recibos.show` de ese recibo; con 2+ recibos (incluyendo el caso de que uno esté anulado) renderiza la lista y muestra ambos.
- [X] T023 [P] [US3] Feature test: el índice (`recibos.registroMasivo.index`) muestra el enlace "Ver Recibos" en la fila de una locación con al menos un recibo (de cualquier estado, incluido uno solo anulado) en el periodo visible, y no lo muestra para una locación sin ningún recibo ese periodo.

### Implementation for User Story 3

- [X] T024 [US3] En `RegistroMasivoRecibosController::datosDelPeriodo()`, agregar al array retornado `tieneRecibosPorLocacion` (bool por locación: `$recibosDeLaLocacion->isNotEmpty()`, sin filtrar por estado — a diferencia de T009, aquí SÍ cuentan los anulados, ver research.md Decisión 5) y pasarlo desde `index()` a la vista.
- [X] T025 [US3] `RegistroMasivoRecibosController::recibosDelPeriodo(Request $solicitud, Locacion $locacion)`: nuevo método — obtiene los recibos de esa locación/periodo (cualquier estado); 0 → `redirect()->route('recibos.registroMasivo.index', ['periodo' => ...])`; 1 → `redirect()->route('recibos.show', $recibo)`; 2+ → `view('recibos.registro-masivo.recibos-del-periodo', [...])`.
- [X] T026 [US3] Ruta `GET recibos.registroMasivo.recibosDelPeriodo` (`/recibos/registro-masivo/{locacion}/recibos`) en `routes/web.php`.
- [X] T027 [P] [US3] Vista nueva `resources/views/recibos/registro-masivo/recibos-del-periodo.blade.php`: lista de recibos de esa locación/periodo (conceptos que cubre cada uno, estado con badge semántico, total, enlace "Ver Detalle" a `recibos.show`).
- [X] T028 [US3] `resources/views/recibos/registro-masivo/partials/estado-recibo-locacion.blade.php`: agregar acción "Ver Recibos" (enlace visible con ícono, no detrás de un menú) hacia `recibos.registroMasivo.recibosDelPeriodo`, mostrada cuando `$tieneRecibos` es verdadero — puede coexistir con "Generar Recibo" en la misma fila.
- [X] T029 [US3] `resources/views/recibos/registro-masivo/partials/fila-registro-masivo-recibos.blade.php` e `index.blade.php`: propagar `tieneRecibosPorLocacion` hacia el parcial de cada fila (mismo patrón ya usado para `cantidadRecibosPorLocacion`/`totalFacturadoPorLocacion`, specs/024).

**Checkpoint**: desde el registro masivo se puede auditar qué se emitió realmente para cada locación y
periodo, sin adivinar por los badges de conceptos.

---

## Phase 6: User Story 4 - Barra de herramientas del registro de lecturas en una sola fila (Priority: P4)

**Goal**: tarifa por kWh, navegación de periodo y botones de exportar en la misma fila de controles.

**Independent Test**: abrir `/lecturas/registro-masivo` y verificar que los tres grupos están en la misma
fila en escritorio, y se reorganizan de forma legible en una ventana angosta (quickstart.md Escenario 4).

### Implementation for User Story 4

- [X] T030 [US4] `resources/views/lecturas/registro-masivo/index.blade.php`: fusionar los dos `<div class="card">` (navegación de periodo, líneas ~27-70, y tarifa/exportar, líneas ~72-109) en un único `card` con un solo `card-body d-flex flex-wrap align-items-end gap-3` que contenga los tres grupos (navegación de periodo, tarifa por kWh, botones de exportar), conservando cada `hx-get`/`hx-patch`/`hx-boost="false"` ya existente sin cambios de comportamiento.
- [X] T031 [P] [US4] Correr `tests/Feature/RegistroMasivoLecturasControllerTest.php` y confirmar que sigue en verde; si algún test dependía de la estructura anterior de dos `<div>` separados (ej. conteo de `card` en el HTML), ajustarlo para reflejar el nuevo marcado sin cambiar su intención.

**Checkpoint**: la barra de herramientas de lecturas queda en una sola fila, sin romper ninguna función
existente.

---

## Phase 7: User Story 5 - Completar la eliminación de un concepto de gasto fijo (Priority: P5)

**Goal**: un concepto cuyo único uso en recibos está en recibos anulados deja de contar como "en uso" y
puede eliminarse.

**Independent Test**: crear un concepto, usarlo en un recibo, anular ese recibo, y verificar que el
concepto ahora puede eliminarse (quickstart.md Escenario 5, reutilizando el Escenario 1).

### Tests for User Story 5 ⚠️

- [X] T032 [P] [US5] Extender `tests/Feature/ConceptoGastoFijoControllerTest.php`: un concepto cuyo único uso en `recibo_conceptos` pertenece a un recibo anulado muestra "0 registros" en la columna "En uso" de `conceptosGastoFijo.index` y su botón "Eliminar" está habilitado (no `disabled`); `conceptosGastoFijo.destroy` lo elimina exitosamente en ese caso; un concepto con al menos un uso en un recibo vigente sigue bloqueado como hoy (regresión de specs/024).

### Implementation for User Story 5

- [X] T033 [US5] `ConceptoGastoFijoController::index()`: cambiar `withCount(['reciboConceptos as recibos_en_uso'])` por un conteo que excluya recibos anulados (`withCount(['reciboConceptos as recibos_en_uso' => fn ($q) => $q->whereHas('recibo', fn ($q2) => $q2->vigente())])`).
- [X] T034 [US5] `ConceptoGastoFijoController::destroy()`: aplicar el mismo criterio de conteo filtrado al chequeo `$enUso` que bloquea la eliminación.
- [X] T035 [US5] Hallazgo durante la implementación (research.md, sección "Hallazgo"): `recibo_conceptos.concepto_gasto_fijo_id` tenía `restrictOnDelete()` (specs/024), así que corregir el conteo no bastaba — PostgreSQL seguía rechazando el `DELETE` con un error 500. Migración `permitir_eliminar_concepto_gasto_fijo_en_uso`: relaja esa FK a `nullOnDelete()` (columna nullable). Ajustar `resources/views/locaciones/recibos/show.blade.php`, `comprobante.blade.php` y `resources/views/recibos/registro-masivo/recibos-del-periodo.blade.php` a `$reciboConcepto->conceptoGastoFijo?->nombre ?? 'Concepto eliminado'`.

**Checkpoint**: las 5 historias de usuario están completas e independientemente verificables.

---

## Phase 8: Polish & Cross-Cutting Concerns

- [X] T036 Correr la suite completa (`php artisan test`, binario Herd) y confirmar 0 fallos.
- [X] T037 [P] Revisión de diseño con el skill `impeccable` (`audit` o `polish`, según corresponda) sobre las vistas nuevas/modificadas: `resources/views/locaciones/recibos/create.blade.php`, `resources/views/recibos/registro-masivo/index.blade.php`, `resources/views/recibos/registro-masivo/partials/estado-recibo-locacion.blade.php`, `resources/views/recibos/registro-masivo/partials/fila-registro-masivo-recibos.blade.php`, `resources/views/recibos/registro-masivo/recibos-del-periodo.blade.php`, `resources/views/lecturas/registro-masivo/index.blade.php` (Principio VI de la constitución).
- [X] T038 Grep de todo el codebase por referencias colgantes a lo retirado en T021 (`recibos.registroMasivo.modal`, `recibos.registroMasivo.store`, `modal-recibo`, `error-modal-recibo`, `SolicitudGuardarReciboRegistroMasivo`) y confirmar que no queda ninguna.
- [X] T039 Validar manualmente los 5 escenarios de `specs/026-mejoras-recibos-lecturas/quickstart.md` contra la base de datos de desarrollo real, en navegador.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de T001. Bloquea US1 y US5.
- **User Story 1 (Phase 3)**: depende de Foundational (T002). Independiente de US2/US3/US4/US5.
- **User Story 2 (Phase 4)**: depende solo de Setup — no usa `Recibo::vigente()` directamente. Independiente de US1, aunque en la práctica conviene tenerla resuelta primero para no confundir "conceptos disponibles" incorrectos con el trabajo de la página nueva mientras se prueba.
- **User Story 3 (Phase 5)**: depende solo de Setup. Independiente de US1/US2/US4/US5, aunque reutiliza `datosDelPeriodo()` (ya tocada por US1/T009) — sin conflicto real porque agrega una clave nueva al array en vez de modificar las de US1.
- **User Story 4 (Phase 6)**: depende solo de Setup — no toca nada de recibos ni conceptos.
- **User Story 5 (Phase 7)**: depende de Foundational (T002). Independiente de US1-US4 en el código, aunque comparte la misma causa raíz conceptual que US1.
- **Polish (Phase 8)**: depende de que las 5 historias estén completas (T038 en particular depende de que T021 de US2 ya haya retirado el código correspondiente).

### Dentro de cada fase

- Los tests marcados ⚠️ se escriben/actualizan antes que su implementación y deben fallar primero
  (Principio IV).
- Dentro de US2: T013 (migración) antes que T014 (modelo); T015 (ruta) antes que T016 (controlador);
  T016-T018 (controlador) antes que T019 (vista, que depende de que el borrador ya se pueda guardar/leer);
  T020-T021 (retirar el modal) pueden ir en paralelo con T013-T019 (archivos distintos), pero deben
  completarse antes de dar la historia por terminada, ya que T012 (test) verifica que las rutas viejas ya
  no existen.
- Dentro de US3: T024 antes que T029 (la vista necesita el dato nuevo en el array); T025-T026 (controlador
  + ruta) antes que T028 (la vista enlaza a esa ruta); T027 (vista nueva) es independiente de T024-T026.

### Parallel Opportunities

- T003 (Foundational) puede escribirse tan pronto T002 esté implementado.
- T004, T005 (tests US1) en paralelo entre sí.
- T010, T011, T012 (tests US2) en paralelo entre sí (archivos distintos).
- T013 y T030 (US2/US4, archivos completamente distintos) pueden trabajarse en paralelo si hay más de un
  desarrollador.
- T022, T023 (tests US3) en paralelo entre sí.
- T027 (vista nueva US3) en paralelo con T024-T026 (backend US3).
- T032 (test US5) puede escribirse tan pronto Foundational esté listo, en paralelo con cualquier otra
  historia.
- US1, US3, US4 y US5 no comparten archivos entre sí (más allá de `datosDelPeriodo()` entre US1 y US3, ya
  señalado arriba) y pueden trabajarse en paralelo si hay más de un desarrollador; US2 es la más grande y
  puede avanzar en paralelo con cualquiera de las otras tres.

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Setup (T001) → Foundational (T002-T003).
2. US1 (T004-T009): corrige el defecto reportado — es la historia de mayor impacto inmediato con el menor
   esfuerzo.
3. **Parar y validar**: quickstart.md Escenario 1.

### Incremental Delivery

1. Setup → Foundational → listas para US1/US2/US3/US4/US5.
2. US1 (T004-T009) → validar → demo (corrige el bug reportado).
3. US2 (T010-T021) → validar → demo (el cambio de mayor esfuerzo y el motivo principal del pedido).
4. US3 (T022-T029) → validar → demo.
5. US4 (T030-T031) → validar → demo (la más chica y aislada).
6. US5 (T032-T035) → validar → demo (cierra el vacío de eliminación).
7. Polish (T036-T039) cierra la feature.

### Recomendación de orden real de ejecución (una sola persona/agente, no un equipo)

Dado que US1 es pequeña, de alto impacto y no bloquea a nadie más, conviene resolverla primero para
retirar el defecto reportado cuanto antes. US4 y US5 son las siguientes más chicas y pueden intercalarse
en cualquier momento sin riesgo. US2 es, con diferencia, el bloque más grande (retira todo un flujo
existente) — conviene dejarla para cuando el resto ya esté resuelto y probado, para no mezclar su propio
riesgo de regresión con el de las demás historias. Orden sugerido: Foundational → US1 → US4 → US5 → US3 →
US2 → Polish.

---

## Notes

- [P] = archivos distintos, sin dependencia de código entre las tareas.
- [US1]/[US2]/[US3]/[US4]/[US5] = trazabilidad a las historias de usuario de `spec.md`.
- T009 y T024 tocan la misma función (`datosDelPeriodo()`) desde historias distintas (US1 y US3) — se
  documentan por separado porque sirven propósitos distintos (una filtra, la otra no debe filtrar), pero
  quien implemente ambas en la misma sesión puede aplicarlas juntas sin conflicto real.
- T021 (retirar el modal) es la tarea de mayor riesgo de esta feature por ser destructiva (elimina rutas,
  vistas y un Form Request) — T012 y T037 existen específicamente para confirmar que no queda ningún
  llamador antes y después de borrarlo.
- Confirmar que los tests de cada fase fallan contra el estado anterior antes de implementar su código
  correspondiente.
