# Feature Specification: Optimización de Esquema y Consultas PostgreSQL

**Feature Branch**: `018-optimizacion-esquema-postgresql`

**Created**: 2026-08-25

**Status**: Draft

**Input**: User description: "Mejoras de esquema y consultas PostgreSQL en rent-tracker, siguiendo las mejores prácticas del skill "postgres" (PlanetScale database-skills). Basado en una auditoría completa de las 24 migraciones, 9 modelos Eloquent y controladores/servicios existentes, el objetivo es endurecer la base de datos sin romper el comportamiento actual de la app: (1) agregar índices de soporte a columnas de llave foránea sin cobertura; (2) eliminar el patrón N+1 detectado en el registro masivo de lecturas; (3) resolver el escaneo secuencial forzado por búsqueda ILIKE en inquilinos; (4) evaluar restricciones de integridad para configuración general y periodos; (5) evaluar migrar timestamps sin zona horaria a timestamptz. Cada mejora debe mantener el comportamiento observable actual salvo que se documente lo contrario, y el feature debe pasar por el flujo completo de speckit (specify, clarify, plan, tasks) antes de implementar."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Registro masivo de lecturas sin demoras (Priority: P1)

Un operador que registra las lecturas de medidor de un piso completo (varias decenas de locaciones) en una sola operación de registro masivo espera que el sistema procese el lote completo con la misma fluidez sin importar cuántas locaciones incluya, sin bloqueos ni tiempos de espera perceptibles que crezcan con el tamaño del lote.

**Why this priority**: Es el problema de rendimiento más severo detectado (crecimiento lineal de consultas por fila enviada) y el que más directamente afecta una operación diaria del negocio (cierre de periodo de lecturas).

**Independent Test**: Puede probarse enviando un lote de registro masivo con un número significativo de locaciones y verificando que el tiempo de respuesta no degrada de forma proporcional al tamaño del lote, y que el resultado guardado (lecturas creadas, duplicados detectados, sugerencias de lectura anterior) es idéntico al comportamiento actual.

**Acceptance Scenarios**:

1. **Given** un periodo con N locaciones pendientes de lectura, **When** el operador envía el formulario de registro masivo con lecturas para todas ellas, **Then** el sistema guarda todas las lecturas válidas en una sola operación consistente, sin que el tiempo de procesamiento crezca de forma lineal por locación.
2. **Given** un lote de registro masivo que incluye una locación con una lectura ya registrada para ese periodo, **When** se envía el lote, **Then** el sistema detecta el duplicado y lo reporta exactamente igual que hoy, sin omitir ni duplicar el resto de las lecturas válidas del mismo lote.

---

### User Story 2 - Búsqueda instantánea de inquilinos (Priority: P2)

Un operador que busca un inquilino por DNI o apellido desde un formulario de autocompletado espera resultados casi instantáneos sin importar cuántos inquilinos existan registrados en el sistema.

**Why this priority**: Afecta una interacción frecuente (buscar inquilino al crear/editar un contrato) y su costo crece con el tiempo a medida que la base de inquilinos aumenta, aunque hoy con pocos registros el impacto es bajo.

**Independent Test**: Puede probarse ejecutando la búsqueda con distintos términos (coincidencia exacta, parcial al inicio, parcial en medio de la palabra) y verificando que los resultados devueltos son idénticos a los actuales, con tiempo de respuesta estable a medida que crece el número de inquilinos.

**Acceptance Scenarios**:

1. **Given** una base de inquilinos con muchos registros, **When** el operador escribe parte de un DNI o apellido en el buscador, **Then** el sistema devuelve las mismas coincidencias que produce hoy (incluyendo coincidencias en medio de la palabra), en un tiempo que no se degrada al crecer la tabla.

---

### User Story 3 - Fechas y horas consistentes ante cambios de huso horario (Priority: P3)

Un administrador que revisa el historial de notificaciones de vencimiento, pagos y resoluciones de garantía espera que las marcas de fecha/hora registradas representen siempre el mismo instante real, independientemente de la zona horaria configurada en el servidor de la aplicación o de la base de datos en el futuro.

