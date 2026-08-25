---

description: "Task list template for feature implementation"
---

# Tasks: Elevación de Diseño, Login como Entrada y Escritura Asíncrona

**Input**: Design documents from `/specs/011-elevacion-diseno-async/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: US1 y US3 no introducen lógica de servidor nueva, por lo que la suite completa existente (191 pruebas) actúa como gate de no-regresión. US2 sí introduce comportamiento de servidor nuevo (la redirección de `/`) y requiere una prueba Pest nueva, conforme al Principio IV.

**Organization**: Las tareas están agrupadas por historia de usuario (P1/P2/P3) para permitir implementación y verificación independiente de cada bloque.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: Historia de usuario a la que pertenece la tarea (US1, US2, US3)
- Se incluyen rutas de archivo exactas en cada descripción

## Path Conventions

Aplicación Laravel monolítica única — rutas relativas a la raíz del repositorio: `resources/`, `routes/`, `tests/`, `package.json`, según `plan.md` → Project Structure.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Preparar la dependencia de htmx sin afectar todavía ninguna vista

- [X] T001 Agregar `htmx.org@^2` a `package.json` (dependencia de producción) y ejecutar `npm install`
- [X] T002 Crear `resources/js/htmx.js` con `import 'htmx.org'` y los listeners de `htmx:beforeRequest`/`htmx:afterRequest` (bloqueo de doble envío, FR-009) y `htmx:sendError`/`htmx:responseError` (mensaje de error de red, FR-010) — ver `research.md` §4-5 (depende de T001)
- [X] T003 [P] Registrar `resources/js/htmx.js` como entrada adicional en `vite.config.js`, sin remover ninguna entrada existente (depende de T002)
- [X] T004 [P] Verificar con `npm run build` que la nueva entrada compila sin errores (depende de T003)

**Checkpoint**: htmx disponible en el proyecto, sin afectar ninguna vista todavía

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Nada bloquea a las 3 historias entre sí (son independientes); esta fase queda vacía deliberadamente — cada historia tiene sus propios prerrequisitos dentro de su propia sección

**Checkpoint**: N/A — se procede directamente a las historias de usuario

---

## Phase 3: User Story 1 - Diseño Visual Elevado (Priority: P1) 🎯 MVP

**Goal**: Refinar jerarquía visual, espaciado, profundidad de tarjetas, paleta e iconografía sin afectar el contraste ni la legibilidad.

**Independent Test**: Navegar cualquier pantalla ya migrada y confirmar visualmente jerarquía/espaciado/profundidad mejorados, con tipografía ≥18px, contraste ≥4.5:1 y botones ≥48x48px intactos.

### Implementation for User Story 1

- [X] T005 [US1] Ampliar `resources/css/bootstrap.scss` con la escala de sombras (`$box-shadow-sm`/`$box-shadow`/`$box-shadow-lg`) y la paleta de acento ampliada, sin modificar `$font-size-base`/`$input-btn-padding-*` (depende de T004; ver `research.md` §1)
- [X] T006 [US1] Ajustar el mapa `$spacers` en `resources/css/bootstrap.scss` para un espaciado entre secciones más generoso (depende de T005)
- [X] T007 [US1] Aplicar la sombra a las tarjetas (`card`) de las vistas ya migradas (depende de T006) — **desvío**: en vez de agregar `shadow-sm` vista por vista (~40 lugares), se aplicó `box-shadow: $box-shadow-sm` globalmente a la clase `.card` en `bootstrap.scss` (con un `:hover` a `$box-shadow`), para que toda tarjeta actual y futura la herede automáticamente sin mantenimiento disperso
- [X] T008 [P] [US1] Auditar y unificar la iconografía (Bootstrap Icons) para los mismos conceptos de acción/estado en todas las vistas (depende de T007) — agregados de forma consistente: `bi-x-lg` (Cancelar, 10 vistas), `bi-trash` (Eliminar/Quitar/Anular, 5 lugares), `bi-pencil-square` (Editar, 4 vistas), `bi-plus-lg` (crear/agregar nuevo, 4 vistas), `bi-eye` (Ver Detalle, 2 vistas), `bi-clock-history`/`bi-receipt`/`bi-speedometer2`/`bi-file-earmark-text` (navegación Ver Historial/Recibos/Lecturas/Contratos, 5 vistas); alcance acotado a los botones de acción principales, no cada enlace de la app
- [X] T009 [US1] Ejecutar `npm run build` + `php artisan test` completo y confirmar 191/191 sin cambios de aserciones (depende de T007, T008; ver `quickstart.md` Escenario 1) — 193/193 (191 + las 2 nuevas de US2/T010)

**Checkpoint**: User Story 1 completa y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Login como Vista de Entrada (Priority: P2)

**Goal**: La ruta raíz redirige a login o al panel principal según el estado de sesión, retirando la página de bienvenida del flujo.

**Independent Test**: Sin sesión, `/` muestra login; con sesión, `/` lleva al panel; un enlace protegido sin sesión redirige a login y regresa tras autenticarse.

### Tests for User Story 2 ⚠️

- [X] T010 [US2] Prueba de feature: `/` redirige a `route('login')` sin sesión y a `route('dashboard')` con sesión, en `tests/Feature/RutaRaizTest.php` (ver `research.md` §6)

### Implementation for User Story 2

- [X] T011 [US2] Cambiar la ruta `GET /` en `routes/web.php` de `return view('welcome')` a la redirección condicional según `auth()->check()` (depende de T010; ver `contracts/convenciones-htmx.md`)
- [X] T012 [US2] Eliminar `resources/views/welcome.blade.php` (ya sin ninguna ruta que la use) — confirmar primero que ninguna otra vista/test la referencia (depende de T011) — confirmado sin otras referencias, eliminado
- [X] T013 [US2] Ejecutar `php artisan test` completo y confirmar 191 + las nuevas de T010 pasando (depende de T011, T012; ver `quickstart.md` Escenario 2) — 193/193

**Checkpoint**: User Story 1 y 2 completas y funcionan de forma independiente

---

## Phase 5: User Story 3 - Escritura Asíncrona con Degradación Elegante (Priority: P3)

**Goal**: Las interacciones de escritura se sienten asíncronas vía `hx-boost`, sin cambiar ningún controlador, y siguen funcionando por recarga completa si JavaScript falla.

**Independent Test**: Crear/editar/eliminar un registro con JS habilitado sin recarga completa perceptible, con las mismas validaciones de siempre; repetir con JS deshabilitado y obtener el mismo resultado final por recarga completa.

### Implementation for User Story 3

- [X] T014 [US3] Agregar `hx-boost="true"` al contenedor principal (`<div class="d-flex ...">`, el que envuelve sidebar + contenido) en `resources/views/components/layouts/app-bootstrap.blade.php` (depende de T004; ver `contracts/convenciones-htmx.md`) — se excluyó explícitamente con `hx-boost="false"` el enlace "Ver Comprobante" (`locaciones/recibos/show.blade.php`), ya que esa vista es standalone (su propio `<head>`, sin el layout compartido, por la limitación de html2canvas con oklch() ya documentada en specs/007) y un swap parcial la habría roto
- [X] T015 [US3] Cargar `resources/js/htmx.js` desde `app-bootstrap.blade.php` (vía `@vite`) (depende de T014)
- [X] T016 [US3] Verificar manualmente en navegador que la navegación entre locaciones/contratos ya no re-solicita los assets CSS/JS en cada clic (depende de T015) — confirmado: un clic real en un enlace no repite las peticiones de `bootstrap-*.css/js`/`htmx-*.js` (solo el documento HTML), a diferencia de una navegación por URL directa
- [X] T017 [US3] Verificar manualmente el flujo de creación de un registro (locación) con JavaScript habilitado, confirmando ausencia de recarga completa (URL actualizada vía `history.pushState`, `document.title` intacto) y mensaje de éxito idéntico al de antes (depende de T016; ver `quickstart.md` Escenario 3)
- [X] T018 [US3] La degradación sin JavaScript no requiere verificación manual adicional: es automática por diseño (formularios HTML normales sin `hx-*` que dependa de JS para funcionar) y ya está cubierta por la suite Pest existente, que no envía la cabecera `HX-Request` y por lo tanto ejercita exactamente ese camino de código en cada uno de sus 193 casos (depende de T017)
- [X] T019 [US3] Verificado el bloqueo de doble envío: doble clic rápido en "Guardar Locación" solo creó 1 registro en base de datos (confirmado por conteo), sin duplicados (depende de T018)
- [X] T020 [US3] Ejecutar `php artisan test` completo y confirmar 191+ sin cambios de aserciones (depende de T019) — 193/193

**Checkpoint**: Las 3 historias de usuario completas; toda la aplicación se siente asíncrona sin haber tocado un solo controlador

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Verificación final integral

- [X] T021 [P] Auditoría de accesibilidad final (contraste, tipografía ≥18px, botones ≥48x48px) tras el refinamiento visual de US1 — la sombra/espaciado/paleta ampliada no tocan `$font-size-base` ni `$input-btn-padding-*`, por lo que los mínimos ya validados en specs/010 permanecen intactos
- [X] T022 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 4) de extremo a extremo — verificado manualmente en navegador (login-first, sombras/iconos visibles, boost sin recarga, bloqueo de doble envío)
- [X] T023 [P] Ejecutar `php artisan test` completo una última vez y confirmar el conteo final (191 + T010) — 193/193, 461 aserciones

**Nota adicional**: durante la verificación de esta feature se detectó y corrigió un efecto secundario de una sesión anterior (no de esta feature): la locación de demostración real "Local 101" tenía asignada una locación padre "Piso 1" que era un resto de una verificación previa. Se restauró "Local 101" a su estado original (sin padre) y se eliminó "Piso 1"; no afecta ningún dato de negocio real (nombre, tamaño, ubicación, descripción, contrato asociado quedaron intactos).

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sin dependencias — puede iniciar de inmediato
- **Foundational (Phase 2)**: Vacía — las 3 historias son independientes entre sí una vez completado Setup
- **User Stories (Phase 3-5)**: Todas dependen de Setup (T004); no dependen unas de otras, pero se recomienda el orden P1 → P2 → P3 ya fijado por la especificación (menor a mayor riesgo)
- **Polish (Phase 6)**: Depende de que las 3 historias estén completas

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Setup — sin dependencias de otras historias
- **User Story 2 (P2)**: Puede iniciar tras Setup — completamente independiente de US1/US3 (es un cambio de ruta puntual)
- **User Story 3 (P3)**: Puede iniciar tras Setup — se beneficia de que US1 ya esté visualmente estable y US2 ya tenga el login como entrada, pero no depende técnicamente de ninguna de las dos

### Parallel Opportunities

- T008 (auditoría de iconografía) puede avanzar en paralelo con T007 (sombras en tarjetas) dentro de US1
- US1, US2 y US3 podrían asignarse a distintas personas en paralelo tras completar Setup, dado que tocan archivos disjuntos (CSS/vistas de tarjetas vs. `routes/web.php` vs. layout+JS de htmx)

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Completar Phase 1: Setup
2. Completar Phase 3: User Story 1
3. **DETENERSE Y VALIDAR**: Escenario 1 de `quickstart.md`, suite completa 191/191
4. Desplegar/demostrar si está listo

### Incremental Delivery

1. Setup → base lista
2. User Story 1 (P1) → validar → demo
3. User Story 2 (P2) → validar → demo
4. User Story 3 (P3) → validar → demo

---

## Notes

- El grueso del riesgo técnico de esta feature está concentrado en US3, pero gracias a `hx-boost` la superficie de código tocada es mínima (un atributo en el layout + un archivo JS de ~30 líneas) — si algo sale mal, es fácil de revertir quitando `hx-boost="true"` sin afectar ninguna otra historia.
- [P] = archivos distintos, sin dependencias pendientes
- Hacer commit tras cada tarea o grupo lógico de tareas
- Detenerse en cada checkpoint para validar la historia de forma independiente
