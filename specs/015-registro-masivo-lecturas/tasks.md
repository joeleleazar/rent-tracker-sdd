---

description: "Task list template for feature implementation"
---

# Tasks: Registro Masivo de Lecturas de Luz

**Input**: Design documents from `/specs/015-registro-masivo-lecturas/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Incluidas — el Principio IV de la Constitución exige pruebas automatizadas exhaustivas para toda funcionalidad.

**Organization**: Las tareas están agrupadas por historia de usuario para permitir implementación y prueba independiente de cada una.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: Historia de usuario a la que pertenece la tarea (US1, US2, US3, US4, US5, US6)
- Se incluyen rutas de archivo exactas en cada descripción

## Path Conventions

Aplicación Laravel monolítica única — rutas relativas a la raíz del repositorio: `app/`, `resources/`, `routes/`, `database/`, `tests/`, según `plan.md` → Project Structure.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Proyecto ya inicializado (Laravel, Pest, Bootstrap 5, htmx ya configurados por specs anteriores)

- [X] T001 Sin tareas de setup adicionales para esta feature — no se agregan dependencias nuevas al proyecto

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Confirmar que los servicios y excepciones ya existentes que esta feature va a reutilizar siguen teniendo la forma esperada, antes de construir sobre ellos

**⚠️ CRITICAL**: Ninguna historia de usuario puede comenzar hasta completar esta verificación

- [X] T002 Confirmar en el código ya existente que `ServicioConstruccionArbolLocaciones::construir()` (`app/Services/ServicioConstruccionArbolLocaciones.php`), `ServicioCalculoConsumoMedidor::sugerirLecturaAnterior()/calcularConsumo()` (`app/Services/ServicioCalculoConsumoMedidor.php`), `SolicitudGuardarLecturaMedidor` y las excepciones `ConsumoNegativoSinConfirmarException`/`LecturaMedidorDuplicadaException` (`app/Exceptions/`) siguen teniendo la forma documentada en `research.md` (Decisiones 1-3) — verificación de lectura, sin cambios de código

**Checkpoint**: Fundamento confirmado — las historias de usuario pueden comenzar

---

## Phase 3: User Story 1 - Registrar lecturas de luz de varias locaciones en una sola visita (Priority: P1) 🎯 MVP

**Goal**: Una pantalla nueva, accesible desde la navegación principal, donde se listan todas las locaciones alquilables (agrupadas jerárquicamente) con un campo de lectura editable, y un único guardado que registra todas las filas completadas sin descartar las válidas si alguna otra falla.

**Independent Test**: Abrir la pantalla de registro masivo, completar la lectura de varias locaciones a la vez (dejando alguna vacía y alguna con consumo negativo sin confirmar) y guardar, verificando que las filas válidas quedan registradas, las vacías se ignoran sin error, y la de consumo negativo pide confirmación sin afectar a las demás.

### Tests for User Story 1 ⚠️

> **NOTE: Escribir estas pruebas primero y comprobar que fallan antes de implementar**

- [X] T003 [US1] Prueba de feature: `GET lecturas.registroMasivo.index` muestra una fila por cada locación alquilable agrupada jerárquicamente (mismo orden/anidamiento que `locaciones.index`), con un campo `lectura_actual` editable; una locación con `LecturaMedidor` ya registrada para el periodo se muestra como completada con enlace a `lecturas.edit`; las locaciones no alquilables aparecen solo como encabezado, sin campo — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`
- [X] T004 [US1] Prueba de feature: `POST lecturas.registroMasivo.store` con varias filas completadas y una vacía registra las lecturas completadas en una sola acción y no genera error por la fila vacía (FR-003, FR-004) — mismo archivo
- [X] T005 [US1] Prueba de feature: una fila con lectura actual menor a la anterior sin `confirmar_consumo_negativo` no se guarda y exige confirmación explícita para esa fila específica, sin afectar otras filas válidas del mismo envío (FR-008) — mismo archivo
- [X] T006 [US1] Prueba de feature: un lote con una fila con valor no numérico y otras válidas registra las válidas y señala cuál fila falló, sin descartar las demás (FR-009) — mismo archivo

### Implementation for User Story 1

