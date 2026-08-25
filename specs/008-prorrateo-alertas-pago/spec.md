# Feature Specification: Fecha Límite de Pago Mensual, Alertas y Prorrateo por Días Activos

**Feature Branch**: `008-prorrateo-alertas-pago`

**Created**: 2026-08-19

**Status**: Ready

**Input**: User description: "El pago es por mes siendo ultimo dia de pago el ultimo sabado del mes por lo que se deben generar alertas para el administrador dias antes y debe ser configurable, si un contrato empieza a estar vigente antes de culminar el mes se calcula sugiere los dias que ha estado activo el contrato lo mismo si finaliza"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Alerta Configurable de Fecha Límite de Pago Mensual (Priority: P1)

Como Administrador, quiero que el sistema me alerte con anticipación configurable antes de que llegue el último sábado de cada mes (fecha límite de pago), de modo que pueda gestionar a tiempo el cobro de los recibos pendientes antes del vencimiento del periodo.

**Why this priority**: Es la necesidad central del negocio: el ciclo de cobro depende de que el administrador actúe antes de la fecha límite mensual; sin esta alerta se corre el riesgo de descuidar el cobro oportuno de varios inquilinos.

**Independent Test**: Se puede verificar configurando la anticipación en "5 días", ubicándose en una fecha que esté a exactamente 5 días del último sábado del mes en curso, ejecutando la verificación periódica del sistema y comprobando que se genere y envíe una alerta al Administrador.

**Acceptance Scenarios**:

1. **Given** que la anticipación configurada es de 5 días, **When** faltan exactamente 5 días para el último sábado del mes en curso, **Then** el sistema envía una alerta al Administrador indicando la fecha límite de pago próxima.
2. **Given** que el Administrador cambia la anticipación configurada de 5 a 10 días, **When** el sistema ejecuta la siguiente verificación periódica, **Then** la alerta se genera cuando falten 10 días para el último sábado del mes, respetando el nuevo valor configurado.
3. **Given** que la alerta del mes en curso ya fue enviada, **When** el sistema vuelve a ejecutar la verificación periódica dentro del mismo mes, **Then** el sistema NO reenvía una alerta duplicada para la misma fecha límite.

---

### User Story 2 - Sugerencia de Días Activos al Iniciar un Contrato a Mitad de Mes (Priority: P2)

Como Administrador, quiero que el sistema calcule y me sugiera automáticamente la cantidad de días que un contrato estuvo vigente dentro del mes en que inició, cuando su fecha de inicio no coincide con el primer día del mes, además de un monto de renta prorrateado sugerido, de modo que pueda ajustar el cobro de ese primer periodo de forma proporcional y justa sin tener que calcularlo manualmente.

**Why this priority**: Cobrar un mes completo cuando el contrato solo estuvo vigente una parte del mes sería injusto para el inquilino y podría generar reclamos; el cálculo automático evita errores manuales.

**Independent Test**: Se puede verificar registrando un contrato con fecha de inicio "15 de agosto de 2026" y costo de renta "S/ 1550.00" (mes de 31 días), iniciando la generación del recibo del periodo "Agosto 2026" para ese contrato, y comprobando que el sistema muestre "17 días activos de 31" y sugiera un monto de renta prorrateado editable de "S/ 850.00" (1550 ÷ 31 × 17) antes de confirmar el recibo.

**Acceptance Scenarios**:

1. **Given** un contrato con fecha de inicio "15 de agosto de 2026" y costo de renta de referencia "S/ 1550.00", **When** el administrador inicia la generación del recibo del periodo "Agosto 2026" para ese contrato, **Then** el sistema muestra de forma visible "17 días de 31" activos y precarga el monto de renta del recibo con el valor prorrateado sugerido "S/ 850.00", editable antes de confirmar.
2. **Given** un contrato con fecha de inicio el primer día del mes, **When** el administrador genera el recibo de ese periodo, **Then** el sistema no muestra ninguna sugerencia de prorrateo y precarga el monto de renta completo del contrato, dado que el contrato estuvo activo el mes completo.

---

### User Story 3 - Sugerencia de Días Activos al Finalizar un Contrato a Mitad de Mes (Priority: P2)

Como Administrador, quiero que el sistema calcule y me sugiera automáticamente la cantidad de días que un contrato estuvo vigente dentro del mes en que finalizó, cuando su fecha de fin no coincide con el último día del mes, además de un monto de renta prorrateado sugerido, de modo que pueda ajustar el cobro del último periodo de forma proporcional.

**Why this priority**: Al igual que en el inicio, cobrar el mes completo de un contrato que finalizó antes de su término generaría un cobro incorrecto y posibles reclamos del inquilino saliente.

**Independent Test**: Se puede verificar registrando un contrato con fecha de fin "10 de agosto de 2026" y costo de renta "S/ 1550.00", iniciando la generación del recibo del periodo "Agosto 2026" para ese contrato, y comprobando que el sistema muestre "10 días activos de 31" y sugiera un monto de renta prorrateado editable de "S/ 500.00" (1550 ÷ 31 × 10) antes de confirmar el recibo.

**Acceptance Scenarios**:

1. **Given** un contrato con fecha de fin "10 de agosto de 2026" y costo de renta de referencia "S/ 1550.00", **When** el administrador inicia la generación del recibo del periodo "Agosto 2026" para ese contrato, **Then** el sistema muestra de forma visible "10 días de 31" activos y precarga el monto de renta del recibo con el valor prorrateado sugerido "S/ 500.00", editable antes de confirmar.

### Edge Cases

