---

description: "Task list template for feature implementation"
---

# Tasks: Inquilinos de Contrato (Inquilino Principal)

**Input**: Design documents from `/specs/003-representantes-contrato/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Incluidas — el Principio IV de la Constitución exige pruebas automatizadas exhaustivas para toda funcionalidad.

**Organization**: Las tareas están agrupadas por historia de usuario para permitir implementación y prueba independiente de cada una. Esta revisión reemplaza el `tasks.md` anterior (enteramente completado bajo el diseño de `Representante` como entidad separada); las tareas de abajo son **trabajo nuevo de consolidación/refactor** hacia el modelo unificado `Inquilino`, no una reimplementación desde cero.

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

**Purpose**: Consolidar `Representante` dentro de `Inquilino` y reemplazar `Contrato.inquilino_id` por el pivote `contrato_inquilino` (ver `research.md` §1). Ninguna historia de usuario puede avanzar hasta completar esta fase.

**⚠️ CRITICAL**: No eliminar `representantes`/`contrato_representante`/`contratos.inquilino_id` hasta verificar que la migración de datos (T003) copió todo correctamente.

- [X] T002 [P] Crear migración `add_datos_personales_to_inquilinos_table` (agrega `apellidos`, `nombres`, `dni` nullable+`UNIQUE`, `fecha_nacimiento` nullable a `inquilinos`) en `database/migrations/`
- [X] T003 [P] Crear migración `create_contrato_inquilino_table` (`contrato_id` FK `onDelete('cascade')`, `inquilino_id` FK `restrictOnDelete()`, `es_principal` boolean, índice único compuesto `(contrato_id, inquilino_id)`, timestamps) en `database/migrations/`
- [X] T004 Crear migración de datos `migrar_representantes_a_inquilinos_table` (script en `up()`): para cada `Contrato`, inserta fila en `contrato_inquilino` con `inquilino_id = contratos.inquilino_id`, `es_principal = true`; para cada `Representante`/`ContratoRepresentante` sin `Inquilino` equivalente por `dni`, completa/crea el `Inquilino` (apellidos/nombres/dni/fecha_nacimiento) y su fila de pivote, evitando duplicar el Principal ya existente por contrato (depende de T002, T003; ver `research.md` §1)
- [X] T005 Crear migración `drop_representantes_y_contrato_inquilino_id_table` (elimina `representantes`, `contrato_representante`, columna `contratos.inquilino_id` y columna `inquilinos.nombre`) en `database/migrations/` (depende de T004, ejecutar solo tras verificar la copia de datos)
- [X] T006 Actualizar modelo `Inquilino`: agregar `apellidos`/`nombres`/`dni`/`fecha_nacimiento` a `$fillable`, quitar `nombre`, reemplazar `hasMany(Contrato::class)` por `belongsToMany(Contrato::class)->using()->withPivot('es_principal')->withTimestamps()` en `app/Models/Inquilino.php` (depende de T002)
- [X] T007 Eliminar modelo `Representante` en `app/Models/Representante.php` (depende de T004)
- [X] T008 Actualizar `Contrato`: quitar `inquilino_id` de `$fillable` y el `belongsTo(Inquilino::class)`; agregar `belongsToMany(Inquilino::class)->using()->withPivot('es_principal')->withTimestamps()` y un accesor `inquilinoPrincipal()` (fila con `es_principal = true`) en `app/Models/Contrato.php` (depende de T003, T005)
- [X] T009 [P] Actualizar `InquilinoFactory` (genera `apellidos`/`nombres`/`dni`/`fecha_nacimiento`) y eliminar `RepresentanteFactory` en `database/factories/` (depende de T006)
- [X] T010 Renombrar `ServicioAsociacionRepresentantesContrato` → `ServicioAsociacionInquilinosContrato` (namespace `Inquilino`; agrega la regla FR-009: bloquear remoción del Principal si hay otros inquilinos y no se designó un reemplazo) en `app/Services/ServicioAsociacionInquilinosContrato.php` (depende de T008)
- [X] T011 [P] Renombrar excepciones `ContratoSinRepresentantesException`→`ContratoSinInquilinosException`, `RepresentantePrincipalInvalidoException`→`InquilinoPrincipalInvalidoException`, `UltimoRepresentanteException`→`UltimoInquilinoException` en `app/Exceptions/` (depende de T010)
- [X] T012 [P] Renombrar `SolicitudGuardarRepresentante` → `SolicitudGuardarInquilino` (regla `unique:inquilinos,dni`, DNI 8 dígitos, mayoría de edad) en `app/Http/Requests/SolicitudGuardarInquilino.php` (depende de T006)
- [X] T013 Quitar la regla `inquilino_id` (`required|integer|exists:inquilinos,id`) y sus mensajes de `SolicitudGuardarContrato` en `app/Http/Requests/SolicitudGuardarContrato.php` (depende de T008)

**Checkpoint**: Fundamento consolidado — las historias de usuario pueden avanzar

---

## Phase 3: User Story 1 - Asignación Obligatoria de al Menos un Inquilino al Contrato (Priority: P1) 🎯 MVP

**Goal**: Impedir guardar un contrato sin al menos un inquilino asociado.

**Independent Test**: Intentar guardar un contrato nuevo sin inquilinos y verificar el bloqueo con mensaje persistente; agregar un inquilino y verificar que el guardado tenga éxito.

### Tests for User Story 1 ⚠️

- [X] T014 [P] [US1] Renombrar y actualizar `tests/Unit/ServicioAsociacionRepresentantesContratoTest.php` → `tests/Unit/ServicioAsociacionInquilinosContratoTest.php` (rechazo con cero inquilinos, éxito con uno marcado Principal automáticamente)
- [X] T015 [P] [US1] Renombrar y actualizar `tests/Feature/RepresentanteControllerTest.php` → `tests/Feature/InquilinoControllerTest.php` (rechazo 422 sin inquilinos en `ContratoController@store`, éxito con uno)

### Implementation for User Story 1

- [X] T016 [US1] Renombrar `RepresentanteController` → `InquilinoController` (`store`: alta en el directorio global vía `Inquilino::firstOrCreate(['dni' => ...], [...])` para reutilizar DNI duplicado) en `app/Http/Controllers/InquilinoController.php` (depende de T010, T012)
- [X] T017 [US1] Ajustar `ContratoController@store` para integrar `ServicioAsociacionInquilinosContrato::sincronizar()` (renombrando toda referencia a "representante") en `app/Http/Controllers/ContratoController.php` (depende de T010)
- [X] T018 [US1] Renombrar ruta `POST /representantes` → `POST /inquilinos` en `routes/web.php` (depende de T016)
- [X] T019 [US1] Renombrar parcial `representantes-contrato.blade.php` → `inquilinos-contrato.blade.php` (mensaje de bloqueo persistente y de alto contraste: "Debe asociar por lo menos un inquilino al contrato antes de guardar") en `resources/views/contratos/partials/inquilinos-contrato.blade.php` (depende de T018)
- [X] T020 [US1] Embeber la parcial de inquilinos en `contratos/create.blade.php` (editor dinámico del arreglo `inquilinos[]` antes de crear el contrato) y en `contratos/show.blade.php` (lista persistida, patrón ya establecido por la gestión de documentos del contrato — ver Notes) usando `hx-boost`/htmx (Principio VI), no Alpine.js, en `resources/views/contratos/create.blade.php` y `resources/views/contratos/show.blade.php` (depende de T019)

**Checkpoint**: User Story 1 funcional y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Soporte de Múltiples Inquilinos con Gestión Accesible (Priority: P2)

**Goal**: Agregar y quitar múltiples inquilinos de un contrato con controles claros y confirmación explícita, respetando la regla del Principal (FR-009).

**Independent Test**: Agregar dos inquilinos a un contrato y luego remover el no-Principal con confirmación; verificar que remover al Principal sin designar reemplazo se bloquea.

### Tests for User Story 2 ⚠️

- [X] T021 [P] [US2] Actualizar `tests/Feature/InquilinoControllerTest.php` con casos de `ContratoController@agregarInquilino`/`@quitarInquilino` (alta de un segundo inquilino, baja con confirmación, bloqueo al quitar el Principal sin reemplazo) (depende de T015)

### Implementation for User Story 2

- [X] T022 [US2] Renombrar/implementar `InquilinoController@buscar` (búsqueda por DNI/apellidos, `ILIKE`) en `app/Http/Controllers/InquilinoController.php` (depende de T016)
- [X] T023 [US2] Renombrar acciones `agregarRepresentante`/`quitarRepresentante` → `agregarInquilino`/`quitarInquilino` en `ContratoController`, aplicando la regla FR-009 (bloquear quitar al Principal si hay otros y no se designó reemplazo) en `app/Http/Controllers/ContratoController.php` (depende de T010, T017)
- [X] T024 [US2] Renombrar rutas `GET /representantes/buscar`, `POST /contratos/{contrato}/representantes`, `DELETE /contratos/{contrato}/representantes/{representante}` → sus equivalentes `/inquilinos` en `routes/web.php` (depende de T022, T023)
- [X] T025 [US2] Renombrar botón "Agregar Otro Representante" → "Agregar Otro Inquilino" y el buscador por DNI/apellidos en la parcial `inquilinos-contrato.blade.php` (depende de T019, T024)
- [X] T026 [US2] Renombrar el modal de confirmación ("Sí, quitar inquilino" / "No, cancelar") y agregar el flujo de reasignación de Principal antes de permitir quitarlo (FR-009) en `resources/views/contratos/partials/inquilinos-contrato.blade.php` (depende de T025)

**Checkpoint**: User Story 1 y 2 funcionan de forma independiente

---

## Phase 5: User Story 3 - Validación de Datos Personales del Inquilino (Priority: P3)

**Goal**: Validar formato de DNI, mayoría de edad y campos obligatorios al registrar un inquilino.

**Independent Test**: Ingresar un DNI con formato incorrecto o una fecha de nacimiento de menor de edad y verificar el bloqueo con mensajes descriptivos.

### Tests for User Story 3 ⚠️

- [X] T027 [P] [US3] Renombrar y actualizar `tests/Unit/RepresentanteTest.php` → `tests/Unit/InquilinoTest.php` (DNI inválido, menor de edad, campos vacíos)

### Implementation for User Story 3

- [X] T028 [US3] Confirmar/ajustar mensajes de error explícitos de `SolicitudGuardarInquilino` ("El DNI debe tener formato válido", "El inquilino debe ser mayor de edad") en `app/Http/Requests/SolicitudGuardarInquilino.php` (depende de T012, T027)
- [X] T029 [US3] Mostrar los mensajes de error junto al campo y en resumen persistente en la parcial de inquilinos en `resources/views/contratos/partials/inquilinos-contrato.blade.php` (depende de T025, T028)

**Checkpoint**: Las 3 historias de usuario funcionan de forma independiente

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Mejoras que afectan a todas las historias de usuario y limpieza de artefactos obsoletos

- [X] T030 [P] Auditoría de accesibilidad (contraste WCAG AA 4.5:1, componentes Bootstrap 5 estándar — sin mínimos de 18px/48px, ya retirados por la enmienda constitucional 2.0.0) en `resources/views/contratos/partials/inquilinos-contrato.blade.php`
- [X] T031 [P] Revisión de seguridad: CSRF, `$fillable` de `Inquilino`, verificar que `quitarInquilino` nunca ejecute el `DELETE` del pivote sin antes validar el mínimo de uno y la regla del Principal (FR-009)
- [X] T032 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 4, incluyendo el paso de FR-009) de extremo a extremo
- [X] T033 [P] Ejecutar `php artisan test --filter=Inquilino` y confirmar que toda la suite pasa
- [X] T034 Eliminar artefactos obsoletos ya reemplazados: `app/Http/Controllers/RepresentanteController.php`, `app/Services/ServicioAsociacionRepresentantesContrato.php`, `app/Http/Requests/SolicitudGuardarRepresentante.php`, `app/Http/Requests/SolicitudAsociarRepresentante.php`, `database/factories/RepresentanteFactory.php`, `app/Exceptions/ContratoSinRepresentantesException.php`, `app/Exceptions/RepresentantePrincipalInvalidoException.php`, `app/Exceptions/UltimoRepresentanteException.php`, `resources/views/contratos/partials/representantes-contrato.blade.php`, `resources/js/representantes-contrato.js` (si existe y ya fue reemplazado por T020) (depende de T016, T019, T028)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Ya completado
- **Foundational (Phase 2)**: BLOQUEA todas las historias de usuario — consolida el modelo de datos antes de tocar controladores/vistas
- **User Stories (Phase 3-5)**: Todas dependen de Foundational; pueden avanzar en orden P1 → P2 → P3 (recomendado, dado que reutilizan los mismos archivos renombrados)
- **Polish (Phase 6)**: Depende de que las historias deseadas estén completas; T034 depende de que cada rename correspondiente ya esté hecho

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Foundational — sin dependencias de otras historias
- **User Story 2 (P2)**: Reutiliza el controlador/parcial/Service renombrados en US1, pero es comprobable de forma independiente
- **User Story 3 (P3)**: Refina validaciones ya presentes desde T012/US1, comprobable de forma independiente

### Within Each User Story

- Las pruebas se actualizan/renombran antes de la implementación y deben fallar (o no compilar) hasta completar el rename
- Migraciones/modelos antes que servicios; servicios antes que controladores; controladores antes que rutas; rutas antes que vistas

### Parallel Opportunities

- T002-T003, T009, T011-T012 de Foundational pueden ejecutarse en paralelo
- Una vez completado Foundational, las 3 historias de usuario pueden avanzar en paralelo si hay capacidad, dado que todas operan sobre los mismos archivos ya renombrados (coordinar para evitar conflictos de merge)

---

## Parallel Example: User Story 1

```bash
Task: "Renombrar y actualizar ServicioAsociacionInquilinosContratoTest en tests/Unit/ServicioAsociacionInquilinosContratoTest.php"
Task: "Renombrar y actualizar InquilinoControllerTest en tests/Feature/InquilinoControllerTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Completar Phase 2: Foundational (CRÍTICO — incluye la migración de datos de consolidación)
2. Completar Phase 3: User Story 1
3. **DETENERSE Y VALIDAR**: Escenario 1 de `quickstart.md`
4. Desplegar/demostrar si está listo

