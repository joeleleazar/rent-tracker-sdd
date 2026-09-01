# Feature Specification: Carga Masiva por Plantilla y Cobro por QR

**Feature Branch**: `044-carga-masiva-y-cobro-qr`

**Created**: 2026-08-31

**Status**: Draft

**Input**: User description: "Carga masiva con plantilla e importación editable para lecturas de luz y para recibos, más una vista de cobro por QR desde el inicio. Tres user stories independientes en una sola feature."

## Clarifications

### Session 2026-08-31

- Q: ¿Dónde vive la nueva función de plantilla + importación para lecturas y recibos? → A: En las pantallas existentes de Registro Masivo (`lecturas.registroMasivo.index` y `recibos.registroMasivo.index`), agregando acciones "Descargar plantilla" e "Importar archivo"; la grilla de captura manual actual se conserva intacta.
- Q: Al importar, ¿qué hace el guardado con lo que ya existe en ese periodo? → A: Upsert — crea los registros que faltan y actualiza los que ya existen para ese periodo/local, según lo editado en la vista previa.
- Q: ¿Qué código se imprime en el comprobante del recibo y cómo se escanea? → A: Un código QR que codifica una URL firmada al recibo; la vista de escaneo usa la cámara y además permite tipear el número de recibo como alternativa manual.
- Q: Al ubicar el recibo escaneado, ¿qué pantalla se muestra para registrar el pago? → A: Un formulario rápido nuevo y dedicado (datos clave del recibo + monto, fecha, medio de pago, evidencia opcional) que reutiliza el registro de pago existente.
- Q: ¿Qué lleva la plantilla de lecturas y cómo se maneja la tarifa por kWh? → A: Lectura actual editable por fila; la tarifa por kWh sigue siendo el input global de la pantalla, no viaja en la plantilla.
- Q: ¿Qué columnas trae la plantilla de recibos y cuáles son editables en la vista previa? → A: Desglose completo — monto de renta, importe de luz, una columna por cada concepto de gasto fijo activo, y total; todo editable salvo local, periodo y contrato; el total se recalcula pero permanece editable.
- Q: ¿Acceso directo en el inicio y quién puede usar la vista de cobro por QR? → A: Card destacada en el panel de inicio más una entrada de menú, disponibles para los perfiles Master y Administrador.
- Q: ¿Cómo se entregan las tres funcionalidades? → A: Una sola feature/rama (`044-carga-masiva-y-cobro-qr`) con tres user stories, con un commit por user story.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Carga masiva de lecturas de luz por plantilla (Priority: P1)

Un administrador que recibe las lecturas de todos los medidores en una planilla externa (o que quiere
completarlas fuera de línea) necesita cargarlas todas de una sola vez, sin tipear local por local en la
grilla. Descarga una plantilla ya armada para el periodo elegido, la completa o corrige, la vuelve a
subir, revisa una vista previa donde puede editar cualquier valor y ver qué filas tienen problemas, y
confirma. El sistema crea las lecturas que faltaban y actualiza las que ya existían para ese periodo.

**Why this priority**: Es la tarea repetitiva de mayor volumen del cierre de periodo. Elimina decenas de
ediciones manuales y el riesgo de saltarse un local. Entrega valor por sí sola aunque no se implementen
US2 ni US3.

**Independent Test**: Con al menos dos locaciones con medidor y un periodo seleccionado, descargar la
plantilla, modificar la "lectura actual" de ambas (una válida, una inválida — menor que la anterior),
reimportar, comprobar que la vista previa marca la fila inválida y permite corregirla, confirmar, y
verificar que la lectura válida quedó registrada/actualizada y la corregida también.

**Acceptance Scenarios**:

1. **Given** un periodo seleccionado en la pantalla de Registro Masivo de Lecturas, **When** el usuario
   pulsa "Descargar plantilla", **Then** obtiene un archivo con una fila por locación aplicable al
   periodo, con identificador técnico del local, nombre legible, lectura del periodo anterior como
   referencia de solo lectura, y la lectura actual precargada si ya existe registro para ese periodo o
   vacía si no.