- **Contrato que inicia y finaliza dentro del mismo mes**: Si un contrato tiene tanto su fecha de inicio como su fecha de fin dentro del mismo mes calendario, el sistema calcula los días activos como la diferencia entre ambas fechas (inclusive), y lo muestra como sugerencia única para el recibo de ese mes.
- **Mes sin ningún sábado adicional (mes que termina en sábado)**: Si el último día del mes es un sábado, el sistema toma ese mismo día como fecha límite de pago.
- **Anticipación configurada mayor a los días restantes del mes**: Si la anticipación configurada en días es mayor que la cantidad de días del mes en curso, el sistema envía la alerta correspondiente en la primera verificación periódica disponible dentro de ese mes, indicando que la fecha límite ya está próxima.
- **Contrato vigente todo el mes**: Si el contrato ya estaba activo desde antes del inicio del mes y sigue vigente después de su fin, el sistema no muestra ninguna sugerencia de prorrateo para ese periodo, ya que el mes se factura completo.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST establecer la fecha límite de pago mensual como el último sábado de cada mes calendario, aplicable de forma general a todos los contratos activos.
- **FR-002**: El sistema MUST permitir al Administrador configurar la cantidad de días de anticipación con la que se genera la alerta de fecha límite de pago próxima.
- **FR-003**: El sistema MUST enviar una alerta al Administrador cuando falten exactamente los días configurados de anticipación para el último sábado del mes en curso.
- **FR-004**: El sistema MUST evitar el envío de alertas duplicadas de fecha límite de pago para el mismo mes.
- **FR-005**: El sistema MUST calcular, para cada contrato cuya fecha de inicio o fecha de fin caiga dentro de un mes determinado (sin coincidir con el primer o último día de ese mes respectivamente), la cantidad de días en que el contrato estuvo activo dentro de ese mes.
- **FR-006**: El sistema MUST mostrar la cantidad de días activos calculada como sugerencia visible al administrador al iniciar la generación del recibo del periodo correspondiente a ese contrato.
- **FR-007**: El sistema MUST calcular y precargar automáticamente, además de mostrar la cantidad de días activos, un monto de renta prorrateado sugerido (`monto_renta` de referencia del contrato ÷ días del mes × días activos) como valor editable en el formulario de generación del recibo de ese periodo, permitiendo que el administrador lo ajuste antes de confirmar.
- **FR-008**: El sistema MUST calcular la cantidad total de días del mes utilizando la cantidad real de días del mes calendario correspondiente (28, 29, 30 o 31 según el mes) como base para el cálculo de días activos y del monto de renta prorrateado.
- **FR-009**: La interfaz de configuración de anticipación de alertas y de visualización de días activos sugeridos MUST usar alto contraste y etiquetas explícitas.

### Key Entities *(include if feature involves data)*

- **ConfiguracionGeneral** (extensión de la entidad de configuración general del sistema): Se agrega la anticipación configurable para la alerta de fecha límite de pago.
  - `dias_anticipacion_alerta_pago` (Entero, Obligatorio, editable por el Administrador, por defecto 5)
  - `alerta_pago_mes_enviada_en` (Marca de tiempo, nulo hasta que se envíe la alerta del mes en curso; se reinicia al iniciar un nuevo mes)
- **Contrato**: No se agregan campos nuevos; se reutilizan `fecha_inicio` y `fecha_fin` ya existentes como base del cálculo de días activos por periodo.
- **Recibo** (extensión de la entidad introducida en las funcionalidades de recibos previas): Se agrega la trazabilidad del cálculo de prorrateo utilizado, cuando aplica.
  - `dias_activos_periodo` (Entero, nulo si el contrato estuvo activo el mes completo)
  - `dias_totales_periodo` (Entero, nulo si el contrato estuvo activo el mes completo; cantidad real de días del mes calendario usada en el cálculo)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las alertas de fecha límite de pago se generan cuando faltan exactamente los días de anticipación configurados, sin duplicados para el mismo mes.
- **SC-002**: El 100% de los contratos cuya fecha de inicio o fin cae dentro de un mes determinado muestran automáticamente la cantidad de días activos y el monto de renta prorrateado sugerido al iniciar la generación del recibo de ese periodo, en menos de 2 segundos.
- **SC-003**: Un administrador puede ajustar la anticipación de la alerta de pago en menos de 1 minuto desde la configuración general del sistema.
- **SC-004**: El 100% de los contratos activos durante el mes completo NO muestran ninguna sugerencia de prorrateo al generar su recibo mensual.

## Assumptions

- **A-001**: La alerta de fecha límite de pago es un recordatorio general dirigido al Administrador (no específico por contrato o inquilino), enviado a la misma dirección de correo administrativa configurada a nivel de sistema (`ConfiguracionGeneral.correo_notificaciones_vencimiento`, ver especificación de condiciones del contrato).
- **A-002**: La regla del "último sábado del mes" como fecha límite de pago es fija y aplica a todos los contratos por igual; no se contempla en esta iteración una fecha límite distinta por locación o por contrato.
- **A-003**: El cálculo de días activos y el monto de renta prorrateado son valores de referencia sugeridos asociados al periodo del recibo; el contrato en sí (`fecha_inicio`, `fecha_fin`, `monto_renta`) no se modifica por este cálculo, y el monto sugerido puede editarse libremente antes de confirmar el recibo, de forma consistente con el principio de valores editables establecido en las funcionalidades previas de recibos.
- **A-004**: El prorrateo automático aplica únicamente al concepto de renta (`monto_renta`); los costos fijos (agua, luz, seguridad, luz de pasadizo) no se prorratean automáticamente por días activos en esta iteración, ya que su naturaleza (consumo o servicio fijo mensual) no necesariamente escala de forma lineal con los días; el administrador puede editarlos manualmente si lo considera necesario.