### Incremental Delivery

1. Foundational → base consolidada (Inquilino unificado, Representante eliminado)
2. User Story 1 → probar → demo (MVP)
3. User Story 2 → probar → demo
4. User Story 3 → probar → demo

---

## Notes

- **Contexto de la corrección**: esta feature ya estaba completamente implementada bajo un diseño de dos entidades (`Inquilino` simple + `Representante` separado). El usuario corrigió esa lectura: *"el inquilino es el representante principal"*, sin necesidad de una entidad separada. Las tareas de este documento son de **consolidación/refactor**, no de construcción desde cero; reutilizan la mayor parte de la lógica ya escrita para `Representante` (búsqueda por DNI, `firstOrCreate`, reglas de mínimo uno/exactamente un Principal), solo renombrada y fusionada con `Inquilino`.
- **Decisión de diseño heredada (UI)**: los inquilinos de un contrato **ya existente** se gestionan igual que los documentos del contrato (patrón ya establecido por `DocumentoContratoController`/`contratos/show.blade.php`): altas/bajas inmediatas y atómicas vía `agregarInquilino`/`quitarInquilino`, embebidas en `contratos/show.blade.php`. La parcial de inquilinos se embebe en `contratos/create.blade.php` (arreglo `inquilinos[]`, un único punto de entrada porque el contrato aún no existe) y en `contratos/show.blade.php` (lista persistida + modales de alta/baja) — no en `contratos/edit.blade.php`. `ContratoController@update` no transporta inquilinos; el invariante ("al menos uno", "exactamente un Principal") se mantiene por construcción porque `quitar()` bloquea la remoción del último/Principal sin reemplazo y `agregar()`/`sincronizar()` siempre dejan exactamente un Principal.
- **Interactividad**: la versión original de la parcial usaba Alpine.js; por la enmienda constitucional documentada en `specs/011-elevacion-diseno-async/research.md`, toda interactividad de escritura del proyecto usa **htmx** (`hx-boost`), no Alpine.js. T020 debe migrar la parcial a ese patrón si aún no lo está.
- **Reutilización de DNI duplicado (FR-007)**: se mantiene el patrón `Inquilino::firstOrCreate(['dni' => ...], [...])` dentro de `ServicioAsociacionInquilinosContrato::resolverInquilino()`, de modo que un DNI ya existente en el directorio se reutiliza silenciosamente en el flujo de creación/asociación a contrato (sin error 422 de "unique"). La regla `unique:inquilinos,dni` solo se aplica en el endpoint independiente `POST /inquilinos` (`InquilinoController@store`, alta directa en el directorio global).
- [P] = archivos distintos, sin dependencias pendientes
- Verificar que las pruebas fallan (o reflejan el estado anterior) antes de completar cada rename
- Hacer commit tras cada tarea o grupo lógico de tareas
- Evitar: tareas vagas, conflictos de archivo simultáneos, dependencias que rompan la independencia entre historias
- **No ejecutar T005 (drop de tablas/columnas antiguas) hasta confirmar en un entorno de prueba que T004 copió el 100% de los datos** (Principio V, integridad de datos).

