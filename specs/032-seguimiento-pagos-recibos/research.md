# Research: Registro y Seguimiento de Pagos de Recibos

## Decisión 1 — Tabla `pagos` nueva, independiente de `recibos`, un pago por fila

**Decisión**: Nueva tabla `pagos` (`recibo_id`, `monto` `decimal(12,2)`, `fecha_pago` `date`,
`registrado_por_id` FK a `users`, timestamps). Un recibo puede tener cero, uno o varios pagos; el estado de
avance se deriva sumando `pagos.monto` de un recibo, nunca de un único campo agregado en `recibos`.

**Rationale**: FR-001/FR-002 piden explícitamente poder registrar varios pagos parciales contra un mismo
recibo, cada uno con su propio monto y fecha — eso exige una tabla propia con relación uno-a-muchos, igual
que `recibo_conceptos` ya separó "cada concepto cobrado" de una fila ancha en `recibos` (specs/024). Guardar
solo un acumulado en `recibos` perdería el detalle de "quién pagó qué y cuándo" que FR-012 exige conservar.

**Alternativas consideradas**: Agregar un campo `monto_pagado` directo en `recibos`, actualizado con cada
pago — descartada porque no deja rastro de los pagos individuales (FR-012), y porque corregir un pago
puntual (US3) sería indistinguible de registrar uno nuevo.

## Decisión 2 — `registrado_por_id` con `nullOnDelete()`, no `restrictOnDelete()`

**Decisión**: La clave foránea `pagos.registrado_por_id → users.id` usa `nullOnDelete()` (columna
nullable), no `restrictOnDelete()`.

**Rationale**: Ya existe un precedente directo en este proyecto para esta misma disyuntiva:
`recibo_conceptos.concepto_gasto_fijo_id` usaba `restrictOnDelete()` y bloqueaba con error 500 una
operación legítima (specs/026, research.md "Hallazgo durante la implementación") — la fila histórica debía
conservarse aunque la referencia dejara de existir, solo perdiendo el nombre. Un pago es, con más razón,
un registro financiero que debe sobrevivir aunque la cuenta de usuario que lo registró se elimine más
adelante; `nullOnDelete()` evita repetir ese mismo error de diseño desde el principio.

**Alternativas consideradas**: `restrictOnDelete()` (impediría eliminar un usuario con pagos a su nombre,
un acoplamiento que no aporta valor real) y `cascadeOnDelete()` (borraría silenciosamente el historial de
pagos al eliminar un usuario — inaceptable para un registro financiero, FR-012).

## Decisión 3 — El estado Pendiente/Pagado de `recibos.estado` se recalcula, no se elimina la columna

**Decisión**: `recibos.estado` (ya existente) se sigue usando tal cual — mismo `enum` de 3 valores, mismas
columnas `fecha_pago`/`fecha_anulacion`. Lo único que cambia es quién lo escribe: en vez de un
administrador eligiéndolo a mano entre Pendiente y Pagado, `ServicioGestionPagosRecibo` lo recalcula cada
vez que se registra, edita o elimina un pago (sin pagos o suma parcial → `pendiente`; suma = total →
`pagado`, fijando `fecha_pago = now()`; si vuelve a caer por debajo del total tras editar/eliminar un pago,
vuelve a `pendiente` y `fecha_pago` se limpia). La transición hacia/desde `anulado` no cambia — sigue
siendo manual y exige la misma confirmación explícita ya existente (FR-006, ya resuelto en Clarifications).

**Rationale**: Todo el resto del sistema ya consulta `recibos.estado` directamente (`scopeVigente()`,
badges de `recibos-del-periodo.blade.php`, el árbol de `RegistroMasivoRecibosController`, el propio
comprobante) — mantener la misma columna, solo cambiando quién la escribe, evita tocar ninguno de esos
puntos de lectura ya existentes. Convertirlo en un valor calculado en el momento (accessor, sin persistir)
en cambio obligaría a reescribir cada consulta que hoy filtra por `estado` en SQL.

