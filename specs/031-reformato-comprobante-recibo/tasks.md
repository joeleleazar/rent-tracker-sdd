---

description: "Task list for 031-reformato-comprobante-recibo"
---

# Tasks: Reformato de Jerarquía Visual del Comprobante de Recibo

**Input**: Design documents from `/specs/031-reformato-comprobante-recibo/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: incluidas — Principio IV de la constitución (cobertura de controladores/modelos).

**Organization**: 3 historias de usuario en orden de prioridad (US1 P1, US2 P1, US3 P2), más una fase
Foundational que agrega el campo `nombre_propietario` a Configuración General. **Nota importante**: las 3
historias reestructuran el mismo archivo (`resources/views/locaciones/recibos/comprobante.blade.php`) —
son independientemente *verificables* (cada una con su propio escenario de `quickstart.md`), pero US2 y
US3 se implementan como refinamientos sobre el esqueleto de bloques que crea US1, no en paralelo real.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan`/`pest` en esta máquina; el dominio real del proyecto en esta máquina es `rent-tracker-sdd.test`.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

## Phase 2: Foundational — campo `nombre_propietario` en Configuración General

**Propósito**: el dato que necesita el bloque de partes del comprobante (US1) para mostrar "Recibido por". Bloquea a US1.

- [X] T002 [P] Agregar `nombre_propietario` a `$fillable` y a `valoresPorDefecto()` (valor por defecto `null`) en `app/Models/ConfiguracionGeneral.php` (research.md Decisión 6; data-model.md).
- [X] T003 [P] Agregar la regla de validación `nombre_propietario` (`nullable|string|max:255`) y su mensaje de error en `app/Http/Requests/SolicitudActualizarConfiguracionGeneral.php` (contracts/configuracion-general.md).
- [X] T004 [P] Agregar el campo "Nombre del propietario/administrador" al formulario en `resources/views/configuracion/edit.blade.php`, siguiendo el mismo patrón Bootstrap (`x-input-label`, `x-text-input`, `x-input-error`) que los campos ya existentes.
- [X] T005 En `app/Http/Controllers/ReciboController.php`, método `comprobante()`, pasar el valor de `ConfiguracionGeneral::actual()->nombre_propietario` a la vista del comprobante (contracts/configuracion-general.md).
- [X] T006 [P] Feature test en `tests/Feature/ConfiguracionGeneralControllerTest.php`: guardar `nombre_propietario` con un valor válido persiste correctamente; dejarlo vacío (`null`) es válido; un valor de más de 255 caracteres falla la validación.
- [X] T007 [P] Unit test en `tests/Unit/ConfiguracionGeneralTest.php`: `ConfiguracionGeneral::actual()->nombre_propietario` es `null` cuando la clave todavía no existe en la tabla.

**Checkpoint**: Configuración General soporta y persiste el nuevo campo, y el comprobante ya tiene el dato disponible — listo para reestructurar la vista.

---

## Phase 3: User Story 1 - Leer el comprobante de un vistazo, de arriba hacia abajo (Priority: P1) 🎯 MVP

**Goal**: el comprobante se reorganiza en los 6 bloques verticales separados, con el encabezado (logo + título), los metadatos y los datos de las partes ya reestructurados.

**Independent Test**: abrir el comprobante de un recibo pagado y recorrerlo de arriba hacia abajo verificando el orden y la separación de bloques (quickstart.md Escenario 1).

### Tests for User Story 1 ⚠️

- [X] T008 [P] [US1] Extender `tests/Feature/ReciboControllerTest.php`: el comprobante muestra, en este orden (`assertSeeInOrder`), "Recibo de Pago", "N.° de recibo", "Recibí de", el nombre del primer concepto del recibo, "Total" y la frase de cierre (contracts/estructura-comprobante.md).
- [X] T009 [P] [US1] Extender `tests/Feature/ReciboControllerTest.php`: con `nombre_propietario` configurado, el comprobante muestra "Recibido por" y el valor configurado; sin configurar (`null`), esa fila no aparece y el resto del comprobante responde igual de completo (spec.md FR-005a).

### Implementation for User Story 1

