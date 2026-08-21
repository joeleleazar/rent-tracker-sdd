---

description: "Task list template for feature implementation"
---

# Tasks: Fecha Límite de Pago Mensual, Alertas y Prorrateo por Días Activos

**Input**: Design documents from `/specs/008-prorrateo-alertas-pago/`

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

**Purpose**: Esquema de base de datos y cálculo de fecha límite que todas las historias de usuario de esta feature requieren

**⚠️ CRITICAL**: Ninguna historia de usuario puede comenzar hasta completar esta fase

- [X] T002 [P] Crear migración de alteración de `configuracion_general` (agrega `dias_anticipacion_alerta_pago` integer default 5, `alerta_pago_mes_enviada_en` timestamp nullable) en `database/migrations/` (depende de la migración de `configuracion_general` de `specs/004`, alterada en `005`)
- [X] T003 [P] Crear migración de alteración de `recibos` (agrega `dias_activos_periodo` integer nullable, `dias_totales_periodo` integer nullable) en `database/migrations/` (depende de la migración de `recibos` de `specs/004`, alterada en `005`/`007`)
- [X] T004 [P] Agregar `dias_anticipacion_alerta_pago`/`alerta_pago_mes_enviada_en` a `$fillable`/`casts()` de `ConfiguracionGeneral` en `app/Models/ConfiguracionGeneral.php` (depende de T002)
- [X] T005 [P] Agregar `dias_activos_periodo`/`dias_totales_periodo` a `$fillable`/`casts()` de `Recibo` en `app/Models/Recibo.php` (depende de T003)
- [X] T006 [P] Implementar `ServicioCalculoFechaLimitePago` (`calcular(Carbon $mes): Carbon`, último sábado del mes con caso borde de mes terminado en sábado) en `app/Services/ServicioCalculoFechaLimitePago.php` (ver `research.md` §1)

**Checkpoint**: Fundamento listo — las historias de usuario pueden comenzar

---

## Phase 3: User Story 1 - Alerta Configurable de Fecha Límite de Pago Mensual (Priority: P1) 🎯 MVP

**Goal**: Alertar al Administrador con anticipación configurable antes del último sábado del mes, sin duplicados.

**Independent Test**: Configurar anticipación en 5 días, ubicarse a exactamente 5 días del último sábado, ejecutar la verificación periódica y comprobar el envío de la alerta sin duplicados en ejecuciones posteriores del mismo mes.

### Tests for User Story 1 ⚠️

- [X] T007 [P] [US1] Prueba unitaria de `ServicioCalculoFechaLimitePago` (último sábado para los 7 días posibles de fin de mes) en `tests/Unit/ServicioCalculoFechaLimitePagoTest.php`
- [X] T008 [P] [US1] Prueba unitaria de `ServicioAlertaFechaLimitePago` (envío al alcanzar la anticipación, no-duplicación mensual, cambio de anticipación configurada, anticipación mayor a los días del mes) en `tests/Unit/ServicioAlertaFechaLimitePagoTest.php`
- [X] T009 [P] [US1] Prueba de feature del comando `pagos:alertar-fecha-limite` de punta a punta con `Mail::fake()` en `tests/Feature/AlertarFechaLimitePagoTest.php`

### Implementation for User Story 1

