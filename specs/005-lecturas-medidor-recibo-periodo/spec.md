# Feature Specification: Lecturas de Medidor de Luz y Recibo por Periodo

**Feature Branch**: `005-lecturas-medidor-recibo-periodo`

**Created**: 2026-08-19

**Status**: Ready

**Input**: User description: "Por cada locacion se debe tambien registrar las lecturas de luz cada una tiene su medidor y se debe asociar a un periodo (mes), ademas se debe poder generar un recibo por cada periodo (mes) donde se puede decidir agregar como detalle el costo de alquiler el costo de luz además de costos fijos editables por conceptos como seguridad y agua, y luz pasadizo"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Registro de Lectura Mensual del Medidor de Luz (Priority: P1)

Como Administrador, quiero registrar la lectura del medidor de luz de cada locación asociándola a un periodo (mes/año) específico, de modo que quede un historial confiable del consumo eléctrico de cada espacio a lo largo del tiempo.

**Why this priority**: Sin el registro de lecturas por periodo no existe una base de datos objetiva de consumo, lo cual es indispensable para calcular y justificar el costo de luz de cada recibo mensual.

**Independent Test**: Se puede verificar ingresando al detalle de una locación, registrando la lectura del medidor para el periodo "Agosto 2026" con su valor numérico, guardando el registro y comprobando que quede asociado correctamente a esa locación y a ese periodo específico.

**Acceptance Scenarios**:

1. **Given** una locación con un medidor de luz registrado, **When** el administrador ingresa la lectura "1250" para el periodo "Agosto 2026", **Then** el sistema guarda la lectura asociada a esa locación y periodo, y muestra el consumo calculado automáticamente como la diferencia con la lectura del periodo anterior.
2. **Given** que ya existe una lectura registrada para la locación en el periodo "Agosto 2026", **When** el administrador intenta registrar una segunda lectura para el mismo periodo, **Then** el sistema le permite corregir/editar la lectura existente en lugar de crear un registro duplicado para ese mismo periodo.

---

### User Story 2 - Generación de Recibo Mensual con Conceptos Configurables (Priority: P1)

Como Administrador, quiero generar un recibo por cada periodo (mes) para una locación, decidiendo qué conceptos incluir en el detalle (costo de alquiler, costo de luz calculado a partir de la lectura del medidor, y costos fijos editables como agua, seguridad y luz de pasadizo), de modo que el recibo refleje exactamente lo que corresponde cobrar ese mes.

**Why this priority**: El cobro mensual real varía según el consumo de luz y los conceptos aplicables a cada locación; el administrador necesita control total sobre qué se factura cada periodo antes de emitir el comprobante final.

**Independent Test**: Se puede verificar generando un recibo para el periodo "Agosto 2026" de una locación con lectura de medidor ya registrada, incluyendo el costo de alquiler y el costo de luz calculado, excluyendo el concepto de seguridad para ese mes, y comprobando que el recibo se emita únicamente con los conceptos seleccionados y sus montos.

**Acceptance Scenarios**:

1. **Given** una locación con lectura de medidor registrada para "Agosto 2026" (consumo calculado de 150 unidades) y un contrato activo con costo de renta "S/ 1500.00", **When** el administrador inicia la generación del recibo del periodo, **Then** el sistema muestra una lista de conceptos disponibles (alquiler, luz, agua, seguridad, luz de pasadizo) con casillas para incluir o excluir cada uno, precargando los montos de referencia del contrato y el monto de luz calculado a partir del consumo.
2. **Given** el formulario de recibo del periodo con todos los conceptos precargados, **When** el administrador desmarca el concepto "seguridad" y ajusta manualmente el monto de "luz" antes de confirmar, **Then** el recibo se emite sin el concepto de seguridad y con el monto de luz editado, sin afectar los valores de referencia del contrato.
3. **Given** un recibo ya emitido para el periodo "Agosto 2026" de una locación, **When** el administrador intenta generar un segundo recibo para la misma locación y el mismo periodo, **Then** el sistema le advierte de forma clara que ya existe un recibo para ese periodo y le permite editarlo en lugar de duplicarlo.

---

### User Story 3 - Consulta de Historial de Consumo y Recibos por Locación (Priority: P3)