- [X] T007 [US1] Crear `SolicitudGuardarRegistroMasivoLecturas` (`periodo` required date; `lecturas` array; `lecturas.*.lectura_actual` nullable numeric min:0; `lecturas.*.confirmar_consumo_negativo` sometimes boolean) en `app/Http/Requests/SolicitudGuardarRegistroMasivoLecturas.php`
- [X] T008 [US1] Crear `RegistroMasivoLecturasController@index` (resuelve el periodo vía query string igual que `LecturaMedidorController::resolverPeriodo`, llama a `ServicioConstruccionArbolLocaciones::construir()`, y construye un mapa `locacion_id => LecturaMedidor` del periodo actual con una sola consulta `whereIn` para marcar filas completadas) en `app/Http/Controllers/RegistroMasivoLecturasController.php` (depende de T002)
- [X] T009 [US1] Implementar `RegistroMasivoLecturasController@store` (itera `lecturas` omitiendo filas con `lectura_actual` vacío — FR-004 —, procesa cada fila no vacía en su propio `DB::transaction` reutilizando `ServicioCalculoConsumoMedidor::calcularConsumo()` y capturando `ConsumoNegativoSinConfirmarException`/`LecturaMedidorDuplicadaException` por fila sin abortar el resto — FR-008, FR-009 —, acumula qué filas se guardaron y cuáles quedaron pendientes de confirmación) en `app/Http/Controllers/RegistroMasivoLecturasController.php` (depende de T007, T008)
- [X] T010 [US1] Registrar `GET /lecturas/registro-masivo` → `lecturas.registroMasivo.index` y `POST /lecturas/registro-masivo` → `lecturas.registroMasivo.store` en `routes/web.php` (depende de T009)
- [X] T011 [US1] Crear la parcial recursiva `resources/views/lecturas/registro-masivo/partials/fila-registro-masivo.blade.php` (mismo patrón de indentación por profundidad que `fila-arbol-locacion.blade.php`: campo `lectura_actual` editable con el nombre `lecturas[{{ $locacion->id }}][lectura_actual]` para locaciones alquilables sin lectura del periodo; estado "completada" de solo lectura con enlace a `lecturas.edit` para las que ya tienen; solo nombre/ícono, sin campo, para las no alquilables) (depende de T008)
- [X] T012 [US1] Crear `resources/views/lecturas/registro-masivo/index.blade.php` (formulario `id="formulario-registro-masivo"` que envuelve las filas recursivas vía `@include`, selector de periodo `type="month"` igual que `locaciones/lecturas/create.blade.php`, botón "Guardar Lecturas") (depende de T010, T011)
- [X] T013 [US1] Agregar el ítem de navegación "Registrar Lecturas" (ícono `bi-speedometer2`, `route('lecturas.registroMasivo.index')`) en `resources/views/components/layouts/app-bootstrap.blade.php` (depende de T010)

**Checkpoint**: User Story 1 funcional y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Ver la lectura del periodo anterior como referencia sin salir de la pantalla (Priority: P2)

**Goal**: Cada fila pendiente de la pantalla de registro masivo muestra, junto al campo editable, la lectura del periodo inmediatamente anterior (o "sin lectura previa"), sin necesidad de abrir el historial de esa locación.

**Independent Test**: Abrir la pantalla de registro masivo con locaciones que ya tienen lecturas de periodos anteriores y verificar que el valor anterior de cada una es visible junto a su campo, sin navegación adicional.

### Tests for User Story 2 ⚠️