**Why this priority**: Es un requisito de integridad de datos ya exigido por la constitución del proyecto (Principio I: "marcas temporales con zona horaria"), pero de menor urgencia inmediata que los dos puntos de rendimiento anteriores porque hoy la app opera en una única zona horaria fija.

**Independent Test**: Puede probarse fijando una fecha/hora conocida, generando un registro que dependa de marca temporal (por ejemplo, una notificación de vencimiento o un pago), y verificando que el instante almacenado y el mostrado al usuario siguen siendo exactamente los mismos antes y después del cambio.

**Acceptance Scenarios**:

1. **Given** un registro existente con una marca de fecha/hora (por ejemplo, una notificación de vencimiento ya enviada), **When** se aplica la migración de endurecimiento del esquema, **Then** la fecha/hora mostrada al usuario para ese registro no cambia respecto a lo que mostraba antes de la migración.
2. **Given** un nuevo evento que registra fecha/hora (pago, notificación, resolución de garantía), **When** ocurre el evento, **Then** el instante guardado sigue representando la hora local de negocio de la misma forma en que lo hace hoy.

---

### User Story 4 - Agregar una nueva configuración sin fricción técnica (Priority: P4)

Un administrador que necesita que el sistema soporte un nuevo parámetro de configuración general (por ejemplo, una nueva tarifa o un nuevo umbral de alerta) espera que agregarlo sea una operación de datos simple, sin requerir una migración de esquema que agregue una columna nueva a la tabla de configuración.

**Why this priority**: Es una mejora de mantenibilidad a futuro, no un problema de rendimiento o integridad activo hoy; se prioriza después de los tres puntos anteriores.

**Independent Test**: Puede probarse agregando una nueva configuración de prueba como un nuevo registro de datos (sin tocar el esquema) y verificando que el resto del sistema sigue leyendo y escribiendo las configuraciones existentes exactamente igual que antes.

**Acceptance Scenarios**:

1. **Given** la configuración general del sistema, **When** se necesita agregar un nuevo parámetro de configuración, **Then** esto se logra insertando un nuevo registro de datos, sin requerir una migración que agregue una columna a la tabla.
2. **Given** el código existente que lee o actualiza una configuración general (tarifa de luz, correo de notificaciones, días de anticipación de alerta, marca de envío de alerta mensual), **When** se reestructura el almacenamiento de la configuración, **Then** ese código sigue funcionando sin modificaciones, obteniendo los mismos valores que antes.

---

### Edge Cases

