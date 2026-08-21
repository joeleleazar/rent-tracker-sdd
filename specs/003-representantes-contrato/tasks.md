---

description: "Task list template for feature implementation"
---

# Tasks: Representantes de Contrato

**Input**: Design documents from `/specs/003-representantes-contrato/`

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

**Purpose**: Entidad `Representante` y tabla pivote que todas las historias de usuario requieren

**⚠️ CRITICAL**: Ninguna historia de usuario puede comenzar hasta completar esta fase

- [X] T002 [P] Crear migración de `representantes` (`id`, `apellidos`, `nombres`, `dni` UNIQUE, `fecha_nacimiento`, timestamps) en `database/migrations/`
- [X] T003 [P] Crear migración de `contrato_representante` (`contrato_id` FK `onDelete('cascade')`, `representante_id` FK `restrictOnDelete()`, `es_principal` boolean, índice único compuesto `(contrato_id, representante_id)`, timestamps) en `database/migrations/` (depende de T002)
- [X] T004 [P] Crear modelo `Representante` (sin relaciones especiales más allá del pivote implícito) en `app/Models/Representante.php` (depende de T002)
- [X] T005 Agregar relación `belongsToMany(Representante::class)->using()->withPivot('es_principal')->withTimestamps()` a `Contrato` en `app/Models/Contrato.php` (depende de T003, T004; no modifica `inquilino_id`, ver `research.md` §1)
- [X] T006 [P] Crear factory `RepresentanteFactory` en `database/factories/RepresentanteFactory.php` (depende de T004)
- [X] T007 Implementar `ServicioAsociacionRepresentantesContrato` (`sincronizar`, `agregar`, `quitar` dentro de `DB::transaction`; reglas: mínimo uno, exactamente un Principal, bloqueo de remoción del último) en `app/Services/ServicioAsociacionRepresentantesContrato.php` (depende de T005)
- [X] T008 [P] Implementar `SolicitudGuardarRepresentante` (DNI 8 dígitos único, mayoría de edad) en `app/Http/Requests/SolicitudGuardarRepresentante.php` (depende de T002; ver `research.md` §4)

**Checkpoint**: Fundamento listo — las historias de usuario pueden comenzar

---

## Phase 3: User Story 1 - Asignación Obligatoria de Representante al Crear Contrato (Priority: P1) 🎯 MVP

**Goal**: Impedir guardar un contrato sin al menos un representante asociado.

**Independent Test**: Intentar guardar un contrato nuevo sin representantes y verificar el bloqueo con mensaje persistente; agregar un representante y verificar que el guardado tenga éxito.

### Tests for User Story 1 ⚠️

- [X] T009 [P] [US1] Prueba unitaria de `ServicioAsociacionRepresentantesContrato` (rechazo con cero representantes, éxito con uno marcado Principal automáticamente) en `tests/Unit/ServicioAsociacionRepresentantesContratoTest.php`
- [X] T010 [P] [US1] Prueba de feature de `ContratoController@store` integrando representantes (rechazo 422 sin representantes, éxito con uno) en `tests/Feature/RepresentanteControllerTest.php`

### Implementation for User Story 1

- [X] T011 [US1] Implementar `RepresentanteController@store` (alta en el directorio global) en `app/Http/Controllers/RepresentanteController.php` (depende de T007, T008)
- [X] T012 [US1] Integrar `ServicioAsociacionRepresentantesContrato::sincronizar()` en `ContratoController@store`/`@update` (de `specs/002`) en `app/Http/Controllers/ContratoController.php` (depende de T007)
- [X] T013 [US1] Registrar ruta `POST /representantes` en `routes/web.php` (depende de T011)
- [X] T014 [US1] Crear parcial `representantes-contrato.blade.php` (sección de representantes embebida en el formulario de contrato, mensaje de bloqueo persistente) en `resources/views/contratos/partials/representantes-contrato.blade.php` (depende de T013)
- [X] T015 [US1] Embeber la parcial de representantes en `contratos.create`/`contratos.edit` en `resources/views/contratos/create.blade.php` y `resources/views/contratos/edit.blade.php` (depende de T014)

