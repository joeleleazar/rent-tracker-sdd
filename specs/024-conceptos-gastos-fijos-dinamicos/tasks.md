---

description: "Task list for 024-conceptos-gastos-fijos-dinamicos"
---

# Tasks: Catálogo Dinámico de Conceptos de Gastos Fijos, Periodo Ágil y Totales por Locación

**Input**: Design documents from `/specs/024-conceptos-gastos-fijos-dinamicos/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/*, quickstart.md

**Tests**: incluidas — Principio IV de la constitución; esta feature es de alto riesgo de regresión (toca
lógica de facturación ya probada en specs/004/005/019/023) y requiere además un test de migración de datos
(Escenario 1 de quickstart.md).

**Organization**: 4 historias de usuario (US1 y US2 en P1, US3 y US4 en P2), más una fase Foundational
mínima (solo el catálogo en sí). US2 es, por lejos, la historia más grande — reemplaza toda la estructura de
datos de conceptos que specs/023 acababa de construir.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan`/`pest` en esta máquina.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

## Phase 2: Foundational — el catálogo mínimo

**Propósito**: crear la tabla y el modelo del catálogo de conceptos, con las 5 filas iniciales sembradas.
Bloquea US1 y US2 (ambas necesitan que el catálogo exista); no bloquea US3 (periodo ágil es independiente
del modelo de conceptos).

- [X] T002 Migración `create_conceptos_gasto_fijo_table`: columnas `id`, `nombre`, `clave` (string, nullable, unique), `orden` (integer), `activo` (boolean, default true), timestamps; sembrar dentro de la misma migración (`up()`) las 5 filas iniciales (research.md Decisión 4): Renta (`clave='renta'`, orden 1), Agua (orden 2), Luz (`clave='luz'`, orden 3), "Luz de Pasadizo" (orden 4), Seguridad (orden 5).
- [X] T003 Modelo `app/Models/ConceptoGastoFijo.php`: `$fillable` (`nombre`, `orden`, `activo`), scopes `activos()`/`ordenados()`, accessor `esProtegido(): bool` (`clave !== null`).
- [X] T004 [P] Factory `database/factories/ConceptoGastoFijoFactory.php`.
- [X] T005 [P] Test unitario `tests/Unit/ConceptoGastoFijoTest.php`: el sembrado inicial tiene exactamente 5 filas con las claves `renta`/`luz` en las posiciones esperadas; `activos()`/`ordenados()` filtran y ordenan correctamente.

**Checkpoint**: el catálogo mínimo existe — US1 y US2 pueden empezar (en paralelo entre sí si hay más de un
desarrollador; secuencialmente si es uno solo, dado el tamaño de US2).

---

## Phase 3: User Story 1 - Administrar el catálogo de conceptos de gastos fijos (Priority: P1)

**Goal**: pantalla CRUD para crear, renombrar, reordenar y desactivar conceptos, protegiendo "Renta" contra
eliminación/desactivación y protegiendo cualquier concepto en uso contra eliminación (no desactivación).

**Independent Test**: crear un concepto nuevo, verificar que queda disponible; intentar desactivar/eliminar
"Renta" y verificar que el sistema lo impide (quickstart.md Escenario 3).

### Tests for User Story 1 ⚠️

- [X] T006 [P] [US1] Feature test `tests/Feature/ConceptoGastoFijoControllerTest.php`: `index` lista los 5 iniciales + uno creado; `store` crea un concepto con `clave=null`; `update` renombra/reordena/activa-desactiva un concepto regular; `update` rechaza desactivar el concepto con `clave='renta'`; `destroy` rechaza eliminar "Renta"; `destroy` rechaza eliminar un concepto referenciado por al menos un contrato o recibo, indicando la cantidad; `destroy` elimina un concepto sin ningún uso.

### Implementation for User Story 1

- [X] T007 [US1] Form Request `app/Http/Requests/SolicitudGuardarConceptoGastoFijo.php` (`nombre` requerido, `orden` requerido entero, `activo` boolean — sin aceptar `clave` nunca desde el formulario).
- [X] T008 [US1] Controlador `app/Http/Controllers/ConceptoGastoFijoController.php`: `index`/`create`/`store`/`edit`/`update`/`destroy`, con las reglas de protección de FR-002/FR-003 (contracts/conceptos-gasto-fijo-crud.md).
- [X] T009 [US1] Rutas `conceptosGastoFijo.*` en `routes/web.php` (grupo `auth`).
- [X] T010 [P] [US1] Vistas `resources/views/conceptos-gasto-fijo/index.blade.php`, `create.blade.php`, `edit.blade.php` — `index` muestra si cada concepto está protegido y en cuántos contratos/recibos está en uso; el botón "Eliminar" usa el modal de confirmación de dos botones ya exigido por el Principio III.
- [X] T011 [US1] Enlace "Conceptos de Gasto Fijo" al menú lateral (`resources/views/components/layouts/app-bootstrap.blade.php`), junto a "Configuración".

