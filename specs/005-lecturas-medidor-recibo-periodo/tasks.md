---

description: "Task list template for feature implementation"
---

# Tasks: Lecturas de Medidor de Luz y Recibo por Periodo

**Input**: Design documents from `/specs/005-lecturas-medidor-recibo-periodo/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Incluidas — el Principio IV de la Constitución exige pruebas automatizadas exhaustivas para toda funcionalidad.

**Organization**: Las tareas están agrupadas por historia de usuario para permitir implementación y prueba independiente de cada una.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: Historia de usuario a la que pertenece la tarea (US1, US2, US3)
- Se incluyen rutas de archivo exactas en cada descripción

## Path Conventions

Aplicación Laravel monolítica única — rutas relativas a la raíz del repositorio: `app/`, `database/`, `resources/`, `routes/`, `tests/`, según `plan.md` → Project Structure.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Ya inicializado por `specs/002-gestion-contratos` (Laravel, PostgreSQL, Pest)

- [X] T001 Proyecto ya inicializado — sin tareas de setup adicionales para esta feature

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Esquema de base de datos y modelos base que todas las historias de usuario de esta feature requieren

**⚠️ CRITICAL**: Ninguna historia de usuario puede comenzar hasta completar esta fase

- [X] T002 [P] Crear migración de `lecturas_medidor` (`id`, `locacion_id` FK `restrictOnDelete()`, `periodo` date, `lectura` decimal(12,2), `consumo_calculado` decimal(12,2) nullable, `fecha_registro` timestamp, timestamps, índice único `(locacion_id, periodo)`) en `database/migrations/`
- [X] T003 [P] Crear migración de alteración de `recibos` (agrega `locacion_id` FK `restrictOnDelete()` con backfill desde `contratos.locacion_id`, `lectura_medidor_id` FK `nullOnDelete()`, `incluye_alquiler`/`incluye_luz`/`incluye_agua`/`incluye_seguridad`/`incluye_pasadizo` boolean default `true`, índice único `(locacion_id, periodo)`) en `database/migrations/` (depende de la migración de `recibos` de `specs/004`; ver `research.md` §1 y §3)
- [X] T004 [P] Crear migración de alteración de `configuracion_general` (agrega `tarifa_luz_por_unidad` decimal(12,4) default 0) en `database/migrations/` (depende de la migración de `configuracion_general` de `specs/004`; ver `research.md` §5)
- [X] T005 [P] Crear modelo `LecturaMedidor` (`$fillable`, casts `decimal:2`/`date`/`datetime`, relación `locacion(): BelongsTo`) en `app/Models/LecturaMedidor.php` (depende de T002)
- [X] T006 [P] Agregar relación `lecturasMedidor(): HasMany` a `Locacion` y helper `contratoActivoEnPeriodo(Carbon $periodo): ?Contrato` en `app/Models/Locacion.php` (depende de T005; ver `research.md` §2)
- [X] T007 [P] Agregar `locacion_id`/`lectura_medidor_id`/`incluye_*` a `$fillable`/`casts()` y relaciones `locacion(): BelongsTo`/`lecturaMedidor(): BelongsTo` de `Recibo` en `app/Models/Recibo.php` (depende de T003)
- [X] T008 [P] Agregar `tarifa_luz_por_unidad` a `$fillable`/`casts()` (`decimal:4`) de `ConfiguracionGeneral` en `app/Models/ConfiguracionGeneral.php` (depende de T004)
- [X] T009 [P] Crear factory `LecturaMedidorFactory` en `database/factories/LecturaMedidorFactory.php` (depende de T005)
- [X] T010 Implementar `ServicioCalculoConsumoMedidor` (`calcular()`: busca lectura anterior, calcula diferencia, detecta consumo negativo) en `app/Services/ServicioCalculoConsumoMedidor.php` (depende de T005)

**Checkpoint**: Fundamento listo — las historias de usuario pueden comenzar

---

## Phase 3: User Story 1 - Registro de Lectura Mensual del Medidor de Luz (Priority: P1) 🎯 MVP

**Goal**: Registrar la lectura del medidor de luz de una locación por periodo, con cálculo automático de consumo.

**Independent Test**: Registrar la lectura "1250" para "Agosto 2026" y comprobar que quede asociada a la locación y al periodo, mostrando el consumo calculado o "sin dato anterior".

### Tests for User Story 1 ⚠️

- [X] T011 [P] [US1] Prueba unitaria de `LecturaMedidor`/`ServicioCalculoConsumoMedidor` (consumo calculado, "sin dato anterior", unicidad por periodo) en `tests/Unit/LecturaMedidorTest.php` y `tests/Unit/ServicioCalculoConsumoMedidorTest.php`
- [X] T012 [P] [US1] Prueba de feature de `LecturaMedidorController@create`/`@store`/`@edit`/`@update` (alta, edición en vez de duplicado, 422 con consumo negativo sin confirmar) en `tests/Feature/LecturaMedidorControllerTest.php`

### Implementation for User Story 1

- [X] T013 [P] [US1] Crear `SolicitudGuardarLecturaMedidor` (`periodo`, `lectura` numeric ≥0, `confirmar_consumo_negativo` condicional) en `app/Http/Requests/SolicitudGuardarLecturaMedidor.php` (depende de T005)
- [X] T014 [US1] Implementar `LecturaMedidorController@create`/`@store`/`@edit`/`@update` (usa `ServicioCalculoConsumoMedidor`, redirige a edición si el periodo ya existe) en `app/Http/Controllers/LecturaMedidorController.php` (depende de T010, T013)
- [X] T015 [US1] Registrar rutas `GET/POST /locaciones/{locacion}/lecturas`, `GET /locaciones/{locacion}/lecturas/crear`, `GET/PUT /lecturas/{lectura}(/editar)` en `routes/web.php` (depende de T014)
- [X] T016 [US1] Crear vista `locaciones/lecturas/create.blade.php` (formulario con advertencia de consumo negativo en alto contraste, botón "Guardar Lectura del Medidor" ≥48x48px) en `resources/views/locaciones/lecturas/create.blade.php` (depende de T015)

**Checkpoint**: User Story 1 funcional y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Generación de Recibo Mensual con Conceptos Configurables (Priority: P1)

**Goal**: Generar un recibo por locación y periodo con conceptos incluibles/excluibles y montos editables (renta, luz calculada, agua, seguridad, pasadizo).

**Independent Test**: Generar un recibo para un periodo con lectura registrada, excluir un concepto y editar otro, comprobando que el recibo se emita solo con lo seleccionado; intentar duplicar el recibo del mismo periodo y comprobar el bloqueo.

### Tests for User Story 2 ⚠️

- [X] T017 [P] [US2] Prueba unitaria de `ServicioGeneracionReciboPeriodo` (bloqueo sin contrato activo, no-duplicación por `(locacion_id, periodo)`, cálculo de monto de luz sugerido) en `tests/Unit/ServicioGeneracionReciboPeriodoTest.php`
- [X] T018 [P] [US2] Prueba de feature de `ReciboController@create`/`@store`/`@edit`/`@update` locación-céntrico (precarga de conceptos, exclusión, 422 por duplicado o sin contrato activo) en `tests/Feature/ReciboControllerTest.php`

### Implementation for User Story 2

- [X] T019 [US2] Implementar `ServicioGeneracionReciboPeriodo` (resuelve `contratoActivoEnPeriodo`, arma conceptos con montos precargados y monto de luz sugerido = consumo × tarifa, valida no-duplicación, `DB::transaction`) en `app/Services/ServicioGeneracionReciboPeriodo.php` (depende de T006, T007, T008, T010)
- [X] T020 [US2] Extender `SolicitudGuardarRecibo` (de `specs/004`) con `incluye_alquiler`/`incluye_luz`/`incluye_agua`/`incluye_seguridad`/`incluye_pasadizo` boolean en `app/Http/Requests/SolicitudGuardarRecibo.php` (depende de T007)
- [X] T021 [US2] Reubicar `ReciboController@create`/`@store` a firma locación-céntrica (`Locacion $locacion`, query `periodo`) e implementar `@edit`/`@update`, usando `ServicioGeneracionReciboPeriodo` en `app/Http/Controllers/ReciboController.php` (depende de T019, T020; ver `research.md` §1)
- [X] T022 [US2] Registrar rutas `GET/POST /locaciones/{locacion}/recibos(/crear)`, `GET/PUT /recibos/{recibo}/editar` en `routes/web.php`, retirando las rutas contrato-céntricas equivalentes de `specs/004` (depende de T021)
- [X] T023 [US2] Crear vista `locaciones/recibos/create.blade.php` (casillas de inclusión ≥48x48px por concepto, montos precargados editables, botón "Emitir Recibo del Periodo") en `resources/views/locaciones/recibos/create.blade.php` (depende de T022)

**Checkpoint**: User Story 1 y 2 funcionan de forma independiente

---

## Phase 5: User Story 3 - Consulta de Historial de Consumo y Recibos por Locación (Priority: P3)

**Goal**: Consultar el historial de lecturas y recibos de una locación ordenado por periodo.

**Independent Test**: Con lecturas de 3 periodos registradas, consultar el historial y comprobar el orden cronológico con lectura, consumo y enlace al recibo (si existe).

### Tests for User Story 3 ⚠️

- [X] T024 [P] [US3] Prueba de feature de `LecturaMedidorController@index` (orden cronológico, enlace a recibo si existe) en `tests/Feature/LecturaMedidorControllerTest.php` (depende de T012)

### Implementation for User Story 3

- [X] T025 [US3] Implementar `LecturaMedidorController@index` (lecturas + recibo asociado por periodo, orden cronológico) en `app/Http/Controllers/LecturaMedidorController.php` (depende de T014)
- [X] T026 [US3] Crear vista `locaciones/lecturas/index.blade.php` (tabla de historial, tipografía ≥18px, alto contraste) en `resources/views/locaciones/lecturas/index.blade.php` (depende de T025)

**Checkpoint**: Las 3 historias de usuario funcionan de forma independiente

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Mejoras que afectan a todas las historias de usuario

- [X] T027 [P] Auditoría de accesibilidad (contraste, tipografía ≥18px, botones/casillas ≥48x48px) en `resources/views/locaciones/lecturas/` y `resources/views/locaciones/recibos/`
- [X] T028 [P] Revisión de seguridad: CSRF, `$fillable`, verificación de que `ReciboController@store` nunca omita la comprobación de `contratoActivoEnPeriodo` ni la de duplicado por `(locacion_id, periodo)`
- [X] T029 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 6) de extremo a extremo
- [X] T030 [P] Ejecutar `php artisan test --filter=LecturaMedidor` y `--filter=ServicioGeneracionReciboPeriodo`, confirmando que toda la suite pasa

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Ya completado
- **Foundational (Phase 2)**: BLOQUEA todas las historias de usuario
- **User Stories (Phase 3-5)**: Todas dependen de Foundational; US1 y US2 son ambas P1 pero US2 depende funcionalmente de que exista una lectura para calcular el monto de luz sugerido (aunque puede probarse con `consumo_calculado = null` de forma independiente); US3 depende de que existan datos de US1/US2 para ser útil, aunque su implementación es independiente
- **Polish (Phase 6)**: Depende de que las historias deseadas estén completas

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Foundational — sin dependencias de otras historias
- **User Story 2 (P1)**: Puede iniciar tras Foundational — reutiliza `contratoActivoEnPeriodo` y `ServicioCalculoConsumoMedidor` de Foundational; funcionalmente se enriquece con US1 pero no depende de que exista una lectura para poder emitir el recibo (el monto de luz simplemente parte en 0 si no hay lectura)
- **User Story 3 (P3)**: Depende de que existan registros de US1/US2 para mostrar contenido, pero su implementación (consulta e vista) es independiente

### Within Each User Story

- Las pruebas se escriben antes de la implementación y deben fallar inicialmente
- Migraciones/modelos antes que Services; Services antes que controladores; controladores antes que rutas; rutas antes que vistas

### Parallel Opportunities

- T002-T004 de Foundational pueden ejecutarse en paralelo (migraciones independientes); T005-T009 dependen de ellas pero son paralelas entre sí por archivo
- Una vez completado Foundational, US1 y US2 pueden avanzar en paralelo por distintos desarrolladores

---

## Parallel Example: User Story 2

```bash
Task: "Prueba unitaria de ServicioGeneracionReciboPeriodo en tests/Unit/ServicioGeneracionReciboPeriodoTest.php"
Task: "Prueba de feature de ReciboController locación-céntrico en tests/Feature/ReciboControllerTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Completar Phase 2: Foundational (CRÍTICO)
2. Completar Phase 3: User Story 1
3. **DETENERSE Y VALIDAR**: Escenario 1 de `quickstart.md`
4. Desplegar/demostrar si está listo

