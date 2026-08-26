---

description: "Task list for 025-correcciones-auditoria-impeccable"
---

# Tasks: Correcciones de Auditoría Impeccable

**Input**: Design documents from `/specs/025-correcciones-auditoria-impeccable/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md (todos existentes)

**Tests**: Incluidas y obligatorias — exigidas por el Principio IV de la constitución y por el Constitution Check de plan.md (TDD).

**Organization**: US1 (P0, bloqueante) y US2 (P2, theming) son independientes entre sí. US3 (cierre de revisión de diseño) depende de que US1 y US2 ya estén aplicadas — no es una historia independiente en el sentido habitual, sino el cierre formal que evalúa el estado ya corregido (ver plan.md, Constitution Check).

## Format: `[ID] [P?] [Story] Description`

---

## Phase 1: Setup

- [X] T001 Ejecutar `php artisan test --filter=LocacionControllerTest` y confirmar la línea base actual (debe incluir el caso ya existente "rechaza guardar una locacion sin tipo" para creación, en verde). **Resultado**: 19/19 tests, 72 assertions.

---

## Phase 2: User Story 1 - Editar una locación existente sin verse forzado a clasificarla (Priority: P1) 🎯 MVP

**Goal**: Permitir guardar la edición de una locación sin `tipo` previo sin exigir que se asigne uno, sin afectar la creación ni la edición de locaciones que ya tienen tipo.

**Independent Test**: Editar una locación sembrada sin tipo, cambiar solo el nombre, guardar sin tocar "Tipo" — debe guardar sin error (quickstart.md paso 1).

### Tests for User Story 1 ⚠️ (escribir primero, deben FALLAR antes de implementar)

- [X] T002 [US1] Agregar a `tests/Feature/LocacionControllerTest.php` tres casos: (a) editar una locación con `tipo = null` cambiando el nombre y sin enviar `tipo` → debe redirigir sin errores de sesión y persistir el nombre nuevo con `tipo` aún `null` (Contrato 1); (b) editar esa misma locación sí enviando un `tipo` válido → debe guardar con ese tipo (comportamiento ya existente, de regresión); (c) editar una locación con un `tipo` ya asignado (ej. `'tipo' => 'piso'` al crearla) sin enviar `tipo` → debe seguir rechazando con `assertSessionHasErrors('tipo')` (Contrato 2). El caso (a) debe FALLAR contra el código actual; (b) y (c) ya deberían pasar (regresión).

### Implementation for User Story 1

- [X] T003 [US1] En `app/Http/Requests/SolicitudGuardarLocacion.php::rules()`, calcular `$permitirTipoVacio = $locacion !== null && $locacion->tipo === null` (usando el `$locacionActualId`/`$this->route('locacion')` ya presente en el método) y cambiar la regla de `tipo` a `[$permitirTipoVacio ? 'nullable' : 'required', Rule::in(array_keys(Locacion::TIPOS))]` (research.md R1).
- [X] T004 [US1] Confirmar que T002 pasa por completo y correr `tests/Feature/LocacionControllerTest.php` completo para confirmar cero regresiones, incluyendo el caso existente de creación sin tipo (Contrato 3). **Resultado**: 22/22 tests, 83 assertions.

**Checkpoint**: El bug P0 queda corregido; entregable de forma independiente como MVP.

---

## Phase 3: User Story 2 - Sidebar con color y layout mantenibles desde un solo lugar (Priority: P2)

**Goal**: Eliminar la duplicación de color/dimensiones del sidebar entre `app-bootstrap.blade.php` y `bootstrap.scss`, sin cambiar el aspecto visual.

**Independent Test**: Inspeccionar el código — `app-bootstrap.blade.php` ya no define estilo base de `.sidebar-principal`; `bootstrap.scss` sí, usando `$dark` (quickstart.md paso 2).

### Implementation for User Story 2

*(Sin tarea de test dedicada: es un cambio de organización de CSS sin comportamiento observable nuevo que testear con Pest; su verificación es de inspección de código + visual, cubierta en T006).*

- [X] T005 [P] [US2] En `resources/css/bootstrap.scss`, dentro de la sección existente de `.sidebar-principal` (línea ~207), agregar la regla base: `background-color: $dark; width: 100%;` y, dentro de un bloque `@media (min-width: 768px)`, `width: 280px; min-height: 100vh;` (research.md R2, data-model.md).
- [X] T006 [US2] En `resources/views/components/layouts/app-bootstrap.blade.php`, eliminar del `<style>` embebido las reglas de `.sidebar-principal` (color, ancho, media query) que ahora viven en `bootstrap.scss`, conservando la regla `body { font-family: ... }` que no está duplicada. Ejecutar `npm run build` (o el comando de build de Vite del proyecto) y confirmar visualmente en desktop y en un viewport <768px que el sidebar se ve idéntico a antes (Contrato 4). **Resultado**: `npm run build` exitoso; verificado con estilos computados en el navegador — 1280px: `background-color: rgb(17,24,39)`, `width: 280px`, `min-height: 800px` (100vh); 375px: `width: 375.2px` (100%), `min-height: auto` — idéntico a los valores originales. **Nota**: durante la edición, el hook de diseño automático detectó que la sesión concurrente (spec 024) ya había agregado el enlace "Conceptos de Gasto Fijo" al sidebar; mi edición se aplicó limpiamente sobre ese cambio sin conflicto (verificado leyendo el archivo completo antes de continuar).

**Checkpoint**: Cero duplicación de color; mismo aspecto visual.

---

## Phase 4: User Story 3 - Cierre formal de la revisión de diseño pendiente (Priority: P3)

**Goal**: Dejar documentada la revisión `impeccable` de las 3 vistas que motivaron esta feature, ya con las correcciones de US1/US2 aplicadas.

**Independent Test**: Las 3 vistas quedan con su revisión registrada en `DESIGN.md`/sidecar (quickstart.md paso 3).

**Depende de**: Phase 2 (US1) y Phase 3 (US2) completas — no tiene sentido documentar una revisión de diseño sobre un estado que todavía tiene el bug P0 o la duplicación de estilos.

### Implementation for User Story 3

- [X] T007 [US3] Ejecutar `/impeccable polish` sobre `resources/views/components/layouts/app-bootstrap.blade.php`, `resources/views/recibos/registro-masivo/partials/error-modal-recibo.blade.php` y `resources/views/recibos/registro-masivo/partials/estado-recibo-locacion.blade.php`, y documentar el resultado en `DESIGN.md` o su sidecar (research.md R3, FR-007). **Resultado**: revisión de cierre sin hallazgos nuevos (triage completo: sin tareas bloqueadas, sin estados faltantes, sin drift de flujo/jerarquía/responsive, sin inconsistencias visuales, sin código muerto). Sin cambios a `DESIGN.md`: las 3 vistas ya cumplen exactamente los patrones que ese documento ya describe (sidebar en "Navigation", `<x-mensaje-alerta>` en "Mensaje / Alert", badges semánticos en "Componentes") — no se introdujo ningún patrón visual nuevo que documentar, consistente con cómo specs 019/020/021/023 manejaron la misma condición ("si corresponde") sin tocar `DESIGN.md` tampoco.

**Checkpoint**: Las 3 vistas quedan formalmente revisadas; el hueco que originó este feature queda cerrado.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T008 Ejecutar `php artisan test` completo y confirmar cero regresiones (SC-005). **Resultado**: 287 tests totales (241 pasan, 6 fallos + 40 errores) — **los 46 fallos/errores son 100% ajenos a este feature**: todos por `SQLSTATE[42703]: no existe la columna "costo_agua" en la relación "contratos"`, causado por trabajo concurrente en curso de otra sesión (spec 024, conceptos de gasto fijo dinámicos) que está migrando esa tabla. Ninguno de los 46 pertenece a `LocacionControllerTest`/`LocacionTest` ni menciona `sidebar`/`app-bootstrap`. Verificación aislada: `php artisan test --filter="LocacionControllerTest|LocacionTest"` → **35/35 en verde, 103 assertions**. No se intentó corregir la falla ajena (fuera de alcance de specs/025 y arriesgado sin el contexto completo de un feature de otra sesión aún no terminado).
- [X] T009 [P] Ejecutar el resto de pasos de `quickstart.md` no cubiertos por tareas anteriores como verificación end-to-end final. Los 4 pasos de quickstart.md ya quedaron cubiertos por tareas previas: paso 1 (fix de tipo) = T004; paso 2 (consolidación de sidebar, verificado además visualmente en el navegador con estilos computados) = T006; paso 3 (cierre de revisión impeccable) = T007; paso 4 (regresión general, con la salvedad documentada en T008) = T008.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sin dependencias.
- **US1 (Phase 2)** y **US2 (Phase 3)**: Ambas pueden empezar después de Setup, en paralelo entre sí (archivos disjuntos: `SolicitudGuardarLocacion.php`/`LocacionControllerTest.php` vs. `bootstrap.scss`/`app-bootstrap.blade.php`).
- **US3 (Phase 4)**: Depende de que Phase 2 y Phase 3 estén completas (ver nota de Phase 4).
- **Polish (Phase 5)**: Depende de que todas las historias estén completas.

### Parallel Opportunities

- T005 y T006 (US2) son secuenciales entre sí (mover una regla de un archivo a otro), pero toda la Phase 3 puede ejecutarse en paralelo con toda la Phase 2 (US1), ya que no comparten archivos.

---

## Implementation Strategy

### MVP First

1. Completar Phase 1 (Setup).
2. Completar Phase 2 (US1) — resuelve el bug P0 bloqueante, el de mayor impacto.
3. Detenerse y validar de forma independiente (quickstart.md paso 1).

### Entrega incremental

1. Setup → base lista.
2. US1 (P1) → validar → MVP (bug bloqueante resuelto).
3. US2 (P2) → validar → deuda de theming resuelta.
4. US3 (P3) → validar → revisión de diseño cerrada formalmente.
5. Polish → verificación final de todo el feature junto.
