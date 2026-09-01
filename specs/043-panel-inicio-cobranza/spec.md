# Feature Specification: Panel de Inicio — Estado de Cobranza (Morosos, Próximos Vencimientos e Indicadores)

**Feature Branch**: `043-panel-inicio-cobranza`

**Created**: 2026-08-30

**Status**: Draft

**Input**: User description: panel de inicio de solo lectura que consolida el estado de cobranza del negocio
en una pantalla: recibos morosos (P1), próximos vencimientos de pago (P2) e indicadores generales de
cobranza del mes en curso más contratos por vencer (P3). Reemplaza la pantalla de inicio actual tras iniciar
sesión, accesible por igual a los perfiles Master y Administrador. No registra ni modifica nada — enlaza a las
pantallas existentes de detalle de recibo, seguimiento de pagos y detalle de contrato.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ver de un vistazo qué inquilinos están morosos (Priority: P1)

Un administrador (perfil Master o Administrador) inicia sesión y cae directamente en el panel de inicio. Lo
primero que ve es el estado de morosidad del negocio: unas tarjetas de resumen con cuántos recibos están
vencidos e impagos, cuántos inquilinos distintos deben, y cuánto dinero vencido hay por cobrar en total y
desglosado por antigüedad del atraso. Debajo, una tabla de esos recibos morosos, ordenada del más atrasado al
menos atrasado, con el inquilino, el local, el periodo, los montos y los días de atraso. Hace clic en una
fila y llega al detalle de ese recibo en la pantalla existente para gestionar el cobro.

**Why this priority**: es el corazón del panel y la razón de construirlo — el ciclo de cobro del negocio
depende de identificar rápido y sin cálculo manual quién está atrasado y por cuánto.

**Independent Test**: con datos que incluyan al menos un recibo no anulado, con saldo pendiente > 0 y fecha
límite de pago ya pasada, abrir el panel y verificar que ese recibo aparece en la tabla de morosos con su
inquilino, local, periodo, total, pagado, saldo pendiente, fecha límite y días de atraso correctos, que las
tarjetas de resumen reflejan ese recibo, y que la fila enlaza al detalle del recibo.

**Acceptance Scenarios**:

1. **Given** un recibo con estado distinto de "anulado", saldo pendiente mayor a cero y fecha límite de pago
   anterior a hoy, **When** se abre el panel, **Then** ese recibo aparece como una fila en la tabla de
   morosos.
2. **Given** un recibo pagado por completo (saldo pendiente cero), o un recibo anulado, o un recibo cuya
   fecha límite aún no venció, **When** se abre el panel, **Then** ese recibo NO aparece en la tabla de
   morosos.
3. **Given** la tabla de morosos con varios recibos, **When** se muestra, **Then** las filas están ordenadas
   por días de atraso de mayor a menor.
4. **Given** un contrato con dos recibos vencidos impagos, **When** se abre el panel, **Then** se muestran
   dos filas separadas (una por recibo), no una fila agregada por contrato.
5. **Given** una fila de la tabla de morosos, **When** se hace clic en ella, **Then** se navega al detalle
   del recibo correspondiente (pantalla existente).
6. **Given** un conjunto de recibos morosos, **When** se muestran las tarjetas de resumen, **Then**
   "recibos morosos" es el conteo de filas, "inquilinos morosos" es la cantidad de inquilinos principales
   distintos con al menos un recibo moroso, y "monto adeudado vencido" es la suma de los saldos pendientes de
   esas filas.
7. **Given** las tarjetas de resumen, **When** se muestra el desglose por antigüedad, **Then** hay cuatro
   tramos — 1 a 30, 31 a 60, 61 a 90 y más de 90 días de atraso — cada uno con su cantidad de recibos y su
   monto sumado, y la suma de los cuatro montos es igual al "monto adeudado vencido" total.
8. **Given** que no existe ningún recibo moroso, **When** se abre el panel, **Then** en lugar de la tabla se
   muestra el estado vacío del proyecto con el mensaje "No hay recibos vencidos impagos".

