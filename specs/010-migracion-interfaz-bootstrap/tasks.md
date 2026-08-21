---

description: "Task list template for feature implementation"
---

# Tasks: Migración de la Interfaz a Bootstrap 5

**Input**: Design documents from `/specs/010-migracion-interfaz-bootstrap/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Esta feature no agrega lógica de negocio nueva, por lo que no se escriben pruebas Pest nuevas; en su lugar, la suite completa existente (191 pruebas de 001-009) actúa como gate de no-regresión obligatorio (Principio IV) al final de cada historia de usuario — ver tareas de verificación dentro de cada fase.

**Organization**: Las tareas están agrupadas por historia de usuario (bloque de prioridad P1/P2/P3) para permitir migración y verificación independiente de cada bloque.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: Historia de usuario a la que pertenece la tarea (US1, US2, US3)
- Se incluyen rutas de archivo exactas en cada descripción

## Path Conventions

Aplicación Laravel monolítica única — rutas relativas a la raíz del repositorio: `resources/`, `package.json`, `vite.config.js`, según `plan.md` → Project Structure. Ninguna tarea toca `app/`, `database/`, `routes/` (fuera de alcance, ver `spec.md` FR-001).

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Instalar Bootstrap 5 y preparar el pipeline de build para la convivencia con Tailwind

- [X] T001 Agregar dependencias `bootstrap@^5.3.3`, `bootstrap-icons`, `chart.js`, `sass` (devDependency) a `package.json` y ejecutar `npm install`
- [X] T002 Crear `resources/css/bootstrap.scss` con las variables Sass personalizadas Senior-First (`$font-size-base: 1.125rem`, paleta de colores de alto contraste, padding de botones para ≥48x48px) antes de `@import "bootstrap/scss/bootstrap"` (ver `research.md` §1)
- [X] T003 Agregar `resources/css/bootstrap.scss` y el bundle JS de Bootstrap como entradas adicionales en `vite.config.js`, sin remover las entradas Tailwind existentes (depende de T001, T002). Nota: se creó `resources/js/bootstrap.js` como wrapper (`import * as bootstrap from 'bootstrap'; window.bootstrap = bootstrap;`) para exponerlo globalmente a scripts inline puntuales.
- [X] T004 [P] Verificar con `npm run build` que ambas hojas de estilo (Tailwind y Bootstrap) compilan sin conflicto como entradas separadas (depende de T003) — build OK, bootstrap.css 306KB/45KB gzip, bootstrap.js 80KB/24KB gzip.

**Checkpoint**: Bootstrap disponible en el proyecto, sin afectar ninguna vista existente todavía

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Layout y componentes Blade compartidos que todas las vistas migradas reutilizarán

**⚠️ CRITICAL**: Ninguna vista puede migrarse hasta completar esta fase

- [X] T005 Crear `resources/views/layouts/app-bootstrap.blade.php` (navbar `navbar navbar-expand-lg`, slot `header`, `main` con `container`) en base al layout Tailwind actual, sin quitar este último (depende de T004). **Desvío documentado**: se creó en `resources/views/components/layouts/app-bootstrap.blade.php` (usado como `<x-layouts.app-bootstrap>`) en vez de `resources/views/layouts/app-bootstrap.blade.php`, porque Laravel solo auto-descubre componentes anónimos `<x-*>` bajo `resources/views/components/`; registrar una ruta adicional requeriría tocar un ServiceProvider en `app/`, fuera de alcance. `<x-layouts.app-bootstrap>` es la convención estándar de Laravel para este mismo caso.
- [X] T006 [P] Migrar el componente `x-mensaje-alerta` a `alert alert-success`/`alert alert-danger` de Bootstrap, conservando el prop `tipo` y el `role="alert"` en `resources/views/components/mensaje-alerta.blade.php` (depende de T005) — **Decisión**: modificado IN PLACE, pero en modo "dual-clase" (se agregan las clases Bootstrap junto a las Tailwind existentes, no se reemplazan). Como cada página carga una sola hoja de estilo a la vez (nunca ambas, ver research.md §3), las clases del framework no usado en cada página quedan inertes sin conflicto visual — esto evita el riesgo de que las ~20 vistas aún no migradas se vean con alertas sin estilo mientras dura la migración incremental.
- [X] T007 [P] Migrar el componente `x-modal` (Alpine) al `Modal` nativo de Bootstrap — **Desvío documentado**: NO se modificó in place. Se creó un componente PARALELO `resources/views/components/modal-bootstrap.blade.php` (`<x-modal-bootstrap>`), dejando `components/modal.blade.php` (Alpine) intacto. Razón: a diferencia de los botones/inputs/alertas (solo clases CSS), el modal depende de un MOTOR JS distinto en cada framework (Alpine vs. Bootstrap JS bundle) y cada layout solo carga uno de los dos — un dual-class no habría funcionado porque el botón que abre el modal necesita `x-on:click` (Alpine) o `data-bs-toggle` (Bootstrap), nunca ambos a la vez. Firma conservada: props `name`/`show`/`maxWidth`/`focusable`.
- [X] T008 [P] Migrar `x-primary-button`/`x-secondary-button`/`x-danger-button` a `btn btn-primary btn-lg`/`btn btn-outline-secondary btn-lg`/`btn btn-danger btn-lg` en `resources/views/components/*-button.blade.php` (depende de T005) — modificado in place en modo dual-clase (mismo razonamiento que T006).
- [X] T009 [P] Migrar `x-input-label`/`x-text-input`/`x-input-error` a `form-label`/`form-control form-control-lg`/`invalid-feedback` de Bootstrap en `resources/views/components/input-*.blade.php` y `text-input.blade.php` (depende de T005) — modificado in place en modo dual-clase. `x-input-error` usa `invalid-feedback d-block` (no solo `invalid-feedback`) porque ese componente ya solo se renderiza cuando hay mensajes (`@if ($messages)`), así que forzar `d-block` es más simple/robusto que cablear `is-invalid` en cada input consumidor.
- [X] T010 Migrar `x-ruta-jerarquia-locacion` al componente `breadcrumb` de Bootstrap en `resources/views/components/ruta-jerarquia-locacion.blade.php`, reutilizando sin cambios el helper `Locacion::rutaJerarquiaTruncada()` (depende de T005) — modificado in place en modo dual-clase; se agregó `breadcrumb`/`breadcrumb-item` solo a los `<li>` de nivel real (nunca a los separadores manuales "…"/"&gt;"), de modo que el divisor automático `::before` de Bootstrap (que solo aplica entre `.breadcrumb-item` consecutivos) nunca se activa y no duplica el separador visual ya existente.

**Nota general de Foundational**: tras T006-T010, se corrió `npm run build` + `php artisan test` completo → 191/191 sin cambios, confirmando que el modo dual-clase no rompió ninguna vista Tailwind todavía no migrada.

**Checkpoint**: Layout y componentes base Bootstrap listos — la migración de vistas por historia de usuario puede comenzar

---

## Phase 3: User Story 1 - Migración de las Vistas Fundamentales del Negocio (Priority: P1) 🎯 MVP

**Goal**: Migrar a Bootstrap 5 las vistas de locaciones, contratos, representantes y condiciones/costos (features 001-004), sin alterar ninguna regla de negocio.

**Independent Test**: Navegar el CRUD completo de locaciones y contratos (con representantes, costos, garantía, documentos) usando exclusivamente componentes Bootstrap 5, y confirmar que la suite completa de pruebas sigue pasando.

### Implementation for User Story 1

- [X] T011 [US1] Migrar `resources/views/locaciones/index.blade.php` a `layouts/app-bootstrap` con listado en `card`/tabla `table-responsive` y badge de "Alquilable" (depende de T006, T010) — usa `<x-layouts.app-bootstrap>` (ver nota T005); listado con `card` por locación (no se usó tabla porque cada fila ya mostraba contenido de varias líneas, un `card` es el equivalente Bootstrap más directo).
- [X] T012 [US1] Migrar `resources/views/locaciones/show.blade.php` (detalle + breadcrumb + modal de eliminación) (depende de T007, T010) — usa `<x-modal-bootstrap>` (T007) para el modal de confirmación de borrado.
- [X] T013 [P] [US1] Migrar `resources/views/locaciones/create.blade.php` y `edit.blade.php` (formularios `form-control form-control-lg`) (depende de T008, T009)
- [X] T014 [US1] Migrar `resources/views/contratos/partials/costos-fijos-contrato.blade.php` (`input-group` con prefijo "S/") (depende de T009)
- [X] T015 [US1] Migrar `resources/views/contratos/partials/garantia-contrato.blade.php` (`input-group` + `form-select` para medio de entrega) (depende de T009)
- [X] T016 [US1] Migrar `resources/views/contratos/partials/representantes-contrato.blade.php` (tarjetas por representante, modal de confirmación) (depende de T007, T008, T009) — **Desvío documentado**: se reemplazó el editor dinámico de filas (Alpine.js) por JS nativo nuevo en `resources/js/representantes-contrato.js` (agregar/quitar fila, reindexar `name`, búsqueda por DNI), registrado como entrada Vite adicional. Fue necesario porque Alpine no está cargado en el layout Bootstrap (research.md §2: se retira junto con Tailwind), y este editor requiere lógica reactiva que Bootstrap no provee nativamente (a diferencia de Modal/Collapse). Sin cambios de nombres de campo ni de comportamiento (add/quitar fila, radio `principal_index`, autocompletar por DNI idénticos al original). No usa `btn-group` (se descartó frente al diseño original de tarjetas individuales con botón "Quitar" — un `btn-group` no aplicaba a este layout de lista vertical de representantes).
- [X] T017 [US1] Migrar `resources/views/contratos/partials/galeria-documentos.blade.php` (`row row-cols-*` de miniaturas) (depende de T008) — el modal de confirmación de borrado (antes Alpine genérico con `$dispatch`) se reescribió con el patrón nativo de Bootstrap "varying modal content" (`resources/js/galeria-documentos.js`, nueva entrada Vite): un único `<x-modal-bootstrap>` compartido por todas las miniaturas, cuya acción de formulario se fija dinámicamente leyendo `data-accion` del botón que lo abrió (evento `show.bs.modal`).
- [X] T018 [US1] Migrar `resources/views/contratos/create.blade.php` y `edit.blade.php`, integrando las parciales T014-T017 (depende de T014, T015, T016, T017)
- [X] T019 [US1] Migrar `resources/views/contratos/show.blade.php` (secciones de costos, garantía, representantes, documentos en un único `card`) (depende de T018)
- [X] T020 [US1] Migrar `resources/views/contratos/index.blade.php` (historial cronológico, badge del contrato activo) (depende de T006)
- [X] T021 [US1] Ejecutar `php artisan test` completo y confirmar 191/191 sin modificar ninguna aserción de negocio (depende de T011-T020; ver `quickstart.md` Escenario 1) — **191/191 OK**. Verificación adicional en navegador con usuario descartable (`qa-local@example.test`): CRUD de locaciones, modal de borrado (Bootstrap `Modal` nativo funcionando, `modal fade show`/`display:block` confirmado por JS), editor dinámico de representantes (agregar/quitar fila, reindexado de `name`, radio Principal) probado por consola JS, y creación de un contrato de prueba (validó correctamente el solapamiento de fechas contra el contrato demo existente, luego se creó con fechas no solapadas, se verificó su HTML — 0 referencias a Alpine/`x-data` — y se eliminó junto con el representante de prueba creado).
  - **Nota de infraestructura**: se agregó `@stack('scripts')` a `components/layouts/app-bootstrap.blade.php` y `@push('scripts')` en las vistas que necesitan JS adicional (representantes/galería), ya que el layout base solo carga `bootstrap.scss`/`bootstrap.js`.

**Checkpoint**: User Story 1 migrada y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Migración de las Vistas del Flujo de Facturación (Priority: P2)

**Goal**: Migrar a Bootstrap 5 las vistas de lecturas de medidor, recibos por periodo, estado/envío de recibos y resolución de garantía (features 005, 007, 009).

**Independent Test**: Registrar una lectura, generar un recibo con prorrateo, cambiar su estado, enviarlo por WhatsApp y registrar una resolución de garantía, todo con componentes Bootstrap, confirmando que la suite sigue pasando.

### Implementation for User Story 2

- [X] T022 [P] [US2] Migrar `resources/views/locaciones/lecturas/index.blade.php` (tabla `table-responsive` de periodos) (depende de T006) — se usó `card` por periodo (igual criterio que T011: cada fila tiene varias líneas de contenido, `card` es el equivalente Bootstrap directo); la tabla-`table-responsive` literal se reserva para T031 (historial+gráfico de 006 en Phase 5) donde el contrato la pide explícitamente.
- [X] T023 [P] [US2] Migrar `resources/views/locaciones/lecturas/create.blade.php` (lectura anterior informativa + actual editable) (depende de T009)
- [X] T024 [US2] Migrar `resources/views/locaciones/recibos/index.blade.php` (depende de T006) — **Desvío documentado**: NO se agregaron badges de estado ni filtro por estado. El controlador/vista actual (`ReciboController@index`) nunca mostró el estado del recibo ni tuvo filtro alguno (solo periodo/total/fecha); agregar esos elementos habría sido una mejora de UX nueva, no una migración 1:1, y el alcance de esta feature es exclusivamente presentacional sobre el comportamiento ya existente (FR-001/FR-004). Se preservó el listado exactamente como estaba, solo con componentes Bootstrap (`card` clicable en vez de `<a>` con fondo Tailwind).
- [X] T025 [US2] Migrar `resources/views/locaciones/recibos/create.blade.php` (conceptos editables con `input-group` S/) (depende de T009)
- [X] T026 [US2] Migrar `resources/views/locaciones/recibos/edit.blade.php` (incluye sugerencia de prorrateo) (depende de T009)
- [X] T027 [US2] Migrar `resources/views/locaciones/recibos/show.blade.php` (depende de T007, T008) — **Desvío documentado**: no se usó `btn-check` (radio real) para el selector de estado. Cada acción (Marcar Pagado/Pendiente, Anular, Revertir) ya es HOY un formulario POST/PATCH independiente con sus propios campos ocultos, no un único grupo de radios — convertirlo a `btn-check` real habría exigido cambiar esa estructura de request (fuera de alcance, FR-001). Se usó `btn-group` solo como agrupador visual de los botones, preservando cada formulario/comportamiento exactamente igual (confirmación vía `<x-modal-bootstrap>` para Anular/Revertir, envío directo para Marcar Pagado/Pendiente, igual que antes).
- [X] T028 [US2] Verificar que `resources/views/locaciones/recibos/comprobante.blade.php` (vista de impresión/imagen `html2canvas` de 007) sigue funcionando sin cambios de framework CSS que la afecten (depende de T027) — no se modificó (ya usa su propio CSS autocontenido, ver comentario existente en el archivo sobre la incompatibilidad de html2canvas con `oklch()`); confirmado que sigue sin referenciar Tailwind ni Bootstrap.
- [X] T029 [US2] Ejecutar `php artisan test` completo y confirmar 191/191 (depende de T022-T028; ver `quickstart.md` Escenario 2) — **191/191 OK**. Verificación manual con datos de prueba descartables (contrato temporal S/1000, lectura de medidor y recibo emitido para un periodo 2030-01 sin conflicto con el contrato demo real, cuyo periodo de vigencia — 19/08/2026 — ya había expirado): confirmado registro de lectura, emisión de recibo con conceptos `input-group` S/, y cambio de estado pendiente→pagado (badge cambió correctamente de `text-bg-secondary` a `text-bg-success`). Todos los datos de prueba (contrato, lectura, recibo) fueron eliminados al finalizar.

**Checkpoint**: User Story 1 y 2 migradas y funcionan de forma independiente

---

## Phase 5: User Story 3 - Migración de las Vistas Complementarias y Panel de Consumo Histórico (Priority: P3)

**Goal**: Migrar a Bootstrap 5 el historial de lecturas (006) y el panel de configuración/prorrateo (008), agregando el gráfico de consumo histórico nuevo.

**Independent Test**: Ver el historial de una locación con 6+ periodos y confirmar que se muestra el gráfico Chart.js junto a la tabla histórica migrada; ajustar la configuración de alerta de pago con componentes Bootstrap.

### Implementation for User Story 3

- [X] T030 [US3] Crear `resources/js/historial-consumo-medidor.js` (inicialización de Chart.js con el arreglo `{periodo, consumo}`) y registrarlo como entrada en `vite.config.js` (depende de T004; ver `research.md` §5) — se importan solo los módulos de Chart.js necesarios (`LineController`, `LineElement`, `PointElement`, `LinearScale`, `CategoryScale`, `Tooltip`, `Filler`) en vez de `chart.js/auto`, para no registrar innecesariamente controladores de otros tipos de gráfico no usados.
- [X] T031 [US3] Agregar el `<canvas>` Chart.js a la vista de historial de lecturas (dentro de `resources/views/locaciones/lecturas/index.blade.php`, ya migrada en T022) alimentado por los mismos datos que la tabla histórica (depende de T022, T030) — el arreglo `{periodo, consumo}` se arma en la propia vista (`@php`) a partir de la misma colección `$lecturas` ya cargada por el controlador para la tabla, sin ninguna consulta ni cálculo nuevo; se muestra solo cuando hay 2+ periodos (con 1 solo punto una línea no aporta información) y se invierte a orden cronológico ascendente SOLO para el gráfico (la tabla se mantiene en el mismo orden descendente ya cubierto por `assertSeeInOrder` en `tests/Feature/LecturaMedidorControllerTest.php`, sin tocar esa aserción).
- [X] T032 [US3] Migrar `resources/views/locaciones/lecturas/edit.blade.php` (edición de lectura anterior trasladada, de 006) (depende de T009)
- [X] T033 [US3] Migrar `resources/views/configuracion/edit.blade.php` (formulario de tarifa de luz y alerta de fecha límite de pago) (depende de T009)
- [X] T034 [US3] Ejecutar `php artisan test` completo y confirmar 191/191 (depende de T030-T033; ver `quickstart.md` Escenario 3) — **191/191 OK**. Verificación manual: se crearon 6 lecturas de prueba consecutivas (periodos 2030-02 a 2030-07) en la locación demo, se confirmó que el gráfico aparece con la etiqueta "Consumo Histórico" arriba de la tabla, que el `<canvas>` recibe el JSON `{periodo, consumo}` correcto vía `data-consumos`, y que efectivamente dibuja contenido real (PNG exportado del canvas ~8KB, no un canvas en blanco). Se verificó también `configuracion/edit` (formulario con `input-group` S/ para la tarifa de luz). Las 6 lecturas de prueba fueron eliminadas al finalizar.

**Checkpoint**: Las 3 historias de usuario migradas; todas las vistas de 001-009 usan Bootstrap 5

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Retirar el sistema de estilos anterior y verificación final de accesibilidad (FR-007, Edge Case de retiro)

- [X] T035 [P] Auditoría de accesibilidad (contraste WCAG AA/AAA, tipografía ≥18px, botones ≥48x48px) en las ~30 vistas migradas, contra `contracts/inventario-vistas-migradas.md` — la paleta y los paddings Senior-First se hornearon una sola vez en `resources/css/bootstrap.scss` (§1-3 de `research.md`), incluyendo overrides adicionales descubiertos durante la auditoría: `$small-font-size`/`$badge-font-size`/`$form-text-font-size`/`$form-feedback-font-size`/`$font-size-sm` igualados a `$font-size-base` (1em) para que Bootstrap NUNCA reduzca texto por debajo de 18px en badges, texto de ayuda o mensajes de error (por defecto Bootstrap los reduce a ~0.75-0.875em). Verificado con inspección de estilos computados en navegador: botón primario 59px de alto / fuente 18px / fondo `#1e40af` con texto blanco (contraste ~8.6:1); mismo criterio para `success`/`danger`/`secondary` (todos tonos "800" de la paleta ya usada y presumida ya auditada en 001-009). Checkboxes/radios se mantuvieron en el mismo tamaño visual que ya tenían en Tailwind (~27px, con su `<label>` asociado ampliando el área clicable) para no introducir un cambio de diseño no solicitado.
- [X] T036 Eliminar `resources/css/app.css` (Tailwind), su entrada en `vite.config.js`, y las dependencias `tailwindcss`/`@tailwindcss/vite`/`alpinejs` de `package.json` (depende de T021, T029, T034 — las 3 historias deben estar completas) — también se eliminó `@tailwindcss/forms` (dependencia Tailwind huérfana, nunca referenciada vía `@plugin` en `app.css`). Se conservaron `autoprefixer`/`postcss` (tooling genérico, no exclusivo de Tailwind, no mencionado explícitamente para retiro).
- [X] T037 Eliminar `layouts/app.blade.php` (Tailwind), `layouts/guest.blade.php`, `components/modal.blade.php` (Alpine) y limpiar las clases `btn-senior-*`/`campo-senior`/`etiqueta-senior` residuales de los 13 archivos que aún las tenían como "dual-clase" (componentes compartidos + `<select>`/`<textarea>` sueltos en contratos/locaciones) (depende de T036).
  - **Desvío de alcance MUY IMPORTANTE, descubierto en esta fase**: el inventario de la spec (`contracts/inventario-vistas-migradas.md`) declara fuera de alcance `resources/views/auth/*`, `resources/views/profile/*`, `resources/views/dashboard.blade.php` y `welcome.blade.php`, por no pertenecer a las features 001-009. Sin embargo, TODAS ellas (salvo `welcome.blade.php`) dependían de `layouts/app.blade.php`/`layouts/guest.blade.php` y de `resources/css/app.css` para renderizarse. Retirar Tailwind sin más las habría roto de raíz — incluyendo el login, que es la puerta de entrada a toda la aplicación. Se decidió migrar también estas vistas a Bootstrap (nuevo `<x-layouts.guest-bootstrap>` para el layout de invitado, mismo `<x-layouts.app-bootstrap>` para dashboard/perfil) en vez de dejar el proyecto roto o mantener Tailwind indefinidamente contradiciendo FR-007. Se comprobó que ninguna de estas vistas tiene test alguno en la suite (`tests/Feature` no contiene pruebas de auth/perfil/dashboard), por lo que el riesgo de regresión de negocio es nulo. **Hallazgo adicional**: `resources/views/profile/edit.blade.php` (y sus 3 parciales) resultó ser código YA MUERTO — no existe ninguna ruta `profile.*` registrada en `routes/web.php` ni `routes/auth.php` (confirmado con `artisan route:list`); se migró de todos modos por consistencia y porque no cuesta nada adicional, pero no es alcanzable en la aplicación tal como está hoy (esto es preexistente, no una regresión introducida por esta migración). Se agregó soporte real (no solo de firma) para el prop `show` de `x-modal-bootstrap` (atributo `data-autoshow` + listener en `resources/js/bootstrap.js`) específicamente porque `delete-user-form.blade.php` lo necesita para reabrir el modal de confirmación tras un error de validación de contraseña.
  - **`welcome.blade.php`** (la landing decorativa por defecto de Laravel, no enlazada desde ninguna navegación de la app): se optó por NO migrarla a Bootstrap — su marcado usa sintaxis arbitraria de Tailwind (`bg-[#FDFDFC]`, etc.) sin valor de negocio. Se cambió únicamente su `@vite([...])` para apuntar a `bootstrap.scss`/`bootstrap.js` (evita una excepción de manifest de Vite al haber retirado `app.css`); la página queda visualmente simple (sin su diseño original) pero funcional — una renuncia deliberada de bajo riesgo, documentada con un comentario en el propio archivo.
- [X] T038 Ejecutar `npm run build` y confirmar que compila sin referencias rotas tras el retiro (depende de T037) — build OK, 43 paquetes npm removidos, `app.css`/`app.js` ya no aparecen en `public/build/`.
- [X] T039 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 4) de extremo a extremo — completado por partes durante cada gate de historia (Escenarios 1-3) más la verificación final de este Phase 6 (Escenario 4): confirmado con navegador y `curl` autenticado que `/`, `/login`, `/dashboard` y las vistas de negocio migradas siguen respondiendo 200 con marcado Bootstrap y cero referencias a Alpine/Tailwind, tras el retiro completo.
- [X] T040 Ejecutar `php artisan test` completo una última vez y confirmar 191/191 (o más) sin ninguna prueba de negocio modificada — **191/191 OK, 457 aserciones**, idéntico al baseline previo a la migración; ninguna aserción de negocio fue tocada en ningún test.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sin dependencias — puede iniciar de inmediato
- **Foundational (Phase 2)**: Depende de Setup — BLOQUEA todas las historias de usuario
- **User Stories (Phase 3-5)**: Todas dependen de Foundational; se recomienda ejecutarlas en orden P1 → P2 → P3 (no en paralelo), dado que cada bloque de prioridad es también el orden de riesgo/importancia de negocio definido por la especificación
- **Polish (Phase 6)**: Depende de que las 3 historias de usuario estén completas (T036 requiere que NINGUNA vista quede en Tailwind)

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Foundational — sin dependencias de otras historias
- **User Story 2 (P2)**: Puede iniciar tras Foundational — reutiliza los componentes compartidos de Foundational, no depende de que US1 esté migrada, pero se recomienda hacerlo después por criticidad
- **User Story 3 (P3)**: Puede iniciar tras Foundational — el gráfico de consumo (T030-T031) es una adición nueva independiente de US1/US2

### Within Each User Story

- Los componentes compartidos (Foundational) se migran antes que cualquier vista que los use
- Las parciales de `contratos/` (costos, garantía, representantes, documentos) se migran antes que las vistas `create`/`edit`/`show` que las incluyen
- Cada historia de usuario termina con una corrida completa de `php artisan test` antes de pasar a la siguiente

### Parallel Opportunities

- T006-T010 (componentes Foundational) pueden migrarse en paralelo entre sí una vez creado el layout (T005)
- T013 (create/edit de locaciones) puede migrarse en paralelo con T011-T012
- T022-T023 (lecturas) pueden migrarse en paralelo con T024-T027 (recibos) dentro de US2

---

## Parallel Example: User Story 1

```bash
Task: "Migrar resources/views/locaciones/create.blade.php y edit.blade.php"
Task: "Migrar resources/views/contratos/partials/costos-fijos-contrato.blade.php"
Task: "Migrar resources/views/contratos/partials/garantia-contrato.blade.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Completar Phase 1: Setup
2. Completar Phase 2: Foundational (CRÍTICO — layout y componentes compartidos)
3. Completar Phase 3: User Story 1
4. **DETENERSE Y VALIDAR**: Escenario 1 de `quickstart.md`, suite completa 191/191
5. Desplegar/demostrar si está listo

### Incremental Delivery

1. Completar Setup + Foundational → base lista
2. Migrar User Story 1 (P1) → validar → demo
3. Migrar User Story 2 (P2) → validar → demo
4. Migrar User Story 3 (P3) → validar → demo
5. Polish: retirar Tailwind/Alpine solo cuando las 3 historias estén confirmadas

---

## Notes

- Esta feature es de presentación pura: ninguna tarea debe tocar `app/`, `database/`, `routes/web.php` ni ningún test de negocio existente — si una migración de vista "necesita" cambiar un nombre de campo o una ruta, es una señal de que se está saliendo del alcance (FR-001).
- T006/T007/T008/T009: decidir en el momento de implementar si se modifican los componentes Blade actuales in place (mismo nombre de tag `x-mensaje-alerta`, etc., para que todas las vistas —migradas o no— seas afectadas a la vez) o se crean variantes paralelas (`x-mensaje-alerta-bootstrap`) para migración estrictamente incremental vista por vista. La especificación (Edge Case de convivencia) permite ambas siempre que no haya conflicto visual; se recomienda la opción in place por simplicidad, ya que estos componentes no dependen de Tailwind en su lógica, solo en sus clases.
- [P] = archivos distintos, sin dependencias pendientes
- Hacer commit tras cada tarea o grupo lógico de tareas
- Detenerse en cada checkpoint para validar la historia de forma independiente
