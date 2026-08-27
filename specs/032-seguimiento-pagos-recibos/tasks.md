---

description: "Task list for 032-seguimiento-pagos-recibos"
---

# Tasks: Registro y Seguimiento de Pagos de Recibos

**Input**: Design documents from `/specs/032-seguimiento-pagos-recibos/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: incluidas — Principio IV de la constitución (cobertura de modelos/servicios/controladores).

**Organization**: 3 historias de usuario en orden de prioridad (US1 P1, US2 P1, US3 P2), más una fase
Foundational que agrega la entidad `Pago` y extiende `Recibo`.

**Nota de dependencia real**: US2 (ver el avance en la jerarquía de locales) solo depende de Foundational —
lee `Recibo::montoPagado()`/`saldoPendiente()` directamente, no del servicio de registro de pagos de US1.
US3 (corregir un pago), en cambio, sí depende de US1: agrega `actualizar()`/`eliminar()` al mismo
`ServicioGestionPagosRecibo` y `update()`/`destroy()` al mismo `PagoReciboController` que crea US1.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan`/`pest`; el dominio real del proyecto en esta máquina es `rent-tracker-sdd.test`.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

## Phase 2: Foundational — entidad Pago y avance de pago en Recibo

**Propósito**: la base de datos y los cálculos que las 3 historias comparten. Bloquea a las 3.

- [X] T002 Migración `create_pagos_table`: `recibo_id` (FK `recibos`, `cascadeOnDelete`), `monto` (`decimal(12,2)`), `fecha_pago` (`date`), `registrado_por_id` (FK `users`, `nullOnDelete`, nullable), timestamps (data-model.md; research.md Decisión 1, 2).
- [X] T003 [P] Modelo `app/Models/Pago.php`: `$fillable` (`recibo_id`, `monto`, `fecha_pago`, `registrado_por_id`), casts (`monto` → `decimal:2`, `fecha_pago` → `date`), `recibo()` (`belongsTo`), `registradoPor()` (`belongsTo` `User`) (data-model.md).
- [X] T004 [US-compartido] Extender `app/Models/Recibo.php`: `pagos()` (`hasMany` `Pago`), `montoPagado()` (suma de `pagos.monto`), `saldoPendiente()` (`total() - montoPagado()`, nunca negativo), `estaPagadoPorCompleto()` (`saldoPendiente() <= 0`) (data-model.md).

**Checkpoint**: la entidad `Pago` existe y `Recibo` puede calcular su propio avance de pago — listo para las 3 historias.

---

## Phase 3: User Story 1 - Registrar un pago contra un recibo emitido (Priority: P1) 🎯 MVP

**Goal**: registrar uno o varios pagos (parciales o totales) contra un recibo, con el estado
Pendiente/Pagado del recibo recalculándose automáticamente; se retira el toggle manual entre esos dos
estados (FR-006).

**Independent Test**: abrir un recibo pendiente, registrar un pago parcial y luego uno que complete el
total, verificando que el recibo pasa de "sin pagos" a "parcial" a "Pagado" sin intervención manual
(quickstart.md Escenario 1).

### Tests for User Story 1 ⚠️

- [X] T005 [P] [US1] Unit test `tests/Unit/ServicioGestionPagosReciboTest.php`: registrar un pago parcial deja el recibo en `pendiente` con el avance correcto; registrar un pago que completa el total pasa el recibo a `pagado` y fija `fecha_pago`; registrar un pago que excedería el saldo pendiente se rechaza indicando el máximo disponible; registrar un pago con monto ≤ 0 se rechaza; registrar un pago sobre un recibo `anulado` se rechaza (contracts/gestion-pagos.md).
- [X] T006 [P] [US1] Feature test `tests/Feature/PagoReciboControllerTest.php`: `POST pagos.store` con datos válidos crea el pago y persiste el nuevo estado del recibo; los 4 casos de rechazo de T005 devuelven errores de validación/sesión con el mensaje esperado.

### Implementation for User Story 1