2. **Given** una plantilla completada, **When** el usuario la importa, **Then** ve una vista previa
   tabular editable en la misma pantalla con todas las filas del archivo, sin que nada se haya guardado
   todavía.
3. **Given** la vista previa con una fila cuya lectura actual es menor que la del periodo anterior,
   **When** el usuario intenta confirmar, **Then** esa fila queda marcada como inválida, no se guarda, y
   el resto de filas válidas sí se pueden guardar.
4. **Given** una vista previa con filas válidas, algunas para locales que ya tienen lectura en el periodo
   y otras que no, **When** el usuario confirma, **Then** el sistema actualiza las existentes y crea las
   faltantes, y muestra un resumen de cuántas se crearon, cuántas se actualizaron y cuántas se omitieron
   por error.
5. **Given** la tarifa por kWh global de la pantalla, **When** se confirma una importación, **Then** el
   consumo y el total por local se calculan con esa tarifa global (la plantilla no contiene tarifa).
6. **Given** una importación confirmada, **When** el usuario vuelve a la grilla de captura manual,
   **Then** la grilla refleja los valores recién importados y sigue funcionando como antes.

---

### User Story 2 - Carga masiva de recibos por plantilla (Priority: P1)

Un administrador que arma los recibos del periodo para muchos locales necesita cargar los montos de todos
de una vez en lugar de abrir cada recibo. Descarga una plantilla con el desglose completo por local
(renta, luz, cada concepto de gasto fijo, total), ajusta los montos, reimporta, revisa una vista previa
editable con las filas problemáticas señaladas, y confirma. El sistema crea los recibos faltantes y
actualiza los existentes del periodo.

**Why this priority**: Es el otro gran bloque de trabajo del cierre de periodo. Comparte el patrón de
US1 (plantilla + vista previa editable + upsert + resumen) pero sobre otra entidad. Entrega valor por sí
sola.

**Independent Test**: Con al menos dos locaciones con contrato activo y algunos conceptos de gasto fijo
activos, descargar la plantilla, ajustar renta y un concepto en una fila y el total en otra, reimportar,
comprobar que la vista previa muestra el desglose editable y recalcula el total sugerido sin pisar un
total editado a mano, confirmar, y verificar que los recibos se crearon/actualizaron con esos montos.

**Acceptance Scenarios**:

1. **Given** un periodo seleccionado en la pantalla de Registro Masivo de Recibos, **When** el usuario
   pulsa "Descargar plantilla", **Then** obtiene un archivo con una fila por locación con contrato activo
   en el periodo, con identificador técnico del local, nombre legible, referencia de contrato, monto de
   renta, importe de luz, una columna por cada concepto de gasto fijo activo del catálogo, y total.
2. **Given** un local que ya tiene recibo en el periodo, **When** se genera la plantilla, **Then** su
   fila viene precargada con los montos actuales del recibo; **And Given** un local sin recibo, **Then**
   su fila viene con los montos derivados por la lógica vigente del sistema o vacía si no hay base para
   derivarlos.
3. **Given** una plantilla importada, **When** se muestra la vista previa, **Then** el usuario puede
   editar renta, luz, cada concepto y el total; no puede editar local, periodo ni contrato.
4. **Given** una fila de la vista previa donde el usuario cambió un concepto pero no tocó el total,
   **When** se recalcula, **Then** el total sugerido refleja la suma de los componentes; **And Given**
   una fila donde el usuario editó el total a mano, **Then** ese total se respeta y no se sobrescribe.
5. **Given** una vista previa con filas válidas para locales con y sin recibo en el periodo, **When** el
   usuario confirma, **Then** el sistema actualiza los recibos existentes y crea los faltantes, y muestra
   un resumen de creados, actualizados y omitidos por error.
6. **Given** una fila con un monto no numérico o negativo, o un local sin contrato activo, **When** se
   valida, **Then** esa fila queda marcada como inválida, no se guarda, y no bloquea a las demás.

---

### User Story 3 - Cobro por QR desde el inicio (Priority: P2)

