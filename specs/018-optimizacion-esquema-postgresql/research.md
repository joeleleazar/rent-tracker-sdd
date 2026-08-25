# Research: Optimización de Esquema y Consultas PostgreSQL

**Feature**: `018-optimizacion-esquema-postgresql` | **Date**: 2026-08-25

## R1 — Migración de `timestamp` a `timestamptz` (FR-005, FR-006)

**Decision**: Una migración nueva (no se edita ninguna de las 24 migraciones históricas) que, por cada tabla de dominio, ejecuta `DB::statement("ALTER TABLE {tabla} ALTER COLUMN {columna} TYPE timestamptz USING {columna} AT TIME ZONE 'UTC'")` para cada columna de fecha/hora (`created_at`, `updated_at` de las 9 tablas de dominio, más `notificado_30/15/7_dias_en`, `fecha_pago`, `fecha_anulacion`, `fecha_registro`, `fecha_resolucion_garantia`, `alerta_pago_mes_enviada_en`, y `email_verified_at`/`fecha_registro`donde aplique). La cláusula `AT TIME ZONE 'UTC'` fija explícitamente cómo interpretar cada valor ya almacenado, sin depender del `timezone` de sesión del servidor Postgres (que hoy no está fijado en `config/database.php`).

**Rationale**: `config('app.timezone')` está codificado como `'UTC'` en `config/app.php:68` (no lee `env('APP_TIMEZONE')`, es un valor fijo del framework), por lo que todo timestamp que la app ya escribió representa un instante UTC. Convertir con `AT TIME ZONE 'UTC'` reinterpreta cada valor naive como UTC y lo adjunta como tal — el instante mostrado no cambia (satisface SC-003). Editar las migraciones históricas está descartado porque, si alguna vez se corren desde cero en un entorno nuevo, el orden de ejecución seguiría siendo válido, pero el proyecto ya tiene un patrón establecido de "agregar migración correctiva después" (ej. `2026_08_21_044219_refine_lecturas_medidor_anterior_actual.php`) en vez de reescribir migraciones ya aplicadas.

**Alternatives considered**:
- *Editar las 24 migraciones para usar `timestampTz()` desde el origen*: descartado — rompería cualquier entorno que ya corrió las migraciones originales (estado de `migrations` table desincronizado), y contradice el patrón ya usado en este repo de migraciones correctivas posteriores.
- *Confiar en el `timezone` de sesión de Postgres sin `AT TIME ZONE` explícito*: descartado — frágil ante cambios futuros de configuración del servidor de base de datos; `AT TIME ZONE 'UTC'` es explícito y auto-documentado.
- *Fijar `'timezone' => 'UTC'` en la conexión `pgsql` de `config/database.php` como medida adicional de defensa*: se adopta como complemento (no alternativa) — evita que una sesión con `timezone` distinto de UTC muestre timestamps desplazados al leerlos vía Eloquent (`Carbon` ya asume el offset correcto de `timestamptz`, pero fijar la sesión evita sorpresas en herramientas externas que consulten la BD directamente).

## R2 — Índices de soporte para columnas de llave foránea (FR-004)

**Decision**: `Schema::table()->index('columna')` (nombre de índice generado por defecto de Laravel, ej. `documentos_contrato_contrato_id_index`) para cada una de las 6 columnas señaladas en FR-004, en una única migración nueva.

**Rationale**: El proyecto ya tiene índices compuestos con nombres por defecto de Laravel (`contratos_locacion_id_fecha_inicio_fecha_fin_index` en `2026_08_20_031148...php:23`), así que usar la convención `{table}_{column}_idx` del skill `postgres` introduciría dos estilos de nombre de índice distintos en el mismo esquema sin ningún beneficio funcional. Se prioriza consistencia con el proyecto existente sobre la convención genérica del skill.

**Alternatives considered**: Nombrar explícitamente los índices nuevos como `{tabla}_{columna}_idx` — descartado por la razón anterior (inconsistencia sin beneficio).

## R3 — Rediseño de `configuracion_general` a clave-valor (FR-007, FR-007b)

