---

description: "Task list template for feature implementation"
---

# Tasks: Condiciones del Contrato y Costos de Referencia para Recibos

**Input**: Design documents from `/specs/004-condiciones-contrato-recibo/`

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

- [X] T002 [P] Crear migración de alteración de `contratos` (agrega `costo_agua`, `costo_luz`, `costo_pasadizo`, `costo_seguridad` `decimal(12,2)` default 0, y `notificado_30_dias_en`/`notificado_15_dias_en`/`notificado_7_dias_en` `timestamp` nullable) en `database/migrations/`
- [X] T003 [P] Crear migración de `configuracion_general` (`id`, `correo_notificaciones_vencimiento` string obligatorio, timestamps) + inserción de la fila `id = 1` con correo por defecto en `database/migrations/` (ver `research.md` §2)
- [X] T004 [P] Crear migración de `recibos` (`id`, `contrato_id` FK `restrictOnDelete()`, `monto_renta`, `monto_agua`, `monto_luz`, `monto_pasadizo`, `monto_seguridad` decimal(12,2), `periodo` date, `fecha_emision` date, timestamps, índice `(contrato_id, periodo)`) en `database/migrations/`
- [X] T005 Agregar columnas de costos/notificación a `$fillable`/`casts()` (`decimal:2`, `datetime`) de `Contrato` y relación `recibos(): HasMany` en `app/Models/Contrato.php` (depende de T002)
- [X] T006 [P] Crear modelo `ConfiguracionGeneral` con helper estático `actual(): self` (`firstOrCreate`) en `app/Models/ConfiguracionGeneral.php` (depende de T003)
- [X] T007 [P] Crear modelo `Recibo` (`$fillable`, casts `decimal:2`/`date`, relación `contrato(): BelongsTo`) en `app/Models/Recibo.php` (depende de T004)
- [X] T008 [P] Crear factories `ReciboFactory` y `ConfiguracionGeneralFactory` en `database/factories/` (depende de T006, T007)

**Checkpoint**: Fundamento listo — las historias de usuario pueden comenzar

---

## Phase 3: User Story 1 - Registro de Costo de Renta y Costos Fijos del Contrato (Priority: P1) 🎯 MVP

**Goal**: Registrar en cada contrato el costo de renta y los costos fijos (agua, luz, pasadizo, seguridad) como campos individuales persistentes.

**Independent Test**: Crear/editar un contrato ingresando renta y los 4 costos fijos y comprobar que persistan; dejar uno vacío y comprobar el default de "S/ 0.00" sin bloquear el guardado.

### Tests for User Story 1 ⚠️

- [X] T009 [P] [US1] Prueba unitaria del modelo `Contrato` (defaults de costos fijos en 0.00, casts `decimal:2`) en `tests/Unit/ContratoTest.php`
- [X] T010 [P] [US1] Prueba de feature de `ContratoController@store`/`@update` con costos fijos (persistencia de los 4 valores, default a 0.00 si se omiten) en `tests/Feature/ContratoControllerTest.php`
- [X] T011 [P] [US1] Prueba de feature de `ContratoController@actualizarCostos` (edición rápida de solo costos, 422 si no numérico) en `tests/Feature/ContratoControllerTest.php`

### Implementation for User Story 1

- [X] T012 [US1] Agregar `costo_agua`/`costo_luz`/`costo_pasadizo`/`costo_seguridad` a las reglas de `SolicitudGuardarContrato` (`numeric`, `min:0`, `nullable` con default 0) en `app/Http/Requests/SolicitudGuardarContrato.php` (depende de T005)
- [X] T013 [P] [US1] Crear `SolicitudGuardarCostosContrato` (Form Request de edición rápida de costos) en `app/Http/Requests/SolicitudGuardarCostosContrato.php` (depende de T005)
- [X] T014 [US1] Implementar acción `actualizarCostos` en `ContratoController` en `app/Http/Controllers/ContratoController.php` (depende de T013)
- [X] T015 [US1] Registrar ruta `PATCH /contratos/{contrato}/costos` en `routes/web.php` (depende de T014)
- [X] T016 [US1] Agregar campos de costos fijos (con etiquetas "Costo de Agua", "Costo de Luz", etc.) a `resources/views/contratos/create.blade.php` y `resources/views/contratos/edit.blade.php` (depende de T012)
- [X] T017 [US1] Agregar sección de edición rápida de costos con botón "Guardar Costos del Contrato" (≥48x48px) a `resources/views/contratos/show.blade.php` (depende de T015)

