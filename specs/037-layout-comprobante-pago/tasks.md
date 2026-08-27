---

description: "Task list for 037-layout-comprobante-pago"
---

# Tasks: Más Espacio para Firmar y Aprovechamiento Horizontal en el Comprobante de Pago

**Input**: Design documents from `/specs/037-layout-comprobante-pago/`

**Prerequisites**: plan.md, spec.md, research.md, quickstart.md

**Tests**: incluidas — Principio IV de la constitución.

**Organization**: 2 historias de usuario (US1 P1, US2 P2). Sin fase Foundational: no hay datos nuevos que
preparar — `recibo.pagos` ya llega cargado desde specs/035.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan`/`pest`; el dominio real del proyecto en esta máquina es `rent-tracker-sdd.test`.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

---

## Phase 2: User Story 1 - Firmar cómodamente el comprobante impreso (Priority: P1) 🎯 MVP

**Goal**: el bloque de firma del comprobante de pago reserva un área en blanco notoriamente mayor a la
actual, en pantalla y en impresión.

**Independent Test**: abrir el comprobante de cualquier pago, activar la vista previa de impresión y
confirmar que el espacio de firma es visiblemente mayor (quickstart.md Escenario 1).

### Tests for User Story 1 ⚠️

- [X] T002 [US1] En `tests/Feature/ComprobantePagoControllerTest.php`, agregar un test que confirme que el bloque de firma incluye el nuevo espacio en blanco ampliado (verificar la presencia del contenedor con la altura mínima definida, ej. `style="height: 5rem` o la clase equivalente) junto al texto "Firma de quien recibe el pago" ya existente.

### Implementation for User Story 1

- [X] T003 [US1] En `resources/views/pagos/comprobante.blade.php`, ampliar el bloque de firma con un área en blanco de altura fija antes de la línea de firma (research.md Decisión 3), conservando el texto "Firma de quien recibe el pago" ya existente.
- [X] T004 [US1] Ejecutar el Escenario 1 de `quickstart.md` (pantalla e impresión) y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: User Story 1 completa — el comprobante impreso tiene espacio suficiente para firmar a mano.

---

## Phase 3: User Story 2 - Ver el historial de pagos del recibo junto al comprobante (Priority: P2)

**Goal**: el comprobante muestra, aprovechando el ancho ganado, una lista de todos los pagos del recibo,
marcando cuál corresponde a este comprobante.

**Independent Test**: abrir el comprobante de un pago de un recibo con 2+ pagos y confirmar que la lista
muestra todos los pagos, con el correspondiente a este comprobante marcado (quickstart.md Escenario 2).

### Tests for User Story 2 ⚠️

- [X] T005 [P] [US2] En `tests/Feature/ComprobantePagoControllerTest.php`, agregar un test que registre 2 pagos sobre un recibo y confirme que el comprobante de cada uno muestra la fecha y el monto de AMBOS pagos (la lista completa, no solo el propio).
- [X] T006 [P] [US2] En el mismo archivo, agregar un test que confirme que el badge "Este pago" aparece exactamente asociado al pago del comprobante que se está viendo — abrir el comprobante del primer pago y confirmar que el marcado corresponde a él, luego el del segundo y confirmar que el marcado cambia al segundo.
- [X] T007 [P] [US2] En el mismo archivo, agregar un test para el caso de un recibo con un único pago: el comprobante muestra la lista con ese único pago, marcado como "Este pago".

### Implementation for User Story 2

- [X] T008 [US2] En `resources/views/pagos/comprobante.blade.php`, reestructurar el `card-body` en un `row` de Bootstrap: `col-md-7` con todo el contenido ya existente (metadatos, partes, monto destacado, avance, firma) y `col-md-5` con la nueva lista de pagos del recibo (research.md Decisión 1), ordenada por `id` ascendente (research.md Decisión 4), marcando con `<span class="badge bg-primary">Este pago</span>` el que corresponde al comprobante actual (research.md Decisión 2). Ensanchar el contenedor general de `max-width: 42rem` a `max-width: 56rem`.
- [X] T009 [US2] Ejecutar el Escenario 2 de `quickstart.md` (lista completa, marcado correcto, apilamiento en pantalla angosta) y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: Las 2 historias de usuario están completas e independientemente verificables.

---

## Phase 4: Polish & Cross-Cutting Concerns

- [X] T010 Revisar el caso límite de `quickstart.md` (recibo con un único pago) si no quedó ya cubierto por T007.
- [X] T011 [P] Revisión de diseño con el skill `impeccable` (`audit` o `polish`) sobre `pagos/comprobante.blade.php` (Principio VI — esta vista usa Bootstrap real sin excepción desde specs/035).
- [X] T012 Correr la suite completa (`php artisan test`, binario Herd) y confirmar 0 fallos.
- [X] T013 Validar manualmente el checklist completo de `quickstart.md` (los 2 escenarios, el caso límite y la regresión) contra la base de datos de desarrollo real, en navegador.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **User Story 1 (Phase 2)**: depende solo de Setup.
- **User Story 2 (Phase 3)**: depende solo de Setup. Comparte el mismo archivo de vista que US1 — T008 se
  aplica después de T003 (ediciones secuenciales sobre `comprobante.blade.php`, no en paralelo).
- **Polish (Phase 4)**: depende de que las 2 historias estén completas.

### Dentro de cada fase

- Los tests marcados ⚠️ se escriben antes que la implementación y deben fallar primero (Principio IV).

### Parallel Opportunities

- T005-T007 (tests de US2) están en el mismo archivo de test que T002 (US1) — aplicar como ediciones
  separadas y secuenciales, no en paralelo real, pese a la etiqueta `[P]` entre sí.
- User Story 1 y User Story 2 modifican el mismo archivo de vista (`comprobante.blade.php`) — se implementan
  en el orden T003 (US1) → T008 (US2), no en paralelo, aunque son historias independientes en cuanto a qué
  requisito prueban.

---

## Implementation Strategy

### MVP First (User Story 1)

1. Setup (T001).
2. User Story 1 (T002-T004): el problema urgente reportado — espacio de firma insuficiente.
3. **Parar y validar**: quickstart.md Escenario 1.

### Incremental Delivery

1. Setup → listo para ambas historias.
2. User Story 1 → validar (Escenario 1) → demo del espacio de firma ampliado.
3. User Story 2 → validar (Escenario 2) → demo de la lista de pagos y el layout de dos columnas.
4. Polish (T010-T013) cierra la feature.

---

## Notes

- `[Story]` = trazabilidad a las historias de usuario de `spec.md`.
- `[P]` = archivos distintos sin dependencia de código entre las tareas (con la salvedad del archivo de
  vista compartido entre US1/US2, ver arriba).
- Sin `data-model.md` ni `contracts/`: no hay entidades, columnas ni rutas nuevas.
