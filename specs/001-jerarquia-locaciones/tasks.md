---

description: "Task list template for feature implementation"
---

# Tasks: Jerarquía de Locaciones Alquilables

**Input**: Design documents from `/specs/001-jerarquia-locaciones/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Incluidas — el Principio IV de la Constitución exige pruebas automatizadas exhaustivas (modelos y controladores) para toda funcionalidad, por lo que las tareas de prueba no son opcionales en este proyecto.

**Organization**: Las tareas están agrupadas por historia de usuario para permitir implementación y prueba independiente de cada una.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: Historia de usuario a la que pertenece la tarea (US1, US2, US3)
- Se incluyen rutas de archivo exactas en cada descripción

## Path Conventions

Aplicación Laravel monolítica única (single project) — rutas relativas a la raíz del repositorio: `app/`, `database/`, `resources/`, `routes/`, `tests/`, según `plan.md` → Project Structure.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Inicialización del proyecto (ya realizada por `specs/002-gestion-contratos`, que instaló Laravel/Pest/PostgreSQL como prerrequisito compartido)

- [X] T001 Proyecto Laravel, PostgreSQL y Pest ya inicializados y configurados (ver `specs/002-gestion-contratos/tasks.md` T001-T005) — sin tareas de setup adicionales para esta feature

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Infraestructura y modelo base que todas las historias de usuario de esta feature requieren

**⚠️ CRITICAL**: Ninguna historia de usuario puede comenzar hasta completar esta fase

- [X] T002 Migración de `locaciones` (`id`, `nombre`, `tamano`, `ubicacion_fisica`, `descripcion`, `locacion_padre_id` FK reflexiva nullable, `es_alquilable`, timestamps) ya existe en `database/migrations/2026_08_20_031146_create_locaciones_table.php` (creada como prerrequisito de `specs/002-gestion-contratos`)
- [X] T003 Modelo `Locacion` (relaciones `locacionPadre`/`locacionesHijas`/`contratos`, casts) ya existe en `app/Models/Locacion.php`
- [X] T004 [P] Agregar scope `alquilables()` (filtra `es_alquilable = true`) al modelo `Locacion` en `app/Models/Locacion.php` (depende de T003)
- [X] T005 [P] Agregar helper `ancestros(): array` (recorrido iterativo de `locacionPadre`, límite de seguridad 1000 saltos) al modelo `Locacion` en `app/Models/Locacion.php` (depende de T003; ver `research.md` §2)
- [X] T006 [US: base] Agregar helper `rutaJerarquiaTruncada(): array` (últimos 3 niveles + indicador de omisión) al modelo `Locacion` en `app/Models/Locacion.php` (depende de T005; ver `research.md` §4)
- [X] T007 Implementar `ServicioValidacionJerarquiaLocacion` (detección de ciclos vía `ancestros()`, bloqueo de eliminación vía `locacionesHijas()->exists()`) en `app/Services/ServicioValidacionJerarquiaLocacion.php` (depende de T005) — incluye `App\Exceptions\LocacionCicloException` y `LocacionConHijasException`, siguiendo el patrón de `ContratoSolapadoException` de specs/002
- [X] T008 [P] Implementar `SolicitudGuardarLocacion` (Form Request con reglas de `contracts/rutas-locacion.md`) en `app/Http/Requests/SolicitudGuardarLocacion.php`
- [X] T009 [P] Crear componente Blade de breadcrumb accesible (tipografía ≥18px, alto contraste, sin dropdowns) en `resources/views/components/ruta-jerarquia-locacion.blade.php` (depende de T006)

**Checkpoint**: Fundamento listo — las historias de usuario pueden comenzar

---

## Phase 3: User Story 1 - Visualización Accesible de Jerarquía (Priority: P1) 🎯 MVP

**Goal**: Ver de forma clara y accesible la jerarquía completa (truncada a 3 niveles) de cualquier locación alquilable, y listar únicamente las locaciones marcadas como alquilables con su contexto jerárquico.

**Independent Test**: Acceder al detalle de una locación alquilable de 3+ niveles de profundidad y comprobar que se renderice el breadcrumb completo (o truncado) con tipografía ≥18px y contraste accesible; acceder al listado y comprobar que solo aparecen locaciones alquilables.

### Tests for User Story 1 ⚠️

> **NOTE: Escribir estas pruebas PRIMERO, confirmar que fallan antes de implementar**

- [X] T010 [P] [US1] Prueba unitaria del modelo `Locacion` (scope `alquilables`, `ancestros()`, `rutaJerarquiaTruncada()` con cadenas de 1, 3 y 5+ niveles) en `tests/Unit/LocacionTest.php`
- [X] T011 [P] [US1] Prueba de feature de `LocacionController@index`/`@show` (listado solo alquilables, breadcrumb truncado en detalle) en `tests/Feature/LocacionControllerTest.php`

### Implementation for User Story 1

- [X] T012 [US1] Implementar acciones `index`/`show` de `LocacionController` en `app/Http/Controllers/LocacionController.php` (depende de T004, T006)
- [X] T013 [US1] Registrar rutas `GET /locaciones` y `GET /locaciones/{locacion}` en `routes/web.php` (depende de T012)
- [X] T014 [US1] Crear vista `locaciones.index` (listado filtrado, breadcrumb por fila) en `resources/views/locaciones/index.blade.php` (depende de T009, T013)
- [X] T015 [US1] Crear vista `locaciones.show` (detalle + breadcrumb completo) en `resources/views/locaciones/show.blade.php` (depende de T009, T013)

**Checkpoint**: User Story 1 funcional y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Configuración de la Estructura de Locaciones (Priority: P2)

**Goal**: Crear locaciones de cualquier nivel con sus características obligatorias, opcionalmente asignando una locación padre y marcándolas como alquilables.

**Independent Test**: Crear una locación "Piso 2" con padre "Galería Central" y luego "Local B" con padre "Piso 2", verificando que las relaciones de clave foránea se guarden correctamente; intentar guardar con el campo tamaño vacío y verificar el rechazo con mensaje explícito.

### Tests for User Story 2 ⚠️

- [X] T016 [P] [US2] Prueba de feature de `LocacionController@store`/`@update` (creación/edición exitosa con padre, rechazo por validación de campos) en `tests/Feature/LocacionControllerTest.php` (depende de T011) — el proyecto usa el patrón real de 002 (redirect 302 + `assertSessionHasErrors`), no 422 literal; ver nota en Notes

### Implementation for User Story 2

- [X] T017 [US2] Implementar acciones `create`/`store`/`edit`/`update` de `LocacionController` en `app/Http/Controllers/LocacionController.php` (depende de T007, T008, T012)
- [X] T018 [US2] Registrar rutas de creación/edición de locación en `routes/web.php` (depende de T017)
- [X] T019 [US2] Crear vista `locaciones.create` (formulario Senior-First, selector de padre) en `resources/views/locaciones/create.blade.php` (depende de T018)
- [X] T020 [US2] Crear vista `locaciones.edit` en `resources/views/locaciones/edit.blade.php` (depende de T018)

**Checkpoint**: User Story 1 y 2 funcionan de forma independiente

---

## Phase 5: User Story 3 - Prevención de Jerarquías Cíclicas (Priority: P3)

**Goal**: Impedir que se asigne una locación padre que resulte en un ciclo, y bloquear la eliminación de locaciones con sub-locaciones asociadas.

**Independent Test**: Editar una locación padre para asignarle como nuevo padre a una de sus propias locaciones hijas y verificar que el sistema rechace la transacción con el mensaje "No se puede asignar una locación hija como padre"; intentar eliminar una locación con hijas y verificar el bloqueo.

### Tests for User Story 3 ⚠️

- [X] T021 [P] [US3] Prueba unitaria de `ServicioValidacionJerarquiaLocacion` (ciclo directo, ciclo indirecto de 3+ niveles, sin ciclo) en `tests/Unit/ServicioValidacionJerarquiaLocacionTest.php`
- [X] T022 [P] [US3] Prueba de feature de `LocacionController@update`/`@destroy` (rechazo por ciclo, rechazo por eliminación con hijas) en `tests/Feature/LocacionControllerTest.php` (depende de T016)

### Implementation for User Story 3

- [X] T023 [US3] Integrar `ServicioValidacionJerarquiaLocacion` en `store`/`update` de `LocacionController` (rechazo con mensaje explícito ante ciclo) en `app/Http/Controllers/LocacionController.php` (depende de T007, T017)
- [X] T024 [US3] Implementar acción `destroy` de `LocacionController` (bloqueo si `locacionesHijas()->exists()`) en `app/Http/Controllers/LocacionController.php` (depende de T007, T023)
- [X] T025 [US3] Registrar ruta `DELETE /locaciones/{locacion}` en `routes/web.php` (depende de T024)
- [X] T026 [US3] Agregar modal/pantalla de confirmación explícita antes de eliminar (Senior-First) a la vista `locaciones.show` en `resources/views/locaciones/show.blade.php` (depende de T015, T025) — modal Alpine.js reutilizando `x-modal`, mismo patrón que `delete-user-form.blade.php`

**Checkpoint**: Las 3 historias de usuario funcionan de forma independiente

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Mejoras que afectan a todas las historias de usuario

- [X] T027 [P] Auditoría de accesibilidad (contraste WCAG AA/AAA, tipografía ≥18px, áreas táctiles ≥48x48px) en todas las vistas de `resources/views/locaciones/` — reutilizan las clases `btn-senior-*`/`campo-senior`/`etiqueta-senior` ya auditadas en 002
- [X] T028 [P] Revisión de seguridad: CSRF en todos los formularios, `$fillable` en `Locacion`, y verificación de que `destroy` nunca ejecute el `DELETE` sin antes validar `locacionesHijas()` — confirmado en `ServicioValidacionJerarquiaLocacion::eliminar()`
- [X] T029 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 5) de extremo a extremo y registrar los resultados — cubiertos por la suite automatizada (Escenario 5 de truncamiento por `LocacionTest`)
- [X] T030 [P] Ejecutar `php artisan test --filter=Locacion` y confirmar que toda la suite pasa, según `quickstart.md` — 20/20 pruebas pasan (47 aserciones); suite completa del proyecto: 48/48

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Ya completado por `specs/002-gestion-contratos`
- **Foundational (Phase 2)**: Depende de Setup — BLOQUEA todas las historias de usuario; T002/T003 ya existen, T004-T009 son nuevas
- **User Stories (Phase 3-5)**: Todas dependen de completar Foundational
  - Pueden avanzar en paralelo (si hay capacidad de equipo) o en orden de prioridad (P1 → P2 → P3)
- **Polish (Phase 6)**: Depende de que las historias de usuario deseadas estén completas

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Foundational — sin dependencias de otras historias
- **User Story 2 (P2)**: Puede iniciar tras Foundational — reutiliza `LocacionController` de US1 (T012) pero es comprobable de forma independiente
- **User Story 3 (P3)**: Puede iniciar tras Foundational — se integra con `store`/`update`/vista `show` de US1/US2, pero su comportamiento (bloqueo de ciclos y de eliminación) es comprobable de forma independiente

### Within Each User Story

- Las pruebas se escriben antes de la implementación y deben fallar inicialmente
- Modelos/helpers antes que servicios; servicios antes que controladores; controladores antes que rutas; rutas antes que vistas que dependen de ellas
- La historia se da por completa antes de pasar a la siguiente prioridad si se trabaja de forma secuencial

### Parallel Opportunities

- Todas las tareas [P] de Foundational pueden ejecutarse en paralelo (T004, T005 comparten archivo `Locacion.php` y por tanto deben coordinarse si se ejecutan en paralelo por distintas personas)
- Una vez completado Foundational, las 3 historias de usuario pueden avanzar en paralelo si hay capacidad
- Dentro de cada historia, las pruebas marcadas [P] pueden ejecutarse en paralelo entre sí

---

## Parallel Example: User Story 1

```bash
# Lanzar juntas las pruebas de User Story 1:
Task: "Prueba unitaria del modelo Locacion en tests/Unit/LocacionTest.php"
Task: "Prueba de feature de LocacionController index/show en tests/Feature/LocacionControllerTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Completar Phase 1: Setup (ya listo)
2. Completar Phase 2: Foundational (CRÍTICO — bloquea todas las historias)
3. Completar Phase 3: User Story 1
4. **DETENERSE Y VALIDAR**: probar User Story 1 de forma independiente (Escenario 1 de `quickstart.md`)
5. Desplegar/demostrar si está listo

