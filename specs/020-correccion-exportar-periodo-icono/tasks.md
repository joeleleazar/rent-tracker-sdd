---

description: "Task list for 020-correccion-exportar-periodo-icono"
---

# Tasks: Corrección de Exportación, Cambio de Periodo e Ícono de Edición en Registro Masivo

**Input**: Design documents from `/specs/020-correccion-exportar-periodo-icono/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/marcado-corregido.md, quickstart.md

**Tests**: incluidas donde Pest puede verificar algo real — contrato HTML (atributos `hx-boost`,
`type`, separación de los dos controles). El comportamiento en el navegador (descarga efectiva,
refresco visual, ausencia de tooltip huérfano) no es verificable por Pest (research.md) y se
valida manualmente en Phase 6 con `quickstart.md`, ya reproducido una vez durante la planificación.

**Organization**: tareas agrupadas por historia de usuario (spec.md), en orden de prioridad (US1
P1, US2 P2, US3 P3). Las tres son independientes entre sí — tocan líneas distintas de
`index.blade.php` (US1/US2) o archivos exclusivos de US3.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`)
para `artisan`/`pest` en esta máquina.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test --filter=RegistroMasivoLecturasControllerTest` (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

## Phase 2: Foundational

No hay tareas fundacionales: los tres defectos son de marcado/atributos en archivos ya existentes,
sin esquema, controlador ni servicio nuevo, y ninguna historia depende de otra (research.md,
Decisiones 1-4 son independientes entre sí).

**Checkpoint**: sin bloqueos — se puede empezar directamente por la Historia 1 (P1).

---

## Phase 3: User Story 1 - Confiar en la lectura del periodo anterior al cambiar de periodo (Priority: P1)

**Goal**: el botón "Cambiar Periodo" envía el formulario GET, para que la pantalla realmente
recargue con el periodo recién seleccionado (contracts/marcado-corregido.md, Contrato 2).

**Independent Test**: cambiar el selector de periodo y hacer clic en "Cambiar Periodo"; verificar
que la URL y el contenido de la pantalla reflejan el nuevo periodo.

### Tests for User Story 1 ⚠️

> Escribir primero, confirmar que falla antes de implementar T003.

- [X] T002 [US1] Prueba: el botón "Cambiar Periodo" del `index()` declara `type="submit"` en el HTML de la respuesta — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`.

### Implementation for User Story 1

- [X] T003 [US1] En `resources/views/lecturas/registro-masivo/index.blade.php`, agregar `type="submit"` explícito a `<x-secondary-button>Cambiar Periodo</x-secondary-button>` (research.md Decisión 2 — el componente por sí solo es `type="button"` por defecto).

**Checkpoint**: US1 funcional y verificable de forma independiente — `quickstart.md` Escenario 1.

---

## Phase 4: User Story 2 - Exportar el registro masivo a Excel o PDF (Priority: P2)

**Goal**: los enlaces de exportar quedan excluidos del `hx-boost` del layout, para que el navegador
los trate como una descarga normal en vez de una navegación AJAX (contracts/marcado-corregido.md,
Contrato 1).

**Independent Test**: hacer clic en "Exportar a Excel"/"Exportar a PDF" y verificar que el
navegador descarga el archivo sin abandonar la pantalla.

### Tests for User Story 2 ⚠️

> Escribir primero, confirmar que falla antes de implementar T005.