Como Administrador, quiero consultar el historial de lecturas de medidor y de recibos emitidos de una locación ordenados por periodo, de modo que pueda comparar el consumo mes a mes y verificar qué se ha facturado anteriormente.

**Why this priority**: Facilita la detección de consumos anómalos y sirve de respaldo ante reclamos de inquilinos sobre montos cobrados en periodos pasados.

**Independent Test**: Se puede validar ingresando al historial de una locación con al menos tres periodos registrados y comprobando que se listen en orden cronológico las lecturas, el consumo calculado y el recibo asociado (si existe) de cada periodo.

**Acceptance Scenarios**:

1. **Given** una locación con lecturas registradas para "Junio 2026", "Julio 2026" y "Agosto 2026", **When** el administrador consulta el historial de consumo, **Then** el sistema muestra los tres periodos en orden cronológico con su lectura, consumo calculado y, si corresponde, un enlace al recibo emitido de ese periodo, con tipografía de al menos 18px y alto contraste.

### Edge Cases

- **Lectura menor a la del periodo anterior**: Si la nueva lectura ingresada es menor que la lectura del periodo anterior (posible cambio o reinicio de medidor), el sistema advierte de forma explícita y de alto contraste que el consumo calculado sería negativo, y exige que el administrador confirme explícitamente o corrija el valor antes de guardar.
- **Locación sin lectura previa (primer periodo)**: Si es la primera lectura registrada para una locación, el sistema no calcula consumo (no hay lectura anterior de referencia) y lo indica claramente en pantalla, permitiendo continuar con el registro.
- **Recibo sin contrato activo en el periodo**: Si se intenta generar un recibo para una locación que no tiene un contrato activo vigente durante ese periodo, el sistema bloquea la generación del recibo y muestra un mensaje explicando que debe existir un contrato activo para poder facturar ese periodo; sin embargo, la lectura del medidor sí puede seguir registrándose de forma independiente para no perder el historial de consumo.
- **Edición de una lectura ya usada en un recibo emitido**: Si el administrador corrige una lectura de un periodo cuyo recibo ya fue emitido, el sistema advierte que el recibo ya emitido no se actualizará automáticamente y que, de ser necesario, debe editar también el recibo manualmente.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST permitir registrar, por cada locación, la lectura de su medidor de luz asociada a un periodo específico (mes y año).
- **FR-002**: El sistema MUST calcular automáticamente el consumo del periodo como la diferencia entre la lectura registrada y la lectura del periodo inmediatamente anterior de esa misma locación, mostrando "sin dato anterior" cuando no exista lectura previa.
- **FR-003**: El sistema MUST impedir registrar más de una lectura por locación y periodo, permitiendo en su lugar editar la lectura ya existente de ese periodo.
- **FR-004**: El sistema MUST permitir generar, para cada locación y periodo (mes), un recibo que incluya como conceptos disponibles: costo de alquiler, costo de luz, y los costos fijos de agua, seguridad y luz de pasadizo.
- **FR-005**: El sistema MUST permitir al administrador seleccionar (incluir o excluir) individualmente cada concepto disponible antes de emitir el recibo del periodo; los conceptos excluidos NO aparecen en el detalle ni en el total del recibo.
- **FR-006**: El sistema MUST precargar, para los conceptos incluidos, los montos de referencia definidos en el contrato activo de la locación (alquiler y costos fijos) y un monto sugerido de luz calculado automáticamente como el consumo del periodo multiplicado por una tarifa configurable por unidad de consumo (S/ por unidad), permitiendo que el administrador edite el monto final antes de confirmar la emisión.
- **FR-007**: El sistema MUST permitir al Administrador configurar y actualizar la tarifa por unidad de consumo (S/ por unidad) utilizada para calcular el monto sugerido de luz, aplicándose la tarifa vigente al momento de generar cada recibo.
- **FR-008**: El sistema MUST impedir la generación de un recibo para una locación y periodo que no tenga un contrato activo vigente durante ese periodo, sin bloquear el registro independiente de la lectura del medidor para ese mismo periodo.
- **FR-009**: El sistema MUST impedir generar un segundo recibo para la misma locación y el mismo periodo, ofreciendo en su lugar la edición del recibo ya existente.
- **FR-010**: El sistema MUST registrar de forma transaccional y atómica (`DB::transaction`) tanto el guardado de lecturas de medidor como la generación y edición de recibos por periodo.
- **FR-011**: La interfaz de registro de lecturas y de generación de recibos por periodo MUST cumplir los estándares Senior-First del proyecto (tipografía mínima de 18px, alto contraste, botones de al menos 48x48px y etiquetas explícitas como "Guardar Lectura del Medidor" o "Emitir Recibo del Periodo").

