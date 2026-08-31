---

description: "Task list for 042-loader-y-notificaciones-autocierre"
---

# Tasks: Loader de Carga de Página y Notificaciones de Respuesta con Autocierre

**Input**: Design documents from `/specs/042-loader-y-notificaciones-autocierre/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/comportamiento-ui.md, quickstart.md

**Tests**: incluidas de forma mínima — no hay modelo ni controlador nuevo (Principio IV no aplica a ese
nivel), pero el plan compromete una prueba Feature del contrato del componente `x-mensaje-alerta` y la
no-regresión de la suite completa. El comportamiento temporizado (8 s, pausa por hover/foco) y la barra se
verifican en el navegador vía `quickstart.md`, como en `specs/041`.

**Organization**: 2 historias de usuario, ambas P1 e independientes entre sí — US1 (notificaciones de
respuesta efímeras) y US2 (barra de progreso de navegación) —, más una fase Foundational de una sola tarea
(la enmienda constitucional que autoriza el autocierre).

**Nota de entorno**: usar el binario PHP de Herd `C:\Users\joel5\.config\herd\bin\php84\php.exe` para
`artisan` / `pest`; `npm run build` / `npm run dev` para los assets.

**Alcance cero**: 0 migraciones, 0 rutas, 0 controladores, 0 modelos, 0 Form Requests. Todo el trabajo vive
en `resources/` (presentación) más la enmienda de `.specify/memory/constitution.md`, la actualización de
`DESIGN.md` y una prueba en `tests/Feature/`.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y `npm run build`, y
  verificar que la suite (433 pruebas) está en verde y el build sin errores antes de tocar ningún archivo.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Propósito**: dejar la regla normativa alineada con la decisión del usuario antes de implementar el
autocierre, para no introducir una contradicción silenciosa con la Constitución.

**⚠️ CRITICAL**: US1 no debe cerrarse sin esta tarea; conviene hacerla primero.

- [X] T002 Enmendar `.specify/memory/constitution.md`, sección "Restricciones Técnicas y Estándares de
  Interfaz → Mensajes de Estado y Feedback": reemplazar la exigencia de "mensajes persistentes … no por un
  temporizador" por notificaciones de respuesta efímeras que se autocierran tras un máximo de 8 s, con el
  temporizador en pausa mientras el puntero o el foco están sobre la notificación y con control de cierre
  manual; aclarar que los errores de validación **por campo** siguen siendo persistentes junto a su campo.
  Añadir el bloque `Sync Impact Report` al inicio, subir la versión `2.1.1 → 2.2.0` y actualizar
  `**Last Amended**` a 2026-08-30 (research.md §5; plan.md Complexity Tracking).

**Checkpoint**: la Constitución ya autoriza el autocierre; US1 y US2 pueden implementarse en cualquier orden.

---

## Phase 3: User Story 1 - Las confirmaciones de una acción no se acumulan en pantalla (Priority: P1) 🎯 MVP

**Goal**: toda notificación de respuesta (éxito o error) se muestra, se puede leer, y se cierra sola tras un
máximo de 8 s; el hover del puntero o el foco de teclado detienen el temporizador y lo reinician entero al
salir; además hay un botón de cierre inmediato. Sin JavaScript, la notificación queda persistente como hoy.

**Independent Test**: ejecutar una operación CRUD exitosa y otra que devuelva error de resumen; confirmar que
cada banner aparece, se autocierra en ≤ 8 s sin interacción, permanece visible mientras se le hace hover >
8 s, se vuelve a cerrar al retirar el puntero, y que el botón "Cerrar" lo oculta al instante.

### Tests for User Story 1 ⚠️

- [X] T003 [P] [US1] Escribir la prueba Feature `tests/Feature/ComponenteMensajeAlertaTest.php` (debe fallar
  antes de T004): al renderizar `<x-mensaje-alerta tipo="exito">` y `<x-mensaje-alerta tipo="error">` el
  HTML contiene `role="alert"`, las clases `alert-dismissible fade show` y un
  `<button ... class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar">`; además, una vista real que
  emite flash (p. ej. `conceptos-gasto-fijo.index` con `session('mensaje')`) sigue conteniendo el texto del
  mensaje (no-regresión). Usar `Blade::render(...)` o una petición HTTP a una ruta que ya flashea
  (contracts/comportamiento-ui.md §A; §C).

### Implementation for User Story 1

- [X] T004 [P] [US1] Modificar `resources/views/components/mensaje-alerta.blade.php`: añadir
  `alert-dismissible fade show` a la lista de clases del `<div>`, agregar como último hijo
  `<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>`, y
  reescribir el comentario de encabezado (ya no "Mensaje persistente (no se oculta automáticamente)") para
  reflejar el autocierre con pausa por hover. Conservar `role="alert"`, el ícono `bi-*` y el `match($tipo)`
  de `alert-success` / `alert-danger` (contracts/comportamiento-ui.md §A).