- [X] T010 [US1] En `resources/views/locaciones/recibos/comprobante.blade.php`, reemplazar el `<h1>Recibo #{{ $recibo->id }}</h1>` por un `.bloque-encabezado` que agrupa el `<img class="logo-comprobante">` ya existente junto al título genérico "Recibo de Pago" (research.md Decisión 5).
- [X] T011 [US1] Construir `.bloque-metadatos` con filas `.fila-dato` (flex de dos columnas, etiqueta izquierda/valor derecha) para N.° de recibo (el id que salió del título), Fecha de emisión, Período y Estado (research.md Decisión 2 y 5).
- [X] T012 [US1] Construir `.bloque-partes` con "Recibí de" (inquilino), la Locación, y "Recibido por" mostrado solo cuando `nombre_propietario` no es `null`/vacío (data-model.md; contracts/estructura-comprobante.md; spec.md FR-004, FR-005, FR-005a).
- [X] T013 [US1] Agregar `<hr class="separador-bloque">` entre `.bloque-encabezado`, `.bloque-metadatos` y `.bloque-partes`, con el CSS correspondiente (research.md Decisión 1).
- [X] T014 [US1] Ajustar la hoja de estilos embebida de `comprobante.blade.php` para la jerarquía tipográfica de 3 niveles (título, texto base con variaciones de peso/mayúsculas/`letter-spacing` para etiquetas de bloque y de fila, total) — sin introducir un tamaño de fuente adicional (research.md Decisión 4; spec.md FR-010).
- [X] T015 [US1] Ejecutar el Escenario 1 de `quickstart.md` (recorrido de los 6 bloques, conteo de tamaños tipográficos) y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: el comprobante se lee en un único recorrido vertical, con encabezado, metadatos y partes ya reestructurados.

---

## Phase 4: User Story 2 - Identificar el monto total pagado de inmediato (Priority: P1)

**Goal**: el bloque de total se distingue de cualquier otro número del documento por tamaño, peso y fondo de color.

**Independent Test**: con User Story 1 ya implementada, ver el comprobante por 1-2 segundos y confirmar que el total es lo primero que se identifica (quickstart.md Escenario 2).

### Tests for User Story 2 ⚠️

- [X] T016 [P] [US2] Extender `tests/Feature/ReciboControllerTest.php`: el comprobante sigue mostrando el monto total correcto (mantener las aserciones ya existentes, ej. `assertSee('1,500.00')`), ahora dentro de `.bloque-total`.

### Implementation for User Story 2

- [X] T017 [US2] Reemplazar `.fila-total` por `.bloque-total` en `comprobante.blade.php`: fondo `#1e40af` (mismo azul ya usado en `.btn-primario` de este archivo), texto blanco, y el tamaño de fuente más grande de todo el documento (research.md Decisión 3; spec.md FR-008).
- [X] T018 [US2] Confirmar que `.bloque-total` no queda tapado por la marca "Anulado" (`position: absolute; inset: 0`) en el comprobante de un recibo anulado, ni la superpone de forma que pierda legibilidad — ajustar si hace falta (spec.md FR-012).
- [X] T019 [US2] Ejecutar el Escenario 2 de `quickstart.md` (el total como elemento más destacado) y el caso límite del recibo anulado, corrigiendo cualquier hallazgo antes de continuar.

**Checkpoint**: User Stories 1 y 2 completas — el total es el elemento visualmente más destacado del documento.

---

## Phase 5: User Story 3 - Verificar el detalle exacto de qué se cobró (Priority: P2)

**Goal**: cada concepto cobrado aparece en su propia línea, con todos los montos del documento alineados en una misma columna a la derecha.

**Independent Test**: con User Story 1 ya implementada, abrir el comprobante de un recibo con varios conceptos y verificar que cada uno es una línea independiente con su monto alineado (quickstart.md Escenario 3).

### Tests for User Story 3 ⚠️

- [X] T020 [P] [US3] Extender `tests/Feature/ReciboControllerTest.php`: un recibo con alquiler + varios conceptos de gasto fijo muestra cada uno como línea independiente (assert por cada nombre de concepto); un recibo sin monto de alquiler no muestra ninguna línea de "Alquiler" (spec.md Edge Cases).

### Implementation for User Story 3

- [X] T021 [US3] Construir `.bloque-conceptos` en `comprobante.blade.php` con una `.fila-concepto` (flex de dos columnas) por Alquiler (solo si `monto_renta` no es `null`) y por cada `recibo_concepto`, nombre a la izquierda y monto alineado a la derecha (`text-align:right; font-variant-numeric: tabular-nums`) (research.md Decisión 2; spec.md FR-006).
- [X] T022 [US3] Verificar y ajustar si hace falta que los montos de `.fila-dato` (metadatos), `.fila-concepto` (conceptos) y `.bloque-total` queden alineados en la misma columna vertical, compartiendo el mismo padding horizontal del contenedor `.recibo` (research.md Decisión 2; spec.md FR-007, SC-003).
- [X] T023 [US3] Ejecutar el Escenario 3 de `quickstart.md` (cada concepto en su propia línea, alineación de montos) y corregir cualquier hallazgo antes de continuar.