### Key Entities *(include if feature involves data)*

- **LecturaMedidor**: Representa la lectura del medidor de luz de una locación en un periodo específico.
  - `id` (Entero, Auto-incremental, Clave Primaria)
  - `locacion_id` (Entero, Referencia a `Locacion.id`, Clave Foránea, Obligatorio)
  - `periodo` (Fecha o referencia de mes/año que representa el periodo de la lectura, Obligatorio, único junto con `locacion_id`)
  - `lectura` (Numérico exacto, Obligatorio, valor acumulado mostrado en el medidor)
  - `consumo_calculado` (Numérico exacto, calculado automáticamente como la diferencia con la lectura del periodo anterior; nulo si no hay lectura previa)
  - `fecha_registro` (Marca de tiempo, Obligatorio)
- **Recibo** (extensión de la entidad introducida en la funcionalidad de condiciones del contrato): Se agrega la asociación al periodo y el carácter seleccionable de cada concepto.
  - `periodo` (Fecha o referencia de mes/año, Obligatorio, único junto con `contrato_id`/`locacion_id`)
  - `lectura_medidor_id` (Entero, Referencia opcional a `LecturaMedidor.id`, usado para calcular el monto de luz sugerido)
  - `incluye_alquiler`, `incluye_luz`, `incluye_agua`, `incluye_seguridad`, `incluye_pasadizo` (Booleanos, indican qué conceptos se incluyeron en ese recibo)
- **ConfiguracionGeneral** (extensión de la entidad de configuración general del sistema): Se agrega la tarifa usada para calcular el monto sugerido de luz.
  - `tarifa_luz_por_unidad` (Decimal exacto, Obligatorio, editable por el Administrador, aplicado al consumo calculado de cada periodo)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las lecturas registradas calculan y muestran automáticamente el consumo del periodo (o "sin dato anterior") en menos de 2 segundos tras guardarse.
- **SC-002**: Un administrador puede generar un recibo de periodo seleccionando los conceptos deseados y ajustando montos en menos de 3 minutos utilizando la interfaz Senior-First.
- **SC-003**: El sistema bloquea el 100% de los intentos de crear una segunda lectura o un segundo recibo para la misma locación y el mismo periodo, ofreciendo siempre la edición del registro existente en su lugar.
- **SC-004**: El 100% de los recibos emitidos muestran únicamente los conceptos que el administrador seleccionó incluir, sin conceptos ocultos ni montos no solicitados.
- **SC-005**: El monto sugerido de luz se calcula automáticamente (consumo × tarifa vigente) y se muestra al administrador en menos de 2 segundos al iniciar la generación del recibo, sin requerir cálculos manuales previos.

## Assumptions

- **A-001**: Cada locación cuenta con un único medidor de luz propio; el registro de múltiples medidores por locación queda fuera del alcance de esta iteración.
- **A-002**: El "costo de luz de pasadizo" es un concepto de costo fijo distinto e independiente del "costo de luz" calculado por consumo individual de la locación (que proviene de la lectura de su propio medidor); ambos conceptos coexisten en el mismo recibo.
- **A-003**: El periodo se maneja a nivel de mes/año (sin día específico), consistente con la facturación mensual estándar del negocio de alquiler.
- **A-004**: La generación de recibos sigue asociada a un contrato activo (según la funcionalidad de condiciones del contrato), por lo que los montos de referencia de alquiler y costos fijos provienen del contrato vigente de la locación en ese periodo.
- **A-005**: Existe una única tarifa por unidad de consumo aplicable a todo el sistema (no diferenciada por locación); si en el futuro se requieren tarifas distintas por locación o por rangos de consumo, se tratará como una funcionalidad separada.
- **A-006**: Si se actualiza la tarifa de luz, los recibos ya emitidos conservan el monto con el que fueron generados; solo los recibos nuevos usan la tarifa vigente al momento de su generación, de forma consistente con el principio de no alterar recibos históricos establecido en la funcionalidad de condiciones del contrato.
