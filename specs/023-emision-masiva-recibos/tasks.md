---

description: "Task list for 023-emision-masiva-recibos"
---

# Tasks: Emisión Masiva de Recibos por Periodo

**Input**: Design documents from `/specs/023-emision-masiva-recibos/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/rutas-registro-masivo-recibos.md,
quickstart.md

**Tests**: incluidas — Principio IV de la constitución (esta feature SÍ es lógica de dominio de
controlador/servicio, a diferencia de specs/022; ver plan.md Constitution Check).

**Organization**: 3 historias de usuario (US1 P1 vista, US2 P1 modal+generación, US3 P2 cobro fraccionado),
más una fase Foundational que cambia la regla de negocio compartida con el flujo individual ya existente.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan`/`pest` en esta máquina.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

## Phase 2: Foundational — nueva regla de no-superposición de conceptos

**Propósito**: reemplazar la regla "un solo recibo por locación y periodo" por una regla a nivel de
concepto, en el único lugar de dónde depende toda la feature (`ServicioGeneracionReciboPeriodo`) y en el
flujo individual ya existente que la comparte (Assumption A-003 de spec.md). Bloquea las 3 historias:
ninguna puede generar recibos correctamente hasta que esta regla exista.

**⚠️ CRITICAL**: ningún trabajo de las Historias 1-3 puede completarse (aunque sí empezarse en paralelo por
archivo) sin que esta fase esté terminada.

### Tests for Foundational ⚠️

> Escribir primero, confirmar que fallan antes de implementar T005-T008.

- [X] T002 [P] En `tests/Unit/ServicioGeneracionReciboPeriodoTest.php`, reescribir el test `'bloquea un segundo recibo para la misma locacion y periodo'` (línea ~42): debe seguir bloqueando cuando el segundo recibo repite un concepto ya cubierto (afirmar `ConceptosReciboYaCubiertosException`, no `ReciboDuplicadoPeriodoException`), y agregar un caso nuevo que confirme que SÍ se permite un segundo recibo con conceptos distintos a los del primero (data-model.md).
- [X] T003 [P] En `tests/Unit/ServicioGeneracionReciboPeriodoTest.php`, agregar un test para el nuevo método de conceptos disponibles/cubiertos (research.md Decisión 1): dado un recibo existente que cubre `incluye_alquiler`, afirmar que el cálculo devuelve exactamente los otros 4 como disponibles.
- [X] T004 [P] En `tests/Feature/ReciboControllerTest.php`, agregar/actualizar tests del flujo individual (Assumption A-003): `create()` ya no bloquea todo el formulario si existe un recibo previo que cubre solo algunos conceptos (debe seguir ofreciendo los conceptos todavía disponibles); `store()` rechaza con 422 si el usuario intenta incluir un concepto ya cubierto por otro recibo del mismo periodo y locación, sin afectar a los demás.

### Implementation for Foundational

- [X] T004b **(descubierta al ejecutar T002)** Crear y correr la migración `quitar_unique_locacion_periodo_de_recibos`: la tabla `recibos` tenía una constraint `UNIQUE(locacion_id, periodo)` real (agregada en specs/004, `2026_08_21_042852_add_conceptos_a_recibos_table.php`) que research.md había pasado por alto al planificar — el primer intento de T002 falló con `SQLSTATE[23505]` antes de llegar a la lógica de aplicación. Reemplazarla por un índice no único. Ver research.md Decisión 2 (actualizada).
- [X] T005 Crear `app/Exceptions/ConceptosReciboYaCubiertosException.php`: recibe la lista de claves de concepto superpuestas y la colección de recibos existentes que las cubren (research.md Decisión 1); eliminar `app/Exceptions/ReciboDuplicadoPeriodoException.php` (código muerto tras este cambio) y sus imports.
- [X] T006 En `app/Services/ServicioGeneracionReciboPeriodo.php`: agregar un método que calcule conceptos cubiertos/disponibles de una locación y periodo (data-model.md) a partir de los recibos existentes; reescribir `generar()` para usar `DB::transaction()` + `Recibo::...->lockForUpdate()->get()` (research.md Decisión 3) y lanzar `ConceptosReciboYaCubiertosException` si algún concepto solicitado ya está cubierto, en vez del chequeo actual de "¿existe algún recibo?". Depende de T005.
- [X] T007 En `app/Services/ServicioGeneracionReciboPeriodo.php`: aplicar la misma regla de no-superposición en `actualizar()` — un recibo existente no puede editarse para incluir un concepto que otro recibo del mismo periodo y locación ya cubre (excluyendo al propio recibo de la comparación). Depende de T006.
- [X] T008 En `app/Http/Controllers/ReciboController.php`: reemplazar el cálculo de `$reciboExistente` (línea ~48, `Recibo::where(...)->first()`) por el nuevo método de conceptos disponibles/cubiertos; actualizar `store()`/`update()` para capturar `ConceptosReciboYaCubiertosException` en vez de `ReciboDuplicadoPeriodoException`. Depende de T006-T007.
- [X] T009 En `resources/views/locaciones/recibos/create.blade.php`: reemplazar el bloqueo total (`@if ($reciboExistente !== null)`, línea ~30) por una vista que oculta/deshabilita solo los checkboxes de los conceptos ya cubiertos (con referencia al recibo que los cubre) y sigue ofreciendo los disponibles. Depende de T008.
- [X] T010 Correr T002-T004 y confirmar que ahora pasan; correr la suite completa y confirmar 0 regresiones fuera de las ya previstas en estos tests.