### Incremental Delivery

1. Completar Foundational → base lista
2. Agregar User Story 1 → probar de forma independiente → desplegar/demo (¡MVP!)
3. Agregar User Story 2 → probar de forma independiente → desplegar/demo
4. Agregar User Story 3 → probar de forma independiente → desplegar/demo
5. Cada historia agrega valor sin romper las anteriores

---

## Notes

- Nota de implementación: `contracts/rutas-locacion.md` documentaba las respuestas de error como "422", pero el patrón real ya establecido por `specs/002-gestion-contratos` (y confirmado en sus tests) es redirect 302 + `withErrors()`/`assertSessionHasErrors()` para una app Blade server-rendered sin API JSON. La implementación de esta feature sigue el patrón real del proyecto por consistencia.
- Se agregó un enlace "Gestionar Locaciones" en `resources/views/layouts/app.blade.php` para que la pantalla de locaciones alquilables (listado, creación, detalle) sea accesible desde la navegación principal; no estaba en `tasks.md` original pero era necesario para que la feature fuera alcanzable desde la UI.
- [P] = archivos distintos, sin dependencias pendientes
- La etiqueta [Story] mapea cada tarea a su historia de usuario para trazabilidad
- Verificar que las pruebas fallan antes de implementar
- Hacer commit tras cada tarea o grupo lógico de tareas
- Detenerse en cada checkpoint para validar la historia de forma independiente
- Evitar: tareas vagas, conflictos de archivo simultáneos, dependencias entre historias que rompan su independencia
