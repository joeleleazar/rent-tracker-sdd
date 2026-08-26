---

description: "Task list for 029-fix-concepto-cubierto-renta"
---

# Tasks: Corregir Cobertura de Conceptos y Edición de Renta en Recibos

**Input**: Design documents from `/specs/029-fix-concepto-cubierto-renta/`

**Prerequisites**: plan.md, spec.md, research.md, quickstart.md

**Tests**: incluidas — Principio IV de la constitución.

**Organization**: 2 historias de usuario independientes (US1 P1, US2 P2). Sin fase Foundational: ninguna
tarea bloquea a la otra historia.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan`/`pest` en esta máquina.

**Nota de concurrencia**: specs/028 (quitar el botón "Ir" de `/recibos/registro-masivo`) está en curso en
paralelo, en otra sesión, y ya agregó un test propio a
`tests/Feature/RegistroMasivoRecibosControllerTest.php`. Las tareas de esta feature que tocan ese mismo
archivo (T004) deben agregarse sin modificar ni reordenar lo que specs/028 ya haya escrito ahí.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

---

## Phase 2: User Story 1 - Editar el monto de Renta de un recibo ya emitido (Priority: P1)

**Goal**: la pantalla de edición de un recibo que ya incluye Renta ofrece su campo, editable, igual que los
demás conceptos.

**Independent Test**: abrir la edición de un recibo con Renta incluida y verificar que el campo aparece,
editable y guardable (quickstart.md Escenario 1).

### Tests for User Story 1 ⚠️

- [X] T002 [P] [US1] Extender `tests/Feature/ReciboControllerTest.php`: `GET recibos.edit` de un recibo con `monto_renta` no nulo muestra el campo "Incluir Renta" marcado con su monto actual; `PUT recibos.update` con un monto de Renta distinto lo actualiza; `PUT recibos.update` desmarcando Renta la quita del recibo (y de su total); regresión — un recibo sin Renta, en un periodo donde ningún otro recibo la cubre, sigue ofreciéndola disponible; regresión — un recibo sin Renta, cuando otro recibo del mismo periodo/locación sí la cubre, NO la ofrece.

### Implementation for User Story 1

- [X] T003 [US1] En `ReciboController::edit()` (`app/Http/Controllers/ReciboController.php`), cuando `$recibo->monto_renta !== null`, agregar el `ConceptoGastoFijo` de Renta (`esRenta()`) a la colección antes de `unique('id')->sortBy('orden')->values()` — junto a la unión ya existente con `$recibo->conceptos->pluck('conceptoGastoFijo')` (research.md Decisión 1).

**Checkpoint**: editar el monto de Renta de un recibo ya emitido funciona igual que para el resto de los conceptos.

---

## Phase 3: User Story 2 - Los badges de conceptos solo marcan cobertura vigente (Priority: P2)

**Goal**: ningún concepto se muestra como cubierto en `/recibos/registro-masivo` sin un recibo vigente real
que lo incluya — ni por tener un valor de referencia configurado en el contrato, ni por figurar en un
recibo ya anulado.

**Independent Test**: un concepto con valor de referencia configurado pero sin ningún recibo vigente que lo
incluya se muestra disponible, no cubierto (quickstart.md Escenario 2).

### Tests for User Story 2 ⚠️

- [X] T004 [P] [US2] Extender `tests/Feature/RegistroMasivoRecibosControllerTest.php` (sin tocar los tests ya agregados por specs/028 en el mismo archivo): un concepto con un valor de referencia configurado en el contrato pero sin ningún recibo del periodo se muestra disponible (badge `bg-light`, sin enlace a recibo); tras generar un recibo que lo incluye, se muestra cubierto (badge `bg-secondary` con enlace a ese recibo); tras anular ese recibo, vuelve a mostrarse disponible; mismo chequeo para Renta específicamente (sin ningún recibo vigente que la cubra, se muestra disponible).

### Implementation for User Story 2

- [X] T005 [US2] Correr T004. Por lo verificado en research.md Decisión 2 (chequeo en vivo contra el entorno de desarrollo), se espera que pase sin cambios de producción — `Recibo::vigente()` (specs/026) ya cubre este invariante. Si pasa: no se requiere cambio de código, T004 queda como cobertura de regresión permanente para el síntoma reportado. Si falla: localizar la causa exacta en `ServicioGeneracionReciboPeriodo::conceptosDisponiblesDesde()`/`reciboQueCubreDesde()` o en `RegistroMasivoRecibosController::datosDelPeriodo()` y corregirla antes de continuar.

**Checkpoint**: el invariante "solo un recibo vigente real cubre un concepto" queda probado explícitamente para el caso reportado.

---

## Phase 4: Polish & Cross-Cutting Concerns

- [X] T006 Correr la suite completa (`php artisan test`, binario Herd) y confirmar 0 fallos.
- [X] T007 Si T003 (o cualquier corrección de T005) modificó alguna vista Blade, correr la revisión del skill `impeccable` sobre esa vista (Principio VI de la constitución); si no se modificó ninguna vista, omitir este paso y dejarlo dicho en el reporte final.
- [X] T008 Validar manualmente los 2 escenarios de `specs/029-fix-concepto-cubierto-renta/quickstart.md` contra la base de datos de desarrollo real, en navegador.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **User Story 1 (Phase 2)**: depende solo de Setup. Independiente de US2.
- **User Story 2 (Phase 3)**: depende solo de Setup. Independiente de US1.
- **Polish (Phase 4)**: depende de que ambas historias estén completas.

### Dentro de cada fase

- T002 se escribe y debe fallar antes de T003 (Principio IV).
- T004 se escribe primero; T005 es, según lo que revele, o bien una confirmación (sin cambio de código) o
  bien la corrección puntual que haga falta.

### Parallel Opportunities

- T002 y T004 (tests de ambas historias) en paralelo entre sí — archivos distintos.
- US1 y US2 son completamente independientes y pueden trabajarse en paralelo si hay más de un
  desarrollador.

---

## Implementation Strategy

### MVP First (User Story 1)

1. Setup (T001).
2. US1 (T002-T003): corrige el defecto de mayor impacto (bloquea corregir un monto ya emitido).
3. **Parar y validar**: quickstart.md Escenario 1.

### Incremental Delivery

1. Setup → listo para US1/US2 en paralelo.
2. US1 → validar → demo.
3. US2 → validar (muy probablemente ya funciona; T004 lo confirma con prueba dedicada) → demo.
4. Polish (T006-T008) cierra la feature.

---

## Notes

- [P] = archivos distintos, sin dependencia de código entre las tareas.
- [US1]/[US2] = trazabilidad a las historias de usuario de `spec.md`.
- T005 es deliberadamente condicional: esta feature parte de que US2 puede ya estar corregida por
  specs/026, y la tarea documenta explícitamente ambos desenlaces posibles en vez de asumir uno solo.
- Confirmar que T002 y T004 fallan contra el estado anterior (T002 debe fallar; T004 puede pasar de
  entrada, ver nota anterior) antes de dar cada historia por completa.