**Decision**: La tabla pasa a `id bigint PK`, `clave varchar unique`, `valor jsonb`, `timestamps`. El modelo `ConfiguracionGeneral` (`app/Models/ConfiguracionGeneral.php`) dejará de mapear sus 4 atributos (`correo_notificaciones_vencimiento`, `tarifa_luz_por_unidad`, `dias_anticipacion_alerta_pago`, `alerta_pago_mes_enviada_en`) a columnas reales; en su lugar:
- `ConfiguracionGeneral::actual()` consulta todas las filas de la tabla, decodifica `valor` (jsonb → PHP), construye un array `[clave => valor]` completando con los valores por defecto actuales para cualquier clave ausente (`tarifa_luz_por_unidad` → 0, `dias_anticipacion_alerta_pago` → 5, `alerta_pago_mes_enviada_en` → null, `correo_notificaciones_vencimiento` → `config('mail.from.address')`), e hidrata una instancia del modelo con esos atributos ya en memoria (`exists = true`), de forma que `$casts` (`decimal:4`, `integer`, `datetime`) siguen aplicando exactamente igual que hoy.
- `save()`/`update()` (invocados hoy como `ConfiguracionGeneral::actual()->update([...])`) se sobrescriben para, por cada atributo modificado (`getDirty()`), hacer `updateOrInsert(['clave' => $atributo], ['valor' => json_encode($valor), 'updated_at' => now()])` contra la tabla, en vez del `UPDATE` de una sola fila que genera Eloquent por defecto.
- Ningún controlador, servicio o vista cambia: `ConfiguracionGeneralController`, `RegistroMasivoLecturasController::actualizarTarifa()/datosDelPeriodo()`, `ServicioGeneracionReciboPeriodo`, `ServicioAlertaFechaLimitePago` y `ServicioNotificacionVencimientoContrato` siguen leyendo/escribiendo `$configuracion->tarifa_luz_por_unidad`, etc., sin saber que el almacenamiento cambió (FR-007, FR-009).

**Rationale**: Es la única forma de cumplir el requisito explícito del usuario (agregar una configuración futura = una fila, no una migración de columna) sin tocar los 5 archivos que ya leen/escriben la configuración actual. `jsonb` para `valor` preserva el tipo nativo por fila (número, texto, fecha) en vez de forzar texto plano, siguiendo la guía del propio skill `postgres` ("Use JSONB for JSON data") y evitando la pérdida de tipado que motivó FR-005/FR-006 en primer lugar.

**Alternatives considered**:
- *Migrar los call sites a una API explícita (`$config->obtener('clave')`/`$config->establecer('clave', $valor)`)*: más simple de implementar, pero viola FR-009 y obliga a tocar 5 archivos sin necesidad real (documentado también en Complexity Tracking de plan.md).
- *Vista de Postgres que pivotea las filas clave-valor en columnas (crosstab) para que Eloquent siga leyendo "columnas"*: descartada — las vistas no son escribibles de forma trivial para el `update()` mass-assignment actual sin triggers `INSTEAD OF`, lo que añade más complejidad de base de datos que la sobrescritura de `save()` en PHP.
- *Mantener la tabla ancha y agregar una tabla separada solo para configuraciones futuras*: descartada — dos sistemas de configuración coexistiendo es más confuso que uno solo, y no es lo que pidió el usuario.

## R4 — Eliminación del N+1 en `RegistroMasivoLecturasController::store()` (FR-001, FR-002)

**Decision**: Antes del `foreach`, tres consultas batch (mismo patrón `whereIn(...)->keyBy('locacion_id')` ya usado en `datosDelPeriodo()` del mismo archivo, líneas 297-309):
1. `Locacion::whereIn('id', array_keys($filas))->get()->keyBy('id')` — reemplaza el `Locacion::find($locacionId)` por fila.
2. `LecturaMedidor::whereIn('locacion_id', array_keys($filas))->where('periodo', $periodo->format('Y-m-d'))->get()->keyBy('locacion_id')` — reemplaza el `->first()` de duplicado por fila dentro de la transacción.
3. La misma consulta de "lectura anterior" que ya usa `datosDelPeriodo()` (`whereIn(...)->where('periodo', '<', ...)->orderByDesc('periodo')->get()->unique('locacion_id')->keyBy('locacion_id')`) reemplaza la llamada a `sugerirLecturaAnterior()` por fila.