---

### User Story 2 - Anticiparse a los pagos que están por vencer (Priority: P2)

El mismo administrador, después de revisar los morosos, mira el bloque de "próximos vencimientos": los
recibos que todavía tienen saldo pendiente pero cuya fecha límite de pago aún no llegó, ordenados por fecha
límite ascendente (lo que vence primero, arriba). Le sirve para llamar a esos inquilinos antes de que caigan
en mora. Una tarjeta le dice cuántos son y cuánto suman.

**Why this priority**: reduce la morosidad futura, pero es secundario frente a cobrar lo ya vencido; el panel
sigue siendo útil aunque este bloque se entregue después.

**Independent Test**: con un recibo no anulado, con saldo pendiente > 0 y fecha límite de pago igual o
posterior a hoy, abrir el panel y verificar que aparece en el bloque de próximos vencimientos con inquilino,
local, periodo, saldo pendiente, fecha límite y días restantes, que el orden es por fecha límite ascendente,
y que la tarjeta resumen muestra la cantidad y el monto correctos.

**Acceptance Scenarios**:

1. **Given** un recibo no anulado con saldo pendiente mayor a cero y fecha límite de pago igual o posterior a
   hoy, **When** se abre el panel, **Then** ese recibo aparece en el bloque de próximos vencimientos.
2. **Given** el bloque de próximos vencimientos con varios recibos, **When** se muestra, **Then** las filas
   están ordenadas por fecha límite de pago de la más próxima a la más lejana.
3. **Given** un recibo cuya fecha límite ya pasó, **When** se abre el panel, **Then** aparece en la tabla de
   morosos (US1) y NO en el bloque de próximos vencimientos.
4. **Given** el bloque de próximos vencimientos, **When** se muestra su tarjeta resumen, **Then** indica la
   cantidad de recibos y la suma de sus saldos pendientes aún en plazo.
5. **Given** que no hay ningún recibo con saldo pendiente en plazo, **When** se abre el panel, **Then** el
   bloque muestra su estado vacío ("No hay pagos próximos a vencer").

---

### User Story 3 - Leer los indicadores generales de cobranza y los contratos por vencer (Priority: P3)

El administrador quiere una foto rápida del mes: cuánto se facturó, cuánto de eso ya está cobrado (y qué
porcentaje representa), cuánto dinero entró en caja este mes contando cualquier periodo, y cuánta cartera
total queda por cobrar sumando todos los periodos. Además, un recordatorio de qué contratos terminan pronto
(30, 15 y 7 días) para renovarlos o cerrarlos a tiempo, cada uno enlazando a su contrato.

**Why this priority**: es contexto de gestión valioso pero no acciona el cobro del día como los morosos; el
panel entrega valor completo sin este bloque.

**Independent Test**: con recibos y pagos del mes calendario en curso, abrir el panel y verificar las
tarjetas de indicadores (facturado del periodo, cobrado de recibos del periodo, recaudado este mes, tasa de
cobranza, cartera total por cobrar) con valores que cuadran con los datos, y las tres listas acumulativas de
contratos que terminan dentro de 30, 15 y 7 días, cada contrato enlazando a su detalle.

**Acceptance Scenarios**:

1. **Given** los recibos no anulados cuyo periodo es el mes calendario en curso, **When** se muestra el
   indicador "facturado del periodo", **Then** su valor es la suma de los totales de esos recibos.
2. **Given** los pagos imputados a recibos cuyo periodo es el mes en curso, **When** se muestra el indicador
   "cobrado de recibos del periodo", **Then** su valor es la suma de esos pagos sin importar la fecha en que
   se pagaron.
3. **Given** los pagos cuya fecha de pago cae dentro del mes calendario en curso, **When** se muestra el
   indicador "recaudado este mes", **Then** su valor es la suma de esos pagos sin importar a qué periodo
   pertenece el recibo al que se imputaron. Este indicador es independiente de "cobrado de recibos del
   periodo" y se muestra como una tarjeta aparte.