**Checkpoint**: User Story 1 funcional y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Notificación por Correo de Vencimiento de Contrato (Priority: P1)

**Goal**: Enviar automáticamente una notificación por correo en los hitos de 30/15/7 días antes del vencimiento del contrato, sin duplicados.

**Independent Test**: Registrar un contrato con `fecha_fin` a 30 días, ejecutar `contratos:verificar-vencimientos`, comprobar el envío del correo (`Mail::fake()`) y que no se duplique en una segunda ejecución.

### Tests for User Story 2 ⚠️

- [X] T018 [P] [US2] Prueba unitaria de `ServicioNotificacionVencimientoContrato` (detección de hitos 30/15/7, no-duplicación, hitos múltiples ya vencidos al crear) en `tests/Unit/ServicioNotificacionVencimientoContratoTest.php`
- [X] T019 [P] [US2] Prueba unitaria de `ConfiguracionGeneral::actual()` (singleton, `firstOrCreate`) en `tests/Unit/ConfiguracionGeneralTest.php`
- [X] T020 [P] [US2] Prueba de feature del comando `contratos:verificar-vencimientos` de punta a punta con `Mail::fake()` en `tests/Feature/VerificarVencimientosContratosTest.php`
- [X] T021 [P] [US2] Prueba de feature de `ConfiguracionGeneralController@edit`/`@update` en `tests/Feature/ConfiguracionGeneralControllerTest.php`

### Implementation for User Story 2

- [X] T022 [P] [US2] Crear Mailable `ContratoProximoAVencer` (locación, inquilino, fecha de fin, hito) en `app/Mail/ContratoProximoAVencer.php` (depende de T005)
- [X] T023 [US2] Implementar `ServicioNotificacionVencimientoContrato` (cálculo de hitos pendientes, envío síncrono, marca de `notificado_X_dias_en` dentro de `DB::transaction`) en `app/Services/ServicioNotificacionVencimientoContrato.php` (depende de T022, T006)
- [X] T024 [US2] Crear comando artisan `VerificarVencimientosContratos` (`contratos:verificar-vencimientos`) en `app/Console/Commands/VerificarVencimientosContratos.php` (depende de T023)
- [X] T025 [US2] Registrar `Schedule::command('contratos:verificar-vencimientos')->daily()` en `routes/console.php` (depende de T024)
- [X] T026 [P] [US2] Crear `SolicitudActualizarConfiguracionGeneral` (`correo_notificaciones_vencimiento` `required`/`email`) en `app/Http/Requests/SolicitudActualizarConfiguracionGeneral.php` (depende de T006)
- [X] T027 [US2] Implementar `ConfiguracionGeneralController@edit`/`@update` en `app/Http/Controllers/ConfiguracionGeneralController.php` (depende de T026)
- [X] T028 [US2] Registrar rutas `GET`/`PUT /configuracion` en `routes/web.php` (depende de T027)
- [X] T029 [US2] Crear vista `configuracion/edit.blade.php` (formulario Senior-First del correo administrativo) en `resources/views/configuracion/edit.blade.php` (depende de T028)
- [X] T030 [US2] Implementar el reinicio a `null` de los tres hitos de notificación en `ContratoController@update` cuando `fecha_fin` cambia de valor, en `app/Http/Controllers/ContratoController.php` (depende de T005; ver `research.md` §4)

**Checkpoint**: User Story 1 y 2 funcionan de forma independiente

---

## Phase 5: User Story 3 - Generación de Recibo con Montos Editables a partir de los Valores de Referencia (Priority: P2)

