---

description: "Task list for 027-simplificar-navegacion-periodo"
---

# Tasks: Simplificar Navegación de Periodo en Registro Masivo de Lecturas

**Input**: Design documents from `/specs/027-simplificar-navegacion-periodo/`

**Tests**: Incluidas — Principio IV de la constitución (TDD).

## Phase 1: Setup

- [X] T001 Ejecutar `php artisan test --filter=RegistroMasivoLecturasControllerTest` como línea base. **Resultado**: 36/36 tests, 128 assertions.

---

## Phase 2: User Story 1 - Navegar de periodo solo con flechas o cambiando la fecha (Priority: P1) 🎯 MVP

**Goal**: Quitar el botón "Ir", conservando flechas + autoenvío del campo de fecha, sin afectar tarifa/exportar.

**Independent Test**: quickstart.md paso 1.

### Tests for User Story 1 ⚠️ (escribir primero, deben FALLAR antes de implementar)

- [X] T002 [US1] En `tests/Feature/RegistroMasivoLecturasControllerTest.php`, reemplazar el test `'el boton de confirmar periodo declara type submit para el envio sin JavaScript'` (líneas ~482-492) por uno nuevo que verifique que el botón "Ir" YA NO aparece en el marcado (`$respuesta->assertDontSee('>Ir<', false)` o equivalente), conservando los tests de flechas/campo de fecha que ya existan sin cambios (Contratos 1-3). Debe FALLAR contra la vista actual (el botón todavía existe).

### Implementation for User Story 1

- [X] T003 [US1] En `resources/views/lecturas/registro-masivo/index.blade.php`, eliminar `<x-secondary-button type="submit">Ir</x-secondary-button>` (línea ~74) y actualizar el comentario de las líneas ~21-31 para ya no citar a "Ir" como razón del `<form>` separado, explicando en su lugar que el aislamiento es para que el autoenvío del periodo no incluya tarifa/exportar (research.md R1).
- [X] T004 [US1] Confirmar que T002 pasa y correr `tests/Feature/RegistroMasivoLecturasControllerTest.php` completo para confirmar cero regresiones (Contrato 4). **Resultado**: 36/36 tests, 127 assertions (1 assertion menos que la línea base porque el test reescrito hace 1 aserción en vez de 2).
- [X] T005 [US1] Ejecutar `/impeccable polish` (o `audit`) sobre `resources/views/lecturas/registro-masivo/index.blade.php` (Principio VI) y documentar en `DESIGN.md` si corresponde. **Resultado**: verificado en el navegador (Herd, admin autenticado): las flechas navegan correctamente (htmx swap actualiza flechas/campo de fecha/exportar), sin consola de errores, sin rastro de "Ir" en el árbol de accesibilidad, layout intacto. Sin cambios a `DESIGN.md`: no se introdujo ningún patrón visual nuevo, solo se retiró un control redundante (mismo criterio que specs/025).

**Checkpoint**: Navegación de periodo simplificada; entregable como MVP único de este feature.

---

## Phase 3: Polish

- [X] T006 Ejecutar `php artisan test` completo y confirmar cero regresiones fuera de lo esperado en T002 (SC-004). **Resultado**: 304/304 tests, 867 assertions, 100% en verde — la falla ajena de la sesión concurrente (spec 024, columna `costo_agua`) reportada durante specs/025 ya no aparece; esa sesión terminó su migración en el ínterin.

---

## Dependencies & Execution Order

Feature de una sola historia de usuario: T001 → T002 → T003 → T004 → T005 → T006, estrictamente secuencial (mismo archivo de vista y de test en cada paso).

## Implementation Strategy

MVP único: completar T001-T006 en orden; no hay entrega incremental por historias múltiples dado el alcance acotado.