- [X] T014 [US2] Prueba de feature: una locación con lectura registrada en el periodo inmediatamente anterior muestra ese valor junto a su campo editable; una locación sin lectura previa muestra "sin lectura previa" en vez de un valor vacío ambiguo (FR-006) — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`

### Implementation for User Story 2

- [X] T015 [US2] En `RegistroMasivoLecturasController@index`, construir un mapa `locacion_id => lectura_actual` con una sola consulta agrupada (`LecturaMedidor::whereIn('locacion_id', $idsAlquilables)->where('periodo', '<', $periodo)`, quedándose con el periodo más reciente por locación) para evitar N+1, en vez de invocar `sugerirLecturaAnterior()` en un bucle (Decisión 2 de `research.md`) en `app/Http/Controllers/RegistroMasivoLecturasController.php` (depende de T008)
- [X] T016 [US2] Mostrar la lectura anterior (o "sin lectura previa") junto al campo editable de cada fila pendiente en `resources/views/lecturas/registro-masivo/partials/fila-registro-masivo.blade.php` (depende de T011, T015)

**Checkpoint**: User Story 1 y 2 funcionan de forma independiente

---

## Phase 5: User Story 3 - No perder el trabajo si se interrumpe el registro masivo (Priority: P3)

**Goal**: Los valores ya escritos en la pantalla se autoguardan cada 2 minutos como borrador persistido en el servidor (por usuario y periodo), se restauran automáticamente al reabrir la pantalla, y se descartan al completar el guardado final.

**Independent Test**: Completar algunas filas sin guardar, esperar (o disparar manualmente) el ciclo de autoguardado, cerrar la sesión, y verificar que al volver a abrir la pantalla para el mismo periodo los valores siguen ahí; luego guardar el lote y verificar que el borrador desaparece.

### Tests for User Story 3 ⚠️

- [X] T017 [US3] Prueba de feature: `POST lecturas.registroMasivo.borrador` con valores de varias filas persiste (upsert) una fila de `BorradorLecturaMedidor` por `usuario_id`+`periodo`+`locacion_id`, sin aplicar validaciones de negocio (FR-010) — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`
- [X] T018 [US3] Prueba de feature: `GET lecturas.registroMasivo.index` con un borrador existente para ese usuario+periodo precarga automáticamente esos valores en los campos correspondientes (FR-011) — mismo archivo
- [X] T019 [US3] Prueba de feature: tras un `POST lecturas.registroMasivo.store` exitoso, el borrador de ese usuario+periodo queda eliminado (FR-012) — mismo archivo

### Implementation for User Story 3

- [X] T020 [US3] Crear la migración `create_borradores_lectura_medidor_table` (`usuario_id` FK → `users` `cascadeOnDelete`, `periodo` date, `locacion_id` FK → `locaciones` `cascadeOnDelete`, `lectura_actual` decimal(10,2) nullable, timestamps, índice único compuesto `(usuario_id, periodo, locacion_id)`) en `database/migrations/2026_08_24_060000_create_borradores_lectura_medidor_table.php`
- [X] T021 [US3] Crear el modelo `BorradorLecturaMedidor` (`fillable`: `usuario_id`, `periodo`, `locacion_id`, `lectura_actual`; casts `periodo:date`, `lectura_actual:decimal:2`; relaciones `usuario()`/`locacion()`) en `app/Models/BorradorLecturaMedidor.php` (depende de T020)
- [X] T022 [US3] Implementar `RegistroMasivoLecturasController@guardarBorrador` (`BorradorLecturaMedidor::upsert()` de las filas de `lecturas` con valor no vacío, bajo la clave única `usuario_id`+`periodo`+`locacion_id`, sin ejecutar `SolicitudGuardarRegistroMasivoLecturas` ni las validaciones de negocio de FR-008 — Assumption de `spec.md`) en `app/Http/Controllers/RegistroMasivoLecturasController.php` (depende de T021)
- [X] T023 [US3] Registrar `POST /lecturas/registro-masivo/borrador` → `lecturas.registroMasivo.borrador` en `routes/web.php` (depende de T022)
- [X] T024 [US3] En `RegistroMasivoLecturasController@index`, si existe un borrador para el usuario+periodo actual, precargar sus valores como `value` por defecto de cada campo editable en vez de vacío (depende de T021, T008)
- [X] T025 [US3] Agregar el disparador de autoguardado — un elemento no-`<form>` (ej. `<div id="autoguardado-borrador" hx-post="{{ route('lecturas.registroMasivo.borrador') }}" hx-trigger="every 120s" hx-include="#formulario-registro-masivo" hx-swap="none">`) más un `<span>` de estado con la hora del último autoguardado — en `resources/views/lecturas/registro-masivo/index.blade.php` (depende de T012, T023; ver Decisiones 4-5 de `research.md` — NO debe ser un `<form>` para que `resources/js/htmx.js` no le aplique el tratamiento visual de "Guardando…" del envío manual)
- [X] T026 [US3] En `RegistroMasivoLecturasController@store`, tras un guardado final exitoso, eliminar el borrador de ese `usuario_id`+`periodo` (`BorradorLecturaMedidor::where(['usuario_id' => ..., 'periodo' => ...])->delete()`) en `app/Http/Controllers/RegistroMasivoLecturasController.php` (depende de T009, T021)

