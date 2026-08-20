# Feature Specification: Condiciones del Contrato y Costos de Referencia para Recibos

**Feature Branch**: `004-condiciones-contrato-recibo`

**Created**: 2026-08-19

**Status**: Ready

**Input**: User description: "cada contrato debe tener caracteristicas propias como el costo de la locacion, la fecha de inicio y fin del contrato, el fin del contrato debe tomar como referencia para enviar notificaciones a un correo de que se vence. El precio del contrato se usara como referencia al momento de generar al recibo pero no significa que al momento de generar el recibo se mantenga podria ser editado, lo mismo para costos fijos como por ejemplo agua, luz pasadizo y seguridad"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Registro de Costo de Renta y Costos Fijos del Contrato (Priority: P1)

Como Administrador, quiero registrar en cada contrato, además del costo de la renta, los costos fijos asociados (agua, luz, pasadizo/mantenimiento y seguridad), de modo que estos valores queden guardados como referencia oficial del contrato para usarse posteriormente en la generación de recibos.

**Why this priority**: Sin estos valores de referencia almacenados en el contrato, no existe una base consistente desde la cual generar los recibos mensuales, lo que obligaría al administrador a recordar o recalcular manualmente cada monto, aumentando el riesgo de error.

**Independent Test**: Se puede verificar creando o editando un contrato, ingresando el costo de renta y los cuatro costos fijos (agua, luz, pasadizo, seguridad), guardando el registro y comprobando que los valores persistan correctamente asociados a ese contrato.

**Acceptance Scenarios**:

1. **Given** el formulario de registro de un contrato, **When** el administrador ingresa el costo de renta "S/ 1500.00" y los costos fijos de agua "S/ 50.00", luz "S/ 80.00", pasadizo "S/ 30.00" y seguridad "S/ 40.00", **Then** el sistema guarda el contrato con todos estos valores como campos individuales y explícitos.
2. **Given** un contrato ya guardado, **When** el administrador no ingresa un valor para alguno de los costos fijos (por ejemplo, "pasadizo" no aplica a esa locación), **Then** el sistema acepta el registro asignando "S/ 0.00" por defecto a ese costo fijo, sin bloquear el guardado del contrato.

---

### User Story 2 - Notificación por Correo de Vencimiento de Contrato (Priority: P1)

Como Administrador, quiero que el sistema envíe automáticamente una notificación por correo electrónico usando como referencia la fecha de fin del contrato, para enterarme con anticipación de que un contrato está por vencer y así gestionar su renovación o rescisión a tiempo.

**Why this priority**: Evita que un contrato venza sin que el administrador se percate, lo cual generaría inconsistencias de cobro, ocupación irregular de la locación y pérdida de continuidad del negocio.

**Independent Test**: Se puede verificar registrando un contrato cuya fecha de fin caiga dentro de alguno de los hitos de anticipación (30, 15 o 7 días), ejecutando el proceso de verificación de vencimientos y comprobando que se genere y envíe un correo a la dirección administrativa configurada, con los datos del contrato y de la locación afectada.

**Acceptance Scenarios**:

1. **Given** un contrato activo cuya fecha de fin está a exactamente 30 días de la fecha actual, **When** el sistema ejecuta la verificación periódica de vencimientos, **Then** se envía un correo electrónico a la dirección administrativa configurada, indicando la locación, el inquilino y la fecha exacta de vencimiento del contrato.
2. **Given** un contrato cuyo hito de 30 días ya generó una notificación previamente, **When** el sistema vuelve a ejecutar la verificación periódica y la fecha de fin sigue estando dentro del rango del hito de 30 días (sin haber alcanzado aún los 15 días), **Then** el sistema NO reenvía una notificación duplicada para ese mismo hito.
3. **Given** un contrato cuyo hito de 30 días ya fue notificado, **When** la fecha actual alcanza el hito de 15 días de anticipación, **Then** el sistema envía una nueva notificación correspondiente a ese segundo hito, distinta de la del hito de 30 días.

---

### User Story 3 - Generación de Recibo con Montos Editables a partir de los Valores de Referencia (Priority: P2)

Como Administrador, quiero que al generar un recibo el sistema me proponga automáticamente el costo de renta y los costos fijos definidos en el contrato como valores iniciales, pero permitiéndome editar cualquiera de ellos antes de emitir el recibo, de modo que pueda reflejar ajustes puntuales (por ejemplo, un consumo de agua mayor ese mes) sin alterar los valores de referencia del contrato.

**Why this priority**: Los montos mensuales reales pueden variar (consumos, descuentos puntuales, moras), por lo que el recibo necesita flexibilidad, pero sin perder la comodidad de partir de los valores acordados en el contrato.

