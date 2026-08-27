# Research: Menú de Registro de Pagos en la Jerarquía de Locales

## Decisión 1: No renombrar los identificadores internos, solo el texto visible

**Decisión**: El controlador (`SeguimientoPagosController`), el nombre de ruta (`pagos.seguimiento.index`),
el directorio de vistas (`resources/views/pagos/seguimiento/`) y el test (`SeguimientoPagosControllerTest`)
conservan su nombre actual. Solo cambian, para el usuario, el `<h2>` de la página ("Registro de Pagos") y el
nuevo ítem de menú que apunta a esa misma ruta.

**Rationale**: La palabra "renombra" en las Assumptions del spec describe la experiencia del usuario (la
pantalla que antes solo existía como "Seguimiento de Pagos" ahora se presenta y se alcanza como "Registro de
Pagos"), no un mandato de renombrar identificadores de código. "Seguimiento de pagos" sigue siendo una
descripción interna igual de válida del propósito técnico de este controlador/vista (rastrear el avance de
pago por locación) que "Registro de Pagos" — ambos nombres describen la misma capacidad desde ángulos
distintos (seguimiento = consultarla, registro = el lugar donde se registran). Renombrar rutas y clases
solo por una etiqueta de menú nueva arriesgaría romper las 6 referencias ya existentes
(`routes/web.php`, 3 vistas, el controlador, el test — ver búsqueda en research previo) sin ningún
beneficio funcional, violando la preferencia del proyecto de evitar refactors no solicitados.

**Alternatives considered**:
- Renombrar todo a `RegistroPagosController`/`pagos.registro.*`: descartado por el riesgo/beneficio
  desfavorable ya explicado; ninguna FR de este spec exige que el nombre interno coincida con la etiqueta
  visible (a diferencia de, por ejemplo, `recibos.registroMasivo.*`, que sí nombra su ruta según su
  pantalla porque nació con ese nombre desde el principio).

## Decisión 2: Reutilizar `recibos.registroMasivo.recibosDelPeriodo` para "Registrar Pago"

**Decisión**: El enlace "Registrar Pago" (FR-005) apunta a la ruta ya existente
`recibos.registroMasivo.recibosDelPeriodo` (specs/026), pasando `locacion` y `periodo`. No se crea una ruta
ni un controlador nuevo.

**Rationale**: Esa ruta ya implementa exactamente el comportamiento que piden las Acceptance Scenarios de
User Story 2: si la locación tiene un único recibo del periodo, redirige directo a `recibos.show` (donde ya
vive el formulario "Registrar Pago" de specs/032); si tiene más de uno, muestra
`recibos-del-periodo.blade.php`, una tarjeta por recibo con su estado y total, cada una enlazando a
`recibos.show`. Es el mismo comportamiento que ya usa "Ver Recibos" en "Emitir Recibos" y que ya usa "Ver
Pagos" en esta misma pantalla (specs/032) — construir una segunda ruta con lógica de
redirección-si-hay-uno-solo idéntica sería duplicar código ya probado.

**Alternatives considered**:
- Una ruta nueva `pagos.registrar` con su propia lógica de selección de recibo: descartado — sería una
  reimplementación letra por letra de `recibosDelPeriodo()`, y además dos rutas resolviendo "a qué recibo
  corresponde esto" para la misma locación/periodo (una para ver, otra para pagar) es una fuente de
  divergencia futura si un caso límite se corrige en una y se olvida en la otra.

## Decisión 3: Condición para mostrar "Registrar Pago" — reutilizar `$estadoAgregado`

**Decisión**: El botón "Registrar Pago" se muestra cuando `$estadoAgregado` es `'sin_pagos'` o `'parcial'`
(oculto en `'pagado'` y `'sin_recibos'`). No se agrega ningún cálculo nuevo al controlador.

**Rationale**: `SeguimientoPagosController::datosDelPeriodo()` (specs/032) ya excluye los recibos anulados
al construir `$recibosPorLocacion`, y ya reduce el estado de *todos* los recibos vigentes de la locación en
el periodo a un único `estadoAgregado`. Esto cubre exactamente los 3 edge cases del spec sin código
adicional:
- Sin ningún recibo emitido → `'sin_recibos'` → sin botón (correcto, FR-005 no aplica).
- Único recibo anulado → no cuenta como vigente → `'sin_recibos'` → sin botón (correcto, edge case 2).
- Varios recibos vigentes, unos pagados y otros no → `$montoPagado < $montoTotal` agregado → `'parcial'` →
  con botón (correcto, edge case 3: "basta con que uno tenga saldo pendiente").

**Alternatives considered**: Calcular una bandera nueva `tieneSaldoPendientePorLocacion` en el controlador —
descartado por redundante: `$estadoAgregado` ya es exactamente esa bandera, con un valor adicional
(`'sin_recibos'`) que también hace falta distinguir.

## Decisión 4: Ícono del nuevo ítem de menú

**Decisión**: `bi-cash-coin`.

**Rationale**: Ya es el ícono usado en esta misma pantalla (`x-estado-vacio icono="bi-cash-coin"` en
`pagos/seguimiento/index.blade.php`, specs/032) para representar el concepto de pago/monto. El Principio VI
exige el mismo ícono para el mismo concepto en toda la aplicación — reutilizarlo en el ítem de menú que
lleva a esa misma pantalla es la aplicación directa de esa regla, no una elección nueva.

**Alternatives considered**: `bi-wallet2`, `bi-cash-stack` — ninguno de los dos aparece ya en el proyecto
para este concepto; se descartan para no introducir una segunda variante del mismo significado.
