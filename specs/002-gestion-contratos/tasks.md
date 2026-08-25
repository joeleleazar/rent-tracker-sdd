---

description: "Task list template for feature implementation"
---

# Tasks: Gestión de Contratos de Locación

**Input**: Design documents from `/specs/002-gestion-contratos/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Incluidas — el Principio IV de la Constitución exige pruebas automatizadas exhaustivas (modelos y controladores) para toda funcionalidad, por lo que las tareas de prueba no son opcionales en este proyecto.

**Organization**: Las tareas están agrupadas por historia de usuario para permitir implementación y prueba independiente de cada una.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: Historia de usuario a la que pertenece la tarea (US1, US2, US3)
- Se incluyen rutas de archivo exactas en cada descripción

## Path Conventions

Aplicación Laravel monolítica única (single project) — rutas relativas a la raíz del repositorio: `app/`, `database/`, `resources/`, `routes/`, `tests/`, `storage/`, según `plan.md` → Project Structure.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Inicialización del proyecto Laravel (aún no existe código en el repositorio)

- [X] T001 Inicializar el proyecto Laravel en la raíz del repositorio (`composer create-project laravel/laravel .` con la última versión estable) según `plan.md` → Project Structure — Laravel 13.26.1 con PHP 8.4.12 (Herd)
- [X] T002 Configurar `.env` para conexión PostgreSQL (`DB_CONNECTION=pgsql`) y establecer `SESSION_DRIVER`, `CACHE_STORE` y `QUEUE_CONNECTION` en `database` (research.md §2, sin Redis en shared hosting)
- [X] T003 [P] Instalar y configurar Pest (`composer require pestphp/pest pestphp/pest-plugin-laravel --dev && vendor/bin/pest --init`) — Pest v4.7.8, `RefreshDatabase` habilitado en `tests/Pest.php`
- [X] T004 [P] Configurar build de Vite con Alpine.js para interactividad ligera, confirmando que no se requiere Node.js en el servidor de producción (research.md §2)
- [X] T005 [P] Crear la carpeta `storage/app/private/contratos/` (disco `local` por defecto) para archivos de contrato y confirmar que NO se genera el symlink público `public/storage` (research.md §3)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Infraestructura y modelos base que TODAS las historias de usuario requieren

**⚠️ CRITICAL**: Ninguna historia de usuario puede comenzar hasta completar esta fase

- [X] T006 Crear migración de `locaciones` (`id`, `nombre`, `tamaño`, `ubicacion_fisica`, `descripcion`, `locacion_padre_id` FK reflexiva nullable, `es_alquilable`, timestamps) en `database/migrations/` — prerrequisito técnico de `specs/001-jerarquia-locaciones`, necesario para la FK de `Contrato`
- [X] T007 [P] Crear modelo `Locacion` (relación reflexiva padre/hijos, cast de `es_alquilable`) en `app/Models/Locacion.php` (depende de T006)
- [X] T008 [P] Crear migración de `inquilinos` (`id`, `nombre`, timestamps) en `database/migrations/`
- [X] T009 [P] Crear modelo `Inquilino` en `app/Models/Inquilino.php` (depende de T008)
- [X] T010 Crear migración de `contratos` (`locacion_id`, `inquilino_id`, `fecha_inicio`, `fecha_fin`, `monto_renta` NUMERIC(12,2), `estado` con `CHECK`, índices `(locacion_id, fecha_inicio, fecha_fin)` y `(locacion_id, estado)`) en `database/migrations/` (depende de T006, T008)
- [X] T011 Crear migración de `documentos_contrato` (`contrato_id` FK `onDelete('cascade')`, `nombre_archivo`, `ruta_archivo`, `tipo_archivo`, `secuencia`, timestamps) en `database/migrations/` (depende de T010) — migraciones ejecutadas contra PostgreSQL 17 (`rent_tracker_dev`), CHECK constraint y NUMERIC(12,2) verificados
- [X] T012 Configurar autenticación de sesión para el rol Administrador y el grupo de middleware `auth` en `routes/web.php` / `bootstrap/app.php` — Laravel Breeze (stack blade): controladores/vistas publicados, `routes/auth.php` creado manualmente tras fallo del subproceso composer interno de `breeze:install` (ver nota abajo)
- [X] T013 [P] Crear layout Blade base accesible (tokens de contraste WCAG AA, navegación plana) en `resources/views/layouts/app.blade.php` — más `resources/css/app.css` (utilidades `.btn-senior-*`/`.campo-senior`, ya retiradas en `specs/010`) y componentes base de Breeze (`primary-button`, `secondary-button`, `danger-button`, `text-input`, `input-label`, `input-error`) migrados a estas clases; componentes de dropdown eliminados en ese momento
- [X] T014 [P] Crear componente Blade de mensaje/alerta persistente y de alto contraste reutilizable en `resources/views/components/mensaje-alerta.blade.php`

> **Nota de implementación**: el sistema tiene PHP 8.0.30 (XAMPP) en el PATH del sistema y PHP 8.4.12 (Laravel Herd); Laravel 13 requiere PHP ^8.3, así que todo el proyecto se ejecuta con Herd. Se detectó que la base de datos `rent_tracker` ya existía con tablas de un prototipo previo no relacionado con esta implementación; por indicación del usuario se usan en su lugar `rent_tracker_dev` (desarrollo) y `rent_tracker_dev_testing` (pruebas), sin tocar la base original.

**Checkpoint**: Fundamento listo — las historias de usuario pueden comenzar

---

## Phase 3: User Story 1 - Registro de Contrato y Regla de No Solapamiento (Priority: P1) 🎯 MVP

**Goal**: Registrar un contrato por locación (inquilino, fechas, monto, estado) impidiendo que se guarde si se solapa con otro contrato activo/programado de la misma locación.

**Independent Test**: Registrar un contrato para una locación con un rango de fechas ya ocupado por otro contrato y verificar que el sistema rechace la operación con un mensaje explícito; luego registrar un contrato secuencial (sin solapamiento) y verificar que se guarde con éxito.

### Tests for User Story 1 ⚠️

> **NOTE: Escribir estas pruebas PRIMERO, confirmar que fallan antes de implementar**

- [X] T015 [P] [US1] Prueba unitaria del modelo `Contrato` (relaciones, cast `monto_renta`, valores de `estado`) en `tests/Unit/ContratoTest.php`
- [X] T016 [P] [US1] Prueba unitaria de `ServicioValidacionSolapamientoContrato` (casos con solapamiento, sin solapamiento, secuenciales) en `tests/Unit/ServicioValidacionSolapamientoContratoTest.php`
- [X] T017 [P] [US1] Prueba de feature de `ContratoController` (creación/edición exitosa, rechazo 422 por solapamiento con mensaje persistente) en `tests/Feature/ContratoControllerTest.php`

### Implementation for User Story 1

- [X] T018 [US1] Crear modelo `Contrato` (casts, relaciones a `Locacion`/`Inquilino`) en `app/Models/Contrato.php` (depende de T007, T009, T010)
- [X] T019 [US1] Implementar `ServicioValidacionSolapamientoContrato` (`DB::transaction` + `lockForUpdate()` + consulta de solapamiento excluyendo estado `rescindido`) en `app/Services/ServicioValidacionSolapamientoContrato.php` (depende de T018)
- [X] T020 [US1] Implementar `SolicitudGuardarContrato` (Form Request con reglas de `contracts/rutas-contrato.md`) en `app/Http/Requests/SolicitudGuardarContrato.php`
- [X] T021 [US1] Implementar acciones `create`/`store`/`show`/`edit`/`update` de `ContratoController` en `app/Http/Controllers/ContratoController.php` (depende de T019, T020)
- [X] T022 [US1] Registrar rutas de creación/edición/detalle de contrato en `routes/web.php` (depende de T021)
- [X] T023 [US1] Crear vista `contratos.create` (formulario accesible) en `resources/views/contratos/create.blade.php`
- [X] T024 [US1] Crear vista `contratos.edit` (incluye rescisión manual del `estado`) en `resources/views/contratos/edit.blade.php`
- [X] T025 [US1] Crear vista `contratos.show` (detalle: locación, inquilino, fechas, monto, estado) en `resources/views/contratos/show.blade.php`

**Checkpoint**: User Story 1 funcional y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Carga Accesible del Contrato Notariado (Priority: P2)

**Goal**: Adjuntar al contrato un PDF único (≤15MB) o hasta 10 fotos (≤5MB c/u) del contrato firmado notarialmente, mediante controles táctiles grandes y accesibles.

**Independent Test**: Desde el detalle de un contrato, subir un PDF y comprobar previsualización/indicador de éxito; subir varias fotos y comprobar la galería; confirmar que los archivos NO son accesibles sin autenticación.

### Tests for User Story 2 ⚠️

- [X] T026 [P] [US2] Prueba unitaria del modelo `DocumentoContrato` (relación, cascada) en `tests/Unit/DocumentoContratoTest.php`
- [X] T027 [P] [US2] Prueba de feature de `DocumentoContratoController` (subida válida de PDF/imágenes, rechazo por tamaño/tipo/mezcla, transmisión solo autenticada, borrado con confirmación) en `tests/Feature/DocumentoContratoControllerTest.php`

### Implementation for User Story 2

- [X] T028 [US2] Crear modelo `DocumentoContrato` en `app/Models/DocumentoContrato.php` (depende de T011, T018)
- [X] T029 [US2] Implementar `SolicitudSubirDocumentoContrato` (validación mimes/tamaño/XOR PDF-vs-imágenes) en `app/Http/Requests/SolicitudSubirDocumentoContrato.php`
- [X] T030 [US2] Implementar `store`/`show`/`destroy` de `DocumentoContratoController` (almacenamiento en `storage/app/private/contratos/{contrato}/` (disco `local`), transmisión autenticada, borrado transaccional archivo+registro) en `app/Http/Controllers/DocumentoContratoController.php` (depende de T028, T029)
- [X] T031 [US2] Registrar rutas de subida/visualización/borrado de documentos en `routes/web.php` (depende de T030)
- [X] T032 [US2] Agregar controles de carga ("Seleccionar PDF del Contrato", "Subir Foto de Página", ≥48x48px) a la vista `contratos.show` en `resources/views/contratos/show.blade.php` (depende de T025, T031)
- [X] T033 [US2] Crear parcial de galería/previsualización de documentos (miniaturas, zoom simple, modal de confirmación de borrado) en `resources/views/contratos/partials/galeria-documentos.blade.php` (depende de T032)

**Checkpoint**: User Story 1 y 2 funcionan de forma independiente

---

## Phase 5: User Story 3 - Consulta de Historial de Contratos Secuenciales (Priority: P3)

**Goal**: Ver el listado histórico de contratos de una locación en orden cronológico, destacando el contrato activo.

**Independent Test**: Con 3 contratos históricos (vencido, activo, futuro) de una misma locación, consultar el historial y verificar el orden cronológico inverso y la etiqueta destacada del contrato activo.

### Tests for User Story 3 ⚠️

- [X] T034 [P] [US3] Prueba de feature de `ContratoController@index` (orden cronológico, etiqueta de contrato activo) en `tests/Feature/ContratoHistorialTest.php`

### Implementation for User Story 3

- [X] T035 [US3] Agregar scope de historial cronológico al modelo `Contrato` en `app/Models/Contrato.php` (depende de T018)
- [X] T036 [US3] Implementar acción `index` (historial cronológico) en `ContratoController` en `app/Http/Controllers/ContratoController.php` (depende de T021, T035)
- [X] T037 [US3] Registrar ruta de historial de contratos por locación en `routes/web.php` (depende de T036)
- [X] T038 [US3] Crear vista `contratos.index` (listado cronológico, etiqueta de contrato activo ≥18px y alto contraste) en `resources/views/contratos/index.blade.php` (depende de T037)

**Checkpoint**: Las 3 historias de usuario funcionan de forma independiente

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Mejoras que afectan a todas las historias de usuario

- [X] T039 [P] Auditoría de accesibilidad (contraste WCAG AA/AAA, tipografía ≥18px, áreas táctiles ≥48x48px) en todas las vistas de `resources/views/contratos/`
- [X] T040 [P] Revisión de seguridad: CSRF en todos los formularios, `$fillable` en `Contrato`/`DocumentoContrato`/`Inquilino`/`Locacion`, y rechazo de acceso no autenticado en la ruta de transmisión de documentos
- [X] T041 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 3) de extremo a extremo y registrar los resultados
- [X] T042 [P] Ejecutar `php artisan test --filter=Contrato` y confirmar que toda la suite pasa, según `quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sin dependencias — puede iniciar de inmediato
- **Foundational (Phase 2)**: Depende de Setup — BLOQUEA todas las historias de usuario
- **User Stories (Phase 3-5)**: Todas dependen de completar Foundational
  - Pueden avanzar en paralelo (si hay capacidad de equipo) o en orden de prioridad (P1 → P2 → P3)