- [X] T010 [P] [US1] Crear Mailable `AlertaFechaLimitePago` (fecha límite calculada, mes) en `app/Mail/AlertaFechaLimitePago.php` (depende de T006)
- [X] T011 [US1] Implementar `ServicioAlertaFechaLimitePago` (compara mes de `alerta_pago_mes_enviada_en`, umbral `>=` de anticipación, envío síncrono, `DB::transaction`) en `app/Services/ServicioAlertaFechaLimitePago.php` (depende de T006, T010, T004; ver `research.md` §2-3)
- [X] T012 [US1] Crear comando artisan `AlertarFechaLimitePago` (`pagos:alertar-fecha-limite`) en `app/Console/Commands/AlertarFechaLimitePago.php` (depende de T011)
- [X] T013 [US1] Registrar `Schedule::command('pagos:alertar-fecha-limite')->daily()` en `routes/console.php`, junto al `contratos:verificar-vencimientos` de `specs/004` (depende de T012)
- [X] T014 [US1] Extender `SolicitudActualizarConfiguracionGeneral` (de `specs/004`) con `dias_anticipacion_alerta_pago` (`required`, `integer`, `min:1`) en `app/Http/Requests/SolicitudActualizarConfiguracionGeneral.php` (depende de T004)
- [X] T015 [US1] Agregar campo "Días de Anticipación para Alerta de Pago" a `resources/views/configuracion/edit.blade.php` (de `specs/004`) (depende de T014)

**Checkpoint**: User Story 1 funcional y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Sugerencia de Días Activos al Iniciar un Contrato a Mitad de Mes (Priority: P2)

**Goal**: Calcular y sugerir los días activos y el monto de renta prorrateado cuando un contrato inicia a mitad de mes.

**Independent Test**: Con un contrato de `fecha_inicio` "15 de agosto de 2026" y renta "S/ 1550.00", generar el recibo de "Agosto 2026" y comprobar "17 días de 31" con monto sugerido "S/ 850.00".

### Tests for User Story 2 ⚠️

- [X] T016 [P] [US2] Prueba unitaria de `ServicioCalculoProrrateoContrato` (inicio a mitad de mes, inicio en el primer día sin sugerencia) en `tests/Unit/ServicioCalculoProrrateoContratoTest.php`

### Implementation for User Story 2

- [X] T017 [US2] Implementar `ServicioCalculoProrrateoContrato::calcular()` (usa `Locacion::contratoActivoEnPeriodo()` de `specs/005`, retorna `null` si mes completo o el arreglo de días/monto sugerido) en `app/Services/ServicioCalculoProrrateoContrato.php` (depende de T005; ver `research.md` §4)
- [X] T018 [US2] Integrar `ServicioCalculoProrrateoContrato` en `ReciboController@create` (de `specs/005`), pasando `dias_activos`/`dias_totales`/`monto_renta_sugerido` a la vista y persistiéndolos en `ReciboController@store` en `app/Http/Controllers/ReciboController.php` (depende de T017)
- [X] T019 [US2] Agregar indicador "X días de Y activos" y precarga del monto de renta prorrateado (editable) a `resources/views/locaciones/recibos/create.blade.php` (de `specs/005`) en `resources/views/locaciones/recibos/create.blade.php` (depende de T018)

**Checkpoint**: User Story 1 y 2 funcionan de forma independiente

---

## Phase 5: User Story 3 - Sugerencia de Días Activos al Finalizar un Contrato a Mitad de Mes (Priority: P2)

**Goal**: Calcular y sugerir los días activos y el monto de renta prorrateado cuando un contrato finaliza a mitad de mes.

**Independent Test**: Con un contrato de `fecha_fin` "10 de agosto de 2026" y renta "S/ 1550.00", generar el recibo de "Agosto 2026" y comprobar "10 días de 31" con monto sugerido "S/ 500.00".

### Tests for User Story 3 ⚠️

- [X] T020 [P] [US3] Prueba unitaria de `ServicioCalculoProrrateoContrato` (fin a mitad de mes, inicio y fin ambos en el mismo mes) en `tests/Unit/ServicioCalculoProrrateoContratoTest.php` (depende de T016)

### Implementation for User Story 3

- [X] T021 [US3] Verificar/completar en `ServicioCalculoProrrateoContrato::calcular()` el caso de `fecha_fin` a mitad de mes y el caso "inicio y fin en el mismo mes" (cálculo inclusive) en `app/Services/ServicioCalculoProrrateoContrato.php` (depende de T017; la lógica de `max`/`min` de `research.md` §4 ya cubre ambos casos simétricamente con US2)

