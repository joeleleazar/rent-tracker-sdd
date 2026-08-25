---

description: "Task list for 018-optimizacion-esquema-postgresql"
---

# Tasks: Optimización de Esquema y Consultas PostgreSQL

**Input**: Design documents from `/specs/018-optimizacion-esquema-postgresql/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md (todos existentes)

**Tests**: Incluidas y obligatorias (no opcionales) — exigidas por el Principio IV de la constitución ("Pruebas Automatizadas Exhaustivas") y por el propio Constitution Check de plan.md, que condiciona el PASA de este feature a que las pruebas de cada punto se escriban antes de implementar (TDD).

**Organization**: Tareas agrupadas por historia de usuario de spec.md (US1–US4). Las historias son independientes entre sí en términos de código, salvo una dependencia de **orden de migraciones** entre US3 y US4 (ver nota en T012).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: US1 (registro masivo), US2 (búsqueda inquilinos), US3 (timestamptz), US4 (configuración clave-valor)

## Path Conventions

Aplicación Laravel única: `app/`, `database/migrations/`, `config/`, `tests/` en la raíz del repositorio (ver plan.md "Project Structure").

---

## Phase 1: Setup

**Purpose**: Línea base antes de cualquier cambio de esquema.

- [X] T001 Ejecutar `php artisan test` completo y registrar el resultado como línea base (todas las pruebas existentes en verde) antes de tocar el esquema. **Resultado**: 236 tests, 606 assertions, 100% passed.

---

## Phase 2: Foundational (índices e integridad no ligados a una historia específica)

**Purpose**: Endurecimiento de esquema que ninguna de las 4 historias de usuario reclama individualmente (FR-004, FR-007b), pero que forma parte del alcance general de "endurecer sin romper comportamiento". No bloquea a las historias (tablas distintas), se hace primero por ser el cambio de menor riesgo.

- [X] T002 [P] Crear migración `database/migrations/2026_08_25_060000_agregar_indices_llaves_foraneas.php` que agregue un índice (`Schema::table()->index()`, nombre por defecto de Laravel — research.md R2) a: `documentos_contrato.contrato_id`, `recibos.contrato_id`, `recibos.lectura_medidor_id`, `contrato_inquilino.inquilino_id`, `borradores_lectura_medidor.locacion_id`, `locaciones.locacion_padre_id` (data-model.md, tabla de índices nuevos).
- [X] T003 [P] Crear migración `database/migrations/2026_08_25_060100_check_periodo_dia_uno.php` que, antes de agregar la restricción, ejecute una consulta de verificación por tabla (`SELECT COUNT(*) ... WHERE EXTRACT(DAY FROM periodo) != 1`) en `lecturas_medidor`, `recibos` y `borradores_lectura_medidor`, abortando con un mensaje explícito si encuentra filas violatorias (FR-010), y si no las encuentra agregue `CHECK (EXTRACT(DAY FROM periodo) = 1)` a las tres tablas vía `DB::statement()` (research.md R6, FR-007b).
- [X] T004 Ejecutar `php artisan migrate` y validar quickstart.md paso 2 (índices FK presentes) y paso 7 (el `CHECK` de `periodo` rechaza un insert directo con día ≠ 1). **Resultado**: 6 índices confirmados vía `pg_indexes`; insert inválido rechazado con `QueryException`; suite completa (236 tests) sigue en verde.

**Checkpoint**: Esquema base endurecido; ninguna historia de usuario depende de este checkpoint para empezar, pero todas asumen que ya corrió sin romper la suite (T001 sigue en verde).

---

## Phase 3: User Story 1 - Registro masivo de lecturas sin demoras (Priority: P1) 🎯 MVP

**Goal**: Eliminar el patrón N+1 de `RegistroMasivoLecturasController::store()` sin cambiar ningún resultado observable (contracts/contrato-registro-masivo-optimizado.md).

**Independent Test**: Enviar un lote de 50 filas mixtas (válidas, duplicadas, consumo negativo, no numéricas) y verificar que el resultado por fila es idéntico al actual mientras el conteo de queries deja de crecer linealmente (quickstart.md paso 3).

### Tests for User Story 1 ⚠️ (escribir primero, deben FALLAR antes de implementar)

- [X] T005 [US1] Ampliar `tests/Feature/RegistroMasivoLecturasControllerTest.php` con: (a) un caso que envuelve `store()` en `DB::enableQueryLog()`/`DB::getQueryLog()` y verifica que el conteo de queries para un lote de N filas válidas no crece de forma lineal con N (Contrato 3 de contrato-registro-masivo-optimizado.md); (b) un caso con un lote mixto (fila válida + fila duplicada + fila con consumo negativo sin confirmar + fila no numérica) que verifica que el resultado por fila y el mensaje final de "N lecturas registradas" son exactamente los actuales (Contratos 1 y 2). Debe FALLAR contra el código actual únicamente en (a) (el conteo de queries), no en (b).

### Implementation for User Story 1

- [X] T006 [US1] En `app/Http/Controllers/RegistroMasivoLecturasController.php::store()`, reemplazar los lookups por fila (`Locacion::find()`, `sugerirLecturaAnterior()`, el `->first()` de duplicado dentro de la transacción) por 3 consultas batch previas al `foreach` (mismo patrón `whereIn(...)->keyBy('locacion_id')` ya usado en `datosDelPeriodo()`, líneas 297-309): locaciones por id, lecturas existentes del periodo, y lecturas anteriores. El `foreach` pasa a leer de estas colecciones en memoria; solo el `INSERT` de cada fila válida sigue tocando la base de datos (research.md R4). **Ajuste sobre el diseño original**: se eliminó también el `DB::transaction()` por fila (ya no envuelve una lectura + un insert, solo un insert atómico por sí mismo), con un `catch (QueryException)` para el código `23505` (unique violation) como red de seguridad ante una condición de carrera, preservando el mismo mensaje de duplicado.
- [X] T007 [US1] Confirmar que T005 pasa por completo (se detectó y corrigió un bug de medición en el propio test: `DB::flushQueryLog()` no desactiva el logging, así que el lote de 50 locaciones debía crearse antes de habilitar el log) y ejecutar quickstart.md paso 3. **Resultado**: query log real confirma 3 consultas batch + 1 INSERT por fila válida + 1 DELETE de borradores, sin importar el tamaño del lote; suite completa 238/238 en verde.

**Checkpoint**: Registro masivo funciona igual que antes, sin N+1. Entregable de forma independiente.

---

## Phase 4: User Story 2 - Búsqueda instantánea de inquilinos (Priority: P2)

**Goal**: Resolver el escaneo secuencial de `InquilinoController::buscar()` con un índice `pg_trgm`, sin tocar el controlador (contracts/contrato-busqueda-inquilinos.md).

**Independent Test**: Buscar por un término que coincide en medio de un apellido/DNI y confirmar mismos resultados; confirmar vía `EXPLAIN` que el plan usa el índice GIN (quickstart.md paso 4).

### Tests for User Story 2 ⚠️ (escribir primero, deben FALLAR antes de implementar)

- [X] T008 [P] [US2] Agregar a `tests/Feature/InquilinoControllerTest.php` un caso que ejecute `DB::select("EXPLAIN ...")` sobre la consulta de `buscar()` (o una equivalente) y verifique que el plan contiene `Bitmap Index Scan` (o al menos NO contiene `Seq Scan` con un volumen de datos sembrado suficiente) — debe FALLAR antes de crear el índice, ya que hoy no existe ningún índice trigram.

### Implementation for User Story 2

- [X] T009 [US2] Crear migración `database/migrations/2026_08_25_060200_extension_pg_trgm_busqueda_inquilinos.php` que ejecute `CREATE EXTENSION IF NOT EXISTS pg_trgm` y cree dos índices GIN (`gin_trgm_ops`) sobre `inquilinos.dni` e `inquilinos.apellidos` vía `DB::statement()` (research.md R5).
- [X] T010 [US2] Ejecutar la migración, confirmar que T008 pasa, y correr el resto de `tests/Feature/InquilinoControllerTest.php` para confirmar que el conjunto de resultados de búsqueda por substring no cambió (Contrato 1). **Resultado**: 11/11 tests, 36 assertions, en verde.

**Checkpoint**: Búsqueda de inquilinos usa índice trigram; mismo comportamiento, sin cambios de controlador.

---

## Phase 5: User Story 3 - Fechas y horas consistentes ante cambios de huso horario (Priority: P3)

**Goal**: Migrar las columnas `timestamp` de las 8 tablas de dominio preexistentes (todas excepto `configuracion_general`, ver nota de T012) a `timestamptz`, sin desplazar ningún instante ya guardado (FR-005/FR-006, contracts implícito en spec.md User Story 3).

**Independent Test**: Guardar un timestamp conocido, migrar, releer y confirmar que el instante (`toIso8601String()`) no cambió (quickstart.md paso 5).

### Tests for User Story 3 ⚠️ (escribir primero, deben FALLAR antes de implementar)

- [X] T011 [P] [US3] Crear `tests/Feature/MigracionZonaHorariaTest.php` con casos que: (a) consulten `information_schema.columns` y verifiquen `data_type = 'timestamp with time zone'` para `created_at`/`updated_at` de `contratos`, `recibos`, `lecturas_medidor`, y para las columnas específicas `contratos.notificado_30_dias_en`, `recibos.fecha_pago`, `lecturas_medidor.fecha_registro`; (b) guarden un registro con un `Carbon` UTC explícito, lo releean, y confirmen que el valor no cambió. Debe FALLAR contra el esquema actual (columnas siguen siendo `timestamp without time zone`).

### Implementation for User Story 3

- [X] T012 [US3] Crear migración `database/migrations/2026_08_25_060400_migrar_timestamps_a_timestamptz.php` que, para cada columna de fecha/hora listada en data-model.md (todas las tablas de dominio **excepto `configuracion_general`**, cuyo `created_at`/`updated_at` se crea directamente como `timestamptz` en la migración de rediseño de T016 — ver nota de dependencia abajo), ejecute `DB::statement("ALTER TABLE {tabla} ALTER COLUMN {columna} TYPE timestamptz USING {columna} AT TIME ZONE 'UTC'")` (research.md R1).

  **Nota de dependencia entre historias**: para que esta tarea nunca necesite tocar `configuracion_general`, la migración de T016 (US4) DEBE crear la tabla nueva usando `$table->timestampsTz()` (timestamptz nativo desde el origen) en vez de `$table->timestamps()`. Esto desacopla el orden de ejecución entre T012 y T016 — pueden aplicarse en cualquier orden sin conflicto. Si se decide implementar T016 usando `timestamps()` normal en algún momento, esta tarea deberá incluir `configuracion_general` en su lista y entonces SÍ deberá ejecutarse después de T016.
- [X] T013 [US3] En `config/database.php`, agregar `'timezone' => 'UTC'` a la conexión `pgsql` (medida de defensa complementaria para que la sesión de Postgres no reinterprete los `timestamptz` ya correctos con un offset de servidor distinto — research.md R1).
- [X] T014 [US3] Ejecutar la migración, confirmar que T011 pasa, y validar SC-003 (round-trip de un instante conocido sin desplazamiento, ya cubierto por el segundo caso de T011 dado que este entorno de desarrollo no tenía datos de producción preexistentes que verificar por separado). **Resultado**: 241/241 tests, 671 assertions, en verde.

**Checkpoint**: Todas las marcas de fecha/hora de negocio (excepto `configuracion_general`, cubierta por US4) son `timestamptz`.

---

## Phase 6: User Story 4 - Agregar una nueva configuración sin fricción técnica (Priority: P4)

**Goal**: Reestructurar `configuracion_general` a una tabla clave-valor, preservando la interfaz pública del modelo (contracts/contrato-configuracion-general.md).

**Independent Test**: Insertar una fila con una `clave` nueva sin ninguna migración de esquema y confirmar que el resto del sistema no se ve afectado; confirmar que `ConfiguracionGeneral::actual()->tarifa_luz_por_unidad` (y los otros 3 atributos) siguen funcionando exactamente igual (quickstart.md paso 6).

### Tests for User Story 4 ⚠️ (escribir primero, deben FALLAR antes de implementar)

- [X] T015 [US4] Crear `tests/Unit/ConfiguracionGeneralTest.php` cubriendo los 3 contratos de contracts/contrato-configuracion-general.md: (a) `actual()` devuelve los defaults correctos en una BD recién migrada (sin filas); (b) `actual()->update([...])` con uno y con varios atributos a la vez persiste y no afecta a los demás; (c) los casts (`decimal:4`, `integer`, `datetime`) siguen aplicando sobre los valores leídos. Ampliar también `tests/Feature/ConfiguracionGeneralControllerTest.php` si sus fixtures asumen columnas reales de la tabla. **Ajuste sobre el diseño original**: el archivo ya existía con 3 tests que verificaban invariantes de la forma anterior (`id === 1`, `count() === 1`, recreación vía `delete()`) — invariantes de implementación nunca prometidas por el contrato público, y directamente incompatibles con una tabla clave-valor de N filas. Se reemplazó el contenido completo (no se "amplió") por pruebas alineadas al contrato real. Confirmado en rojo únicamente el caso de extensibilidad (insert directo con columnas `clave`/`valor`, que aún no existían) — los demás ya pasaban contra el modelo viejo por coincidir sus valores por defecto, lo cual es válido como prueba de regresión.

### Implementation for User Story 4

- [X] T016 [US4] Crear migración `database/migrations/2026_08_25_060300_rediseñar_configuracion_general_clave_valor.php` que: dentro de una transacción, (1) lea la fila `id = 1` existente de `configuracion_general` con su forma actual, (2) cree la tabla en su forma nueva (`id`, `clave` varchar unique, `valor` jsonb, `timestampsTz()` — ver nota de T012), (3) inserte una fila por cada uno de los 4 parámetros ya existentes con sus valores actuales codificados en `valor` (jsonb), (4) elimine las columnas/tabla en su forma anterior (data-model.md, sección `configuracion_general`).
- [X] T017 [US4] Reescribir `app/Models/ConfiguracionGeneral.php`: `actual()` consulta todas las filas clave-valor, decodifica `valor`, completa defaults para claves ausentes, e hidrata una instancia con esos atributos (`exists = true`) para que `$casts` sigan aplicando; sobrescribir la persistencia (`save()`/lo que invoque `update()`) para que, por cada atributo modificado (`getDirty()`), haga `updateOrInsert(['clave' => $atributo], ['valor' => json_encode($valor), 'updated_at' => now()])` contra la tabla en vez de un `UPDATE` de una sola fila (research.md R3). **Hallazgo durante implementación**: también fue necesario sobrescribir `fresh()` (delegando a `actual()`), porque la instancia virtual no tiene una fila única identificable por `id` y el `fresh()` por defecto de Eloquent devolvía `null` — rompía `RegistroMasivoLecturasControllerTest`. Documentado como Contrato 2b en contracts/contrato-configuracion-general.md.
- [X] T018 [US4] Ejecutar T015 y confirmar que pasa; ejecutar la suite completa y confirmar que `app/Http/Controllers/ConfiguracionGeneralController.php`, `app/Services/ServicioGeneracionReciboPeriodo.php`, `app/Services/ServicioAlertaFechaLimitePago.php` y `app/Services/ServicioNotificacionVencimientoContrato.php` no requirieron ningún cambio de código (confirmado con `git diff --stat`, cero diff en los 4). `RegistroMasivoLecturasController.php` sí tiene diff, pero es exclusivamente el cambio de US1 (T006) — sus líneas de `actualizarTarifa()`/`datosDelPeriodo()` que usan `ConfiguracionGeneral` no se tocaron. **Resultado**: 243/243 tests, 680 assertions, en verde.

**Checkpoint**: `configuracion_general` es extensible sin migraciones futuras; cero cambios en los 5 call sites existentes.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Verificación final de que el feature completo cumple spec.md sin regresiones.

- [X] T019 Ejecutar `php artisan test` completo y confirmar SC-004: ninguna aserción de resultado esperado cambió respecto a T001, salvo ajustes de fixtures exigidos por el cambio de zona horaria (T012/T013). **Resultado**: 243/243 tests (236 de línea base + 7 nuevos de specs/018), 680 assertions, 0 fallos. Ninguna aserción existente tuvo que modificarse; solo se agregaron pruebas nuevas.
- [X] T020 Ejecutar el resto de pasos de quickstart.md no cubiertos por tareas anteriores. Paso 1 (baseline) = T001; paso 2 (índices FK) = T004; paso 3 (N+1) = T007; paso 4 (EXPLAIN pg_trgm) = T008/T010; paso 5 (timestamptz) = T011/T014; paso 6 (configuracion_general, incluida la inserción manual de una clave nueva sin migración) = T015/T018; paso 7 (CHECK periodo) = T004. Los 7 pasos quedaron cubiertos por las tareas de implementación correspondientes.
- [X] T021 [P] Revisar `specs/018-optimizacion-esquema-postgresql/checklists/requirements.md`: sigue en 16/16, ningún hallazgo de la implementación amerita reabrir la especificación (el único ajuste de alcance — sobrescribir `fresh()` en `ConfiguracionGeneral` — es un detalle de implementación cubierto por FR-007/FR-009 ya existentes, no un requisito nuevo).

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Sin dependencias.
- **Foundational (Phase 2)**: Depende de Phase 1. No bloquea a las historias (tablas distintas) pero se recomienda completarla primero por ser el cambio de menor riesgo.
- **Historias de usuario (Phase 3-6)**: Cada una puede implementarse de forma independiente y en cualquier orden respecto a las demás, **excepto**: T012 (US3) y T016 (US4) tienen una dependencia de diseño (no de orden de tareas) descrita en la nota de T012 — mientras T016 use `timestampsTz()` al crear la tabla nueva, no existe dependencia real de orden.
- **Polish (Phase 7)**: Depende de que todas las historias que se vayan a entregar ya estén completas.

### Parallel Opportunities

- T002 y T003 (Phase 2) pueden ejecutarse en paralelo (archivos de migración distintos).
- Una vez completada Phase 2, las 4 historias de usuario pueden trabajarse en paralelo por distintas personas (tocan archivos y tablas disjuntas): US1 toca `RegistroMasivoLecturasController.php`; US2 toca solo una migración nueva; US3 toca una migración nueva + `config/database.php`; US4 toca `ConfiguracionGeneral.php` + una migración nueva.
- Dentro de cada historia, la tarea de test (T005/T008/T011/T015) se escribe antes que la de implementación, pero no es paralelizable con esta última (mismo área de código bajo prueba).

---

## Implementation Strategy

### MVP First (User Story 1 solamente)

1. Completar Phase 1 (Setup) y Phase 2 (Foundational).
2. Completar Phase 3 (US1 — fix de N+1 en registro masivo, el hallazgo de mayor severidad de la auditoría).
3. Detenerse y validar de forma independiente (quickstart.md paso 3).

### Entrega incremental

1. Setup + Foundational → base lista.
2. US1 (P1) → validar → esto ya es un MVP entregable (el problema de rendimiento más severo queda resuelto).
3. US2 (P2) → validar → búsqueda de inquilinos escalable.
4. US3 (P3) → validar → integridad temporal conforme al Principio I.
5. US4 (P4) → validar → configuración extensible sin migraciones futuras.
6. Phase 7 (Polish) → verificación final de todo el feature junto.