- ¿Qué ocurre si al momento de aplicar un nuevo índice o restricción ya existen datos que la violarían (por ejemplo, más de una fila en `configuracion_general`, o un `periodo` que no cae en el día 1 del mes)? La migración correspondiente debe detectar y reportar esos casos antes de aplicar la restricción, en vez de fallar a mitad de camino o corromper datos.
- ¿Qué ocurre con una búsqueda de inquilino que hoy depende de un comportamiento de coincidencia parcial (substring, no solo prefijo)? La solución de rendimiento elegida no debe reducir el conjunto de resultados que el operador ya está acostumbrado a obtener.
- ¿Qué ocurre si el lote de registro masivo mezcla locaciones válidas con locaciones inexistentes o sin permiso? El comportamiento de validación/reporte de errores por fila debe seguir siendo el mismo tras optimizar las consultas.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE responder al registro masivo de lecturas con un número de consultas a la base de datos que no crece de forma lineal con la cantidad de locaciones incluidas en el lote (batch-fetch en vez de una consulta por locación), preservando exactamente el mismo resultado (lecturas creadas, duplicados detectados, sugerencias de lectura anterior) que produce hoy.
- **FR-002**: El sistema DEBE mantener la detección de duplicados y la atomicidad del guardado del lote de registro masivo (si una parte del lote falla de forma que hoy invalidaría todo el lote, debe seguir invalidándolo; si hoy permite guardar parcialmente, debe seguir permitiéndolo de la misma forma).
- **FR-003**: El sistema DEBE responder a la búsqueda de inquilinos por DNI/apellido sin recurrir a un recorrido secuencial completo de la tabla, preservando el mismo conjunto de coincidencias (incluyendo coincidencias parciales en medio de la palabra) que produce hoy.
- **FR-004**: El sistema DEBE tener, para cada columna que referencia a otra tabla (llave foránea) y que hoy carece de un índice que la cubra como columna líder, un índice de soporte: `documentos_contrato.contrato_id`, `recibos.contrato_id`, `recibos.lectura_medidor_id`, `contrato_inquilino.inquilino_id`, `borradores_lectura_medidor.locacion_id`, `locaciones.locacion_padre_id`.
- **FR-005**: El sistema DEBE almacenar todas las marcas de fecha/hora del dominio del negocio (no solo fechas puras como `periodo` o `fecha_inicio`) con información de zona horaria, de forma que el instante real representado sea recuperable sin ambigüedad, cumpliendo el Principio I de la constitución del proyecto.
- **FR-006**: La migración de marcas de fecha/hora a un formato con zona horaria DEBE interpretar todos los valores ya almacenados usando la zona horaria configurada en `APP_TIMEZONE` de la aplicación, de forma que el instante mostrado al usuario para cada registro existente no cambie tras la migración.
- **FR-007**: El sistema DEBE reestructurar `configuracion_general` de una tabla de una sola fila con una columna por parámetro, a una tabla de configuración en formato clave-valor (columnas `clave` y `valor`), de modo que agregar un nuevo parámetro de configuración en el futuro sea una operación de datos (una fila nueva) y no una migración de esquema (una columna nueva). La columna `valor` DEBE usar un tipo (`jsonb`) capaz de preservar el tipo de dato específico de cada configuración (numérico para tarifas, texto para el correo de notificación, fecha/hora con zona horaria para marcas de envío) en vez de forzar todos los valores a texto plano. La columna `clave` DEBE tener una restricción de unicidad que impida configuraciones duplicadas. La interfaz de lectura/escritura usada hoy por el resto de la aplicación para cada configuración existente (tarifa por unidad de luz, correo de notificaciones, días de anticipación de alerta de pago, marca de alerta de pago del mes ya enviada) DEBE mantenerse igual, de forma que ningún controlador, servicio o vista existente necesite cambiar para seguir leyendo o escribiendo estas configuraciones.
- **FR-007b**: El sistema DEBE agregar una restricción a nivel de base de datos que impida que las columnas `periodo` de `lecturas_medidor`, `recibos` y `borradores_lectura_medidor` almacenen una fecha que no sea el primer día de un mes calendario, como extensión del mismo esfuerzo de endurecimiento de integridad de FR-007, verificando primero que no existan datos existentes que la violarían (FR-010).
- **FR-008**: El sistema DEBE resolver el escaneo secuencial de la búsqueda de inquilinos habilitando la extensión `pg_trgm` de PostgreSQL y un índice basado en trigramas sobre las columnas usadas en la búsqueda (`dni`, `apellidos`), preservando exactamente el mismo comportamiento de coincidencia parcial (substring, no solo prefijo) que produce hoy.
- **FR-009**: Ninguna de las mejoras de este feature DEBE alterar el comportamiento observable de las funcionalidades existentes (resultados de búsqueda, validaciones, mensajes de error, resultados de registro masivo) salvo en los puntos donde una decisión de esta especificación documente explícitamente lo contrario.
- **FR-010**: Antes de aplicar cualquier restricción de integridad nueva (índice único, `CHECK`), el sistema DEBE verificar que los datos existentes ya la cumplen, y reportar de forma clara cualquier dato existente que la violaría, en vez de aplicar una migración que falle a mitad de camino o corrompa datos.

### Key Entities