Una persona en el mostrador (o en el celular) que recibe el pago de un inquilino con su recibo impreso en
mano necesita registrar ese pago en segundos, sin navegar la jerarquía de locaciones. Desde un acceso
directo en el inicio abre una vista de cobro, apunta la cámara al código del recibo (o, si no hay cámara,
tipea el número de recibo), el sistema ubica el recibo y muestra un formulario rápido con los datos clave
y los campos mínimos para registrar el pago.

**Why this priority**: Acelera el momento del cobro presencial, que hoy exige varios pasos de navegación.
Depende de que exista un código en el comprobante, por eso se prioriza después de las cargas masivas, que
no dependen de nada nuevo.

**Independent Test**: Emitir un recibo, abrir su comprobante y comprobar que muestra un código escaneable;
desde el acceso directo del inicio, resolver ese recibo tanto escaneando el código como tipeando su
número; registrar un pago parcial desde el formulario rápido y verificar que queda registrado igual que
si se hubiera hecho desde la pantalla de gestión del recibo; repetir contra un recibo anulado y contra
uno ya saldado y comprobar que el formulario no se ofrece y se muestra un aviso.

**Acceptance Scenarios**:

1. **Given** un recibo emitido, **When** se visualiza su comprobante, **Then** el comprobante muestra un
   código QR discreto que no rompe el diseño de impresión y que representa un enlace verificable a ese
   recibo.
2. **Given** un usuario con perfil Master o Administrador en el inicio, **When** mira el panel, **Then**
   ve una card de acceso directo a "Cobro por QR" y también una entrada de menú equivalente.
3. **Given** la vista de cobro abierta en un dispositivo con cámara y permiso concedido, **When** el
   usuario apunta al código QR de un recibo emitido con saldo pendiente, **Then** el sistema ubica el
   recibo y muestra un formulario rápido con local, periodo, total y saldo pendiente, más campos de
   monto, fecha, medio de pago y evidencia opcional.
4. **Given** la vista de cobro en un dispositivo sin cámara o con el permiso denegado, **When** el
   usuario ingresa manualmente el número de recibo, **Then** el sistema ubica el mismo recibo y ofrece el
   mismo formulario rápido.
5. **Given** el formulario rápido completado con un monto válido, **When** el usuario lo envía, **Then**
   el pago queda registrado con el mismo efecto que registrarlo desde la pantalla de gestión del recibo
   (saldo actualizado, evidencia adjunta si se cargó) y se muestra una confirmación efímera.
6. **Given** un código o número que corresponde a un recibo anulado, en borrador o ya saldado, **When**
   el sistema lo resuelve, **Then** no se ofrece el formulario de pago y se muestra un aviso explicando
   por qué.
7. **Given** un enlace de recibo manipulado o inválido, **When** se intenta abrir la vista de cobro con
   él, **Then** el sistema lo rechaza y pide escanear de nuevo o ingresar el número manualmente.

---

### Edge Cases

- **Plantilla desalineada**: el usuario sube un archivo cuyas columnas no coinciden con la plantilla, o
  sube una plantilla de otro periodo o de la otra función (recibos en lecturas o viceversa). El sistema
  rechaza el archivo con un mensaje claro y no muestra vista previa.
- **Filas de más o de menos**: la plantilla reimportada tiene locales que ya no aplican al periodo, o le
  faltan locales. Los que no aplican se marcan como inválidos; los ausentes simplemente no se tocan.
- **Identificador técnico alterado**: el usuario editó o borró la columna de identificador técnico del
  local. Esas filas se marcan como inválidas (no se puede emparejar el local de forma segura).
- **Archivo grande**: la plantilla contiene cientos de filas. La vista previa y la confirmación siguen
  siendo utilizables y la confirmación se procesa de forma atómica por lote.
- **Doble confirmación**: el usuario pulsa confirmar dos veces, o recarga y reimporta el mismo archivo.
  El resultado es idéntico a confirmarlo una sola vez (el upsert es idempotente para los mismos valores).
- **Abandono de la vista previa**: el usuario navega a otra pantalla sin confirmar. La vista previa no se
  guarda; al volver debe reimportar el archivo. La grilla de captura manual mantiene su autoguardado de
  borrador como hasta ahora, independiente de esto.
