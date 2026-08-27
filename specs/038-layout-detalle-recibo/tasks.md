---

description: "Task list for 038-layout-detalle-recibo"
---

# Tasks: Distribución en Dos Columnas del Detalle de Recibo

**Input**: Design documents from `/specs/038-layout-detalle-recibo/`

**Prerequisites**: plan.md, spec.md, research.md, quickstart.md

**Tests**: incluidas — Principio IV de la constitución.

**Organization**: 2 historias de usuario (US1 P1, US2 P2). Sin fase Foundational: no hay datos nuevos que
preparar — se reordena contenido ya disponible en `ReciboController::show()`.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan`/`pest`; el dominio real del proyecto en esta máquina es `rent-tracker-sdd.test`.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

---

## Phase 2: User Story 1 - Ver el resumen del recibo y sus pagos lado a lado (Priority: P1) 🎯 MVP

**Goal**: en pantallas anchas, el detalle de recibo muestra el resumen en una columna y la gestión de pagos
(Pagos + Estado del Recibo) en otra, lado a lado.

**Independent Test**: abrir el detalle de un recibo en una pantalla ancha y confirmar que ambas columnas se
ven lado a lado (quickstart.md Escenario 1).

### Tests for User Story 1 ⚠️

- [X] T002 [US1] En `tests/Feature/ReciboControllerTest.php`, agregar un test que confirme que la respuesta de `recibos.show` contiene el marcado de las dos columnas nuevas (`col-lg-7` para el resumen, `col-lg-5` para pagos/estado) en el orden correcto, y que todo el contenido ya existente (estado, locación, total, "Pagos", "Estado del Recibo") sigue presente.
- [X] T003 [P] [US1] En el mismo archivo, agregar un test que confirme que, para un recibo anulado, la columna derecha muestra "Estado del Recibo" pero NO muestra la tarjeta "Pagos" (Acceptance Scenario 3).

### Implementation for User Story 1

- [X] T004 [US1] En `resources/views/locaciones/recibos/show.blade.php`, cambiar el contenedor general de `class="col-12 col-lg-8" style="max-width: 42rem;"` a `class="col-12"` (research.md Decisión 3).
- [X] T005 [US1] En el mismo archivo, envolver la tarjeta de resumen del recibo (la primera `<div class="card">`, con su `dl` y los botones Editar/Ver Comprobante/Ver Historial) en un `<div class="col-lg-7">` dentro de un nuevo `<div class="row g-4">` (research.md Decisión 1).
- [X] T006 [US1] En el mismo archivo, envolver la tarjeta de Pagos (condicional, `@if ($recibo->estado !== 'anulado')`) y la tarjeta de Estado del Recibo en un `<div class="col-lg-5 d-flex flex-column gap-3">`, dentro del mismo `row`, después del `col-lg-7`.
- [X] T007 [US1] Ejecutar el Escenario 1 de `quickstart.md` (incluido el caso de recibo anulado) y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: User Story 1 completa — el detalle de recibo se ve en dos columnas en pantallas anchas.

---

## Phase 3: User Story 2 - Seguir usando la pantalla normalmente en un celular (Priority: P2)

**Goal**: en pantallas angostas, el contenido se apila verticalmente en el mismo orden lógico de siempre.

**Independent Test**: abrir el mismo detalle de recibo en una pantalla angosta y confirmar que todo el
contenido se ve apilado, sin scroll horizontal (quickstart.md Escenario 2).

### Tests for User Story 2 ⚠️

- [X] T008 [P] [US2] Ninguna prueba automatizada nueva — el apilamiento responsive es una propiedad del grid de Bootstrap (`col-lg-*` sin clase de ancho fijo por debajo de `lg`), ya cubierta indistintamente por los tests HTTP existentes (no dependen del ancho del viewport). Se valida exclusivamente en el navegador (T009).

### Implementation for User Story 2

- [X] T009 [US2] Ejecutar el Escenario 2 de `quickstart.md` (reducir el ancho de la ventana) y confirmar que el apilamiento respeta el orden resumen → Pagos → Estado del Recibo, sin scroll horizontal ni contenido cortado.

**Checkpoint**: Las 2 historias de usuario están completas e independientemente verificables.

---

## Phase 4: Polish & Cross-Cutting Concerns

- [X] T010 [P] Revisión de diseño con el skill `impeccable` (`audit` o `polish`) sobre `locaciones/recibos/show.blade.php` (Principio VI de la constitución).
- [X] T011 Correr la suite completa (`php artisan test`, binario Herd) y confirmar 0 fallos.
- [X] T012 Validar manualmente el checklist completo de `quickstart.md` (los 2 escenarios y la regresión: registrar/editar/eliminar pago, subir evidencia, anular/reactivar recibo) contra la base de datos de desarrollo real, en navegador.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **User Story 1 (Phase 2)**: depende solo de Setup.
- **User Story 2 (Phase 3)**: depende de que User Story 1 exista (el apilamiento responsive es una
  propiedad del mismo `row`/`col-lg-*` que introduce US1 — no hay una implementación separada que hacer).
- **Polish (Phase 4)**: depende de que las 2 historias estén completas.

### Dentro de cada fase

- Los tests marcados ⚠️ (T002-T003) se escriben antes que la implementación (T004-T006) y deben fallar
  primero (Principio IV) — hoy el marcado no tiene `col-lg-7`/`col-lg-5`.

### Parallel Opportunities

- T002 y T003 pueden escribirse en paralelo (mismo archivo, casos distintos, sin dependencia de contenido
  entre sí).

---

## Implementation Strategy

### MVP First (User Story 1)

1. Setup (T001).
2. User Story 1 (T002-T007): la distribución en dos columnas — el pedido explícito del usuario.
3. **Parar y validar**: quickstart.md Escenario 1.

### Incremental Delivery

1. Setup → listo para ambas historias.
2. User Story 1 → validar (Escenario 1) → demo de las dos columnas.
3. User Story 2 → validar (Escenario 2) → confirma que el apilamiento responsive sigue intacto.
4. Polish (T010-T012) cierra la feature.

---

## Notes

- `[Story]` = trazabilidad a las historias de usuario de `spec.md`.
- `[P]` = archivos distintos o casos independientes sin dependencia de código entre las tareas.
- Sin `data-model.md` ni `contracts/`: no hay entidades, columnas ni rutas nuevas.
