---

description: "Task list for 034-barra-progreso-pagos"
---

# Tasks: Barra de Progreso de Pagos

**Input**: Design documents from `/specs/034-barra-progreso-pagos/`

**Prerequisites**: plan.md, spec.md, research.md, quickstart.md

**Tests**: incluidas — Principio IV de la constitución.

**Organization**: 2 historias de usuario (US1 P1, US2 P2), más una fase Foundational mínima para el
componente reutilizable que ambas necesitan.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan`/`pest`; el dominio real del proyecto en esta máquina es `rent-tracker-sdd.test`.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

---

## Phase 2: Foundational — componente `<x-barra-progreso-pago>`

**Propósito**: el componente reutilizable que necesitan ambas historias de usuario.

- [X] T002 Crear `resources/views/components/barra-progreso-pago.blade.php`: recibe `:monto-pagado` y `:monto-total` (floats), calcula internamente el porcentaje (`min(100, montoTotal > 0 ? round(montoPagado / montoTotal * 100) : 0)`) y la clase de color (`bg-secondary` si `montoPagado <= 0`, `bg-success` si `montoPagado >= montoTotal`, `bg-warning` en cualquier otro caso — research.md Decisión 1), y renderiza `div.progress` (altura reducida, research.md Decisión 3) + `div.progress-bar` con `role="progressbar"`, `aria-valuenow`, `aria-valuemin="0"`, `aria-valuemax="100"` (research.md Decisión 2).

**Checkpoint**: el componente existe y es utilizable — listo para US1 y US2.

---

## Phase 3: User Story 1 - Ver el avance de pago mediante una barra visual en "Registro de Pagos" (Priority: P1) 🎯 MVP

**Goal**: cada locación con recibos vigentes en el periodo, en "Registro de Pagos", muestra una barra de
progreso junto a su monto pagado/total ya existente en texto.

**Independent Test**: con locaciones en distintos estados de avance, abrir "Registro de Pagos" y verificar
que cada barra corresponde a su proporción pagada (quickstart.md Escenario 1).

### Tests for User Story 1 ⚠️

- [X] T003 [P] [US1] En `tests/Feature/SeguimientoPagosControllerTest.php`, agregar tests que confirmen que una locación sin pagos muestra una barra con `aria-valuenow="0"` y la clase `bg-secondary`; una con pago parcial muestra el porcentaje correcto (ej. 40% para S/400 de S/1000) y `bg-warning`; una completamente pagada muestra `aria-valuenow="100"` y `bg-success`; y una locación sin recibos vigentes no muestra ninguna barra (FR-001, FR-002, FR-005, FR-007).

### Implementation for User Story 1

- [X] T004 [US1] En `resources/views/pagos/seguimiento/partials/estado-pago-locacion.blade.php`, agregar `<x-barra-progreso-pago :monto-pagado="$montoPagado" :monto-total="$montoTotal" />` dentro de `.fila-seguimiento-pagos__avance`, junto (no en reemplazo) al texto ya existente, condicionado igual que el texto (`@if ($estadoAgregado !== 'sin_recibos')`).
- [X] T005 [US1] Si el layout de grid de `.fila-seguimiento-pagos__avance` lo requiere para que la barra y el texto se vean bien apilados, ajustar `resources/css/bootstrap.scss` (sección 12, "Tabla de seguimiento de pagos") con el mínimo estilo necesario.
- [X] T006 [US1] Ejecutar el Escenario 1 de `quickstart.md` y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: User Story 1 completa — "Registro de Pagos" refuerza visualmente su avance de pago con una
barra de progreso.

---

## Phase 4: User Story 2 - Ver la misma barra de progreso en el detalle de un recibo individual (Priority: P2)

**Goal**: el detalle de un recibo muestra la misma barra de progreso junto a su avance de pago ya mostrado
en texto (specs/032).

**Independent Test**: abrir el detalle de un recibo con pago parcial y verificar que la barra es consistente
con su monto pagado/total (quickstart.md Escenario 2).

### Tests for User Story 2 ⚠️

- [X] T007 [P] [US2] En `tests/Feature/ReciboControllerTest.php`, agregar tests que confirmen: un recibo sin pagos muestra la barra en `bg-secondary` con `aria-valuenow="0"`; un recibo con pago parcial muestra el porcentaje correcto y `bg-warning`; un recibo completamente pagado muestra `aria-valuenow="100"` y `bg-success` (FR-003, FR-007).

### Implementation for User Story 2

- [X] T008 [US2] En `resources/views/locaciones/recibos/show.blade.php`, agregar `<x-barra-progreso-pago :monto-pagado="$recibo->montoPagado()" :monto-total="$recibo->total()" />` junto al badge de avance ya existente en la cabecera de la tarjeta de Pagos (dentro del `@if ($recibo->estado !== 'anulado')` ya existente).
- [X] T009 [US2] Ejecutar el Escenario 2 de `quickstart.md` (incluyendo registrar/eliminar un pago y confirmar que la barra se actualiza al recargar) y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: Las 2 historias de usuario están completas e independientemente verificables.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T010 Revisar los casos límite de `quickstart.md`: locación con recibos vigentes mixtos (pagados y no pagados) en "Registro de Pagos", y eliminar el único pago de un recibo completamente pagado en el detalle de recibo.
- [X] T011 [P] Revisión de diseño con el skill `impeccable` (`audit` o `polish`) sobre `barra-progreso-pago.blade.php`, `estado-pago-locacion.blade.php` y `locaciones/recibos/show.blade.php` (Principio VI de la constitución — primer uso del componente `progress` de Bootstrap en el proyecto).
- [X] T012 Correr la suite completa (`php artisan test`, binario Herd) y confirmar 0 fallos.
- [X] T013 Validar manualmente el checklist completo de `quickstart.md` (los 2 escenarios, los casos límite y la regresión) contra la base de datos de desarrollo real, en navegador.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de T001. Bloquea a US1 y US2 (ambas necesitan el componente).
- **User Story 1 (Phase 3)**: depende de Foundational.
- **User Story 2 (Phase 4)**: depende de Foundational. Independiente de User Story 1.
- **Polish (Phase 5)**: depende de que las 2 historias estén completas.

### Dentro de cada fase

- Los tests marcados ⚠️ se escriben/actualizan antes que su implementación y deben fallar primero
  (Principio IV).

### Parallel Opportunities

- T003 (test US1) y T007 (test US2) están en archivos distintos y pueden escribirse en paralelo.
- User Story 1 y User Story 2 son independientes entre sí una vez completada Foundational.

---

## Implementation Strategy

### MVP First (User Story 1)

1. Setup (T001) → Foundational (T002).
2. User Story 1 (T003-T006): el pedido explícito de esta feature.
3. **Parar y validar**: quickstart.md Escenario 1.

### Incremental Delivery

1. Setup → Foundational → listo para ambas historias.
2. User Story 1 → validar (Escenario 1) → demo de la barra en "Registro de Pagos".
3. User Story 2 → validar (Escenario 2) → demo de la barra en el detalle de recibo.
4. Polish (T010-T013) cierra la feature.

---

## Notes

- `[Story]` = trazabilidad a las historias de usuario de `spec.md`.
- `[P]` = archivos distintos sin dependencia de código entre las tareas.
- Sin `data-model.md` ni `contracts/`: no hay entidades, columnas ni rutas nuevas.
