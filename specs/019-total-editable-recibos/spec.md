# Feature Specification: Lectura Anterior por Defecto y Total Editable y Persistido

**Feature Branch**: `019-total-editable-recibos`

**Created**: 2026-08-25

**Status**: Draft

**Input**: User description: "en caso de no existir lecturas anteriores por defecto deberia ser 0, la columna del total deberia ser editable, no es necesario almacenar el consumo dado que con la lectura inicial y final si se puede obtener pero si es vital almacenar el total ya que este mismo valor se utilizara para generar los recibos"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Empezar a medir una locación sin lectura anterior sin quedar bloqueado (Priority: P1)

Un usuario que registra por primera vez la lectura de luz de una locación (una que nunca tuvo una
lectura anterior, por ejemplo un medidor recién instalado) necesita que el sistema trate la
ausencia de lectura anterior como cero, para que el consumo del primer periodo se calcule
directamente a partir de la lectura actual, en vez de quedar sin poder calcular nada o mostrar un
mensaje que no le permite avanzar.

**Why this priority**: Sin esto, toda locación nueva queda sin un consumo calculable en su primer
periodo, lo que bloquea la generación de su primer recibo — es la base sobre la que se apoya la
User Story 2.

**Independent Test**: Registrar la lectura actual de una locación sin ninguna lectura anterior
registrada y verificar que el consumo calculado es igual a la lectura actual (como si la lectura
anterior fuese 0), sin errores ni bloqueos.

**Acceptance Scenarios**:

1. **Given** una locación sin ninguna lectura registrada en ningún periodo anterior, **When** el
   usuario registra la lectura actual del periodo, **Then** el sistema calcula el consumo de ese
   periodo usando 0 como lectura anterior (consumo = lectura actual − 0).
2. **Given** esa misma locación ya con su primera lectura registrada, **When** el usuario ve la
   pantalla de registro para el periodo siguiente, **Then** la lectura anterior mostrada es la
   lectura ya registrada (no vuelve a asumir 0).

---

### User Story 2 - Corregir o fijar manualmente el total a cobrar por luz antes de generar el recibo (Priority: P1)

Un usuario que registra las lecturas del periodo necesita poder ajustar manualmente el monto total
a cobrar por consumo de luz de una locación (por ejemplo para redondear, aplicar un acuerdo
puntual con el inquilino, o corregir un error de tarifa), y que ese monto ajustado quede guardado
de forma permanente — para que sea exactamente ese valor, y no uno recalculado más adelante con una
tarifa distinta, el que se use al generar el recibo del periodo.

**Why this priority**: Es el requisito central del pedido: hoy el monto de luz de un recibo se
recalcula en el momento de generar el recibo multiplicando el consumo por la tarifa *vigente en ese
momento*, no por la que regía cuando se registró la lectura — si la tarifa cambia entre el registro
y la generación del recibo, el monto que el usuario vio y aceptó al registrar la lectura deja de
coincidir con el que termina apareciendo en el recibo.

**Independent Test**: Registrar una lectura con su total sugerido, editar manualmente ese total a
un valor distinto, guardar, cambiar luego la tarifa general, y verificar que el recibo generado
para esa locación y periodo usa el total editado y guardado, no uno recalculado con la nueva
tarifa.

**Acceptance Scenarios**:

1. **Given** el usuario está registrando la lectura actual de una locación, **When** ve el total
   sugerido (consumo × tarifa vigente), **Then** puede editar ese valor a un monto distinto antes
   de guardar.
2. **Given** el usuario guardó una lectura con un total editado manualmente, **When** se vuelve a
   consultar esa lectura (pantalla de registro masivo, edición en línea, exportación), **Then** el
   total mostrado es el valor editado y guardado, no el resultado de recalcular consumo × tarifa.
3. **Given** una lectura con su total ya guardado (editado o no), **When** la tarifa general
   cambia después, **Then** el total ya guardado de esa lectura no cambia — sigue siendo el valor
   que se guardó en su momento.
4. **Given** una lectura con su total guardado, **When** se genera el recibo del periodo para esa
   locación, **Then** el monto de luz del recibo usa ese total guardado.

---

### Edge Cases

- ¿Qué pasa si el usuario no edita el total y lo deja como el sugerido (consumo × tarifa vigente al
  momento de guardar)? Se guarda igual ese valor sugerido, para que todas las lecturas — editadas o
  no — tengan siempre un total persistido y consultable de la misma forma.
- ¿Qué pasa con las lecturas ya registradas antes de esta funcionalidad, que no tienen un total
  guardado? Se completan (FR-008): el total de cada lectura histórica se calcula y guarda al
  desplegar esta funcionalidad, para que ninguna lectura quede sin total. Como el sistema no
  guarda un historial de tarifas anteriores (solo la tarifa vigente actual), ese cálculo usa la
  tarifa vigente al momento de completar los datos históricos, no la que pudo haber regido en el
  momento original de cada registro — ver Assumptions.
