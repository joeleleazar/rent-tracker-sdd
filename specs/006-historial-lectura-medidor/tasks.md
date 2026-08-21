---

description: "Task list template for feature implementation"
---

# Tasks: Traslado Editable de Lectura Anterior e Historial de Medidor

**Input**: Design documents from `/specs/006-historial-lectura-medidor/`

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

**Purpose**: Migración de esquema/datos y refactor del Service que todas las historias de usuario de esta feature requieren

**⚠️ CRITICAL**: Ninguna historia de usuario puede comenzar hasta completar esta fase

- [X] T002 Crear migración de alteración de `lecturas_medidor` (renombra `lectura` → `lectura_actual`, agrega `lectura_anterior` decimal(12,2) nullable, migración de datos que puebla `lectura_anterior` desde la fila cronológicamente previa por `locacion_id`, recalcula `consumo_calculado`) en `database/migrations/` (ver `research.md` §1)
- [X] T003 Actualizar `$fillable`/`casts()` de `LecturaMedidor` (`lectura_anterior`, `lectura_actual` en vez de `lectura`) en `app/Models/LecturaMedidor.php` (depende de T002)
- [X] T004 Agregar helper `discrepanciaConSiguiente(): bool` a `LecturaMedidor` en `app/Models/LecturaMedidor.php` (depende de T003; ver `research.md` §3)
- [X] T005 Refactorizar `ServicioCalculoConsumoMedidor` (de `specs/005`) en `sugerirLecturaAnterior(Locacion $locacion, string $periodo): ?float` y `calcularConsumo(?float $lecturaAnterior, float $lecturaActual): ?float` en `app/Services/ServicioCalculoConsumoMedidor.php` (depende de T003; ver `research.md` §2)

**Checkpoint**: Fundamento listo — las historias de usuario pueden comenzar

---

## Phase 3: User Story 1 - Traslado Automático de Lectura Actual a Lectura Anterior del Siguiente Periodo (Priority: P1) 🎯 MVP

**Goal**: Autocompletar "lectura anterior" de un nuevo periodo con la "lectura actual" del periodo previo más reciente.

**Independent Test**: Registrar "1250" como lectura actual de "Julio 2026" e iniciar el registro de "Agosto 2026", comprobando que "lectura anterior" aparezca precargada con "1250"; para una locación sin periodo previo, comprobar que aparezca vacía con el texto explícito correspondiente.

### Tests for User Story 1 ⚠️

- [X] T006 [P] [US1] Prueba unitaria de `ServicioCalculoConsumoMedidor::sugerirLecturaAnterior()` (con periodo previo, sin periodo previo, con huecos de periodos) en `tests/Unit/ServicioCalculoConsumoMedidorTest.php`
- [X] T007 [P] [US1] Prueba de feature de `LecturaMedidorController@create` (precarga de `lectura_anterior`, mensaje "Sin lectura previa registrada") en `tests/Feature/LecturaMedidorControllerTest.php`

### Implementation for User Story 1

- [X] T008 [US1] Actualizar `LecturaMedidorController@create` para invocar `sugerirLecturaAnterior()` y pasar el valor precargado a la vista en `app/Http/Controllers/LecturaMedidorController.php` (depende de T005)
- [X] T009 [US1] Actualizar `resources/views/locaciones/lecturas/create.blade.php` (campo "lectura anterior" precargado y editable, o texto "Sin lectura previa registrada") (depende de T008)

**Checkpoint**: User Story 1 funcional y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Edición del Valor Trasladado antes de Confirmar (Priority: P1)

**Goal**: Permitir editar el valor autocompletado de "lectura anterior" antes de guardar, sin afectar el periodo previo del cual se trasladó.

**Independent Test**: Editar "lectura anterior" precargada de "1250" a "1245", ingresar "lectura actual" "1400" y guardar; comprobar que el consumo calculado sea "155" y que el periodo previo conserve su "lectura actual" original sin cambios.

### Tests for User Story 2 ⚠️

- [X] T010 [P] [US2] Prueba unitaria de `LecturaMedidor`/`ServicioCalculoConsumoMedidor::calcularConsumo()` (edición del valor autocompletado, independencia del registro previo tras guardar) en `tests/Unit/LecturaMedidorTest.php`
- [X] T011 [P] [US2] Prueba de feature de `LecturaMedidorController@store` (guarda `lectura_anterior` editada, no modifica el periodo previo) en `tests/Feature/LecturaMedidorControllerTest.php`

### Implementation for User Story 2

- [X] T012 [US2] Extender `SolicitudGuardarLecturaMedidor` (de `specs/005`) con `lectura_anterior` (`numeric`, `nullable`) y renombrar `lectura` a `lectura_actual` (`numeric`, `required`, `min:0`) en `app/Http/Requests/SolicitudGuardarLecturaMedidor.php` (depende de T003)
- [X] T013 [US2] Actualizar `LecturaMedidorController@store`/`@update` para persistir `lectura_anterior` tal como la confirme el administrador (editada o no) y calcular `consumo_calculado` vía `calcularConsumo()` en `app/Http/Controllers/LecturaMedidorController.php` (depende de T005, T012)