**Checkpoint**: US1 completa y verificable de forma independiente.

---

## Phase 4: User Story 2 - Configurar el valor de referencia de cada concepto por contrato (Priority: P1)

**Goal**: cada contrato configura su propio valor por concepto (salvo Renta/Luz); la emisión de recibos
(individual y masiva) pasa a leer y escribir conceptos dinámicos en vez de las columnas fijas de specs/023.
Incluye migrar los datos existentes sin pérdida.

**Independent Test**: configurar un valor de referencia para un concepto nuevo en un contrato, verificar que
aparece sugerido al emitir un recibo de esa locación (quickstart.md Escenario 2); verificar que "Luz" sigue
viniendo de la lectura de medidor (Escenario 4); verificar que la migración no altera ningún monto histórico
(Escenario 1).

### Tests for User Story 2 ⚠️

> Escribir/actualizar primero, confirmar que fallan contra el modelo viejo antes de implementar T017-T037.

- [X] T012 [P] [US2] Reescribir `tests/Unit/ServicioGeneracionReciboPeriodoTest.php` para el catálogo dinámico: `conceptosDisponibles()` opera sobre `ConceptoGastoFijo::activos()` en vez del array fijo `CONCEPTOS`; los tests de superposición de conceptos crean/verifican filas de `recibo_conceptos` en vez de columnas booleanas.
- [X] T013 [P] [US2] Actualizar `tests/Feature/ReciboControllerTest.php`: payload dinámico `conceptos[{concepto_gasto_fijo_id}][monto]` en vez de `incluye_agua`/`monto_agua` fijos (contracts/recibo-conceptos-dinamico.md); "Renta" sigue usando `incluye_alquiler`/`monto_renta`.
- [X] T014 [P] [US2] Actualizar `tests/Feature/RegistroMasivoRecibosControllerTest.php`: mismo cambio de payload en el modal y en `store`.
- [X] T015 [P] [US2] Nuevo test `tests/Feature/ContratoControllerTest.php` (o archivo existente si ya cubre costos): `contratos.costos.update` acepta `valores[{concepto_gasto_fijo_id}]`, nunca ofrece ni acepta un valor para "Renta" ni "Luz", y no altera ningún recibo ya emitido (FR-007).
- [X] T016 [US2] Test de migración `tests/Feature/MigracionConceptosGastoFijoTest.php` (o script de verificación documentado en quickstart.md si no encaja como test Pest estándar): sembrar contratos/recibos con el esquema viejo (factories con estado "legacy" o datos crudos), correr las migraciones de backfill, y verificar que `Contrato::valorDeConcepto()` y `Recibo::total()` devuelven exactamente los mismos valores que las columnas viejas tenían.

### Implementation for User Story 2