### Incremental Delivery

1. Foundational → base lista
2. User Story 1 → probar → demo (MVP)
3. User Story 2 → probar → demo
4. User Story 3 → probar → demo

---

## Notes

- **T003 ya estaba parcialmente hecha desde specs/004**: `recibos.locacion_id` se creó ya en la migración original de 004 (`2026_08_21_041408_create_recibos_table.php`), no como backfill aquí. La migración de esta feature (`2026_08_21_042852_add_conceptos_a_recibos_table.php`) solo agrega `lectura_medidor_id`, los 5 `incluye_*`, y sustituye el índice simple `(locacion_id, periodo)` de 004 por la restricción `UNIQUE` que exige FR-009.
- **`Locacion::contratoActivoEnPeriodo()` ya estaba implementado desde specs/004** (mismo motivo); se reutilizó tal cual (T006), no se duplicó.
- **Reconciliación de "costo de luz" (Contrato, de specs/004) vs. "luz calculada por consumo" (esta spec)**: `Contrato.costo_luz` (campo fijo editable de 004) deja de usarse como precarga del concepto "luz" del recibo — desde esta especificación, el monto sugerido de "luz" se calcula exclusivamente como `consumo_calculado × tarifa_luz_por_unidad` (`ServicioGeneracionReciboPeriodo::calcularMontoLuzSugerido()`), independientemente de `costo_luz`. `Contrato.costo_luz` permanece en el esquema y en el formulario de costos del contrato (aún editable), pero ya no participa en la precarga del recibo; se documenta esta evolución semántica en vez de eliminar el campo (fuera del alcance de esta spec tocar 004 retroactivamente).
- **Botón renombrado**: "Emitir Recibo" (004) pasa a "Emitir Recibo del Periodo" en `locaciones/recibos/create.blade.php`, tal como pide FR-011 de esta especificación.
- [P] = archivos distintos, sin dependencias pendientes
- Verificar que las pruebas fallan antes de implementar
- Hacer commit tras cada tarea o grupo lógico de tareas
- Evitar: tareas vagas, conflictos de archivo simultáneos, dependencias que rompan la independencia entre historias