- [X] T004 [US2] Prueba: los enlaces "Exportar a Excel" y "Exportar a PDF" del `index()` declaran `hx-boost="false"` en el HTML de la respuesta — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`.

### Implementation for User Story 2

- [X] T005 [US2] En `resources/views/lecturas/registro-masivo/index.blade.php`, agregar `hx-boost="false"` a los dos `<a>` de "Exportar a Excel" y "Exportar a PDF" (research.md Decisión 1).

**Checkpoint**: US2 funcional y verificable de forma independiente — `quickstart.md` Escenario 2. Las pruebas ya existentes de `exportarExcel`/`exportarPdf` (HTTP directo) no cambian: confirman que el backend siempre funcionó, el defecto era exclusivamente el disparo desde el enlace.

---

## Phase 5: User Story 3 - Distinguir el ícono de "completada" del control de editar, sin tooltips atascados (Priority: P3)

**Goal**: separar el ícono informativo de "completada" del botón que dispara la edición, y disponer
los tooltips de Bootstrap antes de que htmx reemplace su elemento (contracts/marcado-corregido.md,
Contrato 3).

**Independent Test**: ver una fila completada, pasar el cursor sobre el ícono verde (no dispara
edición), hacer clic en el botón de editar (control separado) y confirmar que ningún tooltip queda
visible después del clic.

### Tests for User Story 3 ⚠️

> Escribir primero, confirmar que fallan antes de implementar T008-T009.

- [X] T006 [US3] Prueba: para una fila completada, el ícono `bi-check-circle-fill` vive dentro de un `<span>` (no un elemento con `hx-get`), con su propio `aria-label`/`title` informativo — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`.
- [X] T007 [US3] Prueba: para esa misma fila, existe un `<button>` separado con el ícono `bi-pencil-square`, `hx-get` hacia `lecturas.registroMasivo.editarInline`, y su propio `aria-label`/`title` de "editar" — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`. La prueba ya existente `'el icono de lectura completada aparece antes del valor de la lectura en el marcado'` no necesita cambios (el ícono de completada sigue precediendo al valor).

### Implementation for User Story 3

- [X] T008 [US3] En `resources/views/lecturas/registro-masivo/partials/campo-lectura-registro-masivo.blade.php`, reemplazar el `<button>` del ícono de completada por un `<span>` informativo (sin `hx-get`), y agregar un `<button>` nuevo con `bi-pencil-square` que lleve el `hx-get`/`hx-target`/`hx-swap` que antes tenía el ícono (research.md Decisión 4; contracts/marcado-corregido.md Contrato 3).
- [X] T009 [US3] En `resources/js/registro-masivo-lecturas.js`, agregar un listener de `htmx:beforeCleanupElement` que llame `bootstrap.Tooltip.getInstance(...)?.dispose()` sobre el elemento removido y sus descendientes con `data-bs-toggle="tooltip"` (research.md Decisión 3).

**Checkpoint**: US3 funcional — `quickstart.md` Escenario 3 (verificación manual del tooltip, no automatizable).

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T010 [P] Correr `php artisan test` completo (binario Herd) y confirmar 0 regresiones sobre toda la suite.
- [X] T011 Revisión de diseño con el skill `impeccable` (`/impeccable polish` o `audit`) sobre `index.blade.php` y `campo-lectura-registro-masivo.blade.php`, documentando el resultado en `DESIGN.md` si corresponde (Principio VI, obligatorio antes de cerrar la tarea).
- [X] T012 Ejecutar manualmente los 3 escenarios de `specs/020-correccion-exportar-periodo-icono/quickstart.md` en el navegador — los tres ya se reprodujeron una vez durante la planificación; esta vez deben confirmar el comportamiento corregido (descarga real, refresco de pantalla, sin tooltip atascado).

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: vacía — no bloquea nada.
- **User Story 1 (Phase 3)**: puede empezar apenas termina T001. Independiente de US2/US3.
- **User Story 2 (Phase 4)**: puede empezar apenas termina T001, en paralelo con US1/US3 si hay más
  de una persona — toca el mismo archivo que US1 (`index.blade.php`) pero líneas distintas.
- **User Story 3 (Phase 5)**: puede empezar apenas termina T001, en paralelo con US1/US2 — toca
  archivos exclusivos (`campo-lectura-registro-masivo.blade.php`, `registro-masivo-lecturas.js`).
- **Polish (Phase 6)**: depende de que las tres historias que se vayan a entregar estén completas.

### Dentro de cada historia

- Las pruebas (T002, T004, T006-T007) se escriben antes que su implementación correspondiente y
  deben fallar primero (Principio IV).
- T003 y T005 modifican el mismo archivo (`index.blade.php`) en secciones distintas — secuenciales
  entre sí si las hace la misma persona, no `[P]`.
- T008 y T009 tocan archivos distintos entre sí — paralelizables.

### Parallel Opportunities

- US1 (Phase 3), US2 (Phase 4) y US3 (Phase 5) completas son paralelizables entre sí con más de una
  persona trabajando (research.md: las cuatro decisiones son independientes).
- Dentro de US3: T008 y T009 en paralelo.

---

## Parallel Example: Historias completas

```bash
# Tras T001, tres personas podrían trabajar en paralelo:
Task: "US1 completa — type=submit en Cambiar Periodo (T002-T003)"
Task: "US2 completa — hx-boost=false en enlaces de exportar (T004-T005)"
Task: "US3 completa — separar icono/boton editar + dispose de tooltips (T006-T009)"
```

---

## Implementation Strategy

### MVP First (User Story 1 solamente)

1. T001 (Setup).
2. Phase 2 no tiene tareas — pasar directo a Phase 3.
3. Completar Phase 3 (US1): el defecto más consecuente (referencia de datos incorrecta al cambiar
   de periodo) queda resuelto.
4. **Parar y validar**: correr T002 y el Escenario 1 de `quickstart.md` de forma independiente.

### Incremental Delivery

1. Setup → Foundational (vacía) → listo para empezar.
2. Agregar US1 (Cambiar Periodo) → validar independientemente → entrega mínima (el bug de mayor
   impacto en la confiabilidad de los datos).
3. Agregar US2 (Exportar) → validar independientemente.
4. Agregar US3 (ícono/tooltip) → validar independientemente.
5. Phase 6 (Polish: suite completa, revisión `impeccable`, validación manual) cierra la feature.

---

## Notes

- [P] = archivos distintos, sin dependencia de código entre las tareas.
- [US1]/[US2]/[US3] = trazabilidad a la historia de usuario correspondiente de `spec.md`.
- Ningún archivo de backend (controlador, servicio, ruta, migración) se toca en esta feature — todo
  el cambio vive en `index.blade.php`, `campo-lectura-registro-masivo.blade.php` y
  `registro-masivo-lecturas.js` (plan.md, Project Structure).
- Confirmar que las pruebas fallan antes de implementar cada tarea de implementación asociada.