## Registro de Implementación (2026-08-23)

- **Todas las 34 tareas completadas** y verificadas contra la base de datos de desarrollo real (67 inquilinos, 1 contrato, 1 representante previos a la migración; se respaldaron en `storage/app/backups/pre_consolidacion_003.json` antes de migrar). `php artisan test` pasa 197/197 (473 aserciones).
- **Política de migración de datos aplicada** (T004): el inquilino ya referenciado por `contratos.inquilino_id` se preservó como Principal de cada contrato; los `Representante` existentes se migraron como registros nuevos de `Inquilino` y se asociaron como no-Principal, por no existir una forma confiable de correlacionarlos con un inquilino ya existente (ver `research.md` §1).
- **`apellidos`/`nombres`/`dni`/`fecha_nacimiento` quedan `nullable` a nivel de base de datos** (no se agregó un `NOT NULL` retroactivo): 24 de los 67 inquilinos ya existentes tenían el campo `nombre` en formato libre (sin coma, algunos ni siquiera nombres de personas — ej. "GRUPO CLATH SAC", "servicio higienico"), por lo que un split automático a apellidos/nombres habría producido datos incorrectos. La obligatoriedad (FR-002) se exige únicamente a nivel de aplicación (`SolicitudGuardarInquilino`) para inquilinos nuevos o editados.
- **Shim de compatibilidad en `ContratoFactory`** (decisión tomada con el usuario antes de implementar): en vez de reescribir los ~45 call-sites de `Contrato::factory()->create(['inquilino_id' => ...])` en las suites de otras features ya implementadas (garantía, recibos, solapamiento, historial — specs 002/004/005/008/009/012), el factory acepta `inquilino_id` como pseudo-atributo que adjunta ese inquilino como Principal vía el pivote. Sin override, se crea y adjunta un inquilino nuevo por defecto. La implementación inicial de este shim delegaba en `parent::create()`, lo que causó una recursión y un adjuntado duplicado (Factory::create() reingresa a la misma instancia vía `state()->create([])` para atributos no vacíos); se corrigió reimplementando el camino de creación con las primitivas protegidas `make()`/`store()`/`callAfterCreating()` en vez de llamar a `create()`.
- **FR-009 (bloquear remoción del Principal sin reemplazo)**: implementado como `InquilinoPrincipalSinReemplazoException` + parámetro opcional `nuevoPrincipalId` en `ServicioAsociacionInquilinosContrato::quitar()` y `SolicitudQuitarInquilino::nuevo_principal_id`; la UI ofrece un `<select>` de reemplazo en el modal de confirmación solo cuando el inquilino a quitar es el Principal y hay otros disponibles.
- **`npm run build` ejecutado** para regenerar `public/build/manifest.json` con el archivo renombrado `inquilinos-contrato.js` (los tests de vistas fallaban con `ViteException` hasta reconstruir).