**Checkpoint**: Las 3 historias de usuario funcionan de forma independiente

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Mejoras que afectan a todas las historias de usuario

- [X] T022 [P] Auditoría de accesibilidad (contraste, tipografía ≥18px) en `resources/views/configuracion/edit.blade.php` y `resources/views/locaciones/recibos/create.blade.php`
- [X] T023 [P] Revisión de seguridad: verificación de que el prorrateo nunca modifique `Contrato.monto_renta`, solo el formulario/registro del `Recibo` (A-003)
- [X] T024 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 7) de extremo a extremo
- [X] T025 [P] Ejecutar `php artisan test --filter=ServicioCalculoFechaLimitePago`, `--filter=ServicioCalculoProrrateoContrato` y `--filter=AlertarFechaLimitePago`, confirmando que toda la suite pasa

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Ya completado
- **Foundational (Phase 2)**: BLOQUEA todas las historias de usuario
- **User Stories (Phase 3-5)**: Todas dependen de Foundational; US1 (alerta de pago) es completamente independiente de US2/US3 (prorrateo); US3 reutiliza el mismo Service de US2, ampliando su cobertura de pruebas
- **Polish (Phase 6)**: Depende de que las historias deseadas estén completas

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Foundational — sin dependencias de otras historias
- **User Story 2 (P2)**: Puede iniciar tras Foundational — depende de `Locacion::contratoActivoEnPeriodo()` (de `specs/005`, ya existente)
- **User Story 3 (P2)**: Comparte el mismo Service que US2 (`ServicioCalculoProrrateoContrato`); se implementa en la práctica junto con US2, pero se prueba y valida de forma independiente (casos de `fecha_fin` vs. `fecha_inicio`)

### Within Each User Story

- Las pruebas se escriben antes de la implementación y deben fallar inicialmente
- Migraciones/modelos antes que Services; Services antes que comando/controlador; comando/controlador antes que rutas/scheduler; antes que vistas

### Parallel Opportunities

- T002-T006 de Foundational pueden ejecutarse en paralelo (archivos/tablas independientes)
- Una vez completado Foundational, US1 puede avanzar en paralelo con US2/US3 por distintos desarrolladores (Services y archivos distintos)

---

## Parallel Example: User Story 1

```bash
Task: "Prueba unitaria de ServicioCalculoFechaLimitePago en tests/Unit/ServicioCalculoFechaLimitePagoTest.php"
Task: "Prueba unitaria de ServicioAlertaFechaLimitePago en tests/Unit/ServicioAlertaFechaLimitePagoTest.php"
Task: "Prueba de feature del comando pagos:alertar-fecha-limite en tests/Feature/AlertarFechaLimitePagoTest.php"
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

- `ServicioCalculoProrrateoContrato` se inyecta en `ServicioGeneracionReciboPeriodo` (además de en `ReciboController@create` directamente para la sugerencia visual) para que `dias_activos_periodo`/`dias_totales_periodo` se persistan en el propio `Recibo` al emitirlo (`generar()`), recalculando el prorrateo internamente en vez de confiar en valores enviados por el formulario — así el registro histórico del recibo documenta objetivamente qué prorrateo se usó como referencia, sin depender de que el cliente no manipule esos campos.
- No hubo necesidad de modificar `ReciboController@update`/`ServicioGeneracionReciboPeriodo::actualizar()`: al editar un recibo ya emitido no se recalcula el prorrateo (los `dias_activos_periodo`/`dias_totales_periodo` quedan fijos desde la emisión original, consistente con A-003 — solo `monto_renta` sigue siendo editable libremente).
- [P] = archivos distintos, sin dependencias pendientes
- Verificar que las pruebas fallan antes de implementar
- Hacer commit tras cada tarea o grupo lógico de tareas
- Evitar: tareas vagas, conflictos de archivo simultáneos, dependencias que rompan la independencia entre historias
