---

description: "Task list for 019-total-editable-recibos"
---

# Tasks: Lectura Anterior por Defecto y Total Editable y Persistido

**Input**: Design documents from `/specs/019-total-editable-recibos/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/total-editable-y-recibo.md, quickstart.md

**Tests**: incluidas — Principio IV de la constitución + brecha de pruebas identificada en
`research.md` (ningún test cubre hoy anterior=0, persistencia de total, o el nuevo contrato de
`calcularMontoLuzSugerido()`).

**Organization**: tareas agrupadas por historia de usuario (spec.md), en orden de prioridad. US1 y
US2 son ambas P1, pero US2 depende de US1 (research.md, "Why this priority" de US2 en spec.md).

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`)
para `artisan`/`pest` en esta máquina.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test --filter=RegistroMasivoLecturasControllerTest` y `php artisan test --filter=ServicioGeneracionReciboPeriodoTest` (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: la columna `total` (NOT NULL) es una dependencia compartida por US1 y US2 — sin
completar este bloque primero, `LecturaMedidor::factory()->create()` (usado en 6 archivos de test
de todo el proyecto, no solo de esta feature) y `LecturaMedidorController::store()` (flujo
individual, specs/005/006) empezarían a fallar por violación de NOT NULL apenas se aplique la
migración.

**⚠️ CRITICAL**: ninguna tarea de US1 o US2 puede empezar hasta completar esta fase.

- [X] T002 Crear la migración `agregar_total_a_lecturas_medidor`: columna `total` `decimal(12,2)` nullable, backfill de todas las filas existentes con `round((consumo_calculado ?? lectura_actual) * tarifa_vigente, 2)` (research.md Decisión 6), luego `NOT NULL` — en `database/migrations/`.
- [X] T003 [P] Crear la migración `agregar_total_a_borradores_lectura_medidor`: columna `total` `decimal(12,2)` nullable, sin backfill (tabla de borradores transitorios) — en `database/migrations/`.
- [X] T004 [P] Actualizar `App\Models\LecturaMedidor`: agregar `total` a `$fillable` y el cast `'total' => 'decimal:2'` — en `app/Models/LecturaMedidor.php`.
- [X] T005 [P] Actualizar `App\Models\BorradorLecturaMedidor`: agregar `total` a `$fillable` y el cast `'total' => 'decimal:2'` — en `app/Models/BorradorLecturaMedidor.php`.
- [X] T006 Actualizar `database/factories/LecturaMedidorFactory.php`: agregar `'total' => 0` a `definition()` para que el resto de la suite (6 archivos de test que usan este factory) siga funcionando tras el `NOT NULL` de T002; los tests que necesiten un total real lo sobrescriben explícitamente.
- [X] T007 (descubierta al correr T008 por primera vez, research.md Decisión 8) Completar `total` también en el flujo individual: `LecturaMedidorController::store()` agrega un método privado `calcularTotal()` (consumo × tarifa vigente, `0.0` sin consumo calculable) y lo persiste en su `LecturaMedidor::create()` — en `app/Http/Controllers/LecturaMedidorController.php`. `update()` no necesita el mismo ajuste (no vuelve a insertar).
- [X] T008 Correr `php artisan migrate` y luego la suite completa (`php artisan test`, binario Herd) para confirmar que solo quedan fallando las pruebas de registro masivo que dependen de US1/US2 (esperado hasta completar esas fases) — ninguna otra regresión.

**Checkpoint**: esquema y modelos listos — US1 y US2 pueden empezar.

---

## Phase 3: User Story 1 - Empezar a medir una locación sin lectura anterior sin quedar bloqueado (Priority: P1)

**Goal**: el registro masivo calcula el consumo de una locación sin lectura anterior usando 0 como
lectura anterior, en vez de dejarlo sin calcular (contracts/total-editable-y-recibo.md, Contrato 1).

**Independent Test**: registrar la lectura actual de una locación sin ninguna lectura anterior y
verificar que `consumo_calculado` guardado es igual a la lectura actual.

### Tests for User Story 1 ⚠️

> Escribir primero, confirmar que fallan antes de implementar T011.

- [X] T009 [US1] Prueba: en `RegistroMasivoLecturasController::store()`, una locación sin lectura anterior guarda `consumo_calculado` igual a `lectura_actual` (anterior tratada como 0) — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`.
- [X] T010 [US1] Prueba de regresión de alcance (Contrato 1): el registro individual de una lectura (`LecturaMedidorController`) sigue guardando `lectura_anterior`/`consumo_calculado` como `null` cuando no hay lectura anterior — no debe empezar a asumir 0 — en `tests/Feature/LecturaMedidorControllerTest.php`. Ya cubierta por la prueba existente `'un administrador puede registrar la lectura del medidor de un periodo'` (líneas 12-25); confirmado en verde sin agregar una prueba duplicada.