**Goal**: Precargar el formulario de recibo con los valores de referencia del contrato, permitiendo su edición antes de emitir, sin alterar el contrato ni los recibos ya emitidos.

**Independent Test**: Generar un recibo desde un contrato con renta/costos definidos, editar un monto antes de confirmar, y comprobar que el recibo guarde el valor editado mientras el contrato conserva su valor de referencia.

### Tests for User Story 3 ⚠️

- [X] T031 [P] [US3] Prueba unitaria del modelo `Recibo` (independencia post-emisión respecto a cambios posteriores del contrato) en `tests/Unit/ReciboTest.php`
- [X] T032 [P] [US3] Prueba de feature de `ReciboController@create`/`@store`/`@show`/`@index` (precarga, edición, historial, 422 por montos inválidos) en `tests/Feature/ReciboControllerTest.php`

### Implementation for User Story 3

- [X] T033 [P] [US3] Crear `SolicitudGuardarRecibo` (`monto_renta`/`costo_*` `numeric`/`min:0`, `periodo`/`fecha_emision` fecha) en `app/Http/Requests/SolicitudGuardarRecibo.php` (depende de T007)
- [X] T034 [US3] Implementar `ReciboController@create` (precarga desde `Contrato`) y `@store` (persistencia dentro de `DB::transaction`) en `app/Http/Controllers/ReciboController.php` (depende de T033)
- [X] T035 [US3] Implementar `ReciboController@show`/`@index` (detalle e historial de recibos del contrato) en `app/Http/Controllers/ReciboController.php` (depende de T034)
- [X] T036 [US3] Registrar rutas `GET/POST /contratos/{contrato}/recibos`, `GET /contratos/{contrato}/recibos/crear`, `GET /recibos/{recibo}` en `routes/web.php` (depende de T035)
- [X] T037 [US3] Crear vista `contratos/recibos/create.blade.php` (formulario precargado y editable, botón "Emitir Recibo" ≥48x48px) en `resources/views/contratos/recibos/create.blade.php` (depende de T036)
- [X] T038 [US3] Crear vistas `contratos/recibos/show.blade.php` y `contratos/recibos/index.blade.php` (detalle e historial, tipografía ≥18px) en `resources/views/contratos/recibos/show.blade.php` y `resources/views/contratos/recibos/index.blade.php` (depende de T036)

**Checkpoint**: Las 3 historias de usuario funcionan de forma independiente

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Mejoras que afectan a todas las historias de usuario

- [X] T039 [P] Auditoría de accesibilidad (contraste, tipografía ≥18px, botones ≥48x48px) en `resources/views/contratos/recibos/` y `resources/views/configuracion/`
- [X] T040 [P] Revisión de seguridad: CSRF en todos los formularios, `$fillable` en `Recibo`/`ConfiguracionGeneral`, verificación de que `ReciboController@store` nunca lea `Contrato` después de persistir el recibo
- [X] T041 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 6) de extremo a extremo
- [X] T042 [P] Ejecutar `php artisan test --filter=Recibo`, `--filter=ConfiguracionGeneral` y `--filter=VerificarVencimientosContratos`, confirmando que toda la suite pasa

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Ya completado
- **Foundational (Phase 2)**: BLOQUEA todas las historias de usuario
- **User Stories (Phase 3-5)**: Todas dependen de Foundational; US1 y US2 son ambas P1 y pueden avanzar en paralelo entre sí; US3 (P2) puede iniciar tras Foundational pero depende de que `Contrato` ya tenga costos fijos (US1) para tener valores útiles que precargar
- **Polish (Phase 6)**: Depende de que las historias deseadas estén completas

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Foundational — sin dependencias de otras historias
- **User Story 2 (P1)**: Puede iniciar tras Foundational — sin dependencias de otras historias (usa `ConfiguracionGeneral` y los hitos de `Contrato`, ambos ya cubiertos en Foundational)
- **User Story 3 (P2)**: Puede iniciar tras Foundational; funcionalmente más útil una vez completada US1 (para tener costos fijos reales que precargar), pero comprobable de forma independiente con valores en 0.00

### Within Each User Story