- **Total editado vs. recalculado (recibos)**: si el usuario deja el total en blanco en la plantilla, se
  usa el total recalculado; si pone un valor, se respeta ese aunque no cuadre con la suma de componentes.
- **Concepto agregado o quitado del catálogo entre descargar y reimportar**: la vista previa se alinea al
  catálogo vigente al momento de importar; una columna de concepto que ya no existe se ignora con aviso,
  y un concepto nuevo aparece con su valor por defecto.
- **Cámara ocupada o no soportada por el navegador**: la vista de cobro cae automáticamente al ingreso
  manual del número de recibo sin que el usuario tenga que hacer nada especial.
- **Recibo inexistente para el número tipeado**: se muestra "no se encontró un recibo con ese número" y
  se mantiene el foco en el campo para reintentar.
- **Pago que excede el saldo pendiente**: se aplica la misma regla que hoy usa el registro de pago desde
  la pantalla de gestión del recibo (sin introducir una regla nueva en esta feature).
- **Sin JavaScript**: la descarga de plantilla y el formulario rápido de cobro (vía número manual)
  siguen operativos; la vista previa editable y el escaneo por cámara requieren JavaScript y muestran un
  mensaje indicándolo.

## Requirements *(mandatory)*

### Functional Requirements

#### Comunes a la carga masiva (US1 y US2)

- **FR-001**: El sistema MUST ofrecer, en cada pantalla de Registro Masivo existente (lecturas y
  recibos), una acción para descargar una plantilla del periodo actualmente seleccionado y una acción
  para importar un archivo completado, sin remover ni degradar la grilla de captura manual existente.
- **FR-002**: La plantilla descargada MUST incluir una fila por cada locación aplicable al periodo, un
  identificador técnico estable del local (no editable por el usuario para efectos de emparejamiento) y
  un nombre legible del local.
- **FR-003**: La plantilla MUST venir precargada con los valores ya registrados para ese periodo cuando
  existan, de modo que el archivo refleje el estado actual y el usuario solo modifique lo que cambia.
- **FR-004**: El sistema MUST aceptar como importación tanto el formato de hoja de cálculo de la
  plantilla como un formato de valores separados equivalente.
- **FR-005**: Tras una importación válida, el sistema MUST mostrar una vista previa tabular editable en
  la misma pantalla, con todas las filas del archivo y sin haber persistido ningún cambio todavía.
- **FR-006**: El sistema MUST validar cada fila de forma independiente y MUST marcar visiblemente las
  filas inválidas indicando el motivo, permitiendo corregirlas en la misma vista previa.
- **FR-007**: Al confirmar, el sistema MUST guardar únicamente las filas válidas y MUST omitir las
  inválidas, sin abortar todo el lote por causa de ellas.
- **FR-008**: La confirmación MUST comportarse como upsert por local y periodo: crea los registros
  faltantes y actualiza los existentes con los valores de la vista previa.
- **FR-009**: Al terminar la confirmación, el sistema MUST mostrar un resumen con la cantidad de
  registros creados, actualizados y omitidos por error.
- **FR-010**: El sistema MUST rechazar, con un mensaje claro y sin mostrar vista previa, los archivos
  cuya estructura de columnas no corresponda a la plantilla o que correspondan a otro periodo o a la
  otra función de carga.
- **FR-011**: La operación de confirmación MUST ejecutarse de forma atómica por lote (o todo el conjunto
  de filas válidas queda persistido, o ninguno).
- **FR-012**: El sistema MUST tratar la reimportación y confirmación del mismo archivo con los mismos
  valores como idempotente (no genera duplicados ni cambios espurios).
- **FR-013**: La vista previa MUST NOT persistirse entre navegaciones; si el usuario abandona la
  pantalla sin confirmar, al volver debe reimportar el archivo.

#### Específicos de lecturas (US1)

- **FR-014**: La plantilla de lecturas MUST contener, por fila, la lectura del periodo anterior como
  dato de solo referencia y la lectura actual como único valor editable de medición.
