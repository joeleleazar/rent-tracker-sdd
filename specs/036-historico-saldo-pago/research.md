# Research: Saldo Histórico en el Comprobante de Pago

## Decisión 1: Calcular al vuelo con un filtro `id <=`, no persistir un snapshot

**Decisión**: `Pago::montoAcumuladoHastaEstePago()` suma `$this->recibo->pagos->where('id', '<=', $this->id)`
sobre la colección de pagos ya cargada por el controlador — sin columna nueva, sin migración, sin escritura
en ningún momento (ni al registrar el pago, ni al mostrar el comprobante).

**Rationale**: FR-003 exige que el acumulado/saldo histórico de un pago se recalcule automáticamente si se
edita o elimina un pago anterior en la misma secuencia. Un valor **calculado siempre desde los pagos que
existen ahora** (filtrados por `id <=`) cumple FR-003 por construcción: si un pago anterior se edita, la
suma cambia sola en la siguiente consulta; si se elimina, deja de existir para sumarse. Un valor
**persistido** (una columna `monto_acumulado_al_momento` en `pagos`) exigiría, en cambio, lógica explícita
de recalculación en cascada cada vez que se edita/elimina cualquier pago anterior en la secuencia —
complejidad y superficie de bugs evitables sin ningún beneficio: no hay ningún caso de uso en el spec que
necesite "congelar" el valor independientemente de los pagos que existan (a diferencia de, por ejemplo, un
precio histórico en una factura que sí debe sobrevivir a un cambio de tarifario).

**Alternatives considered**:
- Columna persistida `monto_acumulado_historico`/`saldo_pendiente_historico` en `pagos`, escrita al
  registrar/editar/eliminar cualquier pago del recibo (recalculando en cascada los pagos posteriores):
  descartada por la razón anterior — mismo resultado observable, con una migración, un servicio de
  recalculación en cascada y un riesgo de desincronización que el cálculo al vuelo no tiene.
- Guardar el acumulado en el momento exacto de creación del pago y nunca recalcularlo después (verdadero
  "congelado"): descartada — contradice explícitamente FR-003 y el edge case ya resuelto en el spec
  ("si se elimina/edita un pago anterior, el histórico de los posteriores debe reflejarlo").

## Decisión 2: Orden por `id` (orden de registro), no por `fecha_pago`

**Decisión**: "Hasta ese pago inclusive" se determina comparando `id` (`id <= $this->id`), no `fecha_pago`.

**Rationale**: Resuelve directamente la Clarification del spec — el usuario eligió "orden de registro en el
sistema" sobre "fecha del pago" para que una `fecha_pago` retroactiva (el administrador registra hoy un
pago que en realidad ocurrió antes de otro ya cargado) nunca reordene ni cambie el histórico de un
comprobante de otro pago ya impreso/firmado. Como los pagos se crean con autoincremento estrictamente en
orden de inserción, `id` es un proxy exacto y ya disponible del "orden de registro" sin necesitar un campo
de timestamp adicional (`created_at` serviría igual de bien, pero `id` es más simple de comparar y ya se usa
como identidad del modelo en el resto del código, ej. rutas `pagos.comprobante`/`pagos.evidencia.*`).

**Alternatives considered**: Comparar por `created_at`: funcionalmente equivalente a `id` en este esquema
(sin backfills ni reordenamientos manuales de historial), pero `id` evita cualquier ambigüedad de empate en
timestamps idénticos (dos pagos registrados en la misma solicitud/segundo).

## Decisión 3: El total del recibo sigue siendo el actual, no se historiza

**Decisión**: `saldoPendienteHastaEstePago()` usa `$this->recibo->total()` (el total **actual** del recibo),
no un total histórico.

**Rationale**: Documentado ya como Assumption del spec — el total de un recibo no cambia en el uso normal
del sistema una vez que empieza a recibir pagos (specs/007/019/024 no ofrecen una vía de edición de
conceptos/monto de renta post-emisión pensada para convivir con pagos ya registrados). Historizar también el
total exigiría una segunda dimensión de "snapshot" sin que el spec haya identificado un caso de uso real que
lo necesite — se documenta como límite explícito de esta feature, no como un vacío accidental.

**Alternatives considered**: Historizar también el total: descartada por la razón anterior — alcance no
solicitado ni justificado por ningún escenario del spec.