**Checkpoint**: la regla de negocio compartida existe y el flujo individual ya la respeta — las Historias
1-3 pueden empezar.

---

## Phase 3: User Story 1 - Ver de un vistazo qué se debe cobrar en todas las locaciones de un periodo (Priority: P1)

**Goal**: pantalla `recibos.registroMasivo.index` que lista todas las locaciones alquilables con su
situación de cobro del periodo (contrato activo o no, conceptos cubiertos con referencia a su recibo).

**Independent Test**: con 3 locaciones (sin contrato activo, con contrato activo y sin recibo, con contrato
activo y un recibo parcial), abrir la pantalla y verificar que cada una muestra su situación correcta
(quickstart.md Escenario 1, primeros pasos).

### Tests for User Story 1 ⚠️

- [X] T011 [P] [US1] Crear `tests/Feature/RegistroMasivoRecibosControllerTest.php` con el test `'la pantalla muestra la situacion de cobro de cada locacion del periodo'`: locación sin contrato activo → mensaje claro sin acción; locación con contrato activo sin recibo → sin conceptos marcados como cubiertos; locación con un recibo parcial (solo "renta") → "renta" señalada como cubierta con referencia al recibo, resto sin marcar.

### Implementation for User Story 1

- [X] T012 [US1] Crear `app/Http/Controllers/RegistroMasivoRecibosController.php` con `index()`: reutiliza `ServicioConstruccionArbolLocaciones::construir()` y, para cada locación alquilable, resuelve `contratoActivoEnPeriodo()` y los conceptos cubiertos/disponibles del método agregado en T006.
- [X] T013 [US1] Agregar la ruta `GET /recibos/registro-masivo` → `recibos.registroMasivo.index` en `routes/web.php` (grupo `auth`, junto a las demás rutas de recibos/lecturas).
- [X] T014 [P] [US1] Crear `resources/views/recibos/registro-masivo/index.blade.php`: selector de periodo (mismo patrón que `lecturas/registro-masivo/index.blade.php`) + contenedor del árbol + contenedor de modal compartido vacío (`<x-modal-bootstrap>`, research.md Decisión 4).
- [X] T015 [P] [US1] Crear `resources/views/recibos/registro-masivo/partials/fila-registro-masivo-recibos.blade.php`: fila recursiva análoga a `fila-registro-masivo.blade.php` de lecturas — nombre/jerarquía, estado del contrato activo, por cada uno de los 5 conceptos un indicador de "cubierto" (con enlace al recibo, `recibos.show`) o "disponible", y sin ninguna acción de generación si no hay contrato activo o si no quedan conceptos disponibles (FR-009).

**Checkpoint**: US1 completa y verificable de forma independiente — la pantalla muestra el estado correcto,
aunque todavía no permite generar recibos desde ella.

---

## Phase 4: User Story 2 - Generar el recibo de una locación desde un modal, sin salir de la pantalla (Priority: P1)

**Goal**: cada fila con conceptos disponibles ofrece una acción que abre un modal (htmx, contracts/Contrato
2) con esos conceptos y sus montos sugeridos; confirmar genera el recibo de inmediato y actualiza la fila.

**Independent Test**: abrir el modal de una locación con contrato activo, marcar conceptos, confirmar, y
verificar que el recibo se creó con exactamente esos conceptos/montos y la fila se actualizó sin recargar
la página (quickstart.md Escenarios 1-2).

### Tests for User Story 2 ⚠️

- [X] T016 [P] [US2] En `tests/Feature/RegistroMasivoRecibosControllerTest.php`, agregar `'el modal muestra los conceptos disponibles con su monto sugerido'`: verificar renta prorrateada cuando el contrato no cubre el mes completo, monto de luz igual al `total` de la lectura del periodo (o S/ 0.00 sin lectura), y agua/pasadizo/seguridad iguales a los `costo_*` del contrato — mismos montos que ya prueba `ReciboControllerTest` para el flujo individual (contracts/rutas-registro-masivo-recibos.md, Contrato "modal").
- [X] T017 [P] [US2] En `tests/Feature/RegistroMasivoRecibosControllerTest.php`, agregar `'confirmar el modal genera el recibo y responde con la fila actualizada'`: verificar creación en BD con exactamente los conceptos/montos enviados, respuesta 200 con la parcial de fila (no redirect), y 422 si no se marca ningún concepto (FR-012).
- [X] T018 [P] [US2] En `tests/Feature/RegistroMasivoRecibosControllerTest.php`, agregar `'rechaza un concepto ya cubierto por otro recibo del mismo periodo y locacion'`: crear un recibo previo cubriendo "renta", intentar confirmar el modal incluyendo "renta" de nuevo, afirmar 422 con el detalle del concepto ya cubierto y que no se creó un recibo nuevo.