- [X] T017 [US2] Migración `create_contrato_valores_concepto_table` (`contrato_id`, `concepto_gasto_fijo_id`, `valor` decimal(12,2), `unique(contrato_id, concepto_gasto_fijo_id)`).
- [X] T018 [US2] Migración de backfill: por cada contrato existente, insertar en `contrato_valores_concepto` los valores de `costo_agua`/`costo_pasadizo`/`costo_seguridad` (no `costo_luz`, research.md Decisión 5) contra los conceptos correspondientes por `clave`/`nombre` del sembrado inicial. Depende de T002, T017.
- [X] T019 [US2] Migración `create_recibo_conceptos_table` (`recibo_id`, `concepto_gasto_fijo_id`, `monto` decimal(12,2), `unique(recibo_id, concepto_gasto_fijo_id)`).
- [X] T020 [US2] Migración de backfill: por cada recibo existente, insertar en `recibo_conceptos` una fila por cada concepto con `incluye_*=true` (agua/luz/pasadizo/seguridad) usando su `monto_*` correspondiente; "alquiler" no se migra a esta tabla (ya vive en `recibos.monto_renta`, que no cambia). Depende de T002, T019.
- [X] T021 [US2] Migración: eliminar `costo_agua`/`costo_luz`/`costo_pasadizo`/`costo_seguridad` de `contratos`. Depende de T018 (backfill ya corrido y verificado por T016).
- [X] T022 [US2] Migración: eliminar `incluye_alquiler`/`incluye_agua`/`incluye_luz`/`incluye_pasadizo`/`incluye_seguridad`/`monto_agua`/`monto_luz`/`monto_pasadizo`/`monto_seguridad` de `recibos`. Depende de T020 (backfill ya corrido y verificado por T016).
- [X] T023 [P] [US2] Modelo `app/Models/ValorConceptoContrato.php`.
- [X] T024 [P] [US2] Modelo `app/Models/ReciboConcepto.php`.
- [X] T025 [US2] `app/Models/Contrato.php`: quitar `costo_*` de `$fillable`/`casts()`; agregar `valoresConceptos(): HasMany` y `valorDeConcepto(ConceptoGastoFijo $concepto): ?float`. Depende de T021, T023.
- [X] T026 [US2] `app/Models/Recibo.php`: quitar `incluye_*`/`monto_agua|luz|pasadizo|seguridad` de `$fillable`/`casts()`; agregar `conceptos(): HasMany`; reescribir `total()` = `(float) ($this->monto_renta ?? 0) + $this->conceptos->sum('monto')`. Depende de T022, T024.
- [X] T027 [US2] `app/Exceptions/ConceptosReciboYaCubiertosException.php`: quitar la constante `ETIQUETAS` fija; el constructor recibe la colección de `ConceptoGastoFijo` superpuestos (no claves `incluye_*`) y arma el mensaje desde `->nombre`.
- [X] T028 [US2] `app/Services/ServicioGeneracionReciboPeriodo.php`: reescribir `conceptosDisponibles()`/`reciboQueCubre()`/`generar()`/`actualizar()`/la validación de no-superposición para operar sobre `ConceptoGastoFijo::activos()` y filas de `recibo_conceptos`, en vez del array fijo `self::CONCEPTOS`. Depende de T023-T027.
- [X] T029 [P] [US2] `app/Http/Requests/SolicitudGuardarCostosContrato.php` + `ContratoController::actualizarCostos()`: payload `valores[{concepto_gasto_fijo_id}]`, validando que ningún id enviado corresponda a un concepto con `clave` no nula. Depende de T025.
- [X] T030 [P] [US2] `resources/views/contratos/partials/costos-fijos-contrato.blade.php`: loop dinámico sobre `ConceptoGastoFijo::activos()->excluyendo Renta/Luz` en vez de los 4 campos fijos (contracts/contrato-valores-concepto.md).
- [X] T031 [US2] `app/Http/Requests/SolicitudGuardarRecibo.php` + `ReciboController::create()`/`store()`/`update()`: payload dinámico (contracts/recibo-conceptos-dinamico.md), "Renta" conserva su forma fija (`incluye_alquiler`/`monto_renta`). Depende de T028.
- [X] T032 [P] [US2] `resources/views/locaciones/recibos/create.blade.php`, `edit.blade.php`: loop dinámico sobre conceptos disponibles/ya incluidos, en vez de 4 bloques fijos (Agua/Luz/Pasadizo/Seguridad).
- [X] T033 [P] [US2] `resources/views/locaciones/recibos/show.blade.php`, `comprobante.blade.php`: iterar `$recibo->conceptos` (con el nombre vigente del concepto) en vez de los 4 `@if incluye_*` fijos.
- [X] T034 [US2] `app/Http/Requests/SolicitudGuardarReciboRegistroMasivo.php` + `RegistroMasivoRecibosController::modal()`/`store()`: payload dinámico. Depende de T028.
- [X] T035 [P] [US2] `resources/views/recibos/registro-masivo/partials/modal-recibo.blade.php`, `estado-recibo-locacion.blade.php`: iterar el catálogo (vía `ConceptosReciboYaCubiertosException`/el nuevo origen de etiquetas) en vez del array fijo de 5 claves.
- [X] T036 [US2] `database/seeders/DatabaseSeeder.php`: los 3 `Recibo::create()` y los 2 `Contrato::create()` que hoy usan `costo_*`/`monto_agua`/`monto_luz`/`monto_pasadizo`/`monto_seguridad` directamente DEBEN reescribirse para usar `valoresConceptos()->create()`/`conceptos()->create()` tras crear el contrato/recibo — de lo contrario el seeder falla con "columna inexistente" apenas se corran estas migraciones (mismo tipo de gap ya corregido reactivamente en specs/022; esta vez se corrige de forma proactiva, en la misma feature que cambia el esquema).
- [X] T037 [P] [US2] `database/factories/ContratoFactory.php`, `database/factories/ReciboFactory.php`: quitar `costo_*`/`monto_agua|luz|pasadizo|seguridad`/`incluye_*` de `definition()` (ya no son columnas); agregar un método de estado (`->conValorConcepto(...)` / `->conConcepto(...)`) para que los tests puedan seguir configurando estos datos de forma conveniente.
- [X] T038 [US2] Correr T012-T016 y confirmar que ahora pasan; correr la suite completa y confirmar 0 regresiones fuera de las ya previstas.