- **FR-015**: La plantilla de lecturas MUST NOT contener la tarifa por unidad; el cálculo de consumo y
  total MUST usar la tarifa global vigente en la pantalla al momento de confirmar.
- **FR-016**: El sistema MUST marcar como inválida toda fila de lecturas cuya lectura actual sea menor
  que la lectura del periodo anterior, cuyo valor no sea numérico, cuyo local no exista o esté inactivo,
  o cuyo periodo no coincida con el seleccionado.
- **FR-017**: Tras confirmar la importación de lecturas, la grilla de captura manual MUST reflejar los
  valores importados al recargarse.

#### Específicos de recibos (US2)

- **FR-018**: La plantilla de recibos MUST incluir una fila por locación con contrato activo en el
  periodo, con referencia de contrato, monto de renta, importe de luz, una columna por cada concepto de
  gasto fijo activo del catálogo y el total.
- **FR-019**: En la vista previa de recibos, el usuario MUST poder editar renta, importe de luz, cada
  concepto y el total; local, periodo y contrato MUST ser de solo lectura.
- **FR-020**: El sistema MUST mostrar un total sugerido recalculado a partir de los componentes de la
  fila, y MUST respetar sin sobrescribir cualquier total que el usuario haya ingresado explícitamente.
- **FR-021**: El sistema MUST marcar como inválida toda fila de recibos con montos no numéricos o
  negativos, con local sin contrato activo en el periodo, o con periodo distinto al seleccionado.
- **FR-022**: La vista previa de recibos MUST alinearse al catálogo de conceptos de gasto fijo vigente
  al momento de importar: una columna de concepto ya inexistente se ignora con aviso y un concepto nuevo
  aparece con su valor por defecto.

#### Específicos del cobro por QR (US3)

- **FR-023**: El comprobante de un recibo MUST mostrar un código QR discreto que no altere el diseño
  orientado a impresión y que represente un enlace verificable (no adulterable) al recibo.
- **FR-024**: El inicio MUST ofrecer, para los perfiles Master y Administrador, una card de acceso
  directo a la vista de cobro por QR y una entrada de menú equivalente.
- **FR-025**: La vista de cobro MUST permitir ubicar un recibo escaneando su código QR con la cámara del
  dispositivo cuando esté disponible y el permiso concedido.
- **FR-026**: La vista de cobro MUST ofrecer siempre una alternativa manual para ubicar el recibo por su
  número, y MUST usarla automáticamente como respaldo cuando la cámara no esté disponible o se deniegue
  el permiso.
- **FR-027**: Al ubicar un recibo emitido con saldo pendiente, el sistema MUST mostrar un formulario
  rápido con local, periodo, total y saldo pendiente, y campos de monto, fecha, medio de pago y
  evidencia opcional.
- **FR-028**: El envío del formulario rápido MUST registrar el pago con el mismo efecto y las mismas
  reglas que el registro de pago desde la pantalla de gestión del recibo (saldo, evidencia, atomicidad),
  reutilizando ese mecanismo en lugar de duplicarlo.
- **FR-029**: Si el recibo ubicado está anulado, en borrador o ya saldado, el sistema MUST NOT ofrecer
  el formulario de pago y MUST mostrar un aviso explicando el motivo.
- **FR-030**: El sistema MUST rechazar enlaces de recibo inválidos o manipulados y pedir reintentar el
  escaneo o el ingreso manual.
- **FR-031**: El acceso a la vista de cobro y al registro de pago desde ella MUST estar sujeto a la
  misma autenticación y control de cuenta activa que el resto de la aplicación.

### Non-Functional Requirements

- **NFR-001**: Toda vista Blade nueva o modificada MUST pasar la revisión con el skill `impeccable` y
  cumplir el Principio VI de la constitución (Bootstrap 5.3, iconografía consistente, contraste ≥4.5:1
  con la paleta del proyecto, responsive sin scroll horizontal, atributos de accesibilidad, estilos de
  impresión donde corresponda).