**Checkpoint**: Las 3 historias de usuario funcionan de forma independiente

---

## Phase 6: User Story 4 - Totalizado por Consumo con Tarifa Editable (FR-013, FR-014, FR-015)

**Goal**: La pantalla suma un único input global de tarifa por kWh (precargado con el valor vigente de Configuración General), muestra un total por fila (consumo × tarifa) y un total general, recalculados en vivo en el navegador sin recargar la página; al editar la tarifa desde la pantalla, se actualiza esa misma configuración general.

**Independent Test**: Abrir la pantalla y verificar que la tarifa aparece precargada; completar una lectura con consumo calculable y verificar que el total de esa fila y el total general se recalculan sin recargar; cambiar la tarifa, verificar que todos los totales se recalculan de inmediato, y confirmar que el nuevo valor queda guardado en Configuración General.

### Tests for User Story 4 ⚠️

- [X] T031 [P] [US4] Prueba de feature: `GET lecturas.registroMasivo.index` incluye en la vista el valor vigente de `tarifa_luz_por_unidad` como valor por defecto del input de tarifa (FR-013) — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`
- [X] T032 [P] [US4] Prueba de feature: `PATCH lecturas.registroMasivo.actualizarTarifa` con un valor numérico válido actualiza `ConfiguracionGeneral::actual()->tarifa_luz_por_unidad` (FR-015) — mismo archivo
- [X] T033 [P] [US4] Prueba de feature: `PATCH lecturas.registroMasivo.actualizarTarifa` con un valor inválido (negativo o no numérico) es rechazado con error de validación, sin modificar la configuración general — mismo archivo

### Implementation for User Story 4

- [X] T034 [US4] En `RegistroMasivoLecturasController@index`, pasar a la vista el valor vigente de `ConfiguracionGeneral::actual()->tarifa_luz_por_unidad` en `app/Http/Controllers/RegistroMasivoLecturasController.php` (depende de T008)
- [X] T035 [US4] Crear `SolicitudActualizarTarifaRegistroMasivo` (`tarifa_luz_por_unidad` required numeric min:0) en `app/Http/Requests/SolicitudActualizarTarifaRegistroMasivo.php`
- [X] T036 [US4] Implementar `RegistroMasivoLecturasController@actualizarTarifa` (valida con `SolicitudActualizarTarifaRegistroMasivo`, actualiza `ConfiguracionGeneral::actual()->update(...)`, responde sin mensaje de sesión para `hx-swap="none"`) en `app/Http/Controllers/RegistroMasivoLecturasController.php` (depende de T035)
- [X] T037 [US4] Registrar `PATCH /lecturas/registro-masivo/tarifa` → `lecturas.registroMasivo.actualizarTarifa` en `routes/web.php` (depende de T036)
- [X] T038 [US4] Agregar el input global de tarifa (precargado con el valor de T034, `hx-patch="{{ route('lecturas.registroMasivo.actualizarTarifa') }}"`, `hx-trigger="change"`, `hx-swap="none"`) y la fila de total general al pie de la tabla en `resources/views/lecturas/registro-masivo/index.blade.php` (depende de T034, T037)
- [X] T039 [US4] Agregar el total por fila y los atributos `data-*` que el JS necesita leer (consumo ya calculado si está completada, o lectura anterior si está pendiente) junto al valor de cada locación en `resources/views/lecturas/registro-masivo/partials/fila-registro-masivo.blade.php` (depende de T038, T016 — reutiliza la lectura anterior ya mostrada por US2)
- [X] T040 [P] [US4] Crear `resources/js/registro-masivo-lecturas.js` (recalcula el total de cada fila como `consumo × tarifa` — usando `consumo_calculado` si la fila está completada, o `lectura_actual - lectura_anterior` si está pendiente — y el total general como su suma, en el evento `input` de la tarifa y de cada campo de lectura; reengancha listeners tras `htmx:afterSettle`, mismo patrón que `resources/js/costos-fijos-contrato.js`)
- [X] T041 [US4] Registrar la entrada `resources/js/registro-masivo-lecturas.js` en `vite.config.js` y su `@vite([...])` en `resources/views/lecturas/registro-masivo/index.blade.php` (depende de T040)

**Checkpoint**: El totalizado funciona de forma independiente sobre la pantalla ya existente

---

## Phase 7: User Story 5 - Exportación a Excel y PDF (FR-016)

**Goal**: Dos botones nuevos permiten descargar el contenido completo de la pantalla para el periodo seleccionado (todas las locaciones alquilables, completadas y pendientes, con lectura anterior, lectura actual, consumo, total por fila y total general) en formato `.xlsx` y `.pdf`.

**Independent Test**: Con una pantalla que mezcla filas completadas y pendientes, descargar el Excel y verificar su contenido contra lo visible en pantalla; repetir con el PDF.

### Tests for User Story 5 ⚠️

- [X] T042 [P] [US5] Prueba de feature: `GET lecturas.registroMasivo.exportarExcel` responde con un archivo `.xlsx` (content-type correcto) que incluye todas las locaciones alquilables del periodo, completadas y pendientes, con lectura anterior/actual/consumo/total (FR-016) — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`
- [X] T043 [P] [US5] Prueba de feature: `GET lecturas.registroMasivo.exportarPdf` responde con un archivo `.pdf` con el mismo contenido que la exportación a Excel — mismo archivo