**Checkpoint**: las tres historias de usuario están completas e independientemente verificables.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T024 Construir `.bloque-cierre` en `comprobante.blade.php` con la frase breve de agradecimiento, precedida de su `<hr class="separador-bloque">` (research.md Decisión 7; spec.md FR-011).
- [X] T025 Revisar los casos límite restantes de `quickstart.md` (nombre de locación/inquilino largos, vista previa de impresión, captura para WhatsApp) y corregir si hace falta.
- [X] T026 Eliminar el CSS/clases muertas que hayan quedado del diseño anterior (`.fila`, estilos de `dt`/`dd` sin uso) tras la reestructuración, confirmando con una búsqueda de referencias antes de borrar.
- [X] T027 [P] Revisión de diseño con el skill `impeccable` sobre `comprobante.blade.php` y `resources/views/configuracion/edit.blade.php` (Principio VI de la constitución) — para `comprobante.blade.php`, la revisión se enfoca en jerarquía tipográfica, contraste y consistencia dentro de la excepción a Bootstrap ya documentada (plan.md Constitution Check), no en componentes Bootstrap.
- [X] T028 Correr la suite completa (`php artisan test`, binario Herd) y confirmar 0 fallos.
- [X] T029 Validar manualmente los 3 escenarios, los casos límite y la regresión de `quickstart.md` contra la base de datos de desarrollo real, en navegador (incluyendo impresión y "Enviar por WhatsApp").

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de T001. Bloquea a User Story 1 (necesita el dato `nombre_propietario` para el bloque de partes).
- **User Story 1 (Phase 3)**: depende de Foundational (T002-T007). Es el esqueleto de bloques que las otras dos historias refinan.
- **User Story 2 (Phase 4)**: depende de Foundational y, por reestructurar el mismo archivo, del esqueleto de bloques que crea User Story 1 (T010-T014) — no tiene sentido destacar un bloque de total que todavía no existe como bloque propio.
- **User Story 3 (Phase 5)**: depende de Foundational y, por el mismo motivo, del esqueleto de User Story 1.
- **Polish (Phase 6)**: depende de que las 3 historias estén completas.

### Dentro de cada fase

- Los tests marcados ⚠️ se escriben/actualizan antes que su implementación y deben fallar primero
  (Principio IV).
- T002, T003 y T004 tocan archivos distintos (modelo, FormRequest, vista de Configuración) sin dependencia
  de código entre sí — pueden hacerse en paralelo. T005 (controlador) es conceptualmente posterior a T002
  (necesita que el atributo exista para tener sentido), aunque no falla en tiempo de ejecución si se hace
  antes.
- T010-T014 (User Story 1) se aplican en orden sobre el mismo archivo — no son paralelizables entre sí.

### Parallel Opportunities

- T002, T003, T004 (Foundational, archivos distintos) en paralelo.
- T006, T007 (tests de Foundational, archivos distintos) en paralelo entre sí, después de T002-T005.
- T008, T009 (tests de US1) en paralelo entre sí — mismo archivo de test pero bloques de `test()`
  independientes.
- Ninguna otra tarea de implementación es paralelizable real: `comprobante.blade.php` es el archivo central
  de las 3 historias y se edita de forma incremental y secuencial.

---

## Implementation Strategy

### MVP First (Foundational + User Story 1)

1. Setup (T001) → Foundational (T002-T007).
2. User Story 1 (T008-T015): entrega el esqueleto completo de 6 bloques con encabezado, metadatos y
   partes reestructurados — el MVP visual del pedido original.
3. **Parar y validar**: quickstart.md Escenario 1.

### Incremental Delivery

1. Setup → Foundational → listo para User Story 1.
2. User Story 1 → validar (Escenario 1) → demo del esqueleto de bloques.
3. User Story 2 → validar (Escenario 2) → demo del total destacado.
4. User Story 3 → validar (Escenario 3) → demo del detalle de conceptos alineado.
5. Polish (T024-T029) cierra la feature.

---

## Notes

- `[Story]` = trazabilidad a las historias de usuario de `spec.md`.
- `[P]` = archivos distintos (o bloques de test independientes en el mismo archivo), sin dependencia de
  código entre las tareas — ver la nota de organización al inicio sobre por qué la mayoría de las tareas de
  implementación no llevan `[P]` en esta feature.
- T026 (eliminar CSS muerto) existe para no dejar reglas sin uso del diseño anterior, siguiendo el mismo
  criterio ya aplicado en features anteriores de este proyecto.