4. **Given** un valor de facturado del periodo mayor a cero, **When** se muestra la "tasa de cobranza del
   periodo", **Then** es el cociente "cobrado de recibos del periodo" ÷ "facturado del periodo" expresado en
   porcentaje; si el facturado del periodo es cero, se muestra un guion o "sin datos" en vez de una división
   por cero. "Recaudado este mes" no interviene en esta tasa.
5. **Given** todos los recibos no anulados de cualquier periodo, **When** se muestra "cartera total por
   cobrar", **Then** su valor es la suma de todos sus saldos pendientes.
6. **Given** los contratos vigentes cuya fecha de fin está entre hoy y hoy + N días, **When** se muestra el
   bloque "contratos por vencer", **Then** hay tres grupos acumulativos —fecha de fin dentro de 30, dentro
   de 15 y dentro de 7 días—, de modo que un contrato que termina en 5 días aparece en los tres grupos; cada
   grupo muestra una lista de contratos, no solo un conteo.
7. **Given** un contrato cuya fecha de fin ya pasó pero cuyo estado sigue vigente (sin marcarse como
   rescindido), **When** se muestra el bloque "contratos por vencer", **Then** ese contrato NO aparece en
   ninguno de los tres grupos (el bloque solo mira hacia adelante: fecha de fin entre hoy y hoy + N).
8. **Given** un contrato listado en el bloque "contratos por vencer", **When** se hace clic en él, **Then**
   se navega al detalle de ese contrato (pantalla existente).

---

### User Story 4 - El panel es la pantalla de inicio tras iniciar sesión (Priority: P1)

Cualquier usuario con perfil Master o Administrador, al iniciar sesión, llega a este panel en vez de a la
pantalla de inicio actual. El panel no ofrece ninguna acción de escritura: no hay botones de registrar pago,
anular recibo ni editar contrato; todo lo que se puede hacer desde aquí es leer y navegar a las pantallas
existentes.

**Why this priority**: sin ser la pantalla de inicio, el panel no cumple su función de "primera foto del
negocio"; es parte del alcance mínimo junto con US1.

**Independent Test**: iniciar sesión con un usuario Master y con un usuario Administrador y verificar que
ambos aterrizan en el panel; revisar el panel y confirmar que no contiene ningún control que cree, edite o
elimine datos.

**Acceptance Scenarios**:

1. **Given** un usuario con perfil Master, **When** inicia sesión, **Then** aterriza en el panel de inicio.
2. **Given** un usuario con perfil Administrador, **When** inicia sesión, **Then** aterriza en el panel de
   inicio.
3. **Given** el panel mostrado, **When** se revisa su contenido, **Then** no existe ningún formulario ni
   botón que registre, edite o anule pagos, recibos o contratos; las únicas interacciones son los filtros
   (US1) y los enlaces a pantallas existentes.
4. **Given** un usuario no autenticado, **When** intenta abrir la URL del panel, **Then** es redirigido a
   iniciar sesión.

---

### Edge Cases

- **Mes del periodo que termina en sábado**: la fecha límite de pago es ese mismo sábado (misma regla que
  specs/008).
- **Recibo cuyo contrato no tiene inquilino principal registrado**: la fila muestra un marcador ("—" o el
  identificador del contrato) en la columna de inquilino, sin romper el listado.
- **Recibo sin ningún pago**: monto pagado 0, saldo pendiente igual al total; entra en morosos o próximos
  vencimientos según su fecha límite.
- **Pagos que igualan o superan el total del recibo**: el saldo pendiente se muestra como 0 (nunca
  negativo), y el recibo no cuenta como moroso ni como próximo vencimiento.
- **Recibo con fecha límite de pago exactamente hoy**: no está vencido todavía → cuenta como próximo
  vencimiento con 0 días restantes, no como moroso.
- **Filtro por rama de la jerarquía de locaciones**: seleccionar una galería o un piso incluye los recibos
  de esa locación y de todas sus locaciones descendientes.
- **Filtro por tramo de antigüedad sin resultados**: se muestra el estado vacío del listado, no una tabla
  vacía sin encabezado.
