---

description: "Task list template for feature implementation"
---

# Tasks: Reconstrucción de Vistas según la Guía de Referencia Bootstrap

**Input**: Design documents from `/specs/012-reconstruccion-vistas-guia/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: No se agrega lógica de negocio nueva; la suite completa existente (193 pruebas) actúa como gate de no-regresión tras cada historia. La única ampliación de controlador (T005) se verifica específicamente contra la prueba existente que ya cubre `assertSessionHasErrors('solapamiento')`.

**Organization**: Las tareas están agrupadas por historia de usuario (P1/P2/P3).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: Historia de usuario a la que pertenece la tarea (US1, US2, US3)
- Se incluyen rutas de archivo exactas en cada descripción

## Path Conventions

Aplicación Laravel monolítica única — rutas relativas a la raíz del repositorio: `resources/`, `app/Http/Controllers/`, `tests/`, según `plan.md` → Project Structure.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Ninguna dependencia nueva — fase vacía deliberadamente

- [X] T001 Confirmar línea base: `php artisan test` completo pasa 193/193 antes de empezar (sin cambios de código)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: La única pieza compartida entre historias es la ampliación aditiva de `ContratoController` (US1), que no bloquea a US2/US3 — fase vacía, cada historia es independiente

**Checkpoint**: N/A — se procede directamente a las historias de usuario

---

## Phase 3: User Story 1 - Componentes de Contrato Fieles a la Guía (Priority: P1) 🎯 MVP

**Goal**: Dropzone de documentos, modal de solapamiento en dos bloques, timeline de historial, grid de costos con total calculado.

**Independent Test**: Crear un contrato con documentos, provocar un solapamiento, revisar el historial de una locación, y registrar costos de referencia, comprobando la estructura de cada componente y que el resultado de negocio es idéntico al actual.

### Implementation for User Story 1

- [X] T002 [P] [US1] Envolver los botones de carga existentes en un contenedor de dropzone visual (borde punteado, texto "O arrastra archivos aquí") en `resources/views/contratos/partials/galeria-documentos.blade.php` (ver `research.md` §7) — implementado en la sección de carga de `contratos/show.blade.php` (donde viven los botones reales), no en la galería de ya-subidos
- [X] T003 [P] [US1] Reestructurar el grid de costos a `row g-4` + `col-md-6` con `input-group` "S/" para los 4 costos existentes, agregando el campo de solo lectura "Total de Referencia" en `resources/views/contratos/partials/costos-fijos-contrato.blade.php` (ver `research.md` §2) — también replicado en el bloque inline equivalente de `contratos/show.blade.php` (formulario dedicado de actualización de costos), para mantener ambos lugares consistentes
- [X] T004 [US1] Crear `resources/js/costos-fijos-contrato.js` (recálculo en vivo del total sobre los 4 campos de costo, evento `input`) y registrarlo en `vite.config.js` (depende de T003) — reengancha sus listeners en `htmx:afterSettle`, no solo `DOMContentLoaded` (ver nota de T006 sobre el mismo problema)
- [X] T005 [US1] Ampliar el `catch (ContratoSolapadoException)` en `app/Http/Controllers/ContratoController.php` (`store`/`update`) para adjuntar `->with('contratoEnConflicto', ...)` a la respuesta existente (ver `research.md` §3 y `plan.md` → Complexity Tracking) — **desvío encontrado en vivo**: se flashea un array plano (`datosContratoEnConflicto()`, nuevo método privado) en vez del modelo Eloquent directamente, porque la sesión de Laravel deserializa objetos flasheados como arrays planos en la siguiente petición (`Attempt to read property "fecha_inicio" on array`); confirmado con prueba manual en navegador tras el fix
- [X] T006 [US1] Construir el modal de solapamiento con dos bloques de `alert` (contrato existente vía `contratoEnConflicto`, contrato intentado vía `old()`) en `resources/views/contratos/create.blade.php` y `edit.blade.php` (depende de T005) — nuevo partial compartido `contratos/partials/modal-solapamiento.blade.php`. **Bug real encontrado y corregido**: el auto-apertura del modal (`data-autoshow`, `resources/js/bootstrap.js`) solo escuchaba `DOMContentLoaded`, que nunca se dispara en una navegación boosteada por htmx (specs/011) — un modal marcado para auto-abrirse tras un envío fallido nunca aparecía. Se agregó el mismo listener también a `htmx:afterSettle`
- [X] T007 [US1] Reconstruir el historial de contratos como timeline (`border-start`, `badge` de estado, indicador de fecha) en `resources/views/contratos/index.blade.php` (ver `research.md` §1) — verificado visualmente con `border-secondary`/`border-success` según estado
- [X] T008 [US1] Ejecutar `npm run build` + `php artisan test` completo y confirmar 193/193 sin cambios de aserciones, verificando en particular que `assertSessionHasErrors('solapamiento')` sigue pasando tras T005 (depende de T002-T007; ver `quickstart.md` Escenario 1) — 193/193; modal de solapamiento verificado end-to-end en navegador con datos reales de ambos contratos

**Checkpoint**: User Story 1 completa y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Paneles de Representantes y Recibos Fieles a la Guía (Priority: P2)

**Goal**: Tarjetas de representante en grid de 2 columnas; selector de estado de recibo con 3 opciones simultáneas.

**Independent Test**: Agregar dos representantes y comprobar el grid de tarjetas; cambiar el estado de un recibo y comprobar el control unificado de 3 opciones.

### Implementation for User Story 2

- [X] T009 [P] [US2] Envolver las tarjetas de representante en `row g-3` + `col-md-6` en `resources/views/contratos/partials/representantes-contrato.blade.php`, sin alterar su contenido interno ni la búsqueda ya modal-based (ver `research.md` §5)
- [X] T010 [P] [US2] Reemplazar los botones de acción condicionales por un control unificado con las 3 opciones (Pendiente/Pagado/Anulado) simultáneamente visibles, apuntando al mismo endpoint `recibos.estado.update` en `resources/views/locaciones/recibos/show.blade.php` (ver `research.md` §4) — **desvío**: se implementó con 3 botones de `btn-group` (la opción vigente deshabilitada/resaltada en color sólido, las otras dos en estilo outline) en vez de `btn-check` real, porque cada transición sigue siendo su propio `<form>` independiente (algunas requieren abrir un modal de confirmación, otras envían directo) — un grupo de `<input type="radio" class="btn-check">` no puede representar ambos comportamientos sin JS adicional. Se preservó exactamente qué transiciones piden confirmación y cuáles no
- [X] T011 [US2] Ejecutar `php artisan test` completo y confirmar 193/193 sin cambios de aserciones, verificando en particular las pruebas de transición de estado de recibo (depende de T009, T010; ver `quickstart.md` Escenario 2) — 193/193; transición Pendiente→Pagado verificada en navegador sin recarga completa (htmx boost) y sin duplicar la confirmación ya exigida para Anulado

**Checkpoint**: User Story 1 y 2 completas y funcionan de forma independiente

---

## Phase 5: User Story 3 - Vista de Impresión y Consistencia Restante (Priority: P3)

**Goal**: Reglas de impresión en el comprobante; auditoría de consistencia del resto de vistas.

**Independent Test**: Imprimir/previsualizar un comprobante y confirmar formato limpio; recorrer el resto de vistas y confirmar consistencia de componentes ya estandarizados.

### Implementation for User Story 3

- [X] T012 [US3] Agregar reglas `@media print` (ocultar navegación/controles, ajustar a una columna limpia) al CSS propio de `resources/views/locaciones/recibos/comprobante.blade.php` (ver `research.md` §6) — la ocultación de `.no-imprimir` ya existía de specs/007; se agregó quitar el padding/borde/border-radius de "tarjeta flotante" del comprobante en impresión
- [X] T013 [P] [US3] Auditar las vistas restantes (`locaciones/`, `locaciones/lecturas/`, `configuracion/`) contra los componentes ya estandarizados (cards, badges, input-groups, breadcrumbs) y corregir cualquier inconsistencia puntual encontrada — sin hallazgos: ninguna clase Tailwind/Alpine residual (`btn-senior-*`, `x-app-layout`, etc.) en todo `resources/views/`
- [X] T014 [US3] Ejecutar `php artisan test` completo y confirmar 193/193 (depende de T012, T013; ver `quickstart.md` Escenario 3) — 193/193

**Checkpoint**: Las 3 historias de usuario completas

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Verificación final integral

- [X] T015 [P] Auditoría de accesibilidad final (contraste, tipografía ≥18px, botones ≥48x48px) sobre los 6 componentes reconstruidos — reutilizan las variables Sass ya auditadas de `bootstrap.scss`, sin overrides puntuales que las comprometan
- [X] T016 Verificar manualmente en navegador que las 3 reconciliaciones vinculantes (sidebar, htmx, paleta) siguen intactas en las vistas reconstruidas (FR-009) — confirmado: sidebar presente en todas las capturas, navegación sin recarga completa (htmx boost activo, verificado por ausencia de re-fetch de assets), y la paleta de `bootstrap.scss` sin tocar
- [X] T017 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 3) de extremo a extremo — verificado manualmente en navegador (dropzone, modal de solapamiento con datos reales, total de costos en vivo, timeline coloreado, grid de representantes, selector de estado de recibo)
- [X] T018 [P] Ejecutar `php artisan test` completo una última vez y confirmar 193/193 — 193/193, 461 aserciones

**Bugs reales encontrados y corregidos durante la implementación** (no estaban en el plan original, surgieron al verificar en navegador):
1. La sesión de Laravel deserializa objetos Eloquent flasheados como arrays planos en la siguiente petición — el modal de solapamiento (T005) tuvo que pasar un array explícito, no el modelo.
2. El mecanismo de auto-apertura de modales (`data-autoshow`) solo escuchaba `DOMContentLoaded`, que nunca se dispara de nuevo bajo `hx-boost` (specs/011) — afecta a CUALQUIER modal auto-abierto tras un envío boosteado, no solo el de solapamiento; corregido de forma centralizada en `resources/js/bootstrap.js`.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Confirma la línea base — puede iniciar de inmediato
- **Foundational (Phase 2)**: Vacía — las 3 historias son independientes entre sí
- **User Stories (Phase 3-5)**: Todas dependen de Setup; se recomienda el orden P1 → P2 → P3 ya fijado por la especificación
- **Polish (Phase 6)**: Depende de que las 3 historias estén completas

### User Story Dependencies

- **User Story 1 (P1)**: Sin dependencias de otras historias — incluye la única ampliación de controlador de toda la feature
- **User Story 2 (P2)**: Completamente independiente de US1 (archivos disjuntos: representantes y recibos vs. contratos)
- **User Story 3 (P3)**: Independiente de US1/US2; se beneficia de que ambas estén completas para la auditoría de consistencia general (T013)

### Parallel Opportunities

- T002/T003 (dropzone, grid de costos) pueden avanzar en paralelo dentro de US1
- T009/T010 (representantes, recibos) pueden avanzar en paralelo dentro de US2
- US1 y US2 podrían asignarse a distintas personas en paralelo tras Setup, dado que tocan archivos disjuntos

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Completar Phase 1: Setup
2. Completar Phase 3: User Story 1
3. **DETENERSE Y VALIDAR**: Escenario 1 de `quickstart.md`, suite completa 193/193
4. Desplegar/demostrar si está listo

### Incremental Delivery

1. Setup → base lista
2. User Story 1 (P1) → validar → demo
3. User Story 2 (P2) → validar → demo
4. User Story 3 (P3) → validar → demo

---

## Notes

- T005 es la única tarea de toda esta feature que toca un controlador; todo lo demás es exclusivamente Blade/CSS/JS de presentación.
- [P] = archivos distintos, sin dependencias pendientes
- Hacer commit tras cada tarea o grupo lógico de tareas
- Detenerse en cada checkpoint para validar la historia de forma independiente