**Checkpoint**: User Story 1 funcional y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Soporte de Múltiples Representantes con Gestión Accesible (Priority: P2)

**Goal**: Agregar y quitar múltiples representantes de un contrato con controles táctiles grandes y confirmación explícita.

**Independent Test**: Agregar dos representantes a un contrato y luego remover uno con confirmación, verificando sincronización correcta en base de datos.

### Tests for User Story 2 ⚠️

- [X] T016 [P] [US2] Prueba de feature de `ContratoController@agregarRepresentante`/`@quitarRepresentante` (alta de un segundo representante, baja con confirmación) en `tests/Feature/RepresentanteControllerTest.php` (depende de T010)

### Implementation for User Story 2

- [X] T017 [US2] Implementar `RepresentanteController@buscar` (búsqueda por DNI/apellidos, `ILIKE`) en `app/Http/Controllers/RepresentanteController.php` (depende de T011)
- [X] T018 [US2] Implementar acciones `agregarRepresentante`/`quitarRepresentante` en `ContratoController` en `app/Http/Controllers/ContratoController.php` (depende de T007, T012)
- [X] T019 [US2] Registrar rutas `GET /representantes/buscar`, `POST /contratos/{contrato}/representantes`, `DELETE /contratos/{contrato}/representantes/{representante}` en `routes/web.php` (depende de T017, T018)
- [X] T020 [US2] Agregar botón "Agregar Otro Representante" (≥48x48px) y buscador por DNI/apellidos a la parcial `representantes-contrato.blade.php` en `resources/views/contratos/partials/representantes-contrato.blade.php` (depende de T014, T019)
- [X] T021 [US2] Agregar modal de confirmación Senior-First ("Sí, quitar representante" / "No, cancelar") a la parcial de representantes en `resources/views/contratos/partials/representantes-contrato.blade.php` (depende de T020)

**Checkpoint**: User Story 1 y 2 funcionan de forma independiente

---

## Phase 5: User Story 3 - Validación de Datos Personales del Representante (Priority: P3)

**Goal**: Validar formato de DNI, mayoría de edad y campos obligatorios al registrar un representante.

**Independent Test**: Ingresar un DNI con formato incorrecto o una fecha de nacimiento de menor de edad y verificar el bloqueo con mensajes descriptivos.

### Tests for User Story 3 ⚠️

- [X] T022 [P] [US3] Prueba unitaria/feature de `SolicitudGuardarRepresentante` (DNI inválido, menor de edad, campos vacíos) en `tests/Unit/RepresentanteTest.php`

### Implementation for User Story 3

- [X] T023 [US3] Afinar mensajes de error Senior-First de `SolicitudGuardarRepresentante` ("El DNI debe tener formato válido", "El representante debe ser mayor de edad") en `app/Http/Requests/SolicitudGuardarRepresentante.php` (depende de T008, T022)
- [X] T024 [US3] Mostrar los mensajes de error junto al campo y en resumen persistente en la parcial de representantes en `resources/views/contratos/partials/representantes-contrato.blade.php` (depende de T020, T023)

**Checkpoint**: Las 3 historias de usuario funcionan de forma independiente

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Mejoras que afectan a todas las historias de usuario