- **Polish (Phase 6)**: Depende de que las historias de usuario deseadas estén completas

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Foundational — sin dependencias de otras historias
- **User Story 2 (P2)**: Puede iniciar tras Foundational — se integra visualmente con la vista `contratos.show` de US1 (T025) pero es comprobable de forma independiente
- **User Story 3 (P3)**: Puede iniciar tras Foundational — reutiliza el modelo `Contrato` de US1 (T018) y el controlador (T021), pero su comportamiento es comprobable de forma independiente

### Within Each User Story

- Las pruebas se escriben antes de la implementación y deben fallar inicialmente
- Modelos antes que servicios; servicios antes que controladores; controladores antes que rutas; rutas antes que vistas que dependen de ellas
- La historia se da por completa antes de pasar a la siguiente prioridad si se trabaja de forma secuencial

### Parallel Opportunities

- Todas las tareas [P] de Setup pueden ejecutarse en paralelo
- Todas las tareas [P] de Foundational pueden ejecutarse en paralelo (respetando las dependencias explícitas de migraciones)
- Una vez completado Foundational, las 3 historias de usuario pueden avanzar en paralelo si hay capacidad
- Dentro de cada historia, las pruebas marcadas [P] pueden ejecutarse en paralelo entre sí

---

## Parallel Example: User Story 1

