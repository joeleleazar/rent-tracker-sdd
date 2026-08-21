# Research: Traslado Editable de Lectura Anterior e Historial de Medidor

**Feature**: `006-historial-lectura-medidor` | **Date**: 2026-08-20

## 1. Migración de `lectura` (specs/005) a `lectura_anterior`/`lectura_actual`

**Decision**: Migración de alteración sobre `lecturas_medidor` que (a) renombra la columna `lectura` a `lectura_actual` (`Schema::table('lecturas_medidor', fn ($t) => $t->renameColumn('lectura', 'lectura_actual'))`), (b) agrega `lectura_anterior` (`decimal(12,2)`, nullable), y (c) ejecuta una migración de datos dentro de la misma migración (bloque `up()`) que, para cada `locacion_id`, recorre sus periodos en orden cronológico y asigna a cada fila `lectura_anterior = lectura_actual` de la fila del periodo cronológicamente anterior de esa misma locación (o `null` si es el primer periodo registrado de esa locación), tal como exige la Asunción A-002 de la especificación.

**Rationale**: La especificación 006 es explícita en que esto es un refinamiento del modelo de datos ya introducido por `specs/005` (Asunción A-001), no una tabla nueva. Ejecutar la migración de datos dentro de la propia migración de esquema (en vez de un comando artisan separado a ejecutar manualmente) garantiza que ningún entorno (desarrollo, pruebas, producción) quede con filas migradas a medias, cumpliendo el Principio V (integridad transaccional) — Laravel envuelve automáticamente cada migración en una transacción de base de datos cuando el driver lo soporta (PostgreSQL lo soporta para DDL transaccional).

**Alternatives considered**:
- Mantener la columna `lectura` y agregar `lectura_anterior` como columna nueva sin renombrar: rechazado, generaría dos nombres distintos para el mismo concepto de "lectura actual" entre especificaciones (`lectura` en 005 vs. `lectura_actual` en 006), lo cual contradice el Principio II (nomenclatura consistente) y confundiría a cualquier desarrollador que lea el código más adelante; la especificación 006 usa explícitamente el término "lectura actual" en su texto.
- Ejecutar la migración de datos como un comando artisan aparte, a correr manualmente después de la migración de esquema: rechazado, introduce una ventana de inconsistencia (esquema nuevo, datos viejos) entre el despliegue de la migración y la ejecución manual del comando.

## 2. Autocompletado de `lectura_anterior` con soporte para periodos salteados (FR-002, Edge Case "Registro de periodos fuera de orden")

**Decision**: `ServicioCalculoConsumoMedidor` (de `specs/005`) se refactoriza en dos métodos explícitos: `sugerirLecturaAnterior(Locacion $locacion, string $periodo): ?float` (busca la fila de esa locación con el `periodo` cronológicamente más reciente estrictamente anterior al periodo solicitado, sin asumir que sea el mes calendario inmediato — cubre el caso de meses salteados) y `calcularConsumo(float $lecturaAnterior, float $lecturaActual): float` (resta directa, ya no requiere consultar la base de datos). El controlador invoca `sugerirLecturaAnterior()` únicamente para precargar el campo del formulario al iniciar el registro de un nuevo periodo; una vez guardado, `lectura_anterior` queda almacenada en la propia fila y no vuelve a recalcularse desde otras filas.

**Rationale**: Separar "sugerencia" (una consulta de solo lectura, usada una vez, al momento de mostrar el formulario) de "cálculo" (una operación aritmética pura sobre datos ya en memoria) hace que `calcularConsumo()` sea trivialmente testeable sin base de datos, y evita que el consumo de un periodo cambie silenciosamente si en el futuro se inserta o edita una fila de un periodo anterior — exactamente el comportamiento desacoplado que exige FR-006 ("cada registro de periodo se trata como independiente una vez guardado").

**Alternatives considered**:
- Mantener el cálculo dinámico de `specs/005` (comparar siempre contra la fila del periodo anterior en tiempo de consulta): rechazado explícitamente por esta especificación, que exige que la edición de `lectura_anterior` en un periodo no dependa de ni modifique el periodo previo (FR-006), lo cual requiere almacenar el valor usado, no derivarlo dinámicamente cada vez.

## 3. Advertencia de discrepancia entre periodos consecutivos (FR-007)

**Decision**: Nuevo helper de solo lectura en el modelo `LecturaMedidor`: `discrepanciaConSiguiente(): bool`, que busca la fila de la misma locación con el periodo cronológicamente siguiente y compara `$this->lectura_actual` contra `siguiente->lectura_anterior`; si son distintos, retorna `true`. Se invoca únicamente al renderizar el historial (`LecturaMedidorController@index`, de `specs/005`), mostrando un ícono/badge de advertencia de alto contraste junto al periodo afectado, sin bloquear ninguna operación de guardado.

**Rationale**: La especificación pide una advertencia visible "al consultar el historial" (Acceptance Scenario de US2, FR-007), no una validación en tiempo de guardado — el desacople es intencional (US2, FR-006) y una discrepancia detectada es informativa, no un error a corregir obligatoriamente. Calcularla en el historial (una consulta ya paginada/acotada por locación) es más barato que mantenerla como columna persistida que habría que recalcular en cascada cada vez que cualquier periodo cambia.

**Alternatives considered**:
- Persistir un booleano `tiene_discrepancia` en la propia fila, recalculado por un observer al guardar cualquier periodo adyacente: rechazado, reintroduce el acoplamiento entre periodos que FR-006 prohíbe explícitamente evitar tras el guardado; además requeriría recalcular la fila anterior cada vez que se guarda una nueva, un efecto colateral no solicitado por la especificación.

## 4. Framework de pruebas

**Decision**: Pest, consistente con `specs/001-005`.

**Rationale**: Ya adoptado por el proyecto.

**Alternatives considered**: Ninguna — decisión ya tomada a nivel de proyecto.