- [X] T025 [P] Auditoría de accesibilidad (contraste, tipografía ≥18px, botones ≥48x48px) en `resources/views/contratos/partials/representantes-contrato.blade.php`
- [X] T026 [P] Revisión de seguridad: CSRF, `$fillable` en `Representante`, verificación de que `quitarRepresentante` nunca ejecute el `DELETE` del pivote sin antes validar el mínimo de uno
- [X] T027 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 4) de extremo a extremo
- [X] T028 [P] Ejecutar `php artisan test --filter=Representante` y confirmar que toda la suite pasa

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Ya completado
- **Foundational (Phase 2)**: BLOQUEA todas las historias de usuario
- **User Stories (Phase 3-5)**: Todas dependen de Foundational; pueden avanzar en paralelo o en orden P1 → P2 → P3
- **Polish (Phase 6)**: Depende de que las historias deseadas estén completas

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Foundational — sin dependencias de otras historias
- **User Story 2 (P2)**: Reutiliza la parcial y el Service de US1, pero es comprobable de forma independiente
- **User Story 3 (P3)**: Refina validaciones ya presentes desde T008/US1, comprobable de forma independiente

### Within Each User Story

- Las pruebas se escriben antes de la implementación y deben fallar inicialmente
- Modelos/migraciones antes que servicios; servicios antes que controladores; controladores antes que rutas; rutas antes que vistas

### Parallel Opportunities

- T002-T004, T006, T008 de Foundational pueden ejecutarse en paralelo
- Una vez completado Foundational, las 3 historias de usuario pueden avanzar en paralelo si hay capacidad

---

## Parallel Example: User Story 1

```bash
Task: "Prueba unitaria de ServicioAsociacionRepresentantesContrato en tests/Unit/ServicioAsociacionRepresentantesContratoTest.php"
Task: "Prueba de feature de RepresentanteController en tests/Feature/RepresentanteControllerTest.php"
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

- **Decisión de diseño (deviación de T012/T015)**: tras reconciliar el diseño de UI, se optó por gestionar los representantes de un contrato **ya existente** exactamente igual que los documentos del contrato (patrón ya establecido por `DocumentoContratoController`/`contratos/show.blade.php`): altas/bajas inmediatas y atómicas vía `agregarRepresentante`/`quitarRepresentante`, embebidas en `contratos/show.blade.php` (no en `contratos/edit.blade.php`). En consecuencia:
  - `SolicitudGuardarContrato` solo exige el arreglo `representantes` (mínimo 1, con `principal_index`) cuando el método HTTP es `POST` (creación). El formulario de edición (`PUT`) no reenvía representantes — permanecen sin cambios y se gestionan desde `show.blade.php`.
  - La parcial `representantes-contrato.blade.php` se embebe en `contratos/create.blade.php` (editor dinámico Alpine.js, arreglo `representantes[]`, un único punto de entrada porque el contrato aún no existe) y en `contratos/show.blade.php` (lista persistida + modales de alta/baja), en lugar de en `contratos/edit.blade.php`.
  - `ContratoController@update` no invoca `ServicioAsociacionRepresentantesContrato` porque el formulario de edición no transporta representantes; el invariante ("al menos uno", "exactamente un Principal") se mantiene por construcción ya que `quitar()` bloquea la remoción del último y `agregar()`/`sincronizar()` siempre dejan exactamente un Principal.
- **Reutilización de DNI duplicado (FR-007)**: `ServicioAsociacionRepresentantesContrato::resolverRepresentante()` usa `Representante::firstOrCreate(['dni' => ...], [...])`, de modo que un DNI ya existente en el directorio se reutiliza silenciosamente en el flujo de creación/asociación a contrato (sin error 422 de "unique"). La regla `unique:representantes,dni` solo se aplica en el endpoint independiente `POST /representantes` (`RepresentanteController@store`, alta directa en el directorio global), consistente con `contracts/rutas-representante.md`.
- **Búsqueda por DNI (`GET /representantes/buscar`)**: implementada como JSON interno (no API pública), consumida por `fetch()` desde Alpine.js tanto en el editor de creación como en el modal "Agregar Otro Representante" de `show.blade.php`, para autocompletar datos y evitar duplicados.
- [P] = archivos distintos, sin dependencias pendientes
- Verificar que las pruebas fallan antes de implementar
- Hacer commit tras cada tarea o grupo lógico de tareas
- Evitar: tareas vagas, conflictos de archivo simultáneos, dependencias que rompan la independencia entre historias
