# Feature Specification: Traslado Editable de Lectura Anterior e Historial de Medidor

**Feature Branch**: `006-historial-lectura-medidor`

**Created**: 2026-08-19

**Status**: Ready

**Input**: User description: "La lectura de luz tiene lectura anterior y lectura actual, la lectura actual en el proximo periodo debe aparecer como lectura anterior y editable, se debe mantener un historial del mismo."

## Actualización (2026-08-25)

El criterio original de esta spec (User Story 1, Escenario 2 más abajo) exigía el texto explícito
`"Sin lectura previa registrada"` cuando no existe periodo previo. specs/019-total-editable-recibos
(Q1) y specs/021-derivar-consumo-calculado (Q1:A) establecieron después, como decisión deliberada, que
la ausencia de lectura anterior se trata como `0` en todo cálculo de consumo del sistema — y esa misma
convención se extendió a la interfaz de registro masivo (`resources/views/lecturas/registro-masivo/`),
donde la columna "Lectura Periodo Anterior" ahora muestra el número `0` en vez de esa etiqueta de
texto. El Escenario 2 y el Edge Case correspondientes de esta spec se actualizan más abajo para
reflejar ese criterio vigente; el texto original queda tachado como referencia histórica. Este cambio
de comportamiento se detectó ya implementado (no documentado) durante specs/021, y se enmienda aquí a
pedido explícito del usuario para mantener las specs alineadas con el código real (ver también
`specs/016-correccion-registro-masivo-lecturas/contracts/lectura-anterior-y-autoguardado.md`, enmendado
en paralelo).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Traslado Automático de Lectura Actual a Lectura Anterior del Siguiente Periodo (Priority: P1)

Como Administrador, quiero que al registrar la lectura de un nuevo periodo el sistema autocomplete el campo "lectura anterior" con el valor de la "lectura actual" del periodo previo, de modo que no tenga que buscar ni volver a escribir manualmente ese dato cada mes.

**Why this priority**: Es el comportamiento central solicitado: elimina la carga de trabajo repetitiva y el riesgo de transcribir mal la lectura anterior, que es la base para calcular el consumo del nuevo periodo.

**Independent Test**: Se puede verificar registrando la lectura "1250" como "lectura actual" del periodo "Julio 2026", y luego iniciando el registro del periodo "Agosto 2026" para la misma locación, comprobando que el campo "lectura anterior" aparezca precargado automáticamente con "1250".

**Acceptance Scenarios**:

1. **Given** que la locación "Local A" tiene registrada una lectura actual de "1250" para el periodo "Julio 2026", **When** el administrador inicia el registro de la lectura del periodo "Agosto 2026" para "Local A", **Then** el sistema precarga el campo "lectura anterior" con el valor "1250".
2. ~~**Given** que una locación no tiene ningún periodo registrado previamente, **When** el administrador inicia el registro de su primera lectura, **Then** el campo "lectura anterior" aparece vacío y editable, indicando claramente "Sin lectura previa registrada", sin bloquear el ingreso de la "lectura actual".~~ (texto original, superado — ver "Actualización 2026-08-25")
2. **Given** que una locación no tiene ningún periodo registrado previamente, **When** el administrador inicia el registro de su primera lectura, **Then** el campo "lectura anterior" muestra "0" y permanece editable, sin bloquear el ingreso de la "lectura actual".

---

### User Story 2 - Edición del Valor Trasladado antes de Confirmar (Priority: P1)

Como Administrador, quiero poder editar el valor de "lectura anterior" que el sistema autocompletó a partir del periodo previo, de modo que pueda corregir errores de digitación o reflejar un cambio físico del medidor antes de calcular el consumo del nuevo periodo.

**Why this priority**: Los datos trasladados automáticamente pueden requerir corrección puntual (medidor reemplazado, dato mal ingresado el mes anterior); sin esta edición el administrador quedaría atrapado con un valor incorrecto que distorsiona el consumo calculado.

