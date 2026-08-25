---

description: "Task list template for feature implementation"
---

# Tasks: Registro de Garantía Entregada por Contrato

**Input**: Design documents from `/specs/009-garantia-contrato/`

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

**Purpose**: Esquema de base de datos que todas las historias de usuario de esta feature requieren

**⚠️ CRITICAL**: Ninguna historia de usuario puede comenzar hasta completar esta fase

- [X] T002 Crear migración de alteración de `contratos` (agrega `monto_garantia`/`monto_devuelto_garantia`/`monto_retenido_garantia` decimal(12,2) nullable, `fecha_entrega_garantia` date nullable, `medio_entrega_garantia` enum nullable, `estado_garantia` enum nullable, `motivo_retencion_garantia` text nullable, `fecha_resolucion_garantia` timestamp nullable) en `database/migrations/` (depende de la migración de `contratos` de `specs/002`, alterada en `specs/004`)
- [X] T003 Agregar los campos de garantía a `$fillable`/`casts()` (`decimal:2`, `date`, `datetime`) de `Contrato` en `app/Models/Contrato.php` (depende de T002)
- [X] T004 [P] Agregar helpers `tieneGarantia(): bool` y `garantiaResuelta(): bool` a `Contrato` en `app/Models/Contrato.php` (depende de T003)

**Checkpoint**: Fundamento listo — las historias de usuario pueden comenzar

---

## Phase 3: User Story 1 - Registro de la Garantía Entregada al Firmar el Contrato (Priority: P1) 🎯 MVP

**Goal**: Registrar en el contrato el monto de garantía, fecha de entrega y medio de entrega.

**Independent Test**: Crear/editar un contrato ingresando monto "S/ 1500.00", fecha "2026-08-19" y medio "Efectivo", comprobando que persistan y se muestren en el detalle; para un contrato sin garantía, comprobar el mensaje "Sin garantía registrada".

### Tests for User Story 1 ⚠️

- [X] T005 [P] [US1] Prueba unitaria de `Contrato` (garantía opcional, `tieneGarantia()` con monto 0 o null, casts) en `tests/Unit/ContratoTest.php`
- [X] T006 [P] [US1] Prueba de feature de `ContratoController@store`/`@update` con campos de garantía (persistencia, `fecha_entrega_garantia` obligatoria solo si monto > 0) en `tests/Feature/ContratoControllerTest.php`

### Implementation for User Story 1

- [X] T007 [US1] Agregar `monto_garantia`/`fecha_entrega_garantia`/`medio_entrega_garantia` a las reglas de `SolicitudGuardarContrato` (de `specs/002`/`004`) en `app/Http/Requests/SolicitudGuardarContrato.php` (depende de T003)
- [X] T008 [US1] Agregar campos de garantía (monto, fecha, medio) a `resources/views/contratos/create.blade.php` y `resources/views/contratos/edit.blade.php` con etiquetas "Monto de Garantía Entregada"/"Fecha de Entrega de Garantía" (depende de T007) — implementado como parcial reutilizable `resources/views/contratos/partials/garantia-contrato.blade.php`, incluida desde ambas vistas
- [X] T009 [US1] Agregar sección de garantía (o mensaje "Sin garantía registrada") a `resources/views/contratos/show.blade.php` con tipografía ≥18px y alto contraste (depende de T007)

**Checkpoint**: User Story 1 funcional y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Consulta de la Garantía desde el Detalle del Contrato (Priority: P2)

**Goal**: Mostrar de forma clara y destacada la garantía en el detalle del contrato.

**Independent Test**: Consultar el detalle de un contrato con garantía registrada y comprobar que monto, fecha y medio se muestren con tipografía ≥18px y alto contraste, junto a renta y fechas de vigencia.

### Tests for User Story 2 ⚠️

- [X] T010 [P] [US2] Prueba de feature de `ContratoController@show` (sección de garantía visible con monto/fecha/medio) en `tests/Feature/ContratoControllerTest.php` (depende de T006)

### Implementation for User Story 2

- [X] T011 [US2] Ajustar la disposición de `resources/views/contratos/show.blade.php` para que la sección de garantía (T009) quede destacada junto a costo de renta y fechas de vigencia, sin requerir navegación adicional (depende de T009)

**Checkpoint**: User Story 1 y 2 funcionan de forma independiente

---

## Phase 5: User Story 3 - Registro de la Devolución o Retención de la Garantía al Finalizar el Contrato (Priority: P2)

**Goal**: Registrar la resolución de la garantía (devuelto/retenido/motivo), validando el cuadre exacto de montos y exigiendo confirmación para corregir una resolución ya registrada.

**Independent Test**: Registrar una resolución con "S/ 1200.00" devueltos y "S/ 300.00" retenidos con motivo, sobre una garantía de "S/ 1500.00"; comprobar el bloqueo si la suma no cuadra o si falta el motivo con retención > 0; comprobar la confirmación exigida al corregir una resolución ya "Resuelta".

### Tests for User Story 3 ⚠️

- [X] T012 [P] [US3] Prueba unitaria de `ServicioResolucionGarantiaContrato` (cuadre exacto con `bccomp`, motivo obligatorio con retención > 0, confirmación exigida al re-editar) en `tests/Unit/ServicioResolucionGarantiaContratoTest.php`
- [X] T013 [P] [US3] Prueba de feature de `ContratoController@registrarResolucionGarantia` (happy path total/parcial, rechazo por discrepancia de montos, rechazo sin motivo, rechazo sin confirmación al corregir) en `tests/Feature/ContratoControllerTest.php` — patrón real 302+`assertSessionHasErrors`, no 422 literal (ver Notes)