### Implementation for User Story 5

- [X] T044 [US5] `composer require barryvdh/laravel-dompdf maatwebsite/excel` (Decisión 8 de `research.md`)
- [X] T045 [US5] Extraer de `RegistroMasivoLecturasController@index` un método privado compartido (ej. `datosDelPeriodo(Carbon $periodo)`) que reúne locaciones/lecturas del periodo/lecturas anteriores/tarifa/consumo/total por fila, reutilizado por `index`, `exportarExcel` y `exportarPdf`, para que el contenido exportado nunca se desincronice del visible en pantalla en `app/Http/Controllers/RegistroMasivoLecturasController.php` (depende de T034)
- [X] T046 [P] [US5] Crear `ExportacionRegistroMasivoLecturas` (`FromCollection`, `WithHeadings`) en `app/Exports/ExportacionRegistroMasivoLecturas.php` (depende de T045)
- [X] T047 [US5] Implementar `RegistroMasivoLecturasController@exportarExcel` (`Excel::download(new ExportacionRegistroMasivoLecturas(...), "lecturas-{periodo}.xlsx")`) en `app/Http/Controllers/RegistroMasivoLecturasController.php` (depende de T046)
- [X] T048 [P] [US5] Crear la plantilla `resources/views/lecturas/registro-masivo/exportar-pdf.blade.php` (tabla estática con el mismo contenido, estilos de impresión) (depende de T045)
- [X] T049 [US5] Implementar `RegistroMasivoLecturasController@exportarPdf` (`Pdf::loadView('lecturas.registro-masivo.exportar-pdf', ...)->download(...)`) en `app/Http/Controllers/RegistroMasivoLecturasController.php` (depende de T048)
- [X] T050 [US5] Registrar `GET /lecturas/registro-masivo/exportar/excel` → `lecturas.registroMasivo.exportarExcel` y `GET /lecturas/registro-masivo/exportar/pdf` → `lecturas.registroMasivo.exportarPdf` en `routes/web.php` (depende de T047, T049)
- [X] T051 [US5] Agregar los botones "Exportar a Excel" / "Exportar a PDF" (`bi-file-earmark-excel`/`bi-file-earmark-pdf`, preservando el `periodo` seleccionado como query param) en `resources/views/lecturas/registro-masivo/index.blade.php` (depende de T050)

**Checkpoint**: La exportación funciona de forma independiente sobre la pantalla ya existente