**Independent Test**: Se puede verificar iniciando el registro de un nuevo periodo con "lectura anterior" precargada en "1250", editándola manualmente a "1245", ingresando la "lectura actual" y guardando, comprobando que el consumo calculado use "1245" y no el valor original trasladado.

**Acceptance Scenarios**:

1. **Given** el formulario de un nuevo periodo con "lectura anterior" precargada en "1250", **When** el administrador la edita a "1245" e ingresa "lectura actual" "1400", **Then** el sistema calcula y muestra el consumo como "155" (1400 - 1245), y guarda "1245" como la "lectura anterior" utilizada para ese periodo.
2. **Given** que el administrador edita la "lectura anterior" precargada de un nuevo periodo, **When** consulta posteriormente el registro histórico del periodo previo (el que originó el traslado), **Then** el valor de "lectura actual" de ese periodo previo permanece sin cambios, mostrando de forma clara que ambos periodos ahora tienen valores distintos para el mismo punto de lectura.

---

### User Story 3 - Consulta del Historial Completo de Lecturas por Locación (Priority: P2)

Como Administrador, quiero consultar el historial completo de lecturas anteriores y actuales de cada periodo de una locación, de modo que pueda verificar la trazabilidad del consumo eléctrico registrado mes a mes, incluyendo los casos donde se corrigió algún valor trasladado.

**Why this priority**: Sin un historial visible y completo no es posible auditar de dónde provienen los montos de luz cobrados en los recibos ni detectar inconsistencias en la cadena de lecturas.

**Independent Test**: Se puede validar consultando el historial de una locación con cuatro periodos registrados y comprobando que se listen los cuatro registros en orden cronológico, cada uno mostrando su "lectura anterior", "lectura actual" y consumo calculado, sin que ningún periodo desaparezca al registrar uno nuevo.

**Acceptance Scenarios**:

1. **Given** una locación con lecturas registradas para "Mayo 2026", "Junio 2026", "Julio 2026" y "Agosto 2026", **When** el administrador consulta el historial de lecturas de esa locación, **Then** el sistema muestra los cuatro periodos en orden cronológico con sus valores de "lectura anterior", "lectura actual" y consumo calculado, con tipografía de al menos 18px y alto contraste.

### Edge Cases

- **Corrección posterior de una lectura actual ya trasladada**: Si el administrador corrige la "lectura actual" de un periodo cuyo valor ya fue trasladado como "lectura anterior" de un periodo posterior, el sistema NO actualiza automáticamente el periodo posterior; muestra una advertencia indicando que existe un periodo posterior que usó el valor anterior y que puede requerir revisión manual.
- **Primer periodo de una locación nueva**: Como no existe periodo previo, el campo "lectura anterior" inicia en `0` y editable (ver "Actualización 2026-08-25"), permitiendo que el administrador registre un valor inicial si el medidor ya tenía consumo acumulado al momento de dar de alta la locación.
- **Registro de periodos fuera de orden**: Si el administrador registra un periodo salteando meses (por ejemplo, registra "Agosto 2026" sin haber registrado "Julio 2026"), el sistema traslada como "lectura anterior" la "lectura actual" del último periodo registrado disponible (el más reciente cronológicamente antes del nuevo periodo), indicando claramente de qué periodo proviene ese valor.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST registrar, para cada periodo de lectura de una locación, dos valores explícitos: "lectura anterior" y "lectura actual".
- **FR-002**: El sistema MUST autocompletar el campo "lectura anterior" de un nuevo periodo con el valor de "lectura actual" del periodo cronológicamente más reciente ya registrado para esa misma locación.
- **FR-003**: El sistema MUST permitir que el administrador edite libremente el valor autocompletado de "lectura anterior" antes de guardar el nuevo periodo, sin exigir que coincida con el valor trasladado automáticamente.
- **FR-004**: El sistema MUST calcular el consumo del periodo como la diferencia entre "lectura actual" y "lectura anterior" (editada o no) del mismo registro, y mostrarlo de inmediato al administrador.
- **FR-005**: El sistema MUST conservar el historial completo de todos los periodos registrados por locación (con sus valores de "lectura anterior", "lectura actual" y consumo calculado), sin eliminar ni sobrescribir periodos anteriores al registrar uno nuevo.
- **FR-006**: El sistema MUST tratar cada registro de periodo como independiente una vez guardado: si el administrador edita manualmente la "lectura anterior" autocompletada de un nuevo periodo, el registro del periodo previo del cual se trasladó el valor NO se modifica automáticamente.
- **FR-007**: El sistema MUST advertir de forma explícita y de alto contraste cuando exista una discrepancia entre la "lectura actual" guardada de un periodo y la "lectura anterior" utilizada en el periodo inmediatamente posterior (por ejemplo, tras una edición manual desincronizada), sin bloquear el registro.
- **FR-008**: El sistema MUST mostrar el historial de lecturas de una locación en orden cronológico, indicando por cada periodo su "lectura anterior", "lectura actual" y consumo calculado.
- **FR-009**: La interfaz de registro y consulta de lecturas MUST usar alto contraste y etiquetas explícitas ("Guardar Lectura del Periodo").