### Implementation for User Story 1

- [X] T011 [US1] En `RegistroMasivoLecturasController::store()`, tratar la ausencia de lectura anterior como `0.0` (no `null`) antes de llamar a `ServicioCalculoConsumoMedidor::calcularConsumo()` — en `app/Http/Controllers/RegistroMasivoLecturasController.php`. No tocar `LecturaMedidorController` ni `ServicioCalculoConsumoMedidor::sugerirLecturaAnterior()` (Contrato 1: exclusivo del registro masivo).

**Checkpoint**: US1 funcional y verificable de forma independiente — `quickstart.md` Escenario 1.

---

## Phase 4: User Story 2 - Corregir o fijar manualmente el total antes de generar el recibo (Priority: P1)

**Goal**: el total de cada lectura del registro masivo es editable antes de guardar, se persiste, no
se recalcula si cambia la tarifa después, y `calcularMontoLuzSugerido()` lo usa directamente
(contracts/total-editable-y-recibo.md, Contratos 2-3). Depende de US1 (T011) para que el total
sugerido de una locación sin lectura anterior también use consumo = lectura actual.

**Independent Test**: editar el total sugerido de una locación antes de guardar, guardar, cambiar
la tarifa general, y verificar que tanto la pantalla como el recibo generado siguen mostrando el
total editado, no uno recalculado.

### Tests for User Story 2 ⚠️

> Escribir primero, confirmar que fallan antes de implementar T018-T022.

- [X] T012 [US2] Prueba: `store()` persiste el valor numérico recibido en `lecturas[{id}][total]` tal cual (no lo recalcula), incluyendo el caso de consumo negativo ya confirmado con un total negativo (research.md Decisión 7) — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`.
- [X] T013 [US2] Prueba: `store()` calcula `total` como `round(consumo × tarifa, 2)` cuando el campo `total` de una fila no llega o no es numérico (research.md Decisión 2) — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`.
- [X] T014 [US2] Prueba: en la respuesta de `index()`, una fila ya completada muestra el `total` persistido de su lectura (no un valor recalculado con la tarifa vigente), y una fila pendiente expone `<input name="lecturas[{id}][total]">` — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`.
- [X] T015 [US2] Prueba: `guardarBorrador()` persiste `total` en `BorradorLecturaMedidor` (upsert por usuario+periodo+locación, igual que `lectura_actual`), y `index()` restaura ese valor en el input de total al reabrir la pantalla para el mismo periodo — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`.
- [X] T016 [US2] Actualizar la prueba existente `'calcula el monto de luz sugerido como consumo por tarifa vigente'` para reflejar el nuevo contrato: crear la lectura con un `total` persistido explícito y afirmar que `calcularMontoLuzSugerido()` devuelve exactamente ese valor, sin recalcular consumo × tarifa — en `tests/Unit/ServicioGeneracionReciboPeriodoTest.php`.
- [X] T017 [US2] Actualizar la prueba existente `'el monto de luz sugerido es 0 sin lectura o sin dato de consumo anterior'`: conservar el caso `null` → `0.0`; reemplazar el caso de `consumo_calculado => null` (ya no aplica igual con `total` `NOT NULL`) por un caso que confirme que un `total` persistido distinto de `consumo_calculado × tarifa_vigente_actual` se devuelve tal cual (prueba explícita de que no se recalcula) — en `tests/Unit/ServicioGeneracionReciboPeriodoTest.php`.

### Implementation for User Story 2

- [X] T018 [US2] En `resources/views/lecturas/registro-masivo/partials/fila-registro-masivo.blade.php`, reemplazar la celda estática `#total-fila-{id}` por: `<x-text-input>` editable con `name="lecturas[{{ $locacion->id }}][total]"` prellenado con `old($clave, $borrador?->total)` para filas pendientes; un `<div>` de solo lectura con `$lecturaDelPeriodo->total` para filas completadas (contracts/total-editable-y-recibo.md, Contrato 2).
- [X] T019 [US2] En `resources/js/registro-masivo-lecturas.js`, agregar un listener `input` sobre el campo de total que marque `dataset.editadoManualmente = 'true'`, y hacer que `recalcularTotales()` no sobrescriba `.value` de ese input si ese flag ya está presente (research.md Decisión 3).
- [X] T020 [US2] En `RegistroMasivoLecturasController::store()`, calcular y persistir `total` en cada `LecturaMedidor::create()`: el valor numérico de `lecturas[{id}][total]` si está presente, o `round($consumo * $tarifa, 2)` como fallback — en `app/Http/Controllers/RegistroMasivoLecturasController.php` (depende de T011: usa el mismo `$consumo` ya calculado con anterior=0).
- [X] T021 [US2] En `RegistroMasivoLecturasController::guardarBorrador()`, incluir `total` en el `map()`/`upsert()` existente (mismo filtro de "es numérico" que `lectura_actual`) — en `app/Http/Controllers/RegistroMasivoLecturasController.php`.
- [X] T022 [US2] En `ServicioGeneracionReciboPeriodo::calcularMontoLuzSugerido()`, devolver `(float) $lectura->total` en vez de `consumo_calculado × tarifa_vigente` (0.0 si `$lectura` es `null`, sin cambios en ese caso) — en `app/Services/ServicioGeneracionReciboPeriodo.php`.

