---

description: "Task list for 036-historico-saldo-pago"
---

# Tasks: Saldo Histórico en el Comprobante de Pago

**Input**: Design documents from `/specs/036-historico-saldo-pago/`

**Prerequisites**: plan.md, spec.md, research.md, quickstart.md

**Tests**: incluidas — Principio IV de la constitución.

**Organization**: 1 sola historia de usuario (P1). Sin fase Foundational: no hay entidades, columnas ni
rutas nuevas que preparar (research.md Decisión 1) — la corrección vive enteramente en 1 modelo y 1 vista ya
existentes.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan`/`pest`; el dominio real del proyecto en esta máquina es `rent-tracker-sdd.test`.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

---

## Phase 2: User Story 1 - Ver el avance de pago tal como era al momento de ese pago, no el actual (Priority: P1) 🎯 MVP

**Goal**: el comprobante de un pago individual muestra el acumulado y el saldo pendiente calculados solo a
partir de los pagos registrados hasta ese pago inclusive (orden de registro, `id` ascendente —
research.md Decisión 2), no el estado actual del recibo completo.

**Independent Test**: registrar dos pagos parciales que entre ambos completan un recibo; abrir el
comprobante del primer pago y confirmar que su acumulado/saldo corresponden solo a ese primer pago, aunque
el recibo ya esté completamente pagado (quickstart.md Escenario 1).

### Tests for User Story 1 ⚠️

- [X] T002 [P] [US1] En `tests/Feature/ComprobantePagoControllerTest.php`, agregar un test que registre dos pagos parciales que completan el total de un recibo y confirme que el comprobante del **primer** pago sigue mostrando el acumulado y el saldo pendiente correspondientes solo a ese primer pago (no el estado actual, completamente pagado, del recibo) — Acceptance Scenario 1.
- [X] T003 [P] [US1] En el mismo archivo, agregar un test que confirme que el comprobante del **segundo** pago (el que completa el total) sí muestra el acumulado de ambos pagos y saldo pendiente en S/ 0.00 — Acceptance Scenario 2.
- [X] T004 [P] [US1] En el mismo archivo, agregar un test para FR-003: tras registrar dos pagos, editar el monto del primero y confirmar que el comprobante del segundo recalcula su acumulado/saldo con el monto corregido del primero.
- [X] T005 [P] [US1] En el mismo archivo, agregar un test para el otro caso de FR-003: tras registrar dos pagos, eliminar el primero y confirmar que el comprobante del segundo recalcula su acumulado/saldo excluyendo el pago eliminado.
- [X] T006 [P] [US1] En el mismo archivo, confirmar (agregando el caso si no está ya cubierto) que un recibo con un único pago no cambia de comportamiento — Acceptance Scenario 3 / SC-003.

### Implementation for User Story 1

- [X] T007 [US1] En `app/Models/Pago.php`, agregar `montoAcumuladoHastaEstePago(): float` (suma de `$this->recibo->pagos` con `id <= $this->id`, sobre la colección ya cargada, sin consulta nueva) y `saldoPendienteHastaEstePago(): float` (`max(0.0, $this->recibo->total() - $this->montoAcumuladoHastaEstePago())`) — research.md Decisión 1/2/3.
- [X] T008 [US1] En `resources/views/pagos/comprobante.blade.php`, reemplazar `$recibo->montoPagado()` por `$pago->montoAcumuladoHastaEstePago()` y `$recibo->saldoPendiente()` por `$pago->saldoPendienteHastaEstePago()` en el bloque "Avance del recibo" (el resto de la vista, incluido "Total del recibo", no cambia).
- [X] T009 [US1] Ejecutar el Escenario 1 de `quickstart.md` y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: User Story 1 completa — el comprobante de cualquier pago refleja el avance real al momento
de ese pago, no el estado actual del recibo.

---

## Phase 3: Polish & Cross-Cutting Concerns

- [X] T010 Revisar los casos límite restantes de `quickstart.md` no cubiertos por T004/T005 (si los hay).
- [X] T011 Correr la suite completa (`php artisan test`, binario Herd) y confirmar 0 fallos.
- [X] T012 Validar manualmente el checklist completo de `quickstart.md` (el escenario, los casos límite y la regresión) contra la base de datos de desarrollo real, en navegador.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **User Story 1 (Phase 2)**: depende solo de Setup. Única historia de esta feature.
- **Polish (Phase 3)**: depende de que User Story 1 esté completa.

### Dentro de cada fase

- Los tests marcados ⚠️ (T002-T006) se escriben antes que la implementación (T007-T008) y deben fallar
  primero (Principio IV) — todos leen `$recibo->montoPagado()`/`saldoPendiente()` en el comprobante hoy, así
  que fallarán hasta que T007/T008 introduzcan los nuevos métodos.

### Parallel Opportunities

- T002-T006 están en el mismo archivo de test — aplicar como ediciones separadas y secuenciales, no en
  paralelo real, pese a la etiqueta `[P]` (se marca `[P]` porque no dependen unas de otras en contenido,
  no porque deban ejecutarse como escrituras concurrentes al archivo).

---

## Implementation Strategy

### MVP First (única historia)

1. Setup (T001).
2. User Story 1 (T002-T009): toda la feature — no hay historias adicionales que priorizar.
3. **Parar y validar**: quickstart.md Escenario 1.
4. Polish (T010-T012) cierra la feature.

---

## Notes

- `[Story]` = trazabilidad a la historia de usuario de `spec.md` (única: US1).
- `[P]` = archivos distintos sin dependencia de código entre las tareas (con la salvedad de T002-T006, ver
  arriba).
- Sin `data-model.md` ni `contracts/`: no hay entidades, columnas ni rutas nuevas (research.md Decisión 1).
- Sin tarea de revisión `impeccable`: esta feature no modifica marcado ni componentes visuales, solo el
  valor de dos interpolaciones ya existentes (plan.md, Constitution Check).