**Alternativas consideradas**: Un accessor no persistido (`getEstadoAttribute()` calculado en cada
lectura) — descartada porque `scopeVigente()` y las consultas de `RegistroMasivoRecibosController` filtran
`estado` directamente en SQL (`where('estado', '!=', 'anulado')`); un accessor en PHP no participa en esas
consultas sin reescribirlas todas.

**Hallazgo durante la implementación**: los recibos ya marcados `pagado` bajo el sistema manual anterior
(antes de esta feature) no tenían ninguna fila en `pagos` — `montoPagado()` los mostraba en `S/ 0.00` pese
a que `recibos.estado` seguía diciendo "pagado", una contradicción visible en `recibos/show.blade.php` (el
badge de estado decía "Pagado" mientras la sección de Pagos decía "todavía no se registró ningún pago").
Se agregó una migración de backfill (`2026_08_26_130000_backfill_pagos_desde_recibos_pagados`) que
reconstruye, para cada recibo `pagado` sin pagos, un pago histórico por su total ya calculado, fechado en
su `fecha_pago` original — así el estado ya calculado queda respaldado por evidencia real en vez de
revertir silenciosamente un recibo que el negocio ya consideraba pagado.

## Decisión 4 — `ServicioCambioEstadoRecibo` se refactoriza a `anular()`/`reactivar()`, sin `cambiar()` genérico

**Decisión**: El método público `cambiar(Recibo $recibo, string $nuevoEstado, bool $confirmado)` se
reemplaza por dos métodos explícitos: `anular(Recibo $recibo, bool $confirmado)` y
`reactivar(Recibo $recibo, bool $confirmado)`. `reactivar()` ya no recibe a qué estado volver — al salir de
"anulado" simplemente vuelve a calcular Pendiente/Pagado a partir de los pagos que el recibo ya tenía
(nunca se borraron al anularlo, ver Decisión 5), delegando ese cálculo a la misma lógica de
`ServicioGestionPagosRecibo` (Decisión 3).

**Rationale**: FR-006 retira la posibilidad de elegir directamente entre Pendiente y Pagado — el método
genérico `cambiar()` con un `$nuevoEstado` de cadena libre ya no tiene sentido cuando solo dos transiciones
reales existen (anular / reactivar) y ninguna de las dos permite elegir el estado resultante de
Pendiente/Pagado a mano. Separar en dos métodos con nombre explícito también deja el código más claro que
seguir aceptando strings arbitrarios ahora restringidos a un subconjunto.

**Alternativas consideradas**: Mantener `cambiar()` aceptando solo `'anulado'` como valor válido de
`$nuevoEstado` y agregar un caso especial para "volver de anulado" — descartada por dejar una firma de
método que sigue pareciendo más general de lo que realmente es, invitando a un futuro mal uso.

## Decisión 5 — Anular un recibo no borra ni desactiva sus pagos ya registrados

**Decisión**: Anular un recibo (o dejarlo pendiente/pagado antes de anularlo) no toca la tabla `pagos` en
absoluto — sus filas se conservan intactas. Al reactivar el recibo, `ServicioGestionPagosRecibo` vuelve a
sumarlas para recalcular Pendiente/Pagado (Decisión 4).

**Rationale**: Es exactamente lo que pide el Edge Case de spec.md ("los pagos ya registrados se conservan
como historial... el recibo deja de contarse en el seguimiento de avance de pago") y FR-011 (excluir
recibos anulados del cálculo/visualización, sin mencionar borrar nada). Conservar los pagos intactos
también hace que Decisión 4 (recalcular al reactivar) sea trivial y correcta sin lógica adicional.

**Alternativas consideradas**: Marcar los pagos como "inactivos" al anular el recibo — descartada por ser
complejidad innecesaria: FR-011 ya resuelve la exclusión filtrando por `recibos.estado != 'anulado'` en las
consultas de seguimiento, sin necesitar ningún flag adicional en `pagos`.