- [X] T007 [US1] `app/Services/ServicioGestionPagosRecibo.php`, método `registrar(Recibo $recibo, array $datos, ?int $registradoPorId)`: crea el `Pago`, recalcula `recibos.estado`/`fecha_pago` según la regla de estado de data-model.md, todo dentro de `DB::transaction` (research.md Decisión 3).
- [X] T008 [US1] `app/Http/Requests/SolicitudGuardarPago.php`: `monto` (`required`, `numeric`, `gt:0`, ≤ saldo pendiente del recibo de la ruta), `fecha_pago` (`required`, `date`, ≤ hoy) (contracts/gestion-pagos.md).
- [X] T009 [US1] `app/Http/Controllers/PagoReciboController.php`, método `store()` + ruta `POST /recibos/{recibo}/pagos` → `pagos.store` en `routes/web.php`; rechaza si `$recibo->estado === 'anulado'` antes de validar el monto (FR-011).
- [X] T010 [US1] Refactorizar `app/Services/ServicioCambioEstadoRecibo.php`: retirar `cambiar(Recibo, string, bool)` genérico, agregar `anular(Recibo $recibo, bool $confirmado)` y `reactivar(Recibo $recibo, bool $confirmado)` — `reactivar()` delega en `ServicioGestionPagosRecibo` para recalcular Pendiente/Pagado a partir de los pagos que el recibo ya tenía, sin recibir a mano a qué estado volver (research.md Decisión 4, 5).
- [X] T011 [US1] Actualizar `app/Http/Controllers/ReciboController.php` (`actualizarEstado()` → `anular()`/`reactivar()`, o mantener una sola acción que solo acepte `nuevo_estado=anulado` y el sentinel de reactivar) y `app/Http/Requests/SolicitudActualizarEstadoRecibo.php` (retira `pendiente`/`pagado` como valores válidos de `nuevo_estado`).
- [X] T012 [US1] En `resources/views/locaciones/recibos/show.blade.php`: retirar el `btn-group` manual Pendiente/Pagado/Anulado (líneas 87-119 actuales); mostrar el estado calculado (badge Pendiente con avance "S/ pagado / S/ total", o Pagado) junto con la lista de pagos ya registrados (monto, fecha, quién los registró) y un formulario para registrar un pago nuevo; conservar los modales `anular-recibo`/`revertir-*` apuntando a las rutas refactorizadas de T010-T011.
- [X] T013 [US1] Ejecutar el Escenario 1 de `quickstart.md` (registrar pago parcial, rechazo por exceso, completar el pago, rechazo de monto S/ 0.00) y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: User Story 1 completa — se pueden registrar pagos parciales/totales y el recibo refleja su avance sin ningún toggle manual.

---

## Phase 4: User Story 2 - Ver el avance de pago en la jerarquía de locales (Priority: P1)

**Goal**: una nueva pantalla, con la misma jerarquía de locales que la emisión de recibos, muestra el
avance de pago de cada locación para el período elegido.

**Independent Test**: con recibos en distintos estados de pago (sin pagos/parcial/completo) en un mismo
período, abrir la nueva pantalla para ese período y verificar el estado de cada locación sin abrir cada
recibo (quickstart.md Escenario 2).

### Tests for User Story 2 ⚠️

- [X] T014 [US2] Feature test `tests/Feature/SeguimientoPagosControllerTest.php`: el árbol muestra `sin_pagos`/`parcial`/`pagado` por locación según sus recibos vigentes del período; una locación sin ningún recibo emitido ese período no muestra ningún estado; cambiar de período (`?periodo=YYYY-MM`) actualiza los datos mostrados; "Ver Pagos" redirige directo a `recibos.show` cuando hay un único recibo vigente, y a la lista de recibos del período cuando hay más de uno (contracts/vista-seguimiento-pagos.md).

### Implementation for User Story 2