- **Contrato, Recibo, LecturaMedidor, DocumentoContrato, Inquilino, Locación, BorradorLecturaMedidor**: entidades de negocio ya existentes; este feature endurece la integridad, el rendimiento y la fidelidad temporal del esquema que ya las respalda, sin cambiar su forma.
- **Configuración General**: entidad existente que cambia de forma (de una fila con una columna por parámetro, a una colección de pares clave-valor), pero conserva el mismo significado de negocio y la misma interfaz de lectura/escritura para el resto del sistema (tarifa por unidad de luz, correo de notificaciones de vencimiento, días de anticipación de alerta de pago, marca de alerta de pago del mes ya enviada).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El tiempo de procesamiento del registro masivo de lecturas para un lote de 50 locaciones no supera en más de un 20% el tiempo de procesamiento de un lote de 5 locaciones (hoy crece de forma aproximadamente lineal por locación).
- **SC-002**: El tiempo de respuesta de la búsqueda de inquilinos permanece estable (variación menor al 20%) al comparar una base con 100 inquilinos contra una base con 5,000 inquilinos.
- **SC-003**: El 100% de los registros de fecha/hora existentes antes de la migración muestran exactamente el mismo instante al usuario después de aplicada la migración de zona horaria.
- **SC-004**: El 100% de las pruebas automatizadas existentes para registro masivo de lecturas, búsqueda de inquilinos, contratos, recibos, lecturas de medidor y configuración general siguen pasando sin modificación de sus aserciones de resultado esperado (solo se permiten ajustes de configuración/fixtures si el cambio de zona horaria lo exige).
- **SC-005**: Cero incidentes de pérdida o corrupción de datos existentes al aplicar las nuevas restricciones de integridad (índices, `CHECK`, unicidad de clave) y la reestructuración de configuración general, en un entorno con datos ya cargados.
- **SC-006**: Agregar una nueva configuración general al sistema en el futuro no requiere escribir ni ejecutar una migración de esquema.

## Assumptions

- El entorno de base de datos objetivo es PostgreSQL 15+ tal como exige la constitución del proyecto; las recomendaciones del skill `postgres` (PlanetScale database-skills) aplican en su forma genérica de PostgreSQL, no en las funcionalidades específicas de hosting de PlanetScale (pooling, CLI `pscale`), que están fuera de alcance porque el proyecto no aloja su base de datos en PlanetScale.
- El volumen de datos actual es bajo (aplicación en uso reciente, agosto 2026), por lo que las mejoras de este feature son preventivas/estructurales más que una respuesta a un problema de rendimiento ya observado en producción, salvo el registro masivo de lecturas donde el patrón N+1 ya es medible en el código actual.
- Las pruebas automatizadas existentes (Principio IV de la constitución) son la referencia de comportamiento observable a preservar; cualquier cambio de resultado esperado debe justificarse explícitamente como parte de este feature, no asumirse como efecto colateral aceptable.
- No se requiere migrar los tres campos `periodo` (que son de tipo fecha, no fecha/hora) a un tipo con zona horaria: representan un mes calendario de negocio, no un instante, por lo que quedan fuera del alcance de FR-005/FR-006; sí reciben la restricción de día-1 descrita en FR-007b.
- El diseño clave-valor de `configuracion_general` (FR-007) es una decisión explícita del usuario para priorizar extensibilidad futura (agregar configuraciones sin migraciones de esquema) sobre la forma tabular actual. Se elige `jsonb` para la columna `valor` en vez de texto plano porque preserva tipado nativo de PostgreSQL por fila (numérico, texto, fecha/hora), siguiendo la guía del skill `postgres` de usar `jsonb` para datos de forma variable, y evitando así la pérdida de tipado que motivó FR-005/FR-006 en primer lugar.
- La restricción de día-1 para `periodo` (FR-007b) se agrega como extensión del mismo esfuerzo de endurecimiento, dado que la decisión del usuario en torno a `configuracion_general` indica preferencia por mayor rigor de integridad; se aplica solo tras verificar que no existan datos existentes que la violarían (FR-010).