- **Sin JavaScript**: los filtros de US1 funcionan igual mediante recarga de página (degradación elegante);
  el panel completo se renderiza en el servidor.
- **Periodo en curso sin recibos generados todavía**: "facturado del periodo" y "cobrado del periodo" valen
  0 y la tasa de cobranza se muestra como guion/"sin datos".
- **Contrato ya vencido (fecha de fin pasada) pero no marcado como rescindido**: su tratamiento en el bloque
  "contratos por vencer" se resuelve en la aclaración de FR-032 / US3 Acceptance Scenario 5.

## Requirements *(mandatory)*

### Functional Requirements

#### Acceso y alcance (US4)

- **FR-001**: El panel DEBE ser la pantalla de inicio a la que llega un usuario autenticado tras iniciar
  sesión, en reemplazo de la pantalla de inicio actual, para los perfiles Master y Administrador por igual.
- **FR-002**: El panel DEBE ser de solo lectura: no DEBE ofrecer ningún control para crear, editar, anular o
  eliminar pagos, recibos, contratos ni ninguna otra entidad. Las únicas interacciones permitidas son los
  filtros del listado de morosos y los enlaces de navegación a pantallas existentes (detalle de recibo,
  seguimiento de pagos, detalle de contrato).
- **FR-003**: Un usuario no autenticado que intente acceder al panel DEBE ser redirigido a iniciar sesión.
- **FR-004**: Todos los cálculos del panel DEBEN excluir los recibos en estado "anulado".

#### Definición de recibo moroso y fecha límite (US1)

- **FR-005**: El sistema DEBE considerar un recibo como **moroso** cuando, y solo cuando, se cumplan las tres
  condiciones: (a) su estado no es "anulado"; (b) su saldo pendiente —total del recibo menos la suma de sus
  pagos— es mayor a cero; (c) su fecha límite de pago es anterior a la fecha actual.
- **FR-006**: La **fecha límite de pago** de un recibo DEBE derivarse de su periodo como el último sábado del
  mes calendario de ese periodo; si el último día de ese mes es sábado, la fecha límite es ese día. No
  requiere capturar un dato nuevo del usuario.
- **FR-007**: El sistema NO DEBE calcular mora, recargos ni intereses. Solo DEBE mostrar el saldo pendiente
  tal cual.
- **FR-008**: El saldo pendiente NUNCA DEBE mostrarse negativo; si la suma de pagos iguala o supera el total,
  el saldo pendiente es 0 y el recibo no es moroso.
- **FR-009**: Los **días de atraso** de un recibo moroso DEBEN calcularse como la cantidad de días
  completos entre su fecha límite de pago y la fecha actual.

#### Listado de morosos (US1)

- **FR-010**: El panel DEBE mostrar un listado de todos los recibos morosos, ordenado por días de atraso de
  mayor a menor.
- **FR-011**: Cada fila del listado de morosos DEBE mostrar: nombre del inquilino principal del contrato,
  la locación (local) con su ruta jerárquica corta, el periodo del recibo, el monto total del recibo, el
  monto pagado, el saldo pendiente, la fecha límite de pago y los días de atraso.
- **FR-012**: Si un mismo contrato tiene varios recibos morosos, el listado DEBE mostrarlos como filas
  separadas (una por recibo), sin agregarlos.
- **FR-013**: Cada fila del listado de morosos DEBE enlazar al detalle del recibo correspondiente.
- **FR-014**: Si no existe ningún recibo moroso, el panel DEBE mostrar el estado vacío del proyecto con el
  mensaje "No hay recibos vencidos impagos" en lugar del listado.

#### Tarjetas de resumen de morosidad (US1)

- **FR-015**: El panel DEBE mostrar, encima del listado de morosos, tarjetas de resumen con: (a) cantidad de
  recibos morosos; (b) cantidad de inquilinos principales distintos con al menos un recibo moroso; (c) monto
  total adeudado vencido, igual a la suma de los saldos pendientes de los recibos morosos.