### Key Entities *(include if feature involves data)*

- **LecturaMedidor** (refinamiento de la entidad introducida en la funcionalidad de lecturas y recibo por periodo): Se explicitan los dos valores de lectura por periodo en lugar de derivarse implícitamente del periodo anterior.
  - `id` (Entero, Auto-incremental, Clave Primaria)
  - `locacion_id` (Entero, Referencia a `Locacion.id`, Clave Foránea, Obligatorio)
  - `periodo` (Fecha o referencia de mes/año, Obligatorio, único junto con `locacion_id`)
  - `lectura_anterior` (Numérico exacto, editable; autocompletado con la `lectura_actual` del periodo previo más reciente, o vacío si no existe periodo previo)
  - `lectura_actual` (Numérico exacto, Obligatorio)
  - `consumo_calculado` (Numérico exacto, calculado automáticamente como `lectura_actual` menos `lectura_anterior`)
  - `fecha_registro` (Marca de tiempo, Obligatorio)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los nuevos periodos de lectura muestran el campo "lectura anterior" precargado automáticamente en menos de 2 segundos al iniciar el registro, cuando existe un periodo previo.
- **SC-002**: Un administrador puede corregir el valor trasladado de "lectura anterior" y confirmar el consumo recalculado en menos de 30 segundos.
- **SC-003**: El 100% de los periodos históricos de lectura permanecen accesibles y sin alterarse tras el registro de nuevos periodos, verificable en la consulta de historial de la locación.
- **SC-004**: El 100% de los casos donde la "lectura anterior" editada de un periodo difiere de la "lectura actual" guardada del periodo previo muestran una advertencia visible al administrador al consultar el historial.

## Assumptions

- **A-001**: Esta funcionalidad refina el modelo de datos de lecturas de medidor introducido en la especificación de "Lecturas de Medidor de Luz y Recibo por Periodo" (spec 005): reemplaza el cálculo implícito de consumo (comparando contra el registro del periodo anterior) por dos campos explícitos (`lectura_anterior` y `lectura_actual`) almacenados en el mismo registro de periodo.
- **A-002**: Los registros de periodos ya existentes previos a esta funcionalidad se migran asumiendo `lectura_actual` igual al valor de `lectura` previamente registrado, y `lectura_anterior` igual a la `lectura_actual` del periodo previo correspondiente; el detalle técnico de esta migración corresponde a la fase de planificación.
- **A-003**: La sincronización entre periodos es unidireccional al momento del traslado (autocompletar) pero desacoplada después de guardar: una vez guardado un periodo, editar valores de un periodo no propaga cambios automáticos a otros periodos; el sistema solo advierte sobre discrepancias (FR-007). Este comportamiento es consistente con el principio ya establecido en las funcionalidades previas de contratos y recibos, donde las ediciones nunca alteran retroactivamente registros históricos ya guardados.
