---

description: "Task list for 033-menu-registro-pagos"
---

# Tasks: Menú de Registro de Pagos en la Jerarquía de Locales

**Input**: Design documents from `/specs/033-menu-registro-pagos/`

**Prerequisites**: plan.md, spec.md, research.md, quickstart.md

**Tests**: incluidas — Principio IV de la constitución.

**Organization**: 3 historias de usuario (US1/US2 P1, US3 P2). Sin fase Foundational: no hay entidades,
rutas ni controladores nuevos que preparar (research.md Decisión 1) — las 3 historias modifican vistas Blade
ya existentes.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan`/`pest`; el dominio real del proyecto en esta máquina es `rent-tracker-sdd.test`.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

---

## Phase 2: User Story 1 - Llegar al registro de pagos desde el menú principal (Priority: P1) 🎯 MVP

**Goal**: la pantalla de avance de pagos (specs/032) se alcanza en un clic desde el menú principal, bajo el
nombre "Registro de Pagos".

**Independent Test**: desde cualquier pantalla, abrir el menú, hacer clic en "Registro de Pagos", confirmar
que el título de la página resultante es "Registro de Pagos" y que el ítem de menú queda marcado como
activo (quickstart.md Escenario 1).

### Tests for User Story 1 ⚠️

- [X] T002 [P] [US1] En `tests/Feature/SeguimientoPagosControllerTest.php`, agregar un test que confirme que `GET pagos.seguimiento.index` muestra el título "Registro de Pagos" (FR-003).
- [X] T003 [P] [US1] Test de Feature nuevo (mismo archivo o `tests/Feature/NavegacionPrincipalTest.php`) que confirme, sobre cualquier página con el layout `app-bootstrap` (ej. `locaciones.index`), que existe un enlace "Registro de Pagos" hacia `route('pagos.seguimiento.index')` (FR-001); y que, al visitar `pagos.seguimiento.index`, ese mismo enlace lleva la clase `active` mientras que el resto de los ítems de menú no la llevan (FR-004).

### Implementation for User Story 1

- [X] T004 [US1] En `resources/views/components/layouts/app-bootstrap.blade.php`, agregar un nuevo `<li class="nav-item">` después de "Emitir Recibos" y antes de "Conceptos de Gasto Fijo": enlace a `route('pagos.seguimiento.index')`, ícono `bi-cash-coin` (research.md Decisión 4), etiqueta "Registro de Pagos", clase `active` condicionada a `request()->routeIs('pagos.seguimiento.*')`, siguiendo exactamente el mismo patrón que los 5 ítems ya existentes.
- [X] T005 [US1] En `resources/views/pagos/seguimiento/index.blade.php`, cambiar el `<h2>` del slot `header` de "Seguimiento de Pagos" a "Registro de Pagos" (FR-003).
- [X] T006 [US1] Ejecutar el Escenario 1 de `quickstart.md` y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: User Story 1 completa — "Registro de Pagos" es alcanzable desde el menú y se identifica
correctamente como tal.

---

## Phase 3: User Story 2 - Registrar un pago directamente desde la fila de una locación (Priority: P1)

**Goal**: cada fila con saldo pendiente en "Registro de Pagos" ofrece una acción "Registrar Pago" que lleva
directo (o, si hay más de un recibo vigente, a elegir cuál) a la pantalla donde se ingresa el monto.

**Independent Test**: con una locación con un recibo vigente con saldo pendiente en el periodo, hacer clic
en "Registrar Pago" en su fila y verificar que lleva directo al recibo (quickstart.md Escenario 2).

### Tests for User Story 2 ⚠️

- [X] T007 [P] [US2] En `tests/Feature/SeguimientoPagosControllerTest.php`, agregar tests que confirmen: (a) una locación con `estadoAgregado` `sin_pagos` o `parcial` muestra un enlace "Registrar Pago" hacia `route('recibos.registroMasivo.recibosDelPeriodo', [...])`; (b) una locación con `estadoAgregado` `pagado` o `sin_recibos` NO muestra ese enlace (FR-005, Acceptance Scenario 3, Edge Cases).

### Implementation for User Story 2

- [X] T008 [US2] En `resources/views/pagos/seguimiento/partials/estado-pago-locacion.blade.php`, agregar junto al botón "Ver Pagos" ya existente un botón "Registrar Pago" (`btn btn-outline-primary btn-sm`, ícono `bi-cash-coin`) que enlace a `route('recibos.registroMasivo.recibosDelPeriodo', ['locacion' => $locacion->id, 'periodo' => $periodo->format('Y-m')])`, visible solo cuando `$estadoAgregado` es `'sin_pagos'` o `'parcial'` (research.md Decisión 2/3 — sin ruta ni controlador nuevo).
- [X] T009 [US2] Ejecutar el Escenario 2 de `quickstart.md` (recibo único → directo; varios recibos → selector; sin saldo pendiente → sin botón) y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: User Stories 1 y 2 completas — registrar un pago desde la jerarquía de locales es tan
directo como generar un recibo ya lo es en "Emitir Recibos".

---

## Phase 4: User Story 3 - Revisar los pagos ya registrados sin registrar uno nuevo (Priority: P2)

**Goal**: confirmar que la acción de revisión de pagos ya existente ("Ver Pagos", specs/032) sigue
disponible en "Registro de Pagos", independiente de "Registrar Pago".

**Independent Test**: sobre una locación con recibos vigentes en el periodo (con o sin saldo pendiente),
confirmar que "Ver Pagos" está presente (quickstart.md Escenario 3).

### Tests for User Story 3 ⚠️

- [X] T010 [P] [US3] En `tests/Feature/SeguimientoPagosControllerTest.php`, agregar (si no existe ya un caso equivalente de specs/032) un test que confirme que "Ver Pagos" aparece tanto para una locación con saldo pendiente como para una ya pagada por completo, y que coexiste con "Registrar Pago" cuando ambas condiciones aplican (FR-006, Acceptance Scenario 1).

### Implementation for User Story 3

- [X] T011 [US3] Verificar (sin modificar código, salvo que el test T010 revele una regresión) que `estado-pago-locacion.blade.php` sigue mostrando "Ver Pagos" sin cambios de specs/032 — esta historia es una confirmación de no-regresión, no una implementación nueva (research.md, Assumptions del spec).
- [X] T012 [US3] Ejecutar el Escenario 3 de `quickstart.md` y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: Las 3 historias de usuario están completas e independientemente verificables.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T013 Revisar los casos límite de `quickstart.md`: locación sin recibos del periodo, locación con único recibo anulado, locación con recibos mixtos (pagados y no pagados).
- [X] T014 [P] Revisión de diseño con el skill `impeccable` (`audit` o `polish`) sobre `app-bootstrap.blade.php`, `pagos/seguimiento/index.blade.php` y `pagos/seguimiento/partials/estado-pago-locacion.blade.php` (Principio VI de la constitución).
- [X] T015 Correr la suite completa (`php artisan test`, binario Herd) y confirmar 0 fallos.
- [X] T016 Validar manualmente el checklist completo de `quickstart.md` (los 3 escenarios, los casos límite y la regresión) contra la base de datos de desarrollo real, en navegador.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **User Story 1 (Phase 2)**: depende solo de Setup. Independiente de US2/US3.
- **User Story 2 (Phase 3)**: depende solo de Setup. Independiente de US1/US3 — el botón "Registrar Pago"
  no depende de que exista el ítem de menú de US1 para funcionar (aunque naturalmente se prueba llegando
  ahí una vez US1 está lista).
- **User Story 3 (Phase 4)**: depende solo de Setup — es una verificación de no-regresión sobre
  comportamiento ya entregado por specs/032.
- **Polish (Phase 5)**: depende de que las 3 historias estén completas.

### Dentro de cada fase

- Los tests marcados ⚠️ se escriben/actualizan antes que su implementación y deben fallar primero
  (Principio IV) — en US1/US2, el test falla inicialmente porque el enlace/botón todavía no existe en el
  HTML renderizado; en US3 el test debería *pasar* de entrada (verifica comportamiento ya implementado), lo
  cual es el resultado esperado, no un error de proceso.

### Parallel Opportunities

- T002, T003 (tests US1) y T007 (test US2) y T010 (test US3) tocan el mismo archivo de test en su mayoría
  — aplicar como ediciones separadas y secuenciales sobre `SeguimientoPagosControllerTest.php`, no en
  paralelo real, pese a la etiqueta `[P]` (archivos distintos entre US1/US3 si se usa
  `NavegacionPrincipalTest.php` para T003).
- Las 3 historias de usuario son independientes entre sí y podrían implementarse en cualquier orden.

---

## Implementation Strategy

### MVP First (User Story 1)

1. Setup (T001).
2. User Story 1 (T002-T006): resuelve el problema concreto que motiva la feature — llegar a la pantalla
   desde el menú.
3. **Parar y validar**: quickstart.md Escenario 1.

### Incremental Delivery

1. Setup → listo para las 3 historias.
2. User Story 1 → validar (Escenario 1) → demo del nuevo ítem de menú.
3. User Story 2 → validar (Escenario 2) → demo de "Registrar Pago" directo desde la fila.
4. User Story 3 → validar (Escenario 3) → confirma que nada de specs/032 se perdió.
5. Polish (T013-T016) cierra la feature.

---

## Notes

- `[Story]` = trazabilidad a las historias de usuario de `spec.md`.
- `[P]` = archivos distintos sin dependencia de código entre las tareas.
- Sin `data-model.md` ni `contracts/`: no hay entidades, columnas ni rutas HTTP nuevas (research.md
  Decisión 1 y 2).
