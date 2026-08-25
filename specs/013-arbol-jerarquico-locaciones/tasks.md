---

description: "Task list template for feature implementation"
---

# Tasks: Árbol Jerárquico Horizontal de Locaciones

**Input**: Design documents from `/specs/013-arbol-jerarquico-locaciones/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Incluidas — el Principio IV de la Constitución exige pruebas automatizadas exhaustivas para toda funcionalidad.

**Organization**: Las tareas están agrupadas por historia de usuario para permitir implementación y prueba independiente de cada una.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: Historia de usuario a la que pertenece la tarea (US1, US2, US3)
- Se incluyen rutas de archivo exactas en cada descripción

## Path Conventions

Aplicación Laravel monolítica única — rutas relativas a la raíz del repositorio: `app/`, `resources/`, `routes/`, `tests/`, según `plan.md` → Project Structure.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Ya inicializado por `specs/001-jerarquia-locaciones` (modelo `Locacion`, migraciones, Pest)

- [X] T001 Proyecto ya inicializado — sin tareas de setup adicionales para esta feature

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Servicio de construcción del árbol y estilos base compartidos por las 3 historias de usuario

**⚠️ CRITICAL**: Ninguna historia de usuario puede comenzar hasta completar esta fase

- [X] T002 [P] Crear `ServicioConstruccionArbolLocaciones::construir(): array` (una única consulta `Locacion::orderBy('nombre')->get()`, agrupación en memoria por `locacion_padre_id` vía `groupBy`, ensamblado recursivo de nodos raíz `locacion_padre_id === null`, límite defensivo `MAXIMO_PROFUNDIDAD_ARBOL = 1000` igual que `Locacion::MAXIMO_SALTOS_ANCESTROS`) en `app/Services/ServicioConstruccionArbolLocaciones.php`
- [X] T003 [P] Agregar sección de estilos del árbol horizontal (gap entre niveles y entre hermanos usando la escala `$spacer` existente, líneas de conexión con `border-left`/`border-top`, clase de contenedor con `overflow-x: auto` para evitar scroll de página) en `resources/css/bootstrap.scss`

**Checkpoint**: Fundamento listo — las historias de usuario pueden comenzar

---

## Phase 3: User Story 1 - Visualización Unificada del Árbol de Locaciones (Priority: P1) 🎯 MVP

**Goal**: Mostrar todas las locaciones (alquilables y contenedoras) en una única vista de árbol horizontal, reemplazando el listado plano de `/dashboard` y el listado filtrado de `/locaciones`.

**Independent Test**: Acceder a `/locaciones` (o `/dashboard`, que redirige ahí) y comprobar que se renderiza un árbol horizontal con todas las locaciones registradas, contenedoras y alquilables, sin necesidad de visitar ninguna otra pantalla.

### Tests for User Story 1 ⚠️

- [X] T004 [P] [US1] Reemplazar la prueba `'el listado solo muestra locaciones alquilables'` (ya no aplica bajo FR-001) por `'el arbol muestra tanto locaciones alquilables como contenedoras'` en `tests/Feature/LocacionControllerTest.php`
- [X] T005 [P] [US1] Prueba unitaria de `ServicioConstruccionArbolLocaciones` (agrupación correcta por `locacion_padre_id`, múltiples raíces independientes, límite defensivo de profundidad ante una cadena artificialmente larga) en `tests/Unit/ServicioConstruccionArbolLocacionesTest.php`

### Implementation for User Story 1

- [X] T006 [US1] Actualizar `LocacionController@index` para pasar a la vista el resultado de `ServicioConstruccionArbolLocaciones::construir()` en vez de `Locacion::alquilables()->orderBy('nombre')->get()` en `app/Http/Controllers/LocacionController.php` (depende de T002)
- [X] T007 [US1] Crear la parcial recursiva `resources/views/locaciones/partials/nodo-arbol-locacion.blade.php` (tarjeta compacta con el nombre de la locación y un `badge` `text-bg-success`/`text-bg-secondary` según `es_alquilable`, renderizando recursivamente sus hijos) (depende de T003)
- [X] T008 [US1] Reescribir `resources/views/locaciones/index.blade.php` para iterar cada raíz del árbol devuelta por el controlador, incluyendo la parcial recursiva por cada una, apiladas verticalmente entre sí, dentro de un contenedor con la clase de scroll horizontal propio de T003 (depende de T006, T007)
- [X] T009 [US1] Reemplazar el closure de `GET /dashboard` por `redirect()->route('locaciones.index')` y eliminar `resources/views/dashboard.blade.php` en `routes/web.php`

**Checkpoint**: User Story 1 funcional y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Gestión Directa desde el Árbol (Priority: P2)

**Goal**: Permitir acceder a las acciones de gestión de cualquier locación directamente desde su nodo en el árbol.

**Independent Test**: Hacer clic en un nodo del árbol (alquilable y contenedor) y comprobar que se navega correctamente al detalle de esa locación con sus acciones de gestión disponibles.

### Tests for User Story 2 ⚠️

- [X] T010 [P] [US2] Prueba de feature: el nombre de un nodo del árbol (tanto alquilable como contenedor) es un enlace que navega a `locaciones.show` de esa locación en `tests/Feature/LocacionControllerTest.php`

### Implementation for User Story 2

- [X] T011 [US2] Convertir el nombre de la locación en cada nodo en un enlace `<a href="{{ route('locaciones.show', $locacion) }}">` en `resources/views/locaciones/partials/nodo-arbol-locacion.blade.php` (depende de T007)

**Checkpoint**: User Story 1 y 2 funcionan de forma independiente

---

## Phase 5: User Story 3 - Manejo de Árboles Grandes (Priority: P3)

**Goal**: Permitir contraer y expandir ramas del árbol para mantenerlo manejable en jerarquías con muchas locaciones.

**Independent Test**: Contraer la rama de una locación con hijos y comprobar que sus descendientes se ocultan; expandirla nuevamente y comprobar que vuelven a mostrarse.

### Tests for User Story 3 ⚠️

- [X] T012 [P] [US3] Prueba de feature: un nodo con locaciones hijas expone un control de colapso con `data-bs-toggle="collapse"` apuntando a un contenedor `collapse` que envuelve a sus hijos; un nodo sin hijos no expone dicho control, en `tests/Feature/LocacionControllerTest.php`

### Implementation for User Story 3

- [X] T013 [US3] Agregar el control de colapso (ícono `bi-chevron-right`/`bi-chevron-down`, `data-bs-toggle="collapse"`, `data-bs-target="#hijos-locacion-{{ $locacion->id }}"`, `aria-expanded`) únicamente cuando el nodo tiene hijos, envolviendo el sub-árbol en un contenedor `collapse show` (expandido por defecto) con `id="hijos-locacion-{{ $locacion->id }}"` en `resources/views/locaciones/partials/nodo-arbol-locacion.blade.php` (depende de T011)
- [X] T014 [US3] Agregar la regla CSS de rotación del ícono chevron según el estado `aria-expanded` del botón toggle en `resources/css/bootstrap.scss` (depende de T003, T013)

**Checkpoint**: Las 3 historias de usuario funcionan de forma independiente

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Mejoras que afectan a todas las historias de usuario

- [X] T015 [P] Auditoría de contraste WCAG AA (4.5:1) en el texto de los nodos, badges y líneas de conexión del árbol en `resources/views/locaciones/partials/nodo-arbol-locacion.blade.php` y `resources/css/bootstrap.scss`
- [X] T016 [P] Verificar visualmente con una locación de muchas hijas directas que el desbordamiento horizontal queda contenido en el árbol (scroll propio) y nunca produce scroll a nivel de página completa
- [X] T017 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 4) de extremo a extremo
- [X] T018 [P] Ejecutar `php artisan test --filter=Locacion` y confirmar que toda la suite pasa, incluyendo `RutaRaizTest` (la redirección de `/` a `route('dashboard')` debe seguir funcionando aunque `dashboard` ahora redirija a su vez a `locaciones.index`)

---

## Phase 7: Revisión de Estilo — Tabla Indentada + Campo Tipo (2026-08-23)

**Purpose**: El usuario vio la iteración de tarjetas horizontales (Fases 2-6) y pidió reemplazarla por una tabla jerárquica indentada con columnas (Nombre/Locación, Estado, Tipo, Acciones), agregando un campo "Tipo" (lista fija) y una acción rápida de creación con padre preseleccionado.

- [X] T019 [P] Crear migración `add_tipo_to_locaciones_table` (columna `tipo` `enum('galeria','piso','sector','pasillo','local')` nullable) en `database/migrations/`
- [X] T020 [P] Agregar `tipo` a `$fillable` de `Locacion` y un mapa estático `Locacion::TIPOS` (etiqueta + ícono `bi-*` por valor) en `app/Models/Locacion.php`
- [X] T021 Agregar la regla `'tipo' => ['required', 'in:galeria,piso,sector,pasillo,local']` a `SolicitudGuardarLocacion` en `app/Http/Requests/SolicitudGuardarLocacion.php` (depende de T019)
- [X] T022 Agregar el `<select name="tipo">` (opciones de `Locacion::TIPOS`) a `resources/views/locaciones/create.blade.php` y `resources/views/locaciones/edit.blade.php` (depende de T020)
- [X] T023 `LocacionController@create` MUST leer `?locacion_padre_id=` de la query string y pasarlo a la vista como valor por defecto del `<select>` de locación padre (FR-011) en `app/Http/Controllers/LocacionController.php`
- [X] T024 Reescribir la sección 5 de `resources/css/bootstrap.scss` (estilos de tarjetas conectadas) por estilos de tabla indentada: encabezado de columnas, grid por fila, variable de indentación por profundidad, franjas alternadas u hover de fila
- [X] T025 Crear `resources/views/locaciones/partials/fila-arbol-locacion.blade.php` (reemplaza a `nodo-arbol-locacion.blade.php`): fila con ícono+nombre indentado, badge de Estado, etiqueta de Tipo, botones "+" y "Editar"; hijos colapsables recursivos (depende de T020, T024)
- [X] T026 Reescribir `resources/views/locaciones/index.blade.php` con el encabezado de columnas y las filas raíz usando la nueva parcial (depende de T025)
- [X] T027 [P] Eliminar `resources/views/locaciones/partials/nodo-arbol-locacion.blade.php` (reemplazada por T025)
- [X] T028 [P] Actualizar `tests/Feature/LocacionControllerTest.php` (columnas/indentación/ícono de tipo, botón "+" con padre preseleccionado, ya no hay enlace de nombre a `locaciones.show`) y `tests/Unit/LocacionTest.php` (mapa `Locacion::TIPOS`)
- [X] T029 Ejecutar `php artisan test --filter=Locacion` y verificar visualmente en navegador (Escenarios 1-5 de `quickstart.md`)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Ya completado
- **Foundational (Phase 2)**: BLOQUEA todas las historias de usuario
- **User Stories (Phase 3-5)**: Todas dependen de Foundational; se recomienda orden P1 → P2 → P3, dado que US2 y US3 modifican la misma parcial creada en US1
- **Polish (Phase 6)**: Depende de que las historias deseadas estén completas

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Foundational — sin dependencias de otras historias
- **User Story 2 (P2)**: Modifica la parcial creada en US1 (T007); comprobable de forma independiente una vez aplicada
- **User Story 3 (P3)**: Modifica la parcial ya con el enlace de US2 (T011); comprobable de forma independiente una vez aplicada

### Within Each User Story

- Las pruebas se escriben antes de la implementación y deben fallar inicialmente
- Servicio antes que controlador; controlador antes que vistas; parcial base antes que sus refinamientos (enlace de US2, colapso de US3)

### Parallel Opportunities

- T002-T003 de Foundational pueden ejecutarse en paralelo
- T004-T005 (tests de US1) pueden ejecutarse en paralelo entre sí
- T015-T016 y T018 de Polish pueden ejecutarse en paralelo

---

## Parallel Example: User Story 1

```bash
Task: "Reemplazar la prueba de listado filtrado en tests/Feature/LocacionControllerTest.php"
Task: "Prueba unitaria de ServicioConstruccionArbolLocaciones en tests/Unit/ServicioConstruccionArbolLocacionesTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Completar Phase 2: Foundational
2. Completar Phase 3: User Story 1
3. **DETENERSE Y VALIDAR**: Escenario 1 de `quickstart.md`
4. Desplegar/demostrar si está listo

### Incremental Delivery

1. Foundational → base lista
2. User Story 1 → probar → demo (MVP: árbol unificado visible)
3. User Story 2 → probar → demo (nodos clicables)
4. User Story 3 → probar → demo (colapsar/expandir)

---

## Notes

- Esta feature es de solo lectura/visualización: no agrega migraciones, Form Requests ni excepciones de negocio nuevas.
- La prueba existente `'el listado solo muestra locaciones alquilables'` en `tests/Feature/LocacionControllerTest.php` queda obsoleta por el cambio de FR-001 (ahora se muestran TODAS las locaciones) y se reemplaza en T004, no se elimina sin reemplazo.
- `tests/Feature/RutaRaizTest.php` no requiere cambios: solo verifica que `/` redirige a la URL de `route('dashboard')`, sin importar que esa ruta a su vez redirija a `locaciones.index`.
- [P] = archivos distintos, sin dependencias pendientes
- Verificar que las pruebas fallan antes de implementar
- Hacer commit tras cada tarea o grupo lógico de tareas
- Evitar: tareas vagas, conflictos de archivo simultáneos, dependencias que rompan la independencia entre historias