**Checkpoint**: User Story 1 y 2 funcionan de forma independiente

---

## Phase 5: User Story 3 - Consulta del Historial Completo de Lecturas por Locación (Priority: P2)

**Goal**: Mostrar el historial completo de periodos con "lectura anterior", "lectura actual" y consumo calculado, incluyendo advertencias de discrepancia.

**Independent Test**: Con 4 periodos registrados, consultar el historial y comprobar que los 4 se listen en orden cronológico con sus valores completos, sin que ninguno desaparezca al registrar uno nuevo.

### Tests for User Story 3 ⚠️

- [X] T014 [P] [US3] Prueba de feature de `LecturaMedidorController@index` (orden cronológico con `lectura_anterior`/`lectura_actual`/consumo, indicador de discrepancia visible) en `tests/Feature/LecturaMedidorControllerTest.php` (depende de T007)

### Implementation for User Story 3

- [X] T015 [US3] Actualizar `LecturaMedidorController@index` (de `specs/005`) para incluir `discrepanciaConSiguiente()` por fila en `app/Http/Controllers/LecturaMedidorController.php` (depende de T004)
- [X] T016 [US3] Actualizar `resources/views/locaciones/lecturas/index.blade.php` (columnas `lectura_anterior`/`lectura_actual`, indicador de discrepancia de alto contraste) (depende de T015)

**Checkpoint**: Las 3 historias de usuario funcionan de forma independiente

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Mejoras que afectan a todas las historias de usuario

- [X] T017 [P] Auditoría de accesibilidad (contraste, tipografía ≥18px) en `resources/views/locaciones/lecturas/`
- [X] T018 [P] Revisión de seguridad: verificación de que la migración de datos (T002) se ejecute atómicamente y no dependa de un comando manual posterior
- [X] T019 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 5) de extremo a extremo
- [X] T020 [P] Ejecutar `php artisan test --filter=LecturaMedidor` y confirmar que toda la suite pasa (incluidas las pruebas heredadas de `specs/005`)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Ya completado
- **Foundational (Phase 2)**: BLOQUEA todas las historias de usuario (migración de datos y refactor del Service son prerrequisito de todo lo demás)
- **User Stories (Phase 3-5)**: Todas dependen de Foundational; US1 y US2 son ambas P1 y están estrechamente relacionadas (comparten el mismo formulario), US3 (P2) es independiente en implementación
- **Polish (Phase 6)**: Depende de que las historias deseadas estén completas

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Foundational — sin dependencias de otras historias
- **User Story 2 (P1)**: Reutiliza el mismo formulario de US1, pero es comprobable de forma independiente (edición del valor ya precargado)
- **User Story 3 (P2)**: Depende de que existan datos de US1/US2 para mostrar contenido útil, pero su implementación es independiente

### Within Each User Story

- Las pruebas se escriben antes de la implementación y deben fallar inicialmente
- Migración/modelo/Service antes que Form Request; Form Request antes que controlador; controlador antes que vista

### Parallel Opportunities

- T003-T005 de Foundational tienen dependencias secuenciales sobre T002 (misma tabla); una vez completado Foundational, US1 y US2 pueden avanzar en paralelo si se coordina el archivo compartido `LecturaMedidorController.php`

---

## Parallel Example: User Story 1

```bash
Task: "Prueba unitaria de ServicioCalculoConsumoMedidor::sugerirLecturaAnterior() en tests/Unit/ServicioCalculoConsumoMedidorTest.php"
Task: "Prueba de feature de LecturaMedidorController@create en tests/Feature/LecturaMedidorControllerTest.php"
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

- Implementado tal como lo describe `research.md`: migración de alteración renombra `lectura`→`lectura_actual`, agrega `lectura_anterior` nullable, y ejecuta la migración de datos dentro del propio `up()` (sin comando artisan separado). En el entorno de dev/testing no había filas preexistentes de 005 al momento de aplicar esta migración, por lo que el bucle de backfill no tuvo datos que migrar en la práctica, pero su lógica quedó implementada para producción real.
- `ServicioCalculoConsumoMedidor` quedó refactorizado en `sugerirLecturaAnterior()` (consulta) y `calcularConsumo()` (aritmética pura), exactamente como especifica `research.md` §2.
- Mensajes de error de "consumo negativo" y de "lectura duplicada" ahora se asocian al campo `lectura_actual` (antes `lectura`) en `LecturaMedidorController`.
- [P] = archivos distintos, sin dependencias pendientes
- Verificar que las pruebas fallan antes de implementar
- Hacer commit tras cada tarea o grupo lógico de tareas
- Evitar: tareas vagas, conflictos de archivo simultáneos, dependencias que rompan la independencia entre historias