## Decisión 6 — La nueva pantalla reutiliza `ServicioConstruccionArbolLocaciones` sin cambios

**Decisión**: `SeguimientoPagosController::index()` sigue el mismo patrón que
`RegistroMasivoRecibosController::index()`/`datosDelPeriodo()`: llama a
`ServicioConstruccionArbolLocaciones::construir()` tal cual para obtener el árbol completo de locaciones, y
arma por separado (con el mismo criterio anti-N+1 de una consulta agrupada en memoria) el avance de pago
por locación para el período elegido.

**Rationale**: El pedido original es explícito ("una nueva vista que copie la estructura de jerarquía de
locales que se ve en emisión de recibos") y ese servicio ya construye el árbol completo sin ningún
acoplamiento a recibos o pagos — es el mismo árbol que usan tanto `recibos/registro-masivo` como
`lecturas/registro-masivo` hoy. Reescribirlo sería duplicar lógica ya probada sin ninguna necesidad.

**Alternativas consideradas**: Ninguna — es la aplicación directa del pedido y del patrón ya establecido en
el proyecto (specs/013, specs/015, specs/023).

## Decisión 7 — Avance de pago por locación se agrega sumando sus recibos vigentes del período

**Decisión**: Cuando una locación tiene más de un recibo vigente en el período (caso ya posible y cubierto
por un Edge Case de spec.md), la fila de la locación en el árbol muestra el avance agregado (suma de
pagado / suma de total de todos sus recibos vigentes de ese período) y un enlace "Ver Pagos" que reutiliza
el mismo patrón de desambiguación que ya existe para "Ver Recibos"
(`RegistroMasivoRecibosController::recibosDelPeriodo()`): si hay un solo recibo vigente, va directo al
detalle de ese recibo (donde vive el registro de pagos, Decisión 8); si hay más de uno, lista los recibos
del período para elegir cuál abrir.

**Rationale**: Mantiene el árbol principal legible con una fila por locación (igual que hoy "Total del
Periodo" ya agrega varios recibos en una sola cifra) sin perder, para quien lo necesite, el detalle por
recibo individual que pide el Edge Case correspondiente de spec.md — reutilizando una página y un patrón de
navegación que el usuario ya conoce de la pantalla de emisión, en vez de crear una pantalla de desambiguación
paralela solo para pagos.

**Alternativas consideradas**: Mostrar una fila por recibo en vez de una fila por locación cuando hay
varios — descartada por romper la correspondencia 1 a 1 entre fila y locación que el árbol jerárquico
mantiene en toda la aplicación (specs/013), y por ser innecesaria dado que la página de desambiguación ya
resuelve el mismo problema para "Ver Recibos".

## Decisión 8 — El registro de pagos vive en la página de detalle del recibo (`recibos/show.blade.php`)

**Decisión**: El formulario para registrar un pago nuevo, la lista de pagos ya registrados de un recibo, y
las acciones de editar/eliminar un pago puntual, se agregan a la página ya existente
`locaciones/recibos/show.blade.php` — no se crea una página separada de "detalle de pagos". El toggle
manual Pendiente/Pagado se retira de esa misma página (FR-006); las acciones de Anular/Revertir se
mantienen.

**Rationale**: Es la página donde ya vive el "Estado de Pago" de un recibo (specs/012) — extenderla es
menos disruptivo que crear una página nueva, y cumple FR-009 ("acceder, desde la nueva vista, al detalle de
pagos de un recibo específico") enlazando directamente ahí, igual que "Ver Recibos" ya enlaza a
`recibos.show` cuando hay un único recibo (Decisión 7).

**Alternativas consideradas**: Una página dedicada `recibos/{recibo}/pagos` separada del detalle del
recibo — descartada por fragmentar en dos pantallas información que hoy conviven en una sola (el resto del
detalle del recibo y su estado de pago), sin ningún requisito que exija esa separación.
