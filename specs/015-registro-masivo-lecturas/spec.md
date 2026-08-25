# Feature Specification: Registro Masivo de Lecturas de Luz

**Feature Branch**: `015-registro-masivo-lecturas`

**Created**: 2026-08-24

**Status**: Draft

**Input**: User description: "Requiero tener otro menu donde puedo registrar de forma masiva pero ordenada y entendible las lecturas de luz, asi como un acceso rapido al historial del periodo anterior"

## Clarifications

### Session 2026-08-24

- Q: El borrador del registro masivo de lecturas, ¿debe sobrevivir aunque el usuario cierre el navegador o pierda la sesión, o alcanza con que sobreviva solo dentro de esa misma sesión/pestaña del navegador? → A: Persiste en el servidor, asociado al usuario y al periodo — sobrevive a cerrar el navegador, perder la sesión o cambiar de dispositivo.
- Q: Cuando el usuario vuelve a abrir la pantalla y existe un borrador guardado para ese periodo, ¿qué debe pasar con los valores que ya había ingresado? → A: Se restauran automáticamente al abrir la pantalla, sin pasos adicionales.
- Q: Cuando el usuario completa el guardado final del lote, ¿qué debe pasar con el borrador y el autoguardado automático de esa sesión? → A: El borrador correspondiente a ese periodo se descarta y el autoguardado cada 2 minutos deja de correr para esa pantalla.
- Q: ¿Qué representa exactamente el valor editable de "Kilowatts" del totalizado, y cómo se calcula el total mostrado por local? → A: Precio unitario (tarifa) por kWh: total_local = consumo_kWh × valor_kWh.
- Q: ¿El valor de la tarifa por kWh se ingresa una sola vez para todo el lote, o cada local tiene su propio valor, y hace falta un total general además de los totales por fila? → A: Un solo input global de tarifa para todo el periodo, con total por fila y un total general al pie.
- Q: ¿El valor de la tarifa por kWh debe persistir para recordarse la próxima vez, o es solo un valor temporal en pantalla? → A: Se persiste, y es el mismo valor ya existente en la configuración general (`ConfiguracionGeneral.tarifa_luz_por_unidad`, usado hoy en la generación de recibos): la pantalla lo precarga como valor por defecto, permite editarlo, y al modificarlo desde ahí actualiza esa misma configuración general para que quede disponible como valor por defecto en periodos futuros.
- Q: Para la exportación a Excel y PDF de esta pantalla, ¿qué contenido debe incluir exactamente? → A: Todo lo visible en pantalla para el periodo seleccionado: todas las locaciones alquilables (completadas y pendientes), lectura anterior, lectura actual, consumo, total por local y total general.
- Q: Para editar una lectura ya registrada sin salir de la pantalla, ¿cómo debe funcionar esa edición en el mismo lugar donde hoy está el ícono/badge de "Completada"? → A: Edición inline en la misma fila (el ícono no invasivo, al hacer clic, convierte el valor en un input editable con guardar/cancelar vía AJAX, sin recargar la página), reutilizando las mismas validaciones que el registro individual existente (incluida la confirmación de consumo negativo).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Registrar lecturas de luz de varias locaciones en una sola visita (Priority: P1)

Un usuario que debe registrar las lecturas del medidor de luz de todas sus locaciones alquilables cada mes necesita hacerlo desde una única pantalla, en vez de repetir el formulario individual de cada locación una por una.

**Why this priority**: Es el pedido explícito del usuario y el que más tiempo le ahorra en su tarea mensual más repetitiva; sin esto, el resto de la funcionalidad (historial de referencia) no tiene dónde vivir.

**Independent Test**: Puede probarse por completo abriendo el nuevo menú, completando la lectura actual de varias locaciones a la vez y guardando en una sola acción, verificando que todas las lecturas completadas quedan registradas.

**Acceptance Scenarios**:

1. **Given** varias locaciones alquilables sin lectura registrada para el periodo actual, **When** el usuario abre el nuevo menú de registro masivo, **Then** ve una fila por cada locación alquilable, agrupadas de forma consistente con la organización jerárquica ya usada en la vista general de locaciones, con un campo para ingresar la lectura actual de cada una.
2. **Given** la pantalla de registro masivo con varias filas completadas, **When** el usuario guarda, **Then** todas las lecturas completadas quedan registradas en una sola acción, sin tener que repetir el proceso locación por locación.
3. **Given** la pantalla de registro masivo con algunas filas vacías y otras completadas, **When** el usuario guarda, **Then** solo se registran las filas completadas; las vacías se ignoran sin producir un error que bloquee el guardado del resto.
4. **Given** una fila con una lectura actual menor a la lectura anterior de esa locación, **When** el usuario intenta guardar sin confirmar, **Then** el sistema le pide una confirmación explícita para esa fila antes de registrarla, igual que en el registro individual ya existente.
5. **Given** un lote con una fila inválida (por ejemplo, un valor no numérico) y otras filas válidas, **When** el usuario guarda, **Then** las filas válidas quedan registradas y el sistema indica claramente cuál fila tuvo un error, sin descartar lo demás.

---

### User Story 2 - Ver la lectura del periodo anterior como referencia sin salir de la pantalla (Priority: P2)

Mientras completa el registro masivo, el usuario necesita ver de un vistazo la lectura del periodo anterior de cada locación, para poder verificar que el nuevo valor tenga sentido antes de guardarlo, sin tener que abrir el historial de cada locación por separado.

**Why this priority**: Complementa a la User Story 1 reduciendo errores de digitación, pero la pantalla de registro masivo ya entrega valor por sí sola sin esta referencia visible.

**Independent Test**: Puede probarse por completo abriendo la pantalla de registro masivo con locaciones que ya tienen lecturas de periodos anteriores, y verificando que el valor del periodo anterior de cada una es visible junto al campo de la lectura nueva, sin navegación adicional.

**Acceptance Scenarios**:

1. **Given** una locación con una lectura registrada en el periodo inmediatamente anterior, **When** el usuario ve su fila en la pantalla de registro masivo, **Then** el valor de esa lectura anterior es visible junto al campo de lectura nueva.
2. **Given** una locación sin ninguna lectura previa registrada, **When** el usuario ve su fila, **Then** se indica claramente que no hay lectura previa, en vez de mostrar un valor vacío ambiguo o un error.

---

### User Story 3 - No perder el trabajo si se interrumpe el registro masivo (Priority: P3)

Mientras completa el registro masivo (una tarea que puede tomar tiempo si recorre varias locaciones físicamente), el usuario necesita que lo ya ingresado se guarde como borrador automáticamente cada 2 minutos, para no perder su trabajo si se queda sin batería, pierde la sesión o cambia de dispositivo antes de guardar el lote completo.

**Why this priority**: Protege contra la pérdida de trabajo en una tarea potencialmente larga, pero la pantalla de registro masivo (User Story 1) ya entrega el valor principal sin esta protección.

**Independent Test**: Puede probarse por completo completando algunas filas sin guardar, esperando a que corra el autoguardado, cerrando la sesión o el navegador, y verificando que al volver a abrir la pantalla para el mismo periodo los valores ingresados siguen ahí.

**Acceptance Scenarios**:

1. **Given** el usuario tiene la pantalla de registro masivo abierta con algunas filas completadas sin guardar, **When** transcurren 2 minutos, **Then** esos valores quedan guardados como borrador asociado a su usuario y al periodo, sin que el usuario tenga que hacer nada.
2. **Given** un borrador guardado para un periodo, **When** el usuario vuelve a abrir la pantalla de registro masivo para ese mismo periodo (aunque sea desde otra sesión o dispositivo), **Then** los valores del borrador se restauran automáticamente en los campos correspondientes.
3. **Given** un borrador con valores restaurados, **When** el usuario completa el guardado final del lote exitosamente, **Then** el borrador de ese periodo se descarta y el autoguardado automático deja de correr para esa pantalla.

---

### Edge Cases