- Las pruebas se escriben antes de la implementación y deben fallar inicialmente
- Migraciones/modelos antes que Form Requests/Services; Services antes que controladores; controladores antes que rutas; rutas antes que vistas

### Parallel Opportunities

- T002-T004 de Foundational pueden ejecutarse en paralelo (migraciones independientes); T005-T008 dependen de ellas pero entre sí son paralelas por archivo
- Una vez completado Foundational, US1 y US2 pueden avanzar en paralelo por distintos desarrolladores; US3 puede avanzar en paralelo también si se acepta precarga en 0.00 temporalmente

---

## Parallel Example: User Story 2

```bash
Task: "Prueba unitaria de ServicioNotificacionVencimientoContrato en tests/Unit/ServicioNotificacionVencimientoContratoTest.php"
Task: "Prueba de feature del comando contratos:verificar-vencimientos en tests/Feature/VerificarVencimientosContratosTest.php"
Task: "Prueba de feature de ConfiguracionGeneralController en tests/Feature/ConfiguracionGeneralControllerTest.php"
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

- **Reconciliación aplicada (T036-T038, ver `specs/005-lecturas-medidor-recibo-periodo/research.md` §1)**: las rutas y vistas de generación/historial de `Recibo` se implementaron directamente con el diseño FINAL locación-céntrico de 005, no con el borrador contrato-céntrico documentado en `contracts/rutas-condiciones-contrato-recibo.md` de esta feature:
  - Rutas reales: `GET/POST /locaciones/{locacion}/recibos`, `GET /locaciones/{locacion}/recibos/crear` (con query `?periodo=YYYY-MM`), `GET /recibos/{recibo}` — en vez de `/contratos/{contrato}/recibos*`.
  - Vistas reales: `resources/views/locaciones/recibos/{create,index,show}.blade.php` — en vez de `resources/views/contratos/recibos/*`.
  - `Recibo` incluye `locacion_id` desde su migración de creación original (`2026_08_21_041408_create_recibos_table.php`), no como una `ALTER TABLE` posterior de 005. **Al implementar 005, no repetir esta migración** — solo agregar ahí `lectura_medidor_id` e `incluye_*` (conceptos genuinamente nuevos de 005, no de esta reconciliación).
  - Se adelantó el helper `Locacion::contratoActivoEnPeriodo(Carbon $periodo): ?Contrato` (en `app/Models/Locacion.php`) porque las rutas locación-céntricas lo requieren para resolver qué contrato factura cada recibo. **Al implementar 005, reutilizar este helper tal cual, no duplicarlo.**
  - `ReciboController@store` bloquea la emisión con `SinContratoActivoEnPeriodoException` (`app/Exceptions/SinContratoActivoEnPeriodoException.php`) si no hay contrato activo vigente en el periodo solicitado — este comportamiento corresponde conceptualmente al FR-008 de 005, pero fue necesario implementarlo ya en 004 porque `Recibo.contrato_id` es una FK obligatoria y las rutas ya son locación-céntricas.
  - NO se adelantó de 005: `lectura_medidor_id`, las columnas booleanas `incluye_*`, el cálculo de `monto_luz` a partir de tarifa/consumo, ni la restricción `UNIQUE (locacion_id, periodo)` (FR-009 de 005) — estas siguen siendo responsabilidad exclusiva de la implementación de 005.
- **Desviación menor (T014/T017)**: la edición rápida de costos (`PATCH /contratos/{contrato}/costos`) se muestra en `contratos/show.blade.php` (no se creó una vista separada), consistente con el patrón ya usado para "Documentos del Contrato".
- **Nota de reinicio de hitos (T030)**: implementado como comparación explícita de `fecha_fin` en `ContratoController@update` (no un Observer), tal como especificaba `research.md` §4.
- [P] = archivos distintos, sin dependencias pendientes
- Verificar que las pruebas fallan antes de implementar
- Hacer commit tras cada tarea o grupo lógico de tareas
- Evitar: tareas vagas, conflictos de archivo simultáneos, dependencias que rompan la independencia entre historias