- [X] T005 [P] [US1] Añadir a `resources/js/bootstrap.js` la función `iniciarAutocierreNotificaciones()`:
  escanea `.alert.alert-dismissible` (marcando cada nodo con un `dataset` para no procesarlo dos veces) en
  `DOMContentLoaded` y en `htmx:afterSettle`; por nodo, `setTimeout` de `MS_AUTOCIERRE = 8000`;
  `mouseenter` y `focusin` → `clearTimeout`; `mouseleave` y `focusout` (cuando no queda ni hover ni foco
  dentro) → nuevo `setTimeout(MS_AUTOCIERRE)`; al vencer → `bootstrap.Alert.getOrCreateInstance(el).close()`.
  Registrar los dos listeners junto a los de `autoabrirModales` ya existentes. Identificadores y comentarios
  en español (contracts/comportamiento-ui.md §A; data-model.md §1).

- [X] T006 [US1] Añadir a `resources/css/bootstrap.scss` una regla
  `@media (prefers-reduced-motion: reduce) { .alert.fade { transition: none; } }` para que el cierre del
  banner sea instantáneo cuando el usuario pide movimiento reducido (research.md §4; FR-005).

- [X] T007 [US1] `npm run build` y verificar en el navegador los Escenarios 1–5 y 10 de `quickstart.md`
  (autocierre ≤ 8 s; hover y foco de teclado pausan y reinician; cierre manual instantáneo; el banner de
  error también se autocierra; con `prefers-reduced-motion` el cierre es sin animación). Corregir hallazgos.

**Checkpoint**: US1 entregable de forma independiente — las notificaciones ya no se acumulan en pantalla.

---

## Phase 4: User Story 2 - El usuario percibe que una página está cargando (Priority: P1)

**Goal**: al navegar entre secciones, si la respuesta tarda más que el umbral anti-parpadeo (~150 ms),
aparece una barra fina fija en el borde superior de la ventana que se retira al completarse, fallar o
abortarse la navegación. Los envíos de formulario no disparan la barra (conservan el botón "Guardando…"). La
primera carga dura de página usa el indicador nativo del navegador.

**Independent Test**: con throttling de red, navegar entre dos secciones y ver la barra aparecer (< 1 s) y
desaparecer al cargar; forzar offline a mitad de navegación y ver que la barra se retira; enviar un
formulario y confirmar que muestra "Guardando…" sin disparar la barra superior.

### Implementation for User Story 2

- [X] T008 [P] [US2] Añadir el markup de la barra al inicio de `<body>` en
  `resources/views/components/layouts/app-bootstrap.blade.php`:
  `<div class="barra-carga-navegacion progress d-none" aria-hidden="true"><div class="progress-bar" style="width: 0%"></div></div>`
  (contracts/comportamiento-ui.md §B).

- [X] T009 [US2] Añadir a `resources/css/bootstrap.scss` los estilos de `.barra-carga-navegacion`: `position:
  fixed; top: 0; left: 0; right: 0;` alto ~3 px, `z-index` por encima del sidebar, oculta por defecto
  (acompaña a `d-none`); `.barra-carga-navegacion .progress-bar` con color `$primary` y `transition` de
  `width`; más `@media (prefers-reduced-motion: reduce) { .barra-carga-navegacion .progress-bar { transition:
  none; } }`. Editar el mismo archivo que T006 — hacerlo después de T006 si se trabaja en serie
  (research.md §3–4; FR-013/FR-014).

- [X] T010 [P] [US2] Añadir a `resources/js/htmx.js` el ciclo de vida de la barra: en `htmx:beforeRequest`,
  si el verbo es `GET` (`evento.detail.requestConfig.verb`; respaldo: `evento.target` es `<a>` o no está
  dentro de un `<form>`) armar `setTimeout(MS_UMBRAL_ANTIPARPADEO = 150)` que llama `mostrarBarraCarga()`
  (quita `d-none`, anima `progress-bar` hacia ~90 %); no hacer nada si el verbo es `POST`/`PUT`/`PATCH`/
  `DELETE`. En `htmx:beforeSwap` y `htmx:afterRequest` → llevar a 100 % y `ocultarBarraCarga()`. En
  `htmx:sendError`, `htmx:responseError` y `htmx:abort` → `clearTimeout` + `ocultarBarraCarga()` inmediato.
  Verificar el nombre exacto de la propiedad del verbo contra htmx 2.0.10. Identificadores y comentarios en
  español (contracts/comportamiento-ui.md §B; data-model.md §2).

- [X] T011 [US2] `npm run build` y verificar en el navegador los Escenarios 6–10 de `quickstart.md` (barra en
  navegación lenta < 1 s y luego oculta; se retira ante error de red/servidor y ante navegación abortada; un
  envío de formulario NO dispara la barra; sin parpadeo en navegación rápida; `prefers-reduced-motion` sin
  animación de ancho). Corregir hallazgos.

**Checkpoint**: US1 y US2 funcionan de forma independiente.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T012 [P] Actualizar `DESIGN.md`: en la sección "Mensaje / Alert" quitar "Persistent (no auto-dismiss) —
  the user closes it by acting, not by a timeout." y describir el autocierre ≤ 8 s con pausa por hover/foco y
  el botón de cierre; añadir una subsección "Barra de carga de navegación" (componente bespoke construido con
  `progress`/`progress-bar` y el token `$primary`, con el precedente de "Estado Vacío") (plan.md Project
  Structure).