**Checkpoint**: US2 completa — el catálogo dinámico gobierna tanto la configuración de contratos como la
emisión de recibos (individual y masiva), con los datos existentes migrados sin pérdida (quickstart.md
Escenario 1).

---

## Phase 5: User Story 3 - Cambiar de periodo sin recargar la pantalla, con flechas (Priority: P2)

**Goal**: flechas «anterior»/«siguiente» + autoenvío del selector de mes, sin botón ni recarga completa, en
las dos pantallas de registro masivo. Independiente de US1/US2 — no depende del catálogo de conceptos.

**Independent Test**: clic en «Siguiente»/«Anterior» actualiza la tabla sin recarga completa; elegir un mes
en el selector hace lo mismo sin botón adicional (quickstart.md Escenario 5).

### Tests for User Story 3 ⚠️

- [X] T039 [P] [US3] Feature test en `tests/Feature/RegistroMasivoLecturasControllerTest.php`: `GET lecturas.registroMasivo.index` con `periodo` correspondiente al mes siguiente/anterior devuelve 200 con las filas de ese periodo, alcanzable como si viniera de las flechas nuevas.
- [X] T040 [P] [US3] Test análogo en `tests/Feature/RegistroMasivoRecibosControllerTest.php`.

### Implementation for User Story 3

- [X] T041 [US3] `resources/views/lecturas/registro-masivo/index.blade.php`: agregar flechas «‹ Anterior»/«Siguiente ›» (`hx-get` con `periodo` ± 1 mes calculado en el servidor) y `hx-trigger="change"` + sin botón en el selector de mes (research.md Decisión 7, contracts/periodo-agil.md); ajustar `hx-target`/`hx-swap` al contenedor de toda la tabla.
- [X] T042 [P] [US3] `resources/views/recibos/registro-masivo/index.blade.php`: mismo cambio, cuidando que el contenedor de modal compartido (specs/023) quede fuera del área reemplazada.
- [X] T043 [US3] Verificar manualmente (quickstart.md Escenario 5, doble clic rápido en una flecha) que htmx cancela la petición en vuelo del mismo elemento por defecto, sin necesitar JS adicional — si hiciera falta, agregar `resources/js/periodo-agil.js` con el mínimo necesario.

**Checkpoint**: US3 completa, verificable independientemente de US1/US2.

---

## Phase 6: User Story 4 - Ver el total facturado y la cantidad de recibos por locación (Priority: P2)

**Goal**: cada fila de la pantalla de registro masivo de recibos muestra cuántos recibos y cuánto total
lleva facturado esa locación en el periodo visible, excluyendo recibos anulados.

**Independent Test**: una locación con 2 recibos muestra "2 recibos" y la suma correcta; anular uno de los
dos baja la cuenta y el total (quickstart.md Escenario 6).

### Tests for User Story 4 ⚠️

- [X] T044 [P] [US4] Feature test en `tests/Feature/RegistroMasivoRecibosControllerTest.php`: locación con 2 recibos (uno cubriendo renta, otro el resto) muestra "2 recibos" y el total exacto de ambos; locación sin recibos muestra "0 recibos" y S/ 0.00; un tercer recibo anulado de esa misma locación no se cuenta ni se suma.

### Implementation for User Story 4

- [X] T045 [US4] `RegistroMasivoRecibosController::datosDelPeriodo()`: agregar, por locación, `cantidadRecibos`/`totalFacturado` calculados sobre la misma colección de recibos ya agrupada (excluyendo `estado = 'anulado'`, research.md Decisión 8) — sin consultas adicionales.
- [X] T046 [US4] `resources/views/recibos/registro-masivo/partials/fila-registro-masivo-recibos.blade.php` / `estado-recibo-locacion.blade.php`: mostrar la cantidad de recibos y el total con la clase `.cifra`.

**Checkpoint**: las 4 historias completas y verificables de forma independiente.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T047 [P] Correr `php artisan test` completo (binario Herd) y confirmar 0 regresiones sobre toda la suite.
- [X] T048 Revisión de diseño con el skill `impeccable` (`/impeccable polish` o `audit`) sobre todas las vistas nuevas/modificadas de esta feature (Principio VI), documentando el resultado en `DESIGN.md` si corresponde.
- [X] T049 Validar manualmente los 6 escenarios de `specs/024-conceptos-gastos-fijos-dinamicos/quickstart.md`, en particular el Escenario 1 (migración sin pérdida de datos) contra la base de datos de desarrollo real.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de T001. Bloquea US1 y US2.
- **User Story 1 (Phase 3)**: depende de Foundational. Independiente de US2 (el catálogo ya existe desde
  Foundational; US1 es solo su UI de administración).