**Independent Test**: Se puede verificar generando un recibo para un contrato con costo de renta "S/ 1500.00" y costo de agua "S/ 50.00", editando manualmente el monto de agua a "S/ 65.00" antes de emitir el recibo, y comprobando que el recibo se guarda con "S/ 65.00" mientras que el contrato conserva "S/ 50.00" como su valor de referencia sin cambios.

**Acceptance Scenarios**:

1. **Given** un contrato con costo de renta "S/ 1500.00" y costo de luz "S/ 80.00", **When** el administrador inicia la generación de un nuevo recibo para ese contrato, **Then** el formulario de recibo se precarga automáticamente con "S/ 1500.00" en renta y "S/ 80.00" en luz, mostrando ambos campos como editables.
2. **Given** el formulario de generación de recibo precargado con los valores del contrato, **When** el administrador modifica el monto de renta a "S/ 1450.00" (por ejemplo, un descuento puntual) y confirma la emisión, **Then** el recibo se guarda con "S/ 1450.00" y el contrato original conserva su valor de referencia de "S/ 1500.00" sin modificarse.
3. **Given** un recibo ya emitido con montos editados, **When** el administrador consulta el historial de recibos del contrato, **Then** el sistema muestra los montos efectivamente cobrados en cada recibo (no los valores de referencia del contrato) de forma clara y con tipografía de al menos 18px.

### Edge Cases

- **Contrato sin costos fijos aplicables**: Si una locación no tiene, por ejemplo, costo de seguridad, el campo se registra en "S/ 0.00" y ese concepto se sigue mostrando en el recibo con monto cero, sin ocultarse, para mantener transparencia con el inquilino.
- **Edición del contrato después de emitir recibos**: Si el administrador edita posteriormente el costo de renta o algún costo fijo en el contrato, los recibos ya emitidos NO se modifican retroactivamente; solo los nuevos recibos que se generen tomarán como referencia los valores actualizados.
- **Contrato vencido sin renovación**: Si un contrato vence y no se registra un contrato nuevo ni una rescisión formal, el sistema continúa mostrándolo como "vencido" en base a su fecha de fin y sigue permitiendo el envío de la notificación de vencimiento correspondiente hasta que el administrador tome acción.
- **Corrección de fecha de fin tras notificación enviada**: Si el administrador edita la fecha de fin del contrato (por ejemplo, al renovar) después de haberse enviado una o más notificaciones de vencimiento, el sistema reinicia el estado de los tres hitos de notificación (30, 15 y 7 días) para ese contrato, permitiendo que se generen nuevas alertas conforme la nueva fecha vaya entrando en cada ventana de anticipación.
- **Contrato con fecha de fin muy cercana al registrarse**: Si un contrato se crea o edita con una fecha de fin que ya se encuentra dentro de uno o varios hitos (por ejemplo, a solo 10 días), el sistema envía en la siguiente verificación periódica todas las notificaciones de los hitos ya alcanzados y aún no enviados (en este ejemplo, la de 30 y 15 días), sin esperar a que se cumplan individualmente en el calendario.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema MUST permitir registrar en cada contrato, como campos propios e independientes, el costo de renta (`monto_renta`), la fecha de inicio y la fecha de fin del contrato (campos ya definidos en la gestión base de contratos).
- **FR-002**: El sistema MUST permitir registrar en cada contrato los costos fijos individuales de agua, luz, pasadizo/mantenimiento y seguridad, cada uno como un campo numérico decimal exacto independiente, con valor por defecto "S/ 0.00" cuando no aplique.
- **FR-003**: El sistema MUST usar la fecha de fin del contrato como referencia para enviar notificaciones por correo electrónico de vencimiento próximo en tres hitos escalonados de anticipación: 30 días, 15 días y 7 días antes de la fecha de fin del contrato.
- **FR-003b**: El sistema MUST enviar todas las notificaciones de vencimiento a una única dirección de correo administrativa, configurada a nivel de sistema (configuración general de la aplicación), independientemente de la locación, contrato o representante involucrado.
- **FR-004**: El sistema MUST evitar reenviar la notificación correspondiente a un mismo hito (30, 15 o 7 días) para un mismo contrato más de una vez, mientras que sí MUST enviar los demás hitos aún pendientes de notificar para ese contrato.
- **FR-005**: El sistema MUST, al iniciar la generación de un recibo para un contrato, precargar automáticamente el monto de renta y los costos fijos definidos en dicho contrato como valores iniciales del recibo.
- **FR-006**: El sistema MUST permitir que el administrador edite libremente el monto de renta y cada costo fijo propuestos antes de confirmar la emisión de un recibo, sin exigir que coincidan con los valores de referencia del contrato.
- **FR-007**: El sistema MUST guardar en cada recibo emitido los montos efectivamente utilizados (editados o no) de forma independiente a los valores de referencia del contrato, de modo que una edición posterior del contrato NO modifique recibos ya emitidos.
- **FR-008**: El sistema MUST registrar de forma transaccional y atómica (`DB::transaction`) tanto la actualización de costos de referencia del contrato como la creación de recibos con sus montos asociados.
- **FR-009**: La interfaz de edición de costos del contrato y de generación de recibos MUST cumplir los estándares Senior-First del proyecto (tipografía mínima de 18px, alto contraste, botones de al menos 48x48px y etiquetas explícitas como "Guardar Costos del Contrato" o "Emitir Recibo").