- [X] T015 [US2] `app/Http/Controllers/SeguimientoPagosController.php`, método `index()`: reutiliza `ServicioConstruccionArbolLocaciones::construir()` sin cambios (research.md Decisión 6) y agrega, por locación, `montoPagadoPorLocacion`/`montoTotalPorLocacion`/`cantidadRecibosPorLocacion`/`estadoAgregadoPorLocacion` calculados sobre sus recibos vigentes del período (research.md Decisión 7; contracts/vista-seguimiento-pagos.md), con el mismo criterio anti-N+1 (una consulta agrupada) que `RegistroMasivoRecibosController::datosDelPeriodo()`.
- [X] T016 [US2] Ruta `GET /pagos/seguimiento` → `pagos.seguimiento.index` en `routes/web.php`.
- [X] T017 [US2] Vista `resources/views/pagos/seguimiento/index.blade.php`: mismo selector de período y navegación anterior/siguiente que `recibos/registro-masivo/index.blade.php` (FR-008), tabla-árbol con las columnas de contracts/vista-seguimiento-pagos.md.
- [X] T018 [US2] Parcial recursivo `resources/views/pagos/seguimiento/partials/fila-seguimiento-pagos.blade.php`, análogo a `recibos/registro-masivo/partials/fila-registro-masivo-recibos.blade.php` (mismo componente `fila-arbol`, expandir/colapsar).
- [X] T019 [US2] Parcial `resources/views/pagos/seguimiento/partials/estado-pago-locacion.blade.php`: badge según `estadoAgregadoPorLocacion` (sin badge si `sin_recibos`; "Sin pagos" secundario; "Parcial" advertencia con el avance; "Pagado" éxito) + botón "Ver Pagos" con la misma desambiguación que "Ver Recibos" (research.md Decisión 7).
- [X] T020 [US2] Ejecutar el Escenario 2 de `quickstart.md` (jerarquía, estados por locación, cambio de período, desambiguación de "Ver Pagos") y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: User Stories 1 y 2 completas — se pueden registrar pagos y ver su avance por locación.

---

## Phase 5: User Story 3 - Corregir un pago registrado por error (Priority: P2)

**Goal**: editar el monto de un pago ya registrado, o eliminarlo con confirmación explícita, recalculando
el avance de pago del recibo en ambos casos.

**Independent Test**: sobre un recibo con un pago registrado por un monto incorrecto, corregirlo (o
eliminarlo) y verificar que el avance de pago del recibo se recalcula (quickstart.md Escenario 3).

### Tests for User Story 3 ⚠️

- [X] T021 [P] [US3] Unit test en `tests/Unit/ServicioGestionPagosReciboTest.php`: editar el monto de un pago recalcula el saldo excluyendo el propio pago editado de la suma previa (permite editar a un monto igual o mayor al que ya tenía, dentro del saldo real); eliminar un pago recalcula el estado del recibo (un recibo `pagado` puede volver a `pendiente`); ambas operaciones se rechazan sobre un recibo `anulado`.
- [X] T022 [P] [US3] Feature test en `tests/Feature/PagoReciboControllerTest.php`: `PUT pagos.update` (incluyendo el caso de exceder el saldo al editar) y `DELETE pagos.destroy`, incluyendo sus casos de rechazo sobre un recibo anulado.

### Implementation for User Story 3

