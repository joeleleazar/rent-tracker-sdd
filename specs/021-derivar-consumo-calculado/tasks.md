---

description: "Task list for 021-derivar-consumo-calculado"
---

# Tasks: Consumo Calculado en el Momento en vez de Almacenado

**Input**: Design documents from `/specs/021-derivar-consumo-calculado/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/accessor-consumo-calculado.md, quickstart.md

**Tests**: incluidas — Principio IV de la constitución + brecha de pruebas identificada en
`research.md` (el caso "sin lectura anterior" cambia de comportamiento por Q1:A, y la prueba de
"cast" de `consumo_calculado` deja de tener sentido tal como está escrita).

**Organization**: una sola historia de usuario (US1, P1) — no hay historias independientes que
paralelizar a ese nivel; las tareas dentro de US1 sí se paralelizan por archivo donde aplica.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`)
para `artisan`/`pest` en esta máquina.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

## Phase 2: Foundational

No hay tareas fundacionales separadas de US1: a diferencia de specs/019, la columna
`consumo_calculado` ya es `nullable` (no hay una migración `NOT NULL` que rompa el resto de la
suite al aplicarse) — el orden seguro es agregar el accessor primero, limpiar los 6 puntos de
escritura después, y recién al final quitar la columna (Phase 3, en ese orden).

**Checkpoint**: sin bloqueos — se puede empezar directamente por la Historia 1 (P1).

---

## Phase 3: User Story 1 - Ver siempre el consumo correcto, sin importar cómo se registró la lectura (Priority: P1)

**Goal**: el consumo de cada lectura se calcula en el momento a partir de `lectura_actual`/
`lectura_anterior` (accessor `consumo_calculado`, mismo nombre y formato que la columna que
reemplaza — contracts/accessor-consumo-calculado.md), sin lectura anterior tratada como 0 en todo
el sistema (Q1:A), y la columna deja de existir.

**Independent Test**: ver el consumo de una lectura con anterior conocida y de una sin anterior, en
las tres pantallas que lo muestran (historial individual, formulario de recibo, registro masivo), y
verificar que coincide con lectura actual menos lectura anterior (0 si no hay anterior) en las tres.

### Tests for User Story 1 ⚠️

> Escribir primero, confirmar que fallan antes de implementar T004.

- [X] T002 [US1] Reescribir la prueba `'lectura_anterior, lectura_actual y consumo_calculado se castean como decimal'` en `tests/Unit/LecturaMedidorTest.php` como una prueba del accessor: crear una lectura con `lectura_actual`/`lectura_anterior` conocidos y afirmar que `consumo_calculado` es la resta correcta con 2 decimales; agregar un caso sin `lectura_anterior` afirmando 0 como anterior (research.md Decisión 1/2).
- [X] T003 [US1] En `tests/Feature/LecturaMedidorControllerTest.php`, actualizar la aserción `expect($lectura->consumo_calculado)->toBeNull()` (línea ~24, caso sin lectura anterior del flujo individual) para reflejar Q1:A: el consumo pasa a ser igual a la lectura actual (0 como anterior), no `null`.

### Implementation for User Story 1

- [X] T004 [US1] En `app/Models/LecturaMedidor.php`: quitar `consumo_calculado` de `$fillable` y de `casts()`; agregar el accessor `consumoCalculado()` (`Illuminate\Database\Eloquent\Casts\Attribute`) que devuelve `lectura_actual − (lectura_anterior ?? 0)` redondeado a 2 decimales como string (research.md Decisión 1/2; contracts, Contrato 1).
- [X] T005 [P] [US1] En `app/Http/Controllers/LecturaMedidorController.php`, quitar `'consumo_calculado' => $consumo` del `LecturaMedidor::create()` de `store()` (línea ~90) y del `$lectura->update()` de `update()` (línea ~137) — la variable `$consumo` se mantiene donde todavía se usa para el chequeo de `ConsumoNegativoSinConfirmarException` (research.md Decisión 3).
- [X] T006 [P] [US1] En `app/Http/Controllers/RegistroMasivoLecturasController.php`, quitar `'consumo_calculado' => $consumo` del `LecturaMedidor::create()` de `store()` (línea ~158) y del `$lectura->update()` de `actualizarInline()` (línea ~307).
- [X] T007 [P] [US1] En `app/Console/Commands/ImportarLecturasMedidorHistoricas.php`, quitar `'consumo_calculado' => $consumoCalculado` del `LecturaMedidor::create()` (línea ~109) y la variable `$consumoCalculado` que queda sin uso.
- [X] T008 [P] [US1] En `database/seeders/DatabaseSeeder.php`, quitar la clave `'consumo_calculado'` del `create()` de lecturas de medidor (línea ~111).
- [X] T009 [P] [US1] En `database/factories/LecturaMedidorFactory.php`, quitar `'consumo_calculado' => null` de `definition()`.
- [X] T010 [US1] En `resources/views/locaciones/lecturas/index.blade.php`, quitar la rama `@if ($lectura->consumo_calculado === null)` ("sin dato anterior") — el consumo siempre tiene un valor tras T004 (research.md Decisión 4). Depende de T004.
- [X] T011 [P] [US1] En `resources/views/locaciones/recibos/create.blade.php`, simplificar `$lectura !== null && $lectura->consumo_calculado !== null` a solo `$lectura !== null` (research.md Decisión 4). Depende de T004.
- [X] T012 [US1] Crear la migración `eliminar_consumo_calculado_de_lecturas_medidor`: `DROP COLUMN consumo_calculado` de `lecturas_medidor`, sin backfill (data-model.md) — en `database/migrations/`. Depende de T004-T009 (nada debe seguir escribiendo esa columna antes de eliminarla).
- [X] T013 [US1] Correr `php artisan migrate` y luego la suite completa (`php artisan test`, binario Herd) — confirmar que solo cambian las aserciones ya previstas en T002/T003, sin ninguna otra regresión.