- **NFR-002**: Las notificaciones de éxito/error de estas funciones MUST ser efímeras con autocierre
  (máx. 8 s, pausa al hover/focus, cierre manual), según specs/042; los errores de validación por fila o
  por campo MUST mostrarse de forma persistente junto a su fila/campo.
- **NFR-003**: La interactividad de escritura MUST implementarse con htmx (no Alpine.js), con
  degradación elegante a envío clásico donde sea posible (Principio VI).
- **NFR-004**: Los cálculos monetarios MUST usar tipos decimales exactos y las confirmaciones de lote y
  el registro de pago MUST ejecutarse dentro de transacciones de base de datos (Principio V).
- **NFR-005**: MUST existir cobertura de pruebas automatizadas para los nuevos caminos: generación de
  plantilla, parseo/validación por fila, upsert de lote (crear/actualizar/omitir), resolución de recibo
  por enlace firmado y por número, y registro de pago desde el formulario rápido, incluyendo casos de
  recibo anulado/borrador/saldado y enlace inválido (Principio IV).
- **NFR-006**: Todo el código, nombres y comentarios nuevos MUST estar en español (Principio II).

### Key Entities *(include if feature involves data)*

- **Plantilla de lecturas del periodo**: representación tabular, por periodo, de las locaciones con
  medidor: identificador técnico del local, nombre, lectura anterior (referencia), lectura actual
  (editable). No se persiste como entidad propia; se deriva al descargar y se interpreta al importar.
- **Plantilla de recibos del periodo**: representación tabular, por periodo, de las locaciones con
  contrato activo: identificador técnico del local, nombre, contrato (referencia), renta, luz, un valor
  por concepto de gasto fijo activo, total. Tampoco se persiste como entidad propia.
- **Vista previa de importación**: conjunto transitorio de filas parseadas del archivo con su estado de
  validación (válida / inválida + motivos) y sus valores editables. Vive solo durante la interacción,
  hasta confirmar o abandonar.
- **Lectura de medidor** (existente): registro por local y periodo de la lectura anterior, la actual, el
  consumo derivado y el total; la carga masiva crea o actualiza estos registros.
- **Recibo** (existente): documento por local y periodo con sus conceptos (renta, luz, gastos fijos),
  total y estado (borrador / emitido / anulado / saldado); la carga masiva crea o actualiza estos
  registros y sus conceptos asociados.
- **Enlace verificable de recibo**: referencia no adulterable que permite abrir la vista de cobro
  apuntando a un recibo concreto; se materializa en el código QR del comprobante.
- **Pago** (existente): registro de un abono contra un recibo (monto, fecha, medio de pago, evidencia
  opcional); el formulario rápido de cobro crea estos registros mediante el mecanismo ya existente.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador puede registrar las lecturas de 30 locales de un periodo mediante
  descargar plantilla, completar, importar, revisar y confirmar en menos de 5 minutos, frente a los
  ~15 minutos de la captura manual local por local.
- **SC-002**: En una importación de lecturas con al menos una fila inválida, el 100 % de las filas
  válidas queda persistido en la confirmación y ninguna fila inválida se guarda.
- **SC-003**: Un administrador puede generar/actualizar los recibos de 20 locales de un periodo por
  plantilla en menos de 5 minutos, incluyendo la revisión de la vista previa.
- **SC-004**: El total mostrado tras importar recibos coincide exactamente (sin diferencias de
  redondeo) con la suma de renta, luz y conceptos cuando el usuario no editó el total a mano.
- **SC-005**: Reimportar y volver a confirmar el mismo archivo no cambia ningún registro ni crea
  duplicados (idempotencia verificable comparando el estado antes y después).
- **SC-006**: Desde el inicio, una persona puede pasar de "abrir cobro por QR" a "pago registrado" en
  menos de 30 segundos usando el escaneo, y en menos de 45 segundos usando el número manual.
- **SC-007**: El 100 % de los intentos de registrar un pago sobre un recibo anulado, en borrador o
  saldado, o con un enlace inválido, es bloqueado con un aviso y no produce ningún pago.
- **SC-008**: El comprobante impreso sigue cabiendo en una página y manteniendo su composición actual
  con el código QR agregado (verificación visual en la vista de impresión).