### Implementation for User Story 2

- [X] T019 [US2] Crear `app/Http/Requests/SolicitudGuardarReciboRegistroMasivo.php`: valida `periodo`, `fecha_emision` (opcional, default hoy), y por cada concepto marcado su `monto_*` — exige al menos un `incluye_*` presente (FR-012).
- [X] T020 [US2] Agregar `modal(Locacion $locacion)` y `store(SolicitudGuardarReciboRegistroMasivo $solicitud, Locacion $locacion)` a `RegistroMasivoRecibosController`: `modal()` arma los conceptos disponibles con monto sugerido (reutiliza `ServicioCalculoProrrateoContrato` y `ServicioGeneracionReciboPeriodo::calcularMontoLuzSugerido()`, research.md Decisión 6); `store()` delega en `ServicioGeneracionReciboPeriodo::generar()` (ya con la regla de T006) y responde con la parcial de fila de T015 actualizada, capturando `ConceptosReciboYaCubiertosException` como 422. Depende de T012, T019.
- [X] T021 [US2] Agregar las rutas `GET /recibos/registro-masivo/{locacion}/modal` → `recibos.registroMasivo.modal` y `POST /recibos/registro-masivo/{locacion}` → `recibos.registroMasivo.store` en `routes/web.php`.
- [X] T022 [P] [US2] Crear `resources/views/recibos/registro-masivo/partials/modal-recibo.blade.php`: checkbox + campo de monto editable por cada concepto disponible (mismo criterio visual que `locaciones/recibos/create.blade.php`), formulario con `hx-post` a `recibos.registroMasivo.store` apuntando al contenedor de fila correspondiente.
- [X] T023 [US2] En `fila-registro-masivo-recibos.blade.php` (T015), agregar el botón "Generar Recibo" con `hx-get` a `recibos.registroMasivo.modal` apuntando al contenedor de modal compartido de T014, visible solo si hay al menos un concepto disponible.
- [X] T024 [US2] Crear `resources/js/registro-masivo-recibos.js`: listener mínimo que abre el modal Bootstrap tras el swap de `hx-get` y lo cierra tras un `hx-post` exitoso del formulario del modal (research.md Decisiones 4-5, mismo patrón acotado que `registro-masivo-lecturas.js`); referenciarlo con `@vite` en `index.blade.php`.

**Checkpoint**: US2 completa — se puede generar recibos de varias locaciones sin salir de la pantalla
(quickstart.md Escenario 2).

---

## Phase 5: User Story 3 - Volver a generar recibo para la misma locación hasta cubrir todos sus conceptos (Priority: P2)

**Goal**: reabrir el modal de una locación con recibo(s) parcial(es) para cubrir lo que falta, sin poder
repetir un concepto ya cubierto, incluyendo bajo confirmaciones casi simultáneas.

**Independent Test**: generar un recibo cubriendo solo "renta", reabrir el modal de la misma locación y
confirmar que "renta" ya no aparece disponible; cubrir el resto con un segundo recibo (quickstart.md
Escenario 3).

### Tests for User Story 3 ⚠️

- [X] T025 [P] [US3] En `tests/Feature/RegistroMasivoRecibosControllerTest.php`, agregar `'reabrir el modal tras un recibo parcial solo ofrece los conceptos restantes'`: generar un recibo cubriendo "renta", pedir de nuevo `GET modal` de esa misma locación, y afirmar que "renta" no aparece entre los conceptos disponibles del modal mientras los otros 4 sí.
- [X] T026 [P] [US3] En `tests/Feature/RegistroMasivoRecibosControllerTest.php`, agregar `'genera un segundo recibo independiente cubriendo los conceptos restantes'`: sobre la misma locación del test anterior, confirmar el modal con los 4 conceptos restantes y afirmar que existen 2 recibos para esa locación/periodo, sin ningún concepto repetido entre ambos.
- [X] T027 [US3] En `tests/Unit/ServicioGeneracionReciboPeriodoTest.php`, agregar un test que simule la condición de carrera de FR-008 (dos llamadas secuenciales a `generar()` con el mismo concepto disponible al leerlo, documentando que dentro de un único proceso Pest esto solo puede probar que la segunda llamada relee el estado real tras la primera y falla — no una concurrencia real de dos procesos; anotar esta limitación en el test, igual que specs/016 documentó no tener Dusk/Playwright).