**Checkpoint**: US1 completa y verificable de forma independiente — `quickstart.md` Escenarios 1-3.

---

## Phase 4: Polish & Cross-Cutting Concerns

- [X] T014 [P] Correr `php artisan test` completo (binario Herd) una vez más y confirmar 0 regresiones sobre toda la suite.
- [X] T015 Revisión de diseño con el skill `impeccable` (`/impeccable polish` o `audit`) sobre `locaciones/lecturas/index.blade.php` y `locaciones/recibos/create.blade.php` (las 2 vistas modificadas), documentando el resultado en `DESIGN.md` si corresponde (Principio VI).
- [X] T016 Validar los 3 escenarios de `specs/021-derivar-consumo-calculado/quickstart.md` — a diferencia de specs/017/019/020, esta vez es 100% verificable con Pest/`tinker` (sin comportamiento de navegador involucrado), así que puede confirmarse sin abrir un navegador.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: vacía.
- **User Story 1 (Phase 3)**: depende de T001. Única historia — no hay otras con las que
  coordinar dependencias entre historias.
- **Polish (Phase 4)**: depende de que US1 esté completa.

### Dentro de la historia

- T002-T003 (pruebas) se escriben antes que T004 y deben fallar primero (Principio IV).
- T005-T009 dependen conceptualmente de T004 (el nuevo contrato del accessor), pero no hay
  dependencia técnica dura entre ellos — son paralelizables entre sí (archivos distintos).
- T010 y T011 dependen de T004 (necesitan que el criterio de 0 ya sea real para que la
  simplificación sea correcta).
- T012 (migración `DROP COLUMN`) depende de que T004-T009 ya hayan quitado toda escritura de la
  columna — va después de todos ellos.
- T013 depende de T012.

### Parallel Opportunities

- T005, T006, T007, T008, T009 en paralelo entre sí (archivos distintos, todos dependen solo de T004).
- T010 y T011 en paralelo entre sí (archivos distintos).

---

## Parallel Example: User Story 1

```bash
# Tras T004, lanzar en paralelo (archivos distintos):
Task: "Quitar escritura de consumo_calculado en LecturaMedidorController.php"
Task: "Quitar escritura de consumo_calculado en RegistroMasivoLecturasController.php"
Task: "Quitar escritura de consumo_calculado en ImportarLecturasMedidorHistoricas.php"
Task: "Quitar escritura de consumo_calculado en DatabaseSeeder.php"
Task: "Quitar default de consumo_calculado en LecturaMedidorFactory.php"
```

---

## Implementation Strategy

### MVP First (única historia)

1. T001 (Setup).
2. Phase 2 no tiene tareas — pasar directo a Phase 3.
3. Completar Phase 3 completa: no hay un "MVP parcial" razonable para esta feature — quitar la
   columna sin haber limpiado los 6 puntos de escritura dejaría el sistema en un estado
   inconsistente (código muerto escribiendo a una columna eliminada).
4. **Parar y validar**: correr T013 y los 3 escenarios de `quickstart.md`.

### Incremental Delivery

1. Setup → Foundational (vacía) → listo para empezar.
2. US1 completa de punta a punta (T002-T013) → validar → única entrega de esta feature.
3. Phase 4 (Polish: suite completa, revisión `impeccable`, validación de `quickstart.md`) cierra la
   feature.

---

## Notes

- [P] = archivos distintos, sin dependencia de código entre las tareas.
- [US1] = trazabilidad a la única historia de usuario de `spec.md`.
- Ningún archivo de rutas, servicios (`ServicioCalculoConsumoMedidor`,
  `ServicioGeneracionReciboPeriodo`) ni el flujo de `total` (specs/019) se toca en esta feature —
  el cambio es exclusivamente sobre `consumo_calculado`.
- Confirmar que las pruebas fallan antes de implementar T004.