## Assumptions

- **Alcance de "carga masiva"**: se trata de completar/ajustar lecturas y recibos de un **periodo**,
  reutilizando la selección de periodo ya presente en cada pantalla de Registro Masivo. No se cargan
  varios periodos en un mismo archivo.
- **Ubicación**: las nuevas acciones viven en las pantallas existentes `lecturas.registroMasivo.index` y
  `recibos.registroMasivo.index`; no se crean pantallas separadas. La grilla de captura manual y su
  autoguardado de borrador se conservan sin cambios de comportamiento.
- **Formato de plantilla**: hoja de cálculo `.xlsx` generada con la librería de exportación ya presente
  en el proyecto (`maatwebsite/excel` 4.0.2); la importación acepta además `.csv` con las mismas
  columnas.
- **Emparejamiento de filas**: se hace por el identificador técnico del local incluido en la plantilla;
  si esa columna fue alterada o vaciada, la fila es inválida.
- **Marca de periodo en la plantilla**: la plantilla incluye una columna técnica `periodo` (`YYYY-MM`);
  al importar, si no coincide con el periodo seleccionado en la pantalla, el archivo se rechaza sin
  vista previa (habilita FR-010 para el caso "plantilla de otro periodo", que de otro modo no sería
  detectable por contenido).
- **Persistencia de la vista previa**: es en memoria durante la petición/interacción; no hay
  autoguardado de borrador para el flujo de importación (a diferencia de la grilla manual).
- **Tarifa por kWh (lecturas)**: permanece como input global de la pantalla; la importación no la
  modifica ni la transporta.
- **Reglas de recibo**: la derivación de montos por defecto, el total editable y el manejo de conceptos
  de gasto fijo dinámicos reutilizan la lógica ya definida (specs/019, specs/023, specs/024).
- **Código del comprobante**: se usa un **código QR** que codifica una **URL firmada** del framework
  hacia el recibo; la vista de cobro también acepta el número de recibo tecleado. La librería de
  generación de QR (`endroid/qr-code`) se agrega como dependencia PHP.
- **Escaneo por cámara**: se implementa con una librería JavaScript de escaneo (`html5-qrcode`) agregada
  como dependencia de frontend; requiere contexto seguro (HTTPS) y permiso de cámara. El respaldo manual
  cubre los casos donde eso no está disponible. Esta feature no puede verificarse end-to-end sin un
  dispositivo con cámara y HTTPS; esa verificación queda a cargo del usuario.
- **Formulario rápido de cobro**: es una pantalla nueva y liviana, pero **no** introduce reglas de
  negocio de pago nuevas: delega en el mecanismo de registro de pago existente (`pagos.store`), incluida
  la regla vigente para montos que exceden el saldo.
- **Estados de recibo**: "emitido con saldo pendiente" es el único estado que habilita el cobro rápido;
  "borrador", "anulado" y "saldado" muestran aviso.
- **Perfiles**: Master y Administrador tienen acceso a la vista de cobro por QR; no se crea un perfil ni
  un permiso nuevo.
- **Entrega**: una sola rama `044-carga-masiva-y-cobro-qr` con un commit por user story; sin `push` ni
  merge automático.

## Dependencies

- Pantallas y controladores existentes de Registro Masivo de Lecturas (specs/015, specs/016) y de
  Recibos (specs/023), y el catálogo de conceptos de gasto fijo (specs/024).
- Mecanismo de registro de pagos de un recibo (`pagos.store`, specs/032) y de evidencia de pago
  (specs/035).
- Comprobante de recibo (`resources/views/locaciones/recibos/comprobante.blade.php`, specs/031/037/039)
  y panel de inicio (`resources/views/panel/inicio.blade.php`, specs/043).
- Librería de hojas de cálculo `maatwebsite/excel` 4.0.2 (ya instalada).
- Nuevas dependencias a incorporar: `endroid/qr-code` (PHP, generación de QR) y `html5-qrcode`
  (JavaScript, escaneo por cámara).
- Sistema de notificaciones efímeras (specs/042).