- [X] T023 [US3] En `app/Services/ServicioGestionPagosRecibo.php`, agregar `actualizar(Pago $pago, array $datos)` y `eliminar(Pago $pago)`, cada uno con su propio recálculo de estado dentro de `DB::transaction` (contracts/gestion-pagos.md).
- [X] T024 [US3] En `app/Http/Controllers/PagoReciboController.php`, agregar `update()`/`destroy()` + rutas `PUT /pagos/{pago}` → `pagos.update` y `DELETE /pagos/{pago}` → `pagos.destroy` en `routes/web.php`, reutilizando `SolicitudGuardarPago` de T008 para `update()`.
- [X] T025 [US3] En `resources/views/locaciones/recibos/show.blade.php`, agregar a cada fila de la lista de pagos (T012) una acción de editar (formulario/modal con el monto y fecha precargados) y una de eliminar (modal de confirmación explícita, Principio III de la constitución).
- [X] T026 [US3] Ejecutar el Escenario 3 de `quickstart.md` (editar un pago, eliminar un pago con confirmación) y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: las tres historias de usuario están completas e independientemente verificables.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T027 Reescribir `tests/Unit/ServicioCambioEstadoReciboTest.php` para los nuevos métodos `anular()`/`reactivar()` (ya no existe `cambiar()` genérico ni las transiciones directas a `pendiente`/`pagado`).
- [X] T028 Revisar y ajustar en `tests/Feature/ReciboControllerTest.php` cualquier aserción que dependiera del toggle manual Pendiente/Pagado retirado por FR-006 (ej. `PATCH recibos.estado.update` con `nuevo_estado=pagado`/`pendiente`).
- [X] T029 Revisar los casos límite restantes de `quickstart.md`: anular un recibo con pagos ya registrados (se conservan, la locación deja de contarse en `pagos/seguimiento`), reactivarlo (recalcula solo, sin pedir elegir estado), e intentar registrar/editar/eliminar un pago sobre un recibo anulado (debe impedirse en los 3 casos).
- [X] T030 [P] Revisión de diseño con el skill `impeccable` sobre `pagos/seguimiento/index.blade.php` y sus parciales, y sobre los cambios en `recibos/show.blade.php` (Principio VI de la constitución).
- [X] T031 Correr la suite completa (`php artisan test`, binario Herd) y confirmar 0 fallos.
- [X] T032 Validar manualmente el checklist completo de `quickstart.md` (los 3 escenarios, los casos límite y la regresión del comprobante/emisión de recibos) contra la base de datos de desarrollo real, en navegador.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de T001. Bloquea a las 3 historias.
- **User Story 1 (Phase 3)**: depende de Foundational (T002-T004). No depende de otra historia.
- **User Story 2 (Phase 4)**: depende solo de Foundational — lee `Recibo::montoPagado()`/`saldoPendiente()`
  directamente, no del servicio de registro de pagos de US1 (ver nota al inicio de este documento).
- **User Story 3 (Phase 5)**: depende de Foundational **y** de User Story 1 (T007, T009) — agrega
  `actualizar()`/`eliminar()` al mismo servicio y `update()`/`destroy()` al mismo controlador que crea US1.
- **Polish (Phase 6)**: depende de que las 3 historias estén completas.

### Dentro de cada fase

- Los tests marcados ⚠️ se escriben/actualizan antes que su implementación y deben fallar primero
  (Principio IV).
- T010-T012 (refactor de `ServicioCambioEstadoRecibo` y de `recibos/show.blade.php`) se aplican en orden
  sobre los mismos archivos que T007-T009 tocan — no son paralelizables entre sí.

### Parallel Opportunities

- T003 (modelo `Pago`) puede avanzar en paralelo a T002 (migración) solo hasta el punto de necesitar la
  tabla real para ejecutarse — en la práctica, aplicar la migración primero es lo más simple.
- T005 y T006 (tests de US1, archivos distintos) en paralelo.
- T021 y T022 (tests de US3, archivos distintos) en paralelo.
- User Story 2 (Phase 4) puede desarrollarse en paralelo a User Story 1 (Phase 3) si hay más de un
  desarrollador, ya que solo depende de Foundational.

---

## Implementation Strategy

### MVP First (Foundational + User Story 1)

1. Setup (T001) → Foundational (T002-T004).
2. User Story 1 (T005-T013): registrar pagos parciales/totales y ver el avance en el propio recibo — el
   MVP del pedido original ("agregar pagos... se permiten pagos parciales").
3. **Parar y validar**: quickstart.md Escenario 1.

### Incremental Delivery

1. Setup → Foundational → listo para las 3 historias.
2. User Story 1 → validar (Escenario 1) → demo del registro de pagos.
3. User Story 2 → validar (Escenario 2) → demo de la jerarquía de avance de pago.
4. User Story 3 → validar (Escenario 3) → demo de corrección de pagos.
5. Polish (T027-T032) cierra la feature.

---

## Notes

- `[Story]` = trazabilidad a las historias de usuario de `spec.md`; T004 no lleva `[Story]` por ser
  Foundational compartida por las 3.
- `[P]` = archivos distintos sin dependencia de código entre las tareas.
- T027-T028 existen porque FR-006 (ya resuelto en Clarifications) rompe deliberadamente el contrato público
  de `ServicioCambioEstadoRecibo` y las transiciones manuales que `ReciboControllerTest.php` ya cubría —
  quedan documentadas aparte para no perderlas de vista durante la implementación de las 3 historias.