El `foreach` pasa a leer de estas 3 colecciones en memoria; solo el `INSERT` de cada lectura válida sigue tocando la base de datos, uno por fila (no se agrupa en un solo `insert()` masivo).

**Rationale**: Reduce el conteo de consultas de ~4N+1 a 3+N (N = filas realmente válidas a insertar), suficiente para cumplir SC-001. Se preserva el `DB::transaction()` por fila (ahora envolviendo solo el `INSERT`, ya no una lectura de duplicado) para conservar exactamente la misma granularidad de error por fila (FR-002): si una fila individual falla (duplicado detectado por la colección prefetched, o una condición de carrera detectada por la restricción única `(locacion_id, periodo)` ya existente en BD), el resto del lote sigue procesándose igual que hoy.

**Alternatives considered**:
- *Un solo `INSERT` masivo (`LecturaMedidor::insert([...])`) para todas las filas válidas*: descartado — perdería la posibilidad de capturar qué fila específica violó la restricción única en una condición de carrera, cambiando el comportamiento de reporte de errores por fila que exige FR-002.
- *Envolver todo el lote en una única transacción*: descartado — cambiaría el comportamiento actual de "éxito parcial" (algunas filas se guardan, otras fallan) a todo-o-nada, violando FR-002/FR-009.

## R5 — Búsqueda de inquilinos sin escaneo secuencial (FR-008)

**Decision**: `CREATE EXTENSION IF NOT EXISTS pg_trgm;` seguido de `CREATE INDEX ... USING gin (dni gin_trgm_ops)` y `CREATE INDEX ... USING gin (apellidos gin_trgm_ops)` sobre `inquilinos`, ambos vía `DB::statement()` en una migración nueva.

**Rationale**: El planificador de PostgreSQL usa automáticamente un índice GIN de trigramas para predicados `ILIKE '%término%'` sin necesitar reescribir la consulta en `InquilinoController::buscar()` — es la única técnica de índice que acelera un `ILIKE` con comodín al inicio sin cambiar la semántica de coincidencia por substring que el usuario ya usa (confirmado viable en el entorno de despliegue, Q3 de `/speckit-specify`). Esto cumple FR-008/FR-009 con cero cambios de código en el controlador.

**Alternatives considered**: Índice `GIN`/`btree` estándar sin `pg_trgm` — descartado, no acelera `LIKE`/`ILIKE` con comodín al inicio. Reescribir la búsqueda a coincidencia por prefijo (`LIKE 'término%'`) para poder usar el índice único existente de `dni` — descartado explícitamente, cambiaría el comportamiento de búsqueda que el usuario ya tiene (viola FR-003/FR-009).

## R6 — `CHECK` de integridad para `periodo` día 1 (FR-007b)

**Decision**: Antes de agregar el `CHECK (EXTRACT(DAY FROM periodo) = 1)` a `lecturas_medidor`, `recibos` y `borradores_lectura_medidor`, la migración ejecuta una consulta de verificación (`SELECT COUNT(*) FROM {tabla} WHERE EXTRACT(DAY FROM periodo) != 1`) y aborta con un mensaje explícito (lanzando una excepción desde la migración) si encuentra alguna fila, en vez de dejar que `ALTER TABLE ... ADD CONSTRAINT` falle con un error genérico de Postgres.

**Rationale**: Cumple FR-010 (verificar antes de restringir, reportar de forma clara) sin asumir que los datos actuales ya cumplen la regla solo porque la aplicación siempre normaliza a día 1 — un mensaje de error explícito ahorra tiempo de diagnóstico si alguna vez hubo una inserción directa que la violó.

**Alternatives considered**: Confiar en que `ADD CONSTRAINT` falle con su propio mensaje de Postgres si hay violaciones — descartado, el mensaje de Postgres no identifica qué filas violan la regla ni en qué tabla de forma amigable para quien ejecute la migración.