### Key Entities *(include if feature involves data)*

- **Contrato** (extensión de la entidad existente): Se agregan los campos de costos fijos de referencia.
  - `costo_agua` (Decimal exacto, por defecto 0.00)
  - `costo_luz` (Decimal exacto, por defecto 0.00)
  - `costo_pasadizo` (Decimal exacto, por defecto 0.00)
  - `costo_seguridad` (Decimal exacto, por defecto 0.00)
  - `notificado_30_dias_en` (Marca de tiempo, nulo hasta que se envíe ese hito; se reinicia a nulo si cambia `fecha_fin`)
  - `notificado_15_dias_en` (Marca de tiempo, nulo hasta que se envíe ese hito; se reinicia a nulo si cambia `fecha_fin`)
  - `notificado_7_dias_en` (Marca de tiempo, nulo hasta que se envíe ese hito; se reinicia a nulo si cambia `fecha_fin`)
- **ConfiguracionGeneral**: Representa los parámetros administrativos globales del sistema relevantes a esta funcionalidad.
  - `correo_notificaciones_vencimiento` (Cadena de caracteres/correo electrónico, Obligatorio, editable por el Administrador)
- **Recibo**: Representa el comprobante mensual generado a partir de un contrato, con los montos efectivamente cobrados en ese periodo.
  - `id` (Entero, Auto-incremental, Clave Primaria)
  - `contrato_id` (Entero, Referencia a `Contrato.id`, Clave Foránea, Obligatorio)
  - `monto_renta` (Decimal exacto, Obligatorio, valor editable independiente del contrato)
  - `monto_agua` (Decimal exacto, por defecto 0.00, editable)
  - `monto_luz` (Decimal exacto, por defecto 0.00, editable)
  - `monto_pasadizo` (Decimal exacto, por defecto 0.00, editable)
  - `monto_seguridad` (Decimal exacto, por defecto 0.00, editable)
  - `periodo` (Fecha o referencia de mes/año que representa el periodo facturado)
  - `fecha_emision` (Fecha, Obligatorio)

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de los contratos activos que alcanzan alguno de los tres hitos de anticipación (30, 15 o 7 días) generan una notificación de vencimiento por correo a la dirección administrativa configurada, sin duplicados para el mismo hito y el mismo contrato.
- **SC-002**: Al iniciar la generación de un recibo, el 100% de los campos de montos (renta y costos fijos) se precargan automáticamente con los valores del contrato en menos de 2 segundos, permitiendo su edición inmediata.
- **SC-003**: Modificar un costo de referencia en un contrato después de emitidos recibos anteriores no altera ningún recibo ya emitido; el 100% de los recibos históricos conservan los montos con los que fueron generados originalmente.
- **SC-004**: Un administrador puede completar el registro de los costos fijos de un contrato o la edición de montos de un recibo en menos de 2 minutos utilizando la interfaz Senior-First.

## Assumptions

- **A-001**: Los costos fijos considerados en esta funcionalidad son exactamente cuatro: agua, luz, pasadizo (mantenimiento de áreas comunes) y seguridad; no se contempla un mecanismo para agregar costos fijos adicionales personalizados en esta iteración.
- **A-002**: El recibo (`Recibo`) es una entidad nueva introducida por esta funcionalidad; no existía previamente en el sistema. Su ciclo de vida completo (estados, anulación, reportes) más allá de la generación con montos editables queda fuera del alcance de esta especificación y podrá tratarse en una funcionalidad futura.
- **A-003**: La verificación de vencimientos y el envío de notificaciones se ejecuta mediante un proceso periódico (por ejemplo, una revisión diaria); el detalle técnico de programación de dicho proceso corresponde a la fase de planificación técnica, no a esta especificación.
- **A-004**: La notificación de vencimiento se envía únicamente a la dirección de correo administrativa configurada a nivel de sistema (`ConfiguracionGeneral.correo_notificaciones_vencimiento`); no se envía a los correos individuales de los representantes del contrato (ver especificación de representantes de contrato) en esta iteración, ya que dicha entidad no cuenta actualmente con un campo de correo electrónico.
- **A-005**: Se asume que existe (o se crea en esta funcionalidad) una pantalla de configuración general accesible solo a Administradores donde se define y edita el correo administrativo de notificaciones; el detalle de permisos y roles de acceso se mantiene consistente con el resto del sistema.