- ¿Qué pasa si no hay ninguna locación alquilable en el sistema? La pantalla debe mostrar un estado vacío claro, en vez de una tabla en blanco sin explicación.
- ¿Qué pasa si una locación ya tiene una lectura registrada para el periodo seleccionado? Su fila se muestra como ya completada (con el valor ya registrado) en vez de un campo vacío para evitar un registro duplicado, con acceso directo para corregirla si hace falta.
- ¿Qué pasa si el usuario cambia el periodo seleccionado a un mes anterior? La pantalla debe recalcular qué locaciones ya tienen lectura para ese periodo y cuáles siguen pendientes, igual que para el periodo actual.
- ¿Qué pasa si una locación deja de ser alquilable después de tener lecturas registradas? No debe aparecer en la pantalla de registro masivo para periodos nuevos, consistente con el resto del sistema.
- ¿Qué pasa si el autoguardado del borrador falla (por ejemplo, sin conexión en ese momento)? Debe reintentarse en el siguiente ciclo de 2 minutos sin interrumpir al usuario ni mostrar un error bloqueante; el trabajo ya escrito en pantalla no se pierde mientras siga ahí.
- ¿Qué pasa si el mismo usuario tiene la pantalla abierta en dos sesiones o dispositivos distintos para el mismo periodo? El último autoguardado en escribirse es el que prevalece como borrador (sin combinar valores de ambas sesiones).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE ofrecer una pantalla accesible desde la navegación principal donde, para un periodo seleccionado, se listan todas las locaciones alquilables con un campo para registrar su lectura de luz actual.
- **FR-002**: La pantalla DEBE organizar las locaciones agrupadas de forma consistente con la jerarquía ya usada en la vista general de locaciones, para que el listado sea entendible de un vistazo aun con muchas locaciones.
- **FR-003**: El sistema DEBE permitir registrar las lecturas de varias locaciones en una sola acción de guardado, sin requerir un envío de formulario independiente por locación.
- **FR-004**: Las filas dejadas vacías al momento de guardar DEBEN ignorarse sin generar un error que bloquee el registro de las demás filas completadas.
- **FR-005**: Una locación que ya tiene una lectura registrada para el periodo seleccionado DEBE mostrarse como completada en vez de con un campo vacío, indicándolo con un ícono discreto (no invasivo, sin texto de badge) en vez de una etiqueta de texto, con un acceso directo para editar esa lectura existente.
- **FR-006**: El sistema DEBE mostrar, junto al campo de cada locación, la lectura del periodo inmediatamente anterior como referencia, o una indicación clara de que no existe lectura previa.
- **FR-007**: La pantalla DEBE permitir cambiar el periodo seleccionado (por defecto el periodo actual) a un periodo anterior, recalculando qué locaciones tienen lectura pendiente para ese periodo.
- **FR-008**: Cuando la lectura actual ingresada en una fila sea menor a su lectura anterior, el sistema DEBE requerir una confirmación explícita para esa fila antes de guardarla, igual que ya exige el registro individual existente.
- **FR-009**: Si una o más filas del lote tienen un error de validación, el sistema DEBE registrar igualmente las filas válidas del mismo lote e indicar con claridad cuáles no se guardaron y por qué.
- **FR-010**: El sistema DEBE guardar automáticamente, cada 2 minutos, los valores ya ingresados y aún no confirmados en la pantalla de registro masivo, como un borrador persistido en el servidor asociado al usuario y al periodo seleccionado.
- **FR-011**: Al abrir la pantalla de registro masivo para un periodo con un borrador existente, el sistema DEBE restaurar automáticamente esos valores en los campos correspondientes, sin requerir una acción adicional del usuario.
- **FR-012**: Al completar exitosamente el guardado final del lote, el sistema DEBE descartar el borrador de ese periodo y detener el autoguardado automático de esa pantalla.
- **FR-013**: La pantalla DEBE ofrecer un único campo editable global (aplicado a todas las filas del periodo) con el valor de la tarifa (precio) por kWh, precargado con el valor vigente de la configuración general (`tarifa_luz_por_unidad`), y DEBE mostrar junto a cada local un total calculado como el consumo de esa fila multiplicado por ese valor de tarifa.
- **FR-014**: La pantalla DEBE mostrar un total general (suma de los totales de todas las filas del periodo) además de los totales por fila.
- **FR-015**: Cuando el usuario modifica el valor de la tarifa desde esta pantalla, el sistema DEBE actualizar la configuración general (`tarifa_luz_por_unidad`) para que ese nuevo valor quede disponible como valor por defecto en periodos futuros (en esta pantalla y en la generación de recibos).
- **FR-016**: El sistema DEBE permitir exportar la pantalla de registro masivo del periodo seleccionado a Excel y a PDF, incluyendo en ambos formatos todas las locaciones alquilables del periodo (completadas y pendientes) con su lectura anterior, lectura actual, consumo, total por local y el total general.
- **FR-017**: El acceso directo para editar una lectura ya registrada (FR-005) DEBE permitir editarla en línea, dentro de la misma fila de la pantalla de registro masivo, sin navegar a otra pantalla; el guardado de esa edición DEBE aplicar las mismas validaciones de negocio que el registro individual existente, incluida la confirmación explícita ante un consumo negativo (FR-008).