- ¿Qué pasa si el usuario edita el total a un valor negativo o no numérico? Debe rechazarse con el
  mismo criterio de validación ya usado para los demás campos monetarios del sistema (Principio V
  de la constitución: `NUMERIC`/`decimal`, sin tipos flotantes inexactos).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: En la pantalla de registro masivo (specs/015), el sistema DEBE calcular el consumo de
  una locación usando 0 como lectura anterior cuando no existe ninguna lectura registrada en un
  periodo anterior al seleccionado, en vez de dejar el consumo sin calcular. Esta regla es
  exclusiva del registro masivo: el flujo individual de registro de una lectura conserva su
  comportamiento actual (specs/006 FR-002, "sin lectura anterior" se trata como "sin dato", sin
  asumir 0).
- **FR-002**: El sistema DEBE mostrar, junto al campo de lectura actual de cada locación, un total
  sugerido calculado como consumo × tarifa vigente, igual que hoy.
- **FR-003**: El sistema DEBE permitir al usuario editar manualmente el total sugerido de cada
  locación durante el registro inicial, antes de guardar el lote de lecturas del periodo. La
  edición del total después de guardado (análoga a la edición en línea ya existente de "Lectura
  Actual", specs/015 FR-005/FR-017) queda fuera de alcance de esta funcionalidad.
- **FR-004**: El sistema DEBE guardar de forma permanente el total de cada lectura registrada (el
  valor editado por el usuario, o el sugerido si no se editó), asociado a esa lectura específica.
- **FR-005**: Una vez guardado, el total de una lectura NO DEBE recalcularse automáticamente si la
  tarifa general cambia después — permanece como el valor que se guardó en su momento.
- **FR-006**: El sistema DEBE usar el total guardado de la lectura correspondiente al generar el
  monto de luz de un recibo, en vez de recalcularlo multiplicando el consumo por la tarifa vigente
  al momento de generar el recibo.
- **FR-007**: El sistema NO NECESITA almacenar el consumo como un valor propio, dado que se puede
  obtener siempre a partir de la lectura actual y la lectura anterior (o 0, por FR-001) al momento
  de mostrarlo.
- **FR-008**: El sistema DEBE completar el total de todas las lecturas ya registradas antes de esta
  funcionalidad (que hoy no tienen un total guardado), calculándolo con la tarifa vigente al
  momento de aplicar esta funcionalidad, para que ninguna lectura — histórica o nueva — quede sin
  un total persistido.

### Key Entities *(include if feature involves data)*

- **Lectura de Medidor**: entidad ya existente (specs/005, specs/006, specs/015). Esta
  funcionalidad agrega la necesidad de un total monetario persistido por lectura (hoy no existe
  ningún campo de este tipo; el total se recalcula cada vez que se necesita) y redefine el
  significado de "sin lectura anterior" para el cálculo de consumo (de "sin dato disponible" a
  "cero").
- **Recibo**: entidad ya existente (specs/004, specs/005). Su monto de luz pasa a tomarse del total
  ya guardado de la lectura del periodo correspondiente, en vez de recalcularse en el momento de
  generar el recibo.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las locaciones sin lectura anterior registrada obtienen un consumo
  calculado en su primer registro, sin ningún caso bloqueado por falta de dato anterior.
- **SC-002**: El 100% de las lecturas guardadas después de esta funcionalidad tienen un total
  asociado, editado o no.
- **SC-003**: Cero recibos generados muestran un monto de luz distinto del total ya guardado de su
  lectura correspondiente, sin importar cuántas veces haya cambiado la tarifa general entre el
  registro de la lectura y la generación del recibo.
- **SC-004**: Un usuario puede corregir el total de una locación antes de guardar el lote, sin
  pasos adicionales a los que ya usa hoy para registrar una lectura.

## Assumptions

- Esta funcionalidad se apoya en specs/015/016/017 (pantalla de registro masivo) y afecta también
  el cálculo del monto de luz en specs/005 (generación de recibos), ya que ese es el consumidor
  final del total que aquí se pide persistir.
- El total sugerido (antes de cualquier edición) sigue calculándose exactamente igual que hoy:
  consumo × tarifa vigente en el momento de guardar la lectura, redondeado a 2 decimales.
- El sistema no guarda hoy un historial de valores de tarifa (`ConfiguracionGeneral` es una fila
  singleton con el valor vigente actual, sin versiones anteriores) — por lo tanto, el total con el
  que se completan las lecturas históricas (FR-008) necesariamente usa la tarifa vigente al momento
  de aplicar esta funcionalidad, no la que pudo haber regido cuando cada lectura se registró
  originalmente. Esta es una limitación de los datos disponibles, no una decisión de producto.
