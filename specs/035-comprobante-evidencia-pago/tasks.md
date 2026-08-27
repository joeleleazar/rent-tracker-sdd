---

description: "Task list for 035-comprobante-evidencia-pago"
---

# Tasks: Comprobante de Pago Firmado y Evidencia de Pago

**Input**: Design documents from `/specs/035-comprobante-evidencia-pago/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: incluidas — Principio IV de la constitución.

**Organization**: 2 historias de usuario, ambas P1 (US1 comprobante de pago, US2 evidencia de pago), más
una fase Foundational mínima.

**Nota de dependencia real**: US1 (exportar el comprobante) NO depende de Foundational — solo lee datos de
`Pago`/`Recibo` ya existentes desde specs/032, sin tocar las columnas de evidencia nuevas. Solo US2 (subir
evidencia) depende de Foundational. Ambas historias son independientes entre sí.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan`/`pest`; el dominio real del proyecto en esta máquina es `rent-tracker-sdd.test`.

## Phase 1: Setup

- [ ] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

## Phase 2: Foundational — columnas de evidencia en Pago

**Propósito**: la base de datos que necesita US2. No bloquea a US1.

- [ ] T002 Migración `add_evidencia_a_pagos_table`: `evidencia_ruta` (string, nullable), `evidencia_nombre_archivo` (string, nullable), `evidencia_tipo` (enum `pdf`/`imagen`, nullable) en `pagos` (data-model.md; research.md Decisión 2).
- [ ] T003 Extender `app/Models/Pago.php`: agregar las 3 columnas nuevas a `$fillable` y el método `tieneEvidencia(): bool` (`evidencia_ruta !== null`) (data-model.md).

**Checkpoint**: `Pago` puede tener una evidencia asociada — listo para US2.

---

## Phase 3: User Story 1 - Exportar el comprobante de un pago (Priority: P1) 🎯 MVP

**Goal**: cada pago tiene su propio comprobante imprimible, con el monto de ese pago destacado, el avance
del recibo (acumulado y saldo pendiente), y un espacio de firma.

**Independent Test**: exportar el comprobante de un pago parcial y verificar que muestra correctamente el
monto de ese pago, el acumulado y el saldo pendiente (quickstart.md Escenario 1).

### Tests for User Story 1 ⚠️

- [ ] T004 [P] [US1] Feature test `tests/Feature/ComprobantePagoControllerTest.php`: `GET pagos.comprobante` muestra N.° de recibo, N.° de pago, fecha, locación, inquilino, monto del pago, total del recibo, acumulado y saldo pendiente, matemáticamente consistentes; el comprobante sigue disponible si el recibo está anulado; refleja el monto actualizado si el pago se edita después de exportarlo por primera vez (contracts/comprobante-pago.md; research.md Decisión 4).

### Implementation for User Story 1

- [ ] T005 [US1] `PagoReciboController::comprobante(Pago $pago)` + ruta `GET /pagos/{pago}/comprobante` → `pagos.comprobante` en `routes/web.php`, cargando `$pago->recibo` con `locacion`/`contrato`/`conceptos`/`pagos` (contracts/comprobante-pago.md).
- [ ] T006 [US1] Vista `resources/views/pagos/comprobante.blade.php`: standalone (propio `<head>`, sin el layout de sidebar), con la hoja de estilos real de Bootstrap 5 vía Vite (research.md Decisión 1) — bloques encabezado (logo + título "Comprobante de Pago"), metadatos, partes, monto de este pago destacado, avance del recibo (total/acumulado/saldo pendiente), espacio de firma, y cierre (contracts/comprobante-pago.md).
- [ ] T007 [US1] En `resources/views/locaciones/recibos/show.blade.php`, agregar un enlace "Ver Comprobante" (`hx-boost="false"`) por cada pago listado, junto a las acciones Editar/Eliminar ya existentes (specs/032).
- [ ] T008 [US1] Ejecutar el Escenario 1 de `quickstart.md` (contenido del comprobante, consistencia matemática, impresión, saldo en S/ 0.00 cuando corresponde) y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: User Story 1 completa — cada pago tiene su propio comprobante imprimible y firmable.

---

## Phase 4: User Story 2 - Subir la evidencia del pago firmado (Priority: P1)

**Goal**: cada pago admite subir (o reemplazar) un archivo de evidencia del comprobante ya firmado, y
consultarlo después.

**Independent Test**: sobre un pago sin evidencia, subir un archivo, confirmar que queda asociado y puede
volver a consultarse; subir uno nuevo y confirmar que reemplaza al anterior (quickstart.md Escenario 2).

### Tests for User Story 2 ⚠️

- [ ] T009 [P] [US2] Feature test `tests/Feature/EvidenciaPagoControllerTest.php`: `POST pagos.evidencia.store` con una imagen válida y con un PDF válido quedan asociados al pago; una segunda subida reemplaza la evidencia anterior (el archivo viejo deja de existir en el disco); `GET pagos.evidencia.show` devuelve el archivo correcto; se rechaza un archivo de tipo no admitido y uno que excede el tamaño máximo, sin afectar el pago; `GET pagos.evidencia.show` sobre un pago sin evidencia responde 404 (contracts/evidencia-pago.md).

