---

description: "Task list for 039-espacio-firma-impresion"
---

# Tasks: Más Espacio para la Firma en la Impresión del Comprobante de Pago

**Input**: Design documents from `/specs/039-espacio-firma-impresion/`

**Prerequisites**: plan.md, spec.md, quickstart.md

**Tests**: incluidas — Principio IV de la constitución.

**Organization**: 1 sola historia de usuario (P1).

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

- [X] T002 [US1] En `tests/Feature/ComprobantePagoControllerTest.php`, agregar un test que confirme que el bloque de firma incluye el área en blanco ampliada (verificar la presencia de `style="height: 5rem` o la clase equivalente) junto al texto "Firma de quien recibe el pago" ya existente.

### Implementation for User Story 1

- [X] T003 [US1] En `resources/views/pagos/comprobante.blade.php`, ampliar el bloque de firma con un área en blanco de altura fija (`height: 5rem`) antes de la línea de firma, conservando el texto "Firma de quien recibe el pago" y el resto del documento en columna única sin cambios.
- [X] T004 [US1] Ejecutar el Escenario 1 de `quickstart.md` (pantalla e impresión) y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: User Story 1 completa.

---

## Phase 3: Polish & Cross-Cutting Concerns

- [X] T005 [P] Revisión de diseño con el skill `impeccable` (`audit` o `polish`) sobre `pagos/comprobante.blade.php`.
- [X] T006 Correr la suite completa (`php artisan test`, binario Herd) y confirmar 0 fallos.
- [X] T007 Validar manualmente el checklist completo de `quickstart.md` contra la base de datos de desarrollo real, en navegador.

---

## Dependencies & Execution Order

- **Setup (Phase 1)**: sin dependencias.
- **User Story 1 (Phase 2)**: depende solo de Setup.
- **Polish (Phase 3)**: depende de que User Story 1 esté completa.
- Los tests marcados ⚠️ se escriben antes que la implementación y deben fallar primero (Principio IV).

## Notes

- `[Story]` = trazabilidad a la historia de usuario de `spec.md`.
- Sin `data-model.md`/`contracts/`: no hay entidades ni rutas nuevas.