- **FR-016**: El panel DEBE mostrar el monto total adeudado vencido desglosado por antigüedad del atraso en
  cuatro tramos —1 a 30 días, 31 a 60 días, 61 a 90 días y más de 90 días—, indicando para cada tramo la
  cantidad de recibos morosos y el monto sumado de sus saldos pendientes.
- **FR-017**: La suma de los montos de los cuatro tramos de antigüedad DEBE ser igual al monto total
  adeudado vencido de FR-015(c).

#### Filtros del listado de morosos (US1)

- **FR-018**: El usuario DEBE poder filtrar el listado de morosos por tramo de antigüedad del atraso (los
  mismos cuatro tramos de FR-016).
- **FR-019**: El usuario DEBE poder filtrar el listado de morosos por rama de la jerarquía de locaciones;
  seleccionar una locación incluye los recibos de esa locación y de todas sus locaciones descendientes.
- **FR-020**: Los filtros DEBEN poder combinarse (tramo de antigüedad + rama de locación) y DEBEN funcionar
  sin JavaScript mediante recarga de página (degradación elegante).
- **FR-021**: Cuando un filtro deja el listado sin resultados, el panel DEBE mostrar el estado vacío del
  listado, no una tabla vacía.
- **FR-022**: Al aplicar cualquier filtro del listado de morosos, las tarjetas de resumen (FR-015) y el
  desglose por antigüedad (FR-016) DEBEN recalcularse sobre el mismo subconjunto filtrado, de modo que el
  panel de morosidad responda como un todo al filtro y las tarjetas siempre cuadren con las filas visibles.
  Sin ningún filtro aplicado, las tarjetas reflejan el total de recibos morosos del negocio.

#### Próximos vencimientos de pago (US2)

- **FR-023**: El panel DEBE mostrar un bloque con los recibos no anulados que tienen saldo pendiente mayor a
  cero y cuya fecha límite de pago es igual o posterior a la fecha actual, ordenados por fecha límite de pago
  ascendente.
- **FR-024**: Cada fila del bloque de próximos vencimientos DEBE mostrar: nombre del inquilino principal, la
  locación con su ruta jerárquica corta, el periodo del recibo, el saldo pendiente, la fecha límite de pago y
  los días restantes hasta esa fecha.
- **FR-025**: El bloque de próximos vencimientos DEBE incluir una tarjeta de resumen con la cantidad de
  recibos y la suma de sus saldos pendientes aún en plazo.
- **FR-026**: Cada fila del bloque de próximos vencimientos DEBE enlazar al detalle del recibo
  correspondiente.
- **FR-027**: Si no hay ningún recibo con saldo pendiente en plazo, el bloque DEBE mostrar su estado vacío
  ("No hay pagos próximos a vencer").

#### Indicadores generales de cobranza (US3)

- **FR-028**: El panel DEBE mostrar el **monto facturado del periodo actual**: la suma de los totales de los
  recibos no anulados cuyo periodo es el mes calendario en curso.
- **FR-029**: El panel DEBE mostrar el **monto cobrado de recibos del periodo**: la suma de los pagos
  imputados a recibos no anulados cuyo periodo es el mes calendario en curso, sin importar la fecha en que se
  registraron esos pagos.
- **FR-029a**: El panel DEBE mostrar, como tarjeta independiente, el **monto recaudado este mes**: la suma de
  los pagos cuya fecha de pago cae dentro del mes calendario en curso, sin importar a qué periodo pertenece
  el recibo al que se imputaron.
- **FR-030**: El panel DEBE mostrar la **tasa de cobranza del periodo**: "monto cobrado de recibos del
  periodo" (FR-029) ÷ "monto facturado del periodo" (FR-028), en porcentaje. Si el facturado del periodo es
  cero, DEBE mostrar un guion o "sin datos", no una división por cero. "Monto recaudado este mes" no
  interviene en esta tasa.
- **FR-031**: El panel DEBE mostrar la **cartera total por cobrar**: la suma de los saldos pendientes de
  todos los recibos no anulados de cualquier periodo.