**Checkpoint**: US2 funcional — el recibo usa el total persistido, verificable con `quickstart.md`
Escenarios 2-5.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T023 [P] Correr `php artisan test` completo (binario Herd) y confirmar 0 regresiones sobre toda la suite (no solo los archivos tocados por esta feature).
- [X] T024 Revisión de diseño con el skill `impeccable` (`/impeccable polish` o `audit`) sobre `fila-registro-masivo.blade.php` (único archivo Blade modificado por esta feature), documentando el resultado en `DESIGN.md` si corresponde (Principio VI, obligatorio antes de cerrar la tarea).
- [X] T025 Ejecutar manualmente los 5 escenarios de `specs/019-total-editable-recibos/quickstart.md` en el navegador — en particular el Escenario 2 (edición en vivo sin que se sobrescriba, research.md Decisión 3) y el Escenario 5 (autoguardado del total), no verificables por Pest.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de T001. Bloquea todo lo demás (T002-T008 antes de cualquier
  tarea de US1/US2, por el `NOT NULL` compartido con el resto de la suite, incluido el flujo
  individual).
- **User Story 1 (Phase 3)**: depende de Phase 2. No depende de US2.
- **User Story 2 (Phase 4)**: depende de Phase 2 **y** de T011 (US1) — el total sugerido/fallback de
  una locación sin lectura anterior necesita que `store()` ya trate esa ausencia como 0.
- **Polish (Phase 5)**: depende de que US1 y US2 estén completas.

### Dentro de cada historia

- Las pruebas (T009-T010, T012-T017) se escriben antes que su implementación correspondiente y
  deben fallar primero (Principio IV).
- T020 depende de T011 (mismo `$consumo` calculado con anterior=0).
- T018, T019, T020, T021, T022 tocan archivos distintos y son paralelizables entre sí salvo T020 y
  T021, que modifican el mismo archivo (`RegistroMasivoLecturasController.php`) en métodos
  distintos — secuenciales entre sí, no `[P]`.

### Parallel Opportunities

- T003, T004, T005 (Foundational) en paralelo tras T002.
- T018, T019, T022 (US2) en paralelo entre sí (archivos distintos); T020/T021 secuenciales entre
  ellas por compartir archivo.

---

## Parallel Example: Foundational

```bash
# Tras T002, lanzar en paralelo (archivos distintos):
Task: "Migración total en borradores_lectura_medidor en database/migrations/"
Task: "Agregar total a fillable/casts de LecturaMedidor en app/Models/LecturaMedidor.php"
Task: "Agregar total a fillable/casts de BorradorLecturaMedidor en app/Models/BorradorLecturaMedidor.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 solamente)

1. T001 (Setup).
2. Phase 2 completa (Foundational) — obligatoria, no opcional, por el `NOT NULL` compartido.
3. Completar Phase 3 (US1): ninguna locación nueva queda bloqueada por falta de lectura anterior.
4. **Parar y validar**: correr T008-equivalente (suite completa) y el Escenario 1 de `quickstart.md`.
5. US1 sola ya es un incremento entregable, aunque su valor completo (recibos consistentes) se
   nota recién con US2 encima.

### Incremental Delivery

1. Setup + Foundational → esquema y modelos listos.
2. Agregar US1 (anterior=0) → validar independientemente.
3. Agregar US2 (total editable/persistido, recibo usa el total) → validar independientemente → esta
   es la entrega que resuelve el pedido central del usuario.
4. Phase 5 (Polish: suite completa, revisión `impeccable`, validación manual) cierra la feature.

---

## Notes

- [P] = archivos distintos, sin dependencia de código entre las tareas.
- [US1]/[US2] = trazabilidad a la historia de usuario correspondiente de `spec.md`.
- La columna `total` de `lecturas_medidor` es la única pieza de esquema nueva; todo lo demás
  reutiliza controladores, modelos y vistas ya existentes de specs/005/015-018.
- Confirmar que las pruebas fallan antes de implementar cada tarea de implementación asociada.