- **User Story 2 (Phase 4)**: depende de Foundational. No depende de US1 (el catálogo sembrado en
  Foundational ya alcanza para que US2 opere, aunque en la práctica conviene tener US1 para poder agregar
  conceptos nuevos y probar ese camino).
- **User Story 3 (Phase 5)**: depende solo de Setup — no toca el catálogo de conceptos en absoluto. Puede
  implementarse en paralelo con US1/US2, o antes.
- **User Story 4 (Phase 6)**: depende de US2 (necesita `Recibo::total()` ya reescrito sobre `recibo_conceptos`
  para que sumar totales tenga sentido con el modelo nuevo).
- **Polish (Phase 7)**: depende de que las 4 historias estén completas.

### Dentro de cada fase

- Los tests de cada fase (⚠️) se escriben/actualizan antes que su implementación y deben fallar primero
  (Principio IV) — para US2, "fallar primero" significa fallar contra el modelo viejo tal como queda tras
  Foundational, antes de tocar T017 en adelante.
- Dentro de US2: T017-T022 (migraciones) son estrictamente secuenciales entre sí (cada una depende de que la
  anterior haya corrido); T023-T024 (modelos) pueden ir en paralelo entre sí tras T017/T019; T025-T028
  dependen de los modelos; T029-T037 dependen de T025-T028 y son mayormente paralelizables entre sí (archivos
  distintos).

### Parallel Opportunities

- T004, T005 (Foundational) en paralelo entre sí.
- T006 (test US1) puede escribirse en paralelo con Foundational terminando.
- T010 (vistas US1) en paralelo consigo misma (3 archivos, misma tarea agrupada por simplicidad).
- T012, T013, T014, T015 (tests US2) en paralelo entre sí.
- T023, T024 (modelos US2) en paralelo entre sí.
- T029/T030, T032/T033, T035, T037 en paralelo entre sí (archivos distintos, todos dependen de T025-T028).
- T039, T040 (tests US3) en paralelo entre sí; US3 completa en paralelo con US1/US2 si hay más de un
  desarrollador.
- T044 (test US4) depende de que US2 esté terminada, pero es independiente de US3.

---

## Implementation Strategy

### MVP First (US1 + US2 = P1)

1. Setup (T001) → Foundational (T002-T005, el catálogo mínimo).
2. US1 (T006-T011): pantalla de administración del catálogo.
3. US2 (T012-T038): la migración completa del modelo de datos y de los 3 flujos de recibo/contrato que lo
   consumen — es, con diferencia, el bloque más grande de esta feature.
4. **Parar y validar**: quickstart.md Escenarios 1-4.

### Incremental Delivery

1. Setup → Foundational → listo para US1/US2/US3 en paralelo (US3 no depende de nada de esto).
2. US3 (T039-T043) puede entregarse primero o en paralelo — es la historia más chica y más aislada de las 4.
3. US1 → validar independientemente (catálogo administrable) → demo.
4. US2 → validar independientemente (migración + flujos dinámicos) → MVP completo del pedido original.
5. US4 (T044-T046, depende de US2) → validar → feature completa.
6. Polish (suite completa, revisión `impeccable`, validación de los 6 escenarios de `quickstart.md`) cierra
   la feature.

### Recomendación de orden real de ejecución (dado que es una sola persona/agente, no un equipo)

Dado el tamaño de US2 y que US4 depende de ella, el orden más eficiente en la práctica es: Foundational → US3
(chica, se saca del medio) → US1 → US2 → US4 → Polish — en vez del orden estrictamente por prioridad P1/P2,
para no dejar a US2 bloqueando todo el resto del trabajo disponible mientras se implementa.

---

## Notes

- [P] = archivos distintos, sin dependencia de código entre las tareas.
- [US1]/[US2]/[US3]/[US4] = trazabilidad a las historias de usuario de `spec.md`.
- T036 (DatabaseSeeder) y T037 (factories) son las dos tareas que existen específicamente para no repetir el
  tipo de gap que motivó specs/022 (un punto de escritura del esquema viejo que nadie actualiza al mismo
  tiempo que el resto del código) — se corrigen dentro de la misma feature que cambia el esquema, no después.
- Confirmar que los tests de cada fase fallan contra el estado anterior antes de implementar su código
  correspondiente.