---

## Phase 8: User Story 6 - Edición en Línea de una Lectura Ya Registrada (FR-005, FR-017)

**Goal**: El badge de texto "Completada" se reemplaza por un ícono no invasivo con equivalente textual accesible (`aria-label`/tooltip) que, al hacer clic, permite editar la lectura ya registrada dentro de la misma fila, sin navegar a otra pantalla, con las mismas validaciones que el registro individual (incluida la confirmación de consumo negativo).

**Independent Test**: En una fila completada, hacer clic en el ícono, editar el valor, intentar guardar un consumo negativo sin confirmar (debe pedir confirmación dentro de la misma fila), confirmar y guardar, y verificar que la fila vuelve a modo lectura con el valor actualizado sin haber navegado fuera de la pantalla.

### Tests for User Story 6 ⚠️

- [X] T052 [P] [US6] Prueba de feature: una fila completada muestra un ícono (`bi-check-circle-fill`) con `aria-label`/`title` accesibles en vez del `badge` de texto "Completada" (FR-005) — en `tests/Feature/RegistroMasivoLecturasControllerTest.php`
- [X] T053 [P] [US6] Prueba de feature: `GET lecturas.registroMasivo.editarInline` responde con la parcial de la fila en modo edición, con el input prellenado con el valor ya registrado (FR-017) — mismo archivo
- [X] T054 [P] [US6] Prueba de feature: `PATCH lecturas.registroMasivo.actualizarInline` con un valor válido actualiza la `LecturaMedidor` existente y responde con la parcial de la fila en modo lectura ya actualizada, sin redirección — mismo archivo
- [X] T055 [P] [US6] Prueba de feature: `PATCH lecturas.registroMasivo.actualizarInline` con un valor que produce consumo negativo sin confirmar responde con la parcial en modo edición mostrando el checkbox de confirmación, igual que el flujo individual (FR-008 reutilizado) — mismo archivo

### Implementation for User Story 6

- [X] T056 [US6] En `fila-registro-masivo.blade.php`, reemplazar `<span class="badge text-bg-success">Completada</span>` por el ícono accesible (`aria-label`, `data-bs-toggle="tooltip"`), disparando `hx-get` hacia `editarInline` con `hx-target` la propia fila y `hx-swap="outerHTML"` (FR-005, Decisión 10 de `research.md`) (depende de T011)
- [X] T057 [US6] Agregar a `fila-registro-masivo.blade.php` un modo de edición (parámetro `$modoEdicion` o similar) que renderiza el input de `lectura_actual` prellenado, con botones guardar (`hx-patch` a `actualizarInline`) y cancelar (`hx-get` de vuelta a modo lectura), reutilizando el checkbox de confirmación de consumo negativo ya existente (depende de T056)
- [X] T058 [US6] Implementar `RegistroMasivoLecturasController@editarInline` (resuelve la `LecturaMedidor` por id, renderiza la parcial de fila en modo edición) en `app/Http/Controllers/RegistroMasivoLecturasController.php` (depende de T057)
- [X] T059 [US6] Implementar `RegistroMasivoLecturasController@actualizarInline` (reutiliza `SolicitudGuardarLecturaMedidor` y el mismo patrón `DB::transaction`/`ConsumoNegativoSinConfirmarException` que `LecturaMedidorController@update`, Decisión 9 de `research.md`; responde con la parcial de fila en modo lectura si tiene éxito, o en modo edición con el checkbox de confirmación si el consumo resulta negativo) en `app/Http/Controllers/RegistroMasivoLecturasController.php` (depende de T058)
- [X] T060 [US6] Registrar `GET /lecturas/registro-masivo/lecturas/{lectura}/editar-inline` → `lecturas.registroMasivo.editarInline` y `PATCH /lecturas/registro-masivo/lecturas/{lectura}` → `lecturas.registroMasivo.actualizarInline` en `routes/web.php` (depende de T059)

**Checkpoint**: La edición en línea funciona de forma independiente; las 6 historias (3 originales + 3 de esta ampliación) funcionan juntas sin romperse entre sí

---

## Phase 9: Polish & Cross-Cutting Concerns