- **FR-032**: El panel DEBE mostrar el bloque **contratos por vencer** con tres grupos **acumulativos** según
  la fecha de fin del contrato: contratos que terminan dentro de 30 días, dentro de 15 días y dentro de 7
  días (un contrato que termina en 5 días aparece en los tres grupos). Cada grupo DEBE presentarse como una
  **lista de contratos** (no solo un conteo). El bloque solo DEBE considerar contratos **vigentes** cuya
  fecha de fin esté entre la fecha actual y la fecha actual + N días; los contratos cuya fecha de fin ya pasó,
  o cuyo estado no es vigente, NO se incluyen.
- **FR-033**: Cada contrato listado en "contratos por vencer" DEBE enlazar al detalle de ese contrato.

#### Presentación y rendimiento (transversal)

- **FR-034**: El panel DEBE construirse con los componentes de interfaz vigentes del proyecto: tarjetas para
  los resúmenes, listado tabular con filas resaltables al pasar el cursor, indicador de estado con color
  semántico, ruta jerárquica para la ubicación del local, prefijo "S/" en los montos y numerales tabulares
  para que las cifras alineen en columna.
- **FR-035**: El panel DEBE ser responsive sin scroll horizontal en los anchos estándar; los listados largos
  se desplazan dentro de su propio contenedor.
- **FR-036**: El panel DEBE cargar completo en menos de 2 segundos con un volumen de cientos de recibos y
  contratos.
- **FR-037**: El panel NO DEBE modificar ningún dato al ser consultado ni al aplicar filtros.

### Key Entities *(include if feature involves data)*

- **Recibo**: comprobante mensual de cobro de un contrato para un periodo (mes). Atributos usados por el
  panel: estado (pendiente/pagado/anulado), periodo, total, y la relación con sus pagos y con su contrato y
  locación. El panel deriva de él: saldo pendiente, fecha límite de pago, condición de moroso, días de
  atraso / días restantes, tramo de antigüedad.
- **Pago**: abono registrado contra un recibo. Atributos usados: monto y fecha de pago. El panel los suma
  para obtener el monto pagado por recibo, el "cobrado de recibos del periodo" (pagos de recibos del mes en
  curso, cualquier fecha de pago) y el "recaudado este mes" (pagos con fecha de pago en el mes en curso,
  cualquier periodo).