- [X] T013 Revisión con el skill `impeccable` (`/impeccable audit` o `/impeccable polish`) sobre
  `resources/views/components/mensaje-alerta.blade.php`,
  `resources/views/components/layouts/app-bootstrap.blade.php` y los estilos nuevos de
  `resources/css/bootstrap.scss`; aplicar los hallazgos (Constitución, Principio VI).

- [X] T014 [P] Auditoría de nomenclatura en español sobre los identificadores y comentarios nuevos en
  `resources/js/bootstrap.js`, `resources/js/htmx.js` y `resources/css/bootstrap.scss` (Constitución,
  Principio II).

- [X] T015 Correr `php artisan test` completo (binario Herd) — las 433 pruebas existentes más
  `ComponenteMensajeAlertaTest` en verde (SC-007 / FR-017) — y `npm run build` sin errores.

- [X] T016 Ejecutar la guía completa `quickstart.md` (Escenarios 1–10 + "Verificación documental":
  Constitución en 2.2.0 con Sync Impact Report, `DESIGN.md` actualizado, comentario del componente
  actualizado) como verificación final del mapeo FR/SC.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de Setup. T002 es documental y no bloquea técnicamente el código, pero
  US1 no se considera completa sin ella.
- **US1 (Phase 3)**: depende de Setup. Independiente de US2.
- **US2 (Phase 4)**: depende de Setup. Independiente de US1. **Nota de archivo compartido**: T009 edita
  `resources/css/bootstrap.scss`, igual que T006 (US1); si US1 y US2 se hacen en paralelo, coordinar ese
  archivo (secciones distintas, sin solape lógico).
- **Polish (Phase 5)**: depende de las historias que se entreguen. T012/T013/T016 asumen US1 y US2 hechas.

### User Story Dependencies

- **US1 (P1)**: solo Setup. Sin dependencia de US2.
- **US2 (P1)**: solo Setup. Sin dependencia de US1.

### Within Each User Story

- US1: T003 (test, debe fallar) → T004/T005/T006 (archivos distintos, en paralelo) → T007 (build + navegador).
- US2: T008/T009/T010 (archivos distintos; T009 tras T006 si es en serie) → T011 (build + navegador).

### Parallel Opportunities

- US1: T004 (`mensaje-alerta.blade.php`), T005 (`bootstrap.js`) y T006 (`bootstrap.scss`) son archivos
  distintos → `[P]` entre sí, tras T003.
- US2: T008 (`app-bootstrap.blade.php`), T010 (`htmx.js`) son `[P]`; T009 (`bootstrap.scss`) en serie
  respecto a T006.
- Con dos personas tras el Setup: Dev A → US1 completa, Dev B → US2 completa, coordinando `bootstrap.scss`.
- Polish: T012 (`DESIGN.md`) y T014 (auditoría de nombres) son `[P]`.

---

## Parallel Example: User Story 1

```bash
# Primero el test (debe fallar):
Task: "T003 Feature test tests/Feature/ComponenteMensajeAlertaTest.php"

# Luego, en paralelo (archivos distintos):
Task: "T004 mensaje-alerta.blade.php — alert-dismissible fade show + btn-close"
Task: "T005 bootstrap.js — iniciarAutocierreNotificaciones() con pausa por hover/foco"
Task: "T006 bootstrap.scss — @media prefers-reduced-motion para .alert.fade"
```

---

## Implementation Strategy

### MVP First (solo US1)

1. Fase 1: Setup (línea base verde).
2. Fase 2: Foundational (enmienda constitucional).
3. Fase 3: US1 → validar Escenarios 1–5 y 10 de `quickstart.md`.
4. **PARAR y VALIDAR**: el pedido central ("las notificaciones no se quedan siempre en la página") está
   entregado. Desplegable como MVP.

### Incremental Delivery

1. Setup + Foundational → base lista.
2. US1 → notificaciones efímeras → demo.
3. US2 → barra de progreso de navegación → demo.
4. Polish → `DESIGN.md`, revisión `impeccable`, auditoría de nombres, suite + quickstart completos.

### Parallel Team Strategy

Tras el Setup: Dev A toma US1 (Fase 3) y Dev B toma US2 (Fase 4); el único punto de coordinación es
`resources/css/bootstrap.scss` (T006 vs T009). La Fase 2 (T002) la puede hacer cualquiera en paralelo, ya
que es un archivo aparte.

---

## Notes

- `[P]` = archivos distintos, sin dependencia entre sí.
- Ninguna tarea toca rutas, controladores, modelos ni migraciones (FR-016).
- Verificar que T003 falla antes de T004.
- La verificación del comportamiento temporizado y de la barra es manual (navegador), por `quickstart.md`;
  el proyecto no tiene navegador de Pest configurado y esta feature no lo agrega.
- Hacer commit tras cada tarea o grupo lógico.