**Purpose**: Verificación final que afecta a las 6 historias de usuario

- [X] T027 Revisión de diseño de `resources/views/lecturas/registro-masivo/index.blade.php` y su parcial con el skill `impeccable` (`/impeccable polish` o `audit`), exigida por el Principio VI de la constitución — primer uso de `hx-trigger="every ..."` en el proyecto; prestar atención a que el indicador de autoguardado sea discreto y no compita visualmente con el guardado manual (depende de T025)
- [X] T028 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 5) de extremo a extremo en el navegador
- [X] T029 [P] Ejecutar `php artisan test --filter=RegistroMasivoLecturas` y confirmar que toda la suite nueva pasa
- [X] T030 [P] Ejecutar la suite completa `php artisan test` y confirmar que no hay regresiones en `LecturaMedidorControllerTest` ni en el resto de la aplicación
- [X] T061 Revisión de diseño con el skill `impeccable` de las vistas modificadas/nuevas (`index.blade.php`, `fila-registro-masivo.blade.php`, `exportar-pdf.blade.php`), exigida por el Principio VI — prestar atención especial al matiz de accesibilidad del ícono no invasivo (Decisión 10 de `research.md`) (depende de T039, T048, T057)
- [X] T062 Ejecutar la validación completa de `quickstart.md` (Escenarios 6 a 8) de extremo a extremo en el navegador
- [X] T063 [P] Ejecutar `php artisan test --filter=RegistroMasivoLecturas` y confirmar que toda la suite (original + ampliación) pasa
- [X] T064 [P] Ejecutar la suite completa `php artisan test` y confirmar que no hay regresiones, incluyendo `ConfiguracionGeneralControllerTest` y `ReciboControllerTest` (que también leen/usan `tarifa_luz_por_unidad`)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sin dependencias — puede iniciar de inmediato
- **Foundational (Phase 2)**: BLOQUEA las 6 historias de usuario
- **User Stories (Phase 3-5, originales)**: Todas dependen de Foundational; se recomienda orden P1 → P2 → P3 porque US2 y US3 modifican archivos ya creados por US1 (`RegistroMasivoLecturasController`, `index.blade.php`, `fila-registro-masivo.blade.php`)
- **User Stories (Phase 6-8, ampliación FR-013 a FR-017)**: Todas dependen de que Phase 3-5 ya estén completas (reutilizan el controlador, la vista índice y la parcial de fila ya creados, no los archivos de Foundational directamente); US5 (Exportación) depende además de que US4 haya agregado el acceso a la tarifa vigente (T034) porque reutiliza esa misma fuente de datos
- **Polish (Phase 9)**: Depende de que las 6 historias estén completas

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Foundational — sin dependencias de otras historias
- **User Story 2 (P2)**: Agrega una columna de referencia a la pantalla ya creada por US1; comprobable de forma independiente una vez aplicada
- **User Story 3 (P3)**: Agrega el autoguardado sobre el formulario ya creado por US1; comprobable de forma independiente una vez aplicada
- **User Story 4 (Totalizado)**: Requiere US1 (pantalla y filas) y US2 (lectura anterior visible, para el cálculo del consumo de filas pendientes); comprobable de forma independiente del autoguardado (US3), la exportación (US5) y la edición en línea (US6)
- **User Story 5 (Exportación)**: Requiere US1/US2 (mismos datos que se exportan) y el acceso a la tarifa agregado por US4 (T034); no depende de US3 ni de US6
- **User Story 6 (Edición en línea)**: Requiere US1 (la fila y el estado "completada" que reemplaza) y reutiliza la validación ya existente de `LecturaMedidorController`; no depende de US2, US3, US4 ni US5

### Within Each User Story