```bash
# Lanzar juntas las pruebas de User Story 1:
Task: "Prueba unitaria del modelo Contrato en tests/Unit/ContratoTest.php"
Task: "Prueba unitaria de ServicioValidacionSolapamientoContrato en tests/Unit/ServicioValidacionSolapamientoContratoTest.php"
Task: "Prueba de feature de ContratoController en tests/Feature/ContratoControllerTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Completar Phase 1: Setup
2. Completar Phase 2: Foundational (CRÍTICO — bloquea todas las historias)
3. Completar Phase 3: User Story 1
4. **DETENERSE Y VALIDAR**: probar User Story 1 de forma independiente (Escenario 1 de `quickstart.md`)
5. Desplegar/demostrar si está listo

### Incremental Delivery

1. Completar Setup + Foundational → base lista
2. Agregar User Story 1 → probar de forma independiente → desplegar/demo (¡MVP!)
3. Agregar User Story 2 → probar de forma independiente → desplegar/demo
4. Agregar User Story 3 → probar de forma independiente → desplegar/demo
5. Cada historia agrega valor sin romper las anteriores

---

## Notes

- [P] = archivos distintos, sin dependencias pendientes
- La etiqueta [Story] mapea cada tarea a su historia de usuario para trazabilidad
- Verificar que las pruebas fallan antes de implementar
- Hacer commit tras cada tarea o grupo lógico de tareas
- Detenerse en cada checkpoint para validar la historia de forma independiente
- Evitar: tareas vagas, conflictos de archivo simultáneos, dependencias entre historias que rompan su independencia