### Key Entities *(include if feature involves data)*

- **Locación**: la propiedad o unidad alquilable cuya lectura de luz se registra; determina si aparece en la pantalla según su condición de alquilable.
- **Lectura de Medidor**: el registro de lectura de luz de una locación en un periodo específico (lectura anterior, lectura actual, consumo calculado); esta funcionalidad agrega una vía de creación masiva sobre la misma entidad ya existente, sin cambiar su forma.
- **Borrador de Registro Masivo**: los valores aún no confirmados que el usuario va ingresando en la pantalla de registro masivo para un periodo, guardados automáticamente cada 2 minutos; es un estado intermedio y transitorio por usuario y periodo, distinto de una Lectura de Medidor ya registrada, y se descarta al completar el guardado final de ese periodo.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un usuario puede registrar las lecturas de luz de todas sus locaciones alquilables pendientes del periodo en una sola visita a la pantalla, sin repetir el proceso de formulario por cada locación.
- **SC-002**: Un usuario puede ver la lectura del periodo anterior de cualquier locación listada sin salir de la pantalla de registro masivo ni abrir otra vista.
- **SC-003**: El 100% de las locaciones alquilables del sistema aparecen listadas en la pantalla de registro masivo, agrupadas de forma consistente con la vista general de locaciones.
- **SC-004**: Un error de validación en una fila del lote no provoca la pérdida de las demás filas válidas ya completadas en el mismo guardado.
- **SC-005**: Si el registro masivo se interrumpe (cierre de sesión, cambio de dispositivo, pérdida de conexión) después de haber estado la pantalla abierta al menos 2 minutos, el usuario recupera automáticamente su trabajo al volver a abrir la pantalla para el mismo periodo, sin tener que volver a escribir los valores ya ingresados.

## Assumptions

- "Lecturas de luz" corresponde a la entidad de lectura de medidor ya existente en el sistema (no hay lecturas de otros servicios como agua registradas por medidor individual hoy).
- El alcance de la pantalla masiva es el conjunto completo de locaciones alquilables del sistema (no limitado a una sola galería o piso a la vez), ya que el pedido es reducir el trabajo repetitivo de recorrerlas una por una.
- "Ordenada y entendible" se resuelve agrupando las filas según la misma jerarquía (galería → piso → local) que ya organiza la vista general de locaciones, en vez de una lista plana o un orden distinto.
- El guardado masivo persiste cada fila válida como una operación independiente (no exige que todo el lote sea válido para guardar cualquier parte de él), priorizando no perder el trabajo ya completado por el usuario.
- El periodo por defecto al abrir la pantalla es el mes actual, con la misma capacidad de cambiar de periodo que ya ofrece el registro individual existente.
- Esta funcionalidad no reemplaza el registro y edición individual de lecturas ya existente (`locaciones/{locacion}/lecturas`); es una vía adicional para el caso de uso de completar varias locaciones a la vez.
- El autoguardado de borrador (User Story 3) no aplica las mismas validaciones de negocio que el guardado final (por ejemplo, la confirmación de consumo negativo de FR-008): guarda los valores tal como están escritos en ese momento, sin bloquear al usuario mientras sigue completando la pantalla. Las validaciones se aplican recién al guardado final del lote.
- Un borrador es por usuario y por periodo: dos usuarios distintos completando el mismo periodo no comparten ni sobrescriben el borrador del otro.
