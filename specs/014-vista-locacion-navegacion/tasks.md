---

description: "Task list template for feature implementation"
---

# Tasks: Navegación a Contratos y Recibos desde las Vistas de Locación

**Input**: Design documents from `/specs/014-vista-locacion-navegacion/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md

**Tests**: Incluidas — el Principio IV de la Constitución exige pruebas automatizadas exhaustivas para toda funcionalidad.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

**Revisión 2026-08-24** (tras `/speckit-clarify` y `/speckit-plan`): una aclaración del usuario reveló que el pedido real apuntaba a la tabla jerárquica de `/locaciones` (User Story 1, ahora P1/MVP), no a la vista de detalle (User Story 2, ahora P3) implementada en una primera interpretación. Las tareas de User Story 2 ya están completas y se conservan marcadas `[X]` como registro histórico — no requieren rehacerse.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: Historia de usuario a la que pertenece la tarea (US1, US2)
- Se incluyen rutas de archivo exactas en cada descripción

## Path Conventions

Aplicación Laravel monolítica única — rutas relativas a la raíz del repositorio: `app/`, `resources/`, `routes/`, `tests/`, según `plan.md` → Project Structure.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Proyecto ya inicializado (Laravel, Pest, Bootstrap 5 ya configurados por specs anteriores)

- [X] T001 Sin tareas de setup adicionales para esta feature — no se agregan dependencias, migraciones ni configuración nueva

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Confirmar que las rutas y controladores que esta feature va a enlazar ya existen y ya cumplen el comportamiento requerido, antes de tocar cualquiera de las dos vistas

**⚠️ CRITICAL**: Ninguna historia de usuario puede comenzar hasta completar esta verificación

- [X] T002 Confirmar en `routes/web.php` que `locaciones.recibos.index` (→ `ReciboController@index`), `contratos.index` (→ `ContratoController@index`), `contratos.create` y `contratos.edit` ya existen y ya implementan el historial y el CRUD requeridos por la spec (ver Decisiones 1-2 de `research.md`) — verificación de lectura, sin cambios de código

**Checkpoint**: Fundamento confirmado — las historias de usuario pueden comenzar

---

## Phase 3: User Story 1 - Acceder a recibos y contratos desde la fila de la locación en la vista general (Priority: P1) 🎯 MVP

**Goal**: Reemplazar los botones sueltos "Editar" (y, para locaciones alquilables, agregar "Ver Contratos"/"Ver Recibos") en la fila de `fila-arbol-locacion.blade.php` por un menú desplegable Bootstrap "Acciones", dejando el botón "+" (crear hija) fuera del menú.

**Independent Test**: Abrir `/locaciones`, desplegar el menú de acciones de la fila de una locación alquilable, y comprobar que "Ver Recibos" y "Ver Contratos" llevan al historial correspondiente de esa locación específica (y que una fila no alquilable solo muestra "Editar" en su menú).

### Tests for User Story 1 ⚠️

> **NOTE: Escribir esta prueba primero y comprobar que falla antes de implementar**

- [X] T003 [US1] Agregar la prueba `'la fila de una locacion alquilable expone un menu de acciones con contratos y recibos'` sobre `route('locaciones.index')` (locación alquilable → `assertSee('data-bs-toggle="dropdown"', false)` para esa fila y `assertSee(route('contratos.index', $locacion), false)` + `assertSee(route('locaciones.recibos.index', $locacion), false)`; locación no alquilable → `assertDontSee` de esos dos, conservando `assertSee(route('locaciones.edit', $locacion), false)` en ambos casos) en `tests/Feature/LocacionControllerTest.php` (depende de T002)

### Implementation for User Story 1

- [X] T004 [US1] Reemplazar el bloque `<a>` suelto de "Editar" en la columna Acciones por un `<div class="dropdown">`: botón trigger `btn btn-sm btn-outline-secondary` con únicamente el ícono `bi-three-dots-vertical` (`data-bs-toggle="dropdown"`, `data-bs-strategy="fixed"`, `aria-expanded="false"`, `aria-label="Acciones para {{ $locacion->nombre }}"`, sin texto visible — mismo patrón que el botón "+" vecino) y un `<ul class="dropdown-menu dropdown-menu-end">` con el `<li>` "Editar" (`route('locaciones.edit', $locacion)`, ícono `bi-pencil-square`) siempre presente; el botón "+" existente permanece igual, fuera del `dropdown` (Decisiones 4-6 de `research.md`) en `resources/views/locaciones/partials/fila-arbol-locacion.blade.php` (depende de T003)
- [X] T005 [US1] Dentro del mismo `<ul class="dropdown-menu">`, agregar — solo cuando `$locacion->es_alquilable` — los `<li>` "Ver Contratos" (`route('contratos.index', $locacion)`, ícono `bi-file-earmark-text`) y "Ver Recibos" (`route('locaciones.recibos.index', $locacion)`, ícono `bi-receipt`, mismos íconos que ya usa `locaciones/show.blade.php`) en `resources/views/locaciones/partials/fila-arbol-locacion.blade.php` (depende de T004)
- [X] T006 [US1] Verificar que el trigger del menú (`btn-sm`) queda alineado en altura con el botón "+" vecino dentro de `.fila-arbol` en `resources/css/bootstrap.scss`; ajustar solo si la verificación visual muestra desalineación (no asumir que hace falta) (depende de T005)

**Checkpoint**: User Story 1 funcional y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Acceder a recibos y contratos desde el detalle individual de la locación (Priority: P3, ya implementado)

**Goal**: Formalizar con pruebas que los botones "Ver Recibos" y "Ver Contratos" en `locaciones/show.blade.php` están disponibles solo para locaciones alquilables y llevan al historial correspondiente.

**Independent Test**: Abrir el detalle de una locación alquilable y comprobar que "Ver Recibos" y "Ver Contratos" llevan al historial correspondiente (y que ninguno aparece en una locación no alquilable).

**Estado**: Completado en la iteración anterior de esta feature, antes de que `/speckit-clarify` corrigiera el alcance principal hacia User Story 1. Se conserva sin cambios — no depende de User Story 1 ni la bloquea.

### Tests for User Story 2 ⚠️

- [X] T007 [US2] Prueba `'el detalle de una locacion alquilable muestra el enlace a su historial de recibos'` (locación alquilable → `assertSee(route('locaciones.recibos.index', $locacion), false)`; no alquilable → `assertDontSee`) en `tests/Feature/LocacionControllerTest.php`
- [X] T008 [US2] Prueba `'el detalle de una locacion alquilable muestra el enlace a su historial de contratos'` (locación alquilable → `assertSee(route('contratos.index', $locacion), false)`; no alquilable → `assertDontSee`) en `tests/Feature/LocacionControllerTest.php`

### Implementation for User Story 2

- [X] T009 [US2] Botón "Ver Recibos" (`btn btn-outline-secondary`, ícono `bi-receipt`) agregado junto a "Ver Contratos" ya existente, dentro del bloque `@if ($locacion->es_alquilable)`, en `resources/views/locaciones/show.blade.php`

**Checkpoint**: User Story 1 y 2 funcionan de forma independiente

---

## Phase 5: Polish & Cross-Cutting Concerns

**Purpose**: Verificación final que afecta a ambas historias de usuario

- [X] T010 Revisión de diseño de `resources/views/locaciones/partials/fila-arbol-locacion.blade.php` y `resources/css/bootstrap.scss` con el skill `impeccable` (`/impeccable polish` o `audit`), exigida por el Principio VI de la constitución — primer uso del componente `Dropdown` en el proyecto, prestar atención particular a foco visible y operabilidad por teclado (Principio III) (depende de T006)
- [X] T011 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 3, User Story 1) de extremo a extremo en el navegador, incluyendo el caso de fila indentada sin recorte del menú (Escenario 3)
- [X] T012 Ejecutar `php artisan test --filter=Locacion` y confirmar que toda la suite pasa, incluyendo la prueba nueva T003 y sin regresiones en T007/T008
- [X] T013 Revisión de diseño de `resources/views/locaciones/show.blade.php` con el skill `impeccable` — completada en la iteración anterior (US2)
- [X] T014 Validación de `quickstart.md` (Escenarios 4-7, User Story 2) y `php artisan test --filter=Locacion` — completados en la iteración anterior (41/41 pruebas)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Ya completado
- **Foundational (Phase 2)**: Ya completado — cubre a ambas historias
- **User Stories (Phase 3-4)**: US1 es el trabajo pendiente (P1); US2 ya está completo (P3) y no bloquea ni depende de US1 — ambas tocan la misma fuente de datos (`Locacion::es_alquilable`) pero archivos de vista distintos
- **Polish (Phase 5)**: T010-T012 dependen de que US1 esté completo; T013-T014 ya completados

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Foundational — sin dependencias de User Story 2
- **User Story 2 (P3)**: Ya completo; no depende de User Story 1

### Within Each User Story

- Las pruebas se escriben antes de la implementación y deben fallar inicialmente
- US1: prueba (T003) antes que implementación (T004 → T005 → T006, secuencial por tocar el mismo bloque de la misma parcial)

### Parallel Opportunities

- No hay tareas [P] dentro de User Story 1: T004/T005/T006 modifican el mismo bloque de `fila-arbol-locacion.blade.php` en orden; T003 (test) es previa a todas
- T011 y T012 en Polish sí son paralelizables entre sí

---

## Parallel Example: Polish

```bash
Task: "Ejecutar la validación de quickstart.md (Escenarios 1-3) en el navegador"
Task: "Ejecutar php artisan test --filter=Locacion"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Completar Phase 3: User Story 1 (menú desplegable en la fila)
2. **DETENERSE Y VALIDAR**: Escenarios 1-3 de `quickstart.md`
3. Desplegar/demostrar si está listo — User Story 2 ya está en producción desde la iteración anterior

### Incremental Delivery

1. Foundational → ya completo
2. User Story 2 → ya completo (detalle de locación)
3. User Story 1 → construir → probar → demo (MVP real: menú de acciones en la tabla general)
4. Polish → revisión `impeccable` del nuevo menú + validación completa + suite de pruebas

---

## Notes

- Esta feature es de solo navegación: no agrega migraciones, Form Requests, controladores ni rutas nuevas — únicamente un menú desplegable Bootstrap en una parcial Blade y sus pruebas Feature.
- `ContratoController@index/create/edit` y `ReciboController@index` no se modifican; ya satisfacen los requisitos funcionales tal como están (ver `research.md`).
- El botón "+" (crear locación hija) permanece sin cambios, fuera del nuevo menú (FR-010).
- [P] = archivos distintos, sin dependencias pendientes
- Verificar que las pruebas fallan antes de implementar
- Hacer commit tras cada tarea o grupo lógico de tareas
- Evitar: tareas vagas, conflictos de archivo simultáneos, dependencias que rompan la independencia entre historias