- Las pruebas se escriben antes de la implementación y deben fallar inicialmente
- US1: Form Request y controlador antes que rutas; parcial de fila antes que la vista índice; vista índice antes que el ítem de navegación
- US2: mapa de lecturas anteriores en el controlador antes que su despliegue en la parcial
- US3: migración antes que modelo; modelo antes que el método del controlador; controlador antes que ruta; ruta antes que el disparador `hx-trigger` en la vista; eliminación del borrador al final, después de que el guardado (US1) ya exista
- US4: acceso a la tarifa en el controlador antes que la solicitud de validación; solicitud antes que la acción `actualizarTarifa`; acción antes que su ruta; ruta antes que el input en la vista; input/total general en la vista antes que el total por fila en la parcial; parcial antes que el JS de cálculo en vivo; JS antes de registrarlo en `vite.config.js`
- US5: dependencias Composer antes que el método privado compartido; método compartido antes que la clase de exportación y la plantilla PDF; clase/plantilla antes que las acciones del controlador; acciones antes que sus rutas; rutas antes que los botones en la vista
- US6: reemplazo del badge por el ícono antes que el modo de edición de la parcial; modo de edición antes que la acción `editarInline`; `editarInline` antes que `actualizarInline`; ambas acciones antes que sus rutas

### Parallel Opportunities

- No hay tareas [P] dentro de US1-US3: las pruebas de una misma historia comparten archivo de test, y la implementación modifica el mismo controlador/vista en orden
- US4: T031-T033 (pruebas) son paralelizables entre sí; T040 (JS) es paralelizable con el resto porque es un archivo nuevo sin dependencias de las demás tareas de implementación
- US5: T042-T043 (pruebas) son paralelizables entre sí; T046 (clase de exportación) y T048 (plantilla PDF) son paralelizables entre sí (archivos distintos, ambos dependen solo de T045)
- US6: T052-T055 (pruebas) son paralelizables entre sí
- T029/T030 y T063/T064 en Polish son paralelizables entre sí dentro de cada grupo

---

## Parallel Example: Polish

```bash
Task: "Ejecutar php artisan test --filter=RegistroMasivoLecturas"
Task: "Ejecutar la suite completa php artisan test"
```

## Parallel Example: User Story 5 (Exportación)

```bash
# Una vez completado T045 (método privado compartido):
Task: "Crear ExportacionRegistroMasivoLecturas en app/Exports/ExportacionRegistroMasivoLecturas.php"
Task: "Crear la plantilla resources/views/lecturas/registro-masivo/exportar-pdf.blade.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Completar Phase 2: Foundational
2. Completar Phase 3: User Story 1 (pantalla + guardado masivo, sin referencia ni autoguardado)
3. **DETENERSE Y VALIDAR**: Escenarios 1-3 de `quickstart.md`
4. Desplegar/demostrar si está listo

### Incremental Delivery

1. Foundational → servicios reutilizables confirmados
2. User Story 1 → probar → demo (MVP: registro masivo funcional)
3. User Story 2 → probar → demo (referencia del periodo anterior visible)
4. User Story 3 → probar → demo (autoguardado y restauración de borrador)
5. User Story 4 → probar → demo (totalizado con tarifa editable)
6. User Story 5 → probar → demo (exportación a Excel y PDF)
7. User Story 6 → probar → demo (edición en línea + ícono no invasivo)
8. Polish → revisión `impeccable` + validación completa + suite de pruebas

---

## Notes

- Esta feature agrega 1 tabla nueva (`borradores_lectura_medidor`) y 1 controlador nuevo; no modifica `LecturaMedidorController` ni el flujo individual de lecturas ya existente, salvo por la reutilización explícita de su patrón de validación desde la edición en línea (US6).
- La persistencia del guardado final es fila por fila (no un único `DB::transaction` para todo el lote) para cumplir FR-009 — ver Decisión 3 de `research.md`.
- El autoguardado (US3) nunca aplica las validaciones de negocio de FR-008 (consumo negativo); esas solo se aplican en el guardado final (US1) y en la edición en línea (US6).
- La ampliación de alcance (US4-US6, FR-013 a FR-017) no agrega tablas nuevas: reutiliza `configuracion_general` ya existente (specs/005) y agrega 2 dependencias Composer (`barryvdh/laravel-dompdf`, `maatwebsite/excel`) solo para US5.
- [P] = archivos distintos, sin dependencias pendientes
- Verificar que las pruebas fallan antes de implementar
- Hacer commit tras cada tarea o grupo lógico de tareas
- Evitar: tareas vagas, conflictos de archivo simultáneos, dependencias que rompan la independencia entre historias