- **Contrato**: vínculo entre una locación y uno o más inquilinos, con fecha de inicio y fecha de fin. El
  panel usa el inquilino principal (para la columna de inquilino) y la fecha de fin (para "contratos por
  vencer").
- **Inquilino**: persona arrendataria. El panel usa el nombre del inquilino principal del contrato.
- **Locación**: unidad arrendable dentro de una jerarquía (edificio → piso → local, etc.). El panel usa su
  nombre y su ruta jerárquica corta, y su jerarquía para el filtro por rama.
- **Fecha límite de pago** (derivada, no persistida por defecto): último sábado del mes calendario del
  periodo del recibo; si el mes termina en sábado, ese día.
- **Tramo de antigüedad del atraso** (derivado): clasificación de un recibo moroso según sus días de atraso
  en 1–30, 31–60, 61–90 o más de 90 días.
- **Indicadores de cobranza** (derivados): facturado del periodo, cobrado de recibos del periodo, recaudado
  este mes, tasa de cobranza del periodo y cartera total por cobrar, calculados al momento de abrir el panel.
- **Contrato por vencer** (derivado): contrato vigente cuya fecha de fin cae entre hoy y hoy + N días
  (N ∈ {30, 15, 7}), listado en los tres grupos acumulativos correspondientes.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Un administrador identifica, sin hacer ningún cálculo manual, cuántos recibos están morosos,
  cuántos inquilinos deben y cuánto suman los saldos vencidos, en la primera pantalla tras iniciar sesión.
- **SC-002**: Para cualquier recibo que cumpla las tres condiciones de morosidad, el panel lo lista con días
  de atraso correctos (verificable contra el cálculo manual "hoy menos último sábado del mes del periodo").
- **SC-003**: La suma de los montos de los cuatro tramos de antigüedad coincide exactamente con el monto
  total adeudado vencido mostrado en las tarjetas de resumen.
- **SC-004**: Ningún recibo pagado por completo, anulado, o con fecha límite futura aparece en el listado de
  morosos; ningún recibo vencido aparece en próximos vencimientos.
- **SC-005**: Aplicar cualquier combinación de filtros (tramo de antigüedad, rama de locación) devuelve
  exactamente los recibos morosos que cumplen ambos criterios, y las tarjetas de resumen y el desglose por
  antigüedad se recalculan sobre ese mismo subconjunto; no se modifica ningún dato.
- **SC-005a**: Con un contrato cuya fecha de fin cae dentro de 5 días, ese contrato aparece en los tres
  grupos de "contratos por vencer" (30, 15 y 7); un contrato cuya fecha de fin ya pasó no aparece en ninguno.
- **SC-006**: El panel se muestra completo en menos de 2 segundos con al menos 300 recibos y 100 contratos
  cargados.
- **SC-007**: Los perfiles Master y Administrador ven el mismo panel al iniciar sesión; ningún otro camino de
  navegación queda roto por el cambio de pantalla de inicio.
- **SC-008**: Una revisión del panel confirma que no existe ningún control de escritura (crear/editar/anular)
  sobre pagos, recibos o contratos.

## Assumptions

- **La fecha límite de pago se deriva del periodo del recibo** (último sábado del mes) usando la misma regla
  general de specs/008. Persistir esa fecha en el recibo es una optimización válida a evaluar en la
  planificación si mejora el rendimiento del listado, pero no cambia el comportamiento observable.
- **"Días de atraso" y "días restantes" se miden en días completos** entre la fecha límite de pago y la
  fecha actual del sistema.
- **El "periodo en curso" es el mes calendario de la fecha actual del sistema**, independientemente de si ya
  se generaron recibos para ese mes.
- **El filtro por rama de locación es jerárquico**: incluye la locación elegida y todas sus descendientes.
- **El bloque de próximos vencimientos (US2) y los indicadores (US3) no tienen filtros propios**; solo el
  listado de morosos (US1) es filtrable, según el pedido. Al filtrar, todo el panel de morosidad (tabla +
  tarjetas + desglose por antigüedad) responde al filtro como un conjunto.
- **"Cobrado de recibos del periodo" y "recaudado este mes" son dos indicadores distintos** (decisión del
  usuario): el primero mide qué parte del facturado del mes en curso ya está cobrada (numerador de la tasa de
  cobranza); el segundo mide el efectivo que entró este mes contando cualquier periodo.
- **El bloque "contratos por vencer" solo mira hacia adelante**: contratos vigentes con fecha de fin entre
  hoy y hoy + N; los grupos 30/15/7 son acumulativos; los contratos ya vencidos sin cerrar quedan fuera de
  este bloque.
- **El inquilino mostrado por fila es el inquilino principal del contrato al que pertenece el recibo**; si el
  contrato no tiene inquilino principal, se usa un marcador visible.
- **El panel reutiliza las pantallas existentes** de detalle de recibo, seguimiento de pagos y detalle de
  contrato como destino de todos sus enlaces; no duplica esas vistas.
- **El cambio de pantalla de inicio reemplaza el destino post-login actual**; el resto de la navegación del
  sistema (menú lateral, rutas directas) se conserva.
- **El panel se recalcula en cada carga**; no hay caché ni "última actualización", dado el objetivo de <2 s.

## Dependencies

- Reutiliza los modelos y reglas existentes de Recibo (saldo pendiente nunca negativo, exclusión de
  anulados), Pago, Contrato (inquilino principal, fechas), y Locación (jerarquía y ruta corta).
- Reutiliza la regla de "último sábado del mes" como fecha límite de pago (specs/008).
- Enlaza a las rutas existentes de detalle de recibo, seguimiento de pagos y detalle de contrato.
- Reemplaza el destino de la ruta de inicio post-login actual.