### Implementation for User Story 3

- [X] T014 [P] [US3] Crear `SolicitudRegistrarResolucionGarantia` (`monto_devuelto_garantia`/`monto_retenido_garantia` numeric ≥0, `motivo_retencion_garantia` required_if retenido>0, `confirmado` boolean) en `app/Http/Requests/SolicitudRegistrarResolucionGarantia.php` (depende de T003)
- [X] T015 [US3] Implementar `ServicioResolucionGarantiaContrato` (`registrar()`: cuadre exacto con `bccomp`, motivo obligatorio, exige `confirmado` si `garantiaResuelta()`, marca `estado_garantia='resuelta'` y `fecha_resolucion_garantia`, `DB::transaction`) en `app/Services/ServicioResolucionGarantiaContrato.php` (depende de T004, T014; ver `research.md` §2-3)
- [X] T016 [US3] Implementar `ContratoController@registrarResolucionGarantia` en `app/Http/Controllers/ContratoController.php` (depende de T015)
- [X] T017 [US3] Registrar ruta `POST /contratos/{contrato}/garantia/resolucion` en `routes/web.php` (depende de T016)
- [X] T018 [US3] Agregar formulario de resolución de garantía (con modal de confirmación explícita para corregir una ya "Resuelta") a `resources/views/contratos/show.blade.php`, disponible solo si `tieneGarantia()` (depende de T009, T017)

**Checkpoint**: Las 3 historias de usuario funcionan de forma independiente

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Mejoras que afectan a todas las historias de usuario

- [X] T019 [P] Auditoría de accesibilidad (contraste, tipografía ≥18px, botones ≥48x48px) en `resources/views/contratos/create.blade.php`, `edit.blade.php` y `show.blade.php` — reutilizan las clases `btn-senior-*`/`campo-senior`/`etiqueta-senior` ya auditadas
- [X] T020 [P] Revisión de seguridad: CSRF, verificación de que `registrarResolucionGarantia` nunca persista sin validar el cuadre exacto ni el motivo obligatorio — confirmado en `ServicioResolucionGarantiaContrato::registrar()` (bccomp + motivo obligatorio dentro de `DB::transaction`)
- [X] T021 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 6) de extremo a extremo — verificado manualmente en navegador (registro de garantía + representante en la creación del contrato, resolución con retención parcial y motivo, transición a "Resuelta"); datos de prueba limpiados después
- [X] T022 [P] Ejecutar `php artisan test --filter=Contrato` y `--filter=ServicioResolucionGarantiaContrato`, confirmando que toda la suite pasa — 21/21 tests de Garantía pasan; suite completa del proyecto: 191/191

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Ya completado
- **Foundational (Phase 2)**: BLOQUEA todas las historias de usuario
- **User Stories (Phase 3-5)**: Todas dependen de Foundational; US1 y US2 están estrechamente relacionadas (registro y visualización); US3 (resolución) depende de que exista `tieneGarantia()` de Foundational pero es independiente de US1/US2 en su lógica de validación
- **Polish (Phase 6)**: Depende de que las historias deseadas estén completas

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Foundational — sin dependencias de otras historias
- **User Story 2 (P2)**: Reutiliza la sección de garantía creada en US1 (T009), pero es comprobable de forma independiente (solo disposición/visibilidad)
- **User Story 3 (P2)**: Puede iniciar tras Foundational — depende de `tieneGarantia()`, comprobable de forma independiente de US1/US2

### Within Each User Story

- Las pruebas se escriben antes de la implementación y deben fallar inicialmente
- Migración/modelo antes que Form Request/Service; Service antes que controlador; controlador antes que rutas; rutas antes que vistas

### Parallel Opportunities

- T003-T004 de Foundational son secuenciales sobre el mismo archivo; una vez completado Foundational, US1/US2 y US3 pueden avanzar en paralelo por distintos desarrolladores

---

## Parallel Example: User Story 3

```bash
Task: "Prueba unitaria de ServicioResolucionGarantiaContrato en tests/Unit/ServicioResolucionGarantiaContratoTest.php"
Task: "Prueba de feature de ContratoController@registrarResolucionGarantia en tests/Feature/ContratoControllerTest.php"
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

- Nota de implementación: el patrón real del proyecto para errores de validación/negocio en esta app Blade server-rendered es redirect 302 + `withErrors()`/`assertSessionHasErrors()`, no códigos HTTP 422 literales (igual que en 001-008).
- Esta spec se completó en dos sesiones: el trabajo de código (migración, modelo, servicio, controlador, rutas, vistas, tests) se hizo íntegramente antes de un corte de sesión; esta segunda sesión solo verificó lo ya construido (suite completa 191/191, prueba manual en navegador de las 3 historias de usuario) y actualizó los checkboxes de este archivo para reflejar el estado real.
- [P] = archivos distintos, sin dependencias pendientes
- Verificar que las pruebas fallan antes de implementar
- Hacer commit tras cada tarea o grupo lógico de tareas
- Evitar: tareas vagas, conflictos de archivo simultáneos, dependencias que rompan la independencia entre historias
