---

description: "Task list for 028-simplificar-navegacion-periodo-recibos"
---

# Tasks: Simplificar Navegación de Periodo en Registro Masivo de Recibos

**Input**: Design documents from `/specs/028-simplificar-navegacion-periodo-recibos/`

**Tests**: Incluidas — Principio IV de la constitución (TDD).

## Phase 1: Setup

- [X] T001 Ejecutar `php artisan test --filter=RegistroMasivoRecibosControllerTest` como línea base. **Resultado**: 11/11 tests, 40 assertions.

---

## Phase 2: User Story 1 - Navegar de periodo solo con flechas o cambiando la fecha (Priority: P1) 🎯 MVP

### Tests for User Story 1 ⚠️ (escribir primero, deben FALLAR antes de implementar)

- [X] T002 [US1] En `tests/Feature/RegistroMasivoRecibosControllerTest.php`, agregar un test nuevo que verifique que el botón "Ir" NO aparece en el marcado de `/recibos/registro-masivo` (mismo patrón que specs/027 T002). Debe FALLAR contra la vista actual.

### Implementation for User Story 1

- [X] T003 [US1] En `resources/views/recibos/registro-masivo/index.blade.php`, eliminar `<x-secondary-button type="submit">Ir</x-secondary-button>` (línea ~57) (research.md R1).
- [X] T004 [US1] Confirmar que T002 pasa y correr `tests/Feature/RegistroMasivoRecibosControllerTest.php` completo para confirmar cero regresiones. **Resultado**: 12/12 tests, 42 assertions.
- [X] T005 [US1] Ejecutar `/impeccable polish` sobre `resources/views/recibos/registro-masivo/index.blade.php` (Principio VI) y documentar en `DESIGN.md` si corresponde. **Resultado**: verificado en el navegador en una pestaña limpia (sin residuos de otras páginas): sin "Ir" en el árbol de accesibilidad, sin errores de consola, flecha "Periodo anterior" navega correctamente vía htmx. (Nota: una pestaña reutilizada mostró errores de consola `htmx:sendError`/`ERR_CONNECTION_REFUSED` residuales del autoguardado de `lecturas/registro-masivo` durante un reinicio momentáneo del servidor de desarrollo — no relacionados con este cambio, confirmado al no reaparecer en una pestaña nueva.) Sin cambios a `DESIGN.md` (mismo criterio que specs/025/027).

**Checkpoint**: MVP único de este feature.

---

## Phase 3: Polish

- [X] T006 Ejecutar `php artisan test` completo y confirmar cero regresiones (SC-004). **Resultado**: 304/310 en la corrida completa — los 6 fallos/errores son 100% ajenos: 1 test explícitamente nombrado `specs_029_...` (feature en desarrollo activo de la sesión concurrente) + 4 errores de tablas inexistentes (`users`, `conceptos_gasto_fijo`) y 1 deadlock de PostgreSQL entre dos procesos, causados por esa misma sesión corriendo `migrate:fresh`/su propia suite contra la base `rent_tracker_dev_testing` compartida al mismo tiempo que esta corrida. Verificación aislada de mi alcance: `--filter="RegistroMasivoRecibosControllerTest|RegistroMasivoLecturasControllerTest"` → **48/48 en verde, 169 assertions**.

---

## Dependencies & Execution Order

Secuencial: T001 → T002 → T003 → T004 → T005 → T006 (mismo archivo en cada paso).