### Implementation for User Story 2

- [ ] T010 [US2] `app/Http/Requests/SolicitudSubirEvidenciaPago.php`: `archivo` (`required`, `file`, `mimes:pdf,jpg,jpeg,png`, `max:10240`) (contracts/evidencia-pago.md; research.md Decisión 5).
- [ ] T011 [US2] `app/Http/Controllers/EvidenciaPagoController.php`, métodos `store()` (borra la evidencia anterior del disco si existía, guarda la nueva en `pagos/{id}/`, actualiza las 3 columnas, todo en `DB::transaction`) y `show()` (`Storage::disk('local')->response(...)`, 404 si no hay evidencia) + rutas `POST /pagos/{pago}/evidencia` → `pagos.evidencia.store` y `GET /pagos/{pago}/evidencia` → `pagos.evidencia.show` en `routes/web.php` (contracts/evidencia-pago.md; research.md Decisión 3).
- [ ] T012 [US2] En `resources/views/locaciones/recibos/show.blade.php`, agregar por cada pago listado: un indicador de "sin evidencia"/"evidencia subida" (`tieneEvidencia()`), un formulario para subir o reemplazar el archivo, y un enlace para consultar la evidencia ya subida.
- [ ] T013 [US2] Ejecutar el Escenario 2 de `quickstart.md` (subir, consultar, reemplazar, con imagen y con PDF) y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: las dos historias de usuario están completas e independientemente verificables.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [ ] T014 Revisar los casos límite restantes de `quickstart.md`: archivo de tipo/tamaño no admitido, comprobante de un pago con recibo anulado, comprobante que refleja un pago editado después de la primera exportación.
- [ ] T015 [P] Revisión de diseño con el skill `impeccable` sobre `pagos/comprobante.blade.php` y los cambios en `recibos/show.blade.php` (Principio VI de la constitución) — a diferencia del comprobante del recibo completo, esta vista sí debe auditarse como una vista Bootstrap normal, sin ninguna excepción.
- [ ] T016 Correr la suite completa (`php artisan test`, binario Herd) y confirmar 0 fallos.
- [ ] T017 Validar manualmente el checklist completo de `quickstart.md` (los 2 escenarios, los casos límite y la regresión) contra la base de datos de desarrollo real, en navegador.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de T001. Bloquea solo a User Story 2 (ver nota al inicio de este
  documento) — User Story 1 no necesita las columnas de evidencia.
- **User Story 1 (Phase 3)**: depende solo de Setup (T001). Independiente de Foundational y de User Story 2.
- **User Story 2 (Phase 4)**: depende de Foundational (T002-T003). Independiente de User Story 1.
- **Polish (Phase 5)**: depende de que las 2 historias estén completas.

### Dentro de cada fase

- Los tests marcados ⚠️ se escriben/actualizan antes que su implementación y deben fallar primero
  (Principio IV).
- T007 y T012 tocan el mismo archivo (`recibos/show.blade.php`) — si se desarrollan en paralelo, deben
  aplicarse como ediciones separadas sobre el mismo archivo, no simultáneas.

### Parallel Opportunities

- T002 y T003 (Foundational) son técnicamente secuenciales (el modelo depende de que la migración exista),
  pero triviales de aplicar en el orden listado.
- User Story 1 (Phase 3) puede desarrollarse en paralelo a Foundational + User Story 2 si hay más de un
  desarrollador, ya que no depende de ninguna de las dos.
- T004 (test de US1) y T009 (test de US2) están en archivos distintos y pueden escribirse en paralelo si
  ambas historias avanzan a la vez.

---

## Implementation Strategy

### MVP First (User Story 1)

1. Setup (T001).
2. User Story 1 (T004-T008): entrega el comprobante de pago imprimible y firmable — el corazón del pedido
   original ("se debe poder exportar el recibo indicando el avance del pago").
3. **Parar y validar**: quickstart.md Escenario 1.

### Incremental Delivery

1. Setup → listo para User Story 1; Foundational → listo para User Story 2 (en paralelo si hay equipo).
2. User Story 1 → validar (Escenario 1) → demo del comprobante de pago.
3. User Story 2 → validar (Escenario 2) → demo de subir/consultar/reemplazar evidencia.
4. Polish (T014-T017) cierra la feature.

---

## Notes

- `[Story]` = trazabilidad a las historias de usuario de `spec.md`.
- `[P]` = archivos distintos sin dependencia de código entre las tareas.
- T015 destaca explícitamente que, a diferencia de `locaciones/recibos/comprobante.blade.php` (specs/031,
  con su excepción documentada a Bootstrap), `pagos/comprobante.blade.php` no tiene ninguna excepción y debe
  auditarse con el mismo criterio que cualquier otra vista Blade del proyecto.