### Implementation for User Story 3

- [X] T028 [US3] Verificar (y ajustar si hace falta) que `fila-registro-masivo-recibos.blade.php` (T015/T023) oculta el botón "Generar Recibo" en cuanto los 5 conceptos quedan cubiertos entre uno o más recibos — no debería requerir código nuevo si T006/T012/T015 se implementaron correctamente; esta tarea es de verificación explícita contra FR-009.

**Checkpoint**: las 3 historias completas y verificables de forma independiente — `quickstart.md`
Escenarios 1-5.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T029 [P] Correr `php artisan test` completo (binario Herd) y confirmar 0 regresiones sobre toda la suite.
- [X] T030 Revisión de diseño con el skill `impeccable` (`/impeccable polish` o `audit`) sobre las 4 vistas nuevas/modificadas (`recibos/registro-masivo/index.blade.php`, `partials/fila-registro-masivo-recibos.blade.php`, `partials/modal-recibo.blade.php`, `locaciones/recibos/create.blade.php`), documentando el resultado en `DESIGN.md` si corresponde (Principio VI).
- [X] T031b **(descubierta durante T031)** Agregar el enlace "Emitir Recibos" al menú lateral (`resources/views/components/layouts/app-bootstrap.blade.php`) apuntando a `recibos.registroMasivo.index` — la pantalla solo era alcanzable por URL directa, sin ningún enlace de navegación, a diferencia de "Registrar Lecturas" que sí lo tiene.
- [X] T031 Validar manualmente los 5 escenarios de `specs/023-emision-masiva-recibos/quickstart.md` en el navegador — en particular el Escenario 4 (condición de carrera con dos pestañas) y el Escenario 2 (sin recarga completa de página), que no son 100% verificables solo con Pest.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de T001. Bloquea todas las historias de usuario.
- **User Story 1 (Phase 3)**: depende de Foundational. Independiente de US2/US3 en cuanto a lo que muestra,
  aunque comparte el método de conceptos cubiertos/disponibles de T006.
- **User Story 2 (Phase 4)**: depende de Foundational y de T012/T015 (controlador y fila de US1) para tener
  dónde montar el botón y la ruta de destino del swap.
- **User Story 3 (Phase 5)**: depende de Foundational y de US2 completa (reutiliza el mismo modal y
  controlador; no agrega endpoints nuevos, solo verifica el comportamiento ya implementado bajo reapertura).
- **Polish (Phase 6)**: depende de que las 3 historias estén completas.

### Dentro de cada fase

- Los tests de cada fase (⚠️) se escriben antes que su implementación y deben fallar primero (Principio IV).
- T005-T010 (Foundational) son mayormente secuenciales (cada una depende de la anterior).
- T012-T015 (US1) pueden avanzar en paralelo entre sí una vez existe T006; T014/T015 son paralelizables entre
  sí (archivos distintos).
- T019-T024 (US2) tienen una cadena de dependencia clara: Request → controlador → rutas → vistas → JS.

### Parallel Opportunities

- T002, T003, T004 (tests Foundational) en paralelo entre sí.
- T014 y T015 (US1) en paralelo entre sí.
- T016, T017, T018 (tests US2) en paralelo entre sí; T022 en paralelo con T019-T021.
- T025 y T026 (tests US3) en paralelo entre sí.

---

## Implementation Strategy

### MVP First (US1 + US2 = P1)

1. Setup (T001) → Foundational (T002-T010, la regla de negocio compartida).
2. US1 (T011-T015): la pantalla ya es útil por sí sola para revisar el estado de cobro, aunque todavía no
   genere nada.
3. US2 (T016-T024): habilita generar recibos desde la pantalla — junto con US1, es el MVP completo del
   pedido original.
4. **Parar y validar**: quickstart.md Escenarios 1-2.

### Incremental Delivery

1. Setup → Foundational → listo para empezar historias.
2. US1 → validar independientemente (solo lectura) → demo.
3. US2 → validar independientemente (generación) → MVP completo.
4. US3 → validar (cobro fraccionado, condición de carrera) → feature completa.
5. Polish (suite completa, revisión `impeccable`, validación de `quickstart.md`) cierra la feature.

---

## Notes

- [P] = archivos distintos, sin dependencia de código entre las tareas.
- [US1]/[US2]/[US3] = trazabilidad a las historias de usuario de `spec.md`.
- La Fase Foundational es la única con impacto fuera del alcance de esta pantalla nueva: modifica el flujo
  individual de recibos ya existente (`ReciboController`), consistente con Assumption A-003 de `spec.md`.
- Confirmar que los tests de cada fase fallan antes de implementar su código correspondiente.
