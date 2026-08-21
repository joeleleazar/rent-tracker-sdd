# Research: Fecha Límite de Pago Mensual, Alertas y Prorrateo por Días Activos

**Feature**: `008-prorrateo-alertas-pago` | **Date**: 2026-08-20

## 1. Cálculo del último sábado del mes

**Decision**: `ServicioCalculoFechaLimitePago::calcular(Carbon $mes): Carbon` — se obtiene el último día del mes (`$mes->copy()->endOfMonth()`) y, si ese día ya es sábado, se retorna tal cual (Edge Case "mes que termina en sábado"); si no, se retrocede con `->previous(Carbon::SATURDAY)`.

**Rationale**: `Carbon::previous()` no incluye el día de referencia si este ya cumple la condición (por eso se necesita la verificación explícita `isSaturday()` antes de invocarlo), un detalle fácil de omitir que generaría el error exacto que describe el Edge Case de la especificación. Aislar esta lógica en un Service puro (sin dependencias de base de datos) permite testear los 7 casos posibles (mes terminando cada día de la semana) de forma exhaustiva y rápida con Pest.

**Alternatives considered**:
- Calcular la fecha límite con una expresión SQL de PostgreSQL (`date_trunc` + aritmética de intervalos): rechazado, la lógica de fechas de negocio se mantiene en PHP/Carbon consistente con el resto del proyecto (Principio I: ORM/aplicación en vez de SQL crudo salvo justificación).

## 2. Umbral de alerta: "exactamente" vs. "en o después de" los días de anticipación

**Decision**: `ServicioAlertaFechaLimitePago` envía la alerta cuando `hoy >= (fecha_limite - dias_anticipacion_alerta_pago dias)` Y `alerta_pago_mes_enviada_en` no corresponde ya al mes en curso (es `null` o de un mes anterior), en vez de exigir una igualdad exacta de días restantes.

**Rationale**: La especificación describe el disparador como "cuando falten exactamente los días configurados" (FR-003), pero el propio Edge Case "Anticipación configurada mayor a los días restantes del mes" exige que, si la ventana exacta ya pasó o nunca se alcanzó con precisión de un solo día (ej. el comando no corrió el día exacto por mantenimiento del servidor, o la anticipación configurada excede los días disponibles), la alerta se envíe "en la primera verificación periódica disponible". Una comparación de igualdad estricta (`dias_restantes === dias_anticipacion`) fallaría silenciosamente ante cualquier día de cron perdido (el mismo riesgo que ya se evitó en `specs/004/research.md` §3-4 para los hitos de vencimiento de contrato, resuelto allí con un enfoque de "hitos alcanzados y no enviados"). Usar `>=` combinado con la marca de "ya enviada este mes" logra el mismo resultado que "exactamente" en el caso normal (el primer día en que la condición se cumple es, en la práctica, el día exacto de anticipación) y además cubre el Edge Case sin lógica adicional.

**Alternatives considered**:
- Comparación de igualdad estricta con un mecanismo de "recuperación" para días perdidos aparte: rechazado, duplicaría lógica que `>=` ya resuelve de forma más simple.

## 3. Reinicio de `alerta_pago_mes_enviada_en` al iniciar un nuevo mes

**Decision**: No se ejecuta ningún "reinicio" activo (no hay un job que ponga la columna en `null` el día 1 de cada mes); en su lugar, `ServicioAlertaFechaLimitePago` compara el mes/año de `alerta_pago_mes_enviada_en` contra el mes/año actual en cada ejecución (`$configuracion->alerta_pago_mes_enviada_en?->isSameMonth(now()) !== true`), tratando una marca de un mes anterior como equivalente a "no enviada este mes".

**Rationale**: Es más simple y menos propenso a errores no depender de una tarea programada adicional exclusivamente para "limpiar" el campo; comparar el mes de la marca de tiempo ya almacenada logra el mismo efecto de forma determinista y sin una ejecución de mantenimiento adicional que pudiera fallar u omitirse.

**Alternatives considered**:
- Job mensual (`Schedule::call(...)->monthlyOn(1, '00:00')`) que reinicia la columna a `null`: rechazado, añade un punto de fallo adicional (si el job no corre el día 1, el sistema quedaría bloqueado sin alertas todo el mes) para un resultado idéntico al de comparar el mes directamente.

## 4. Prorrateo de renta: reutilización de `contratoActivoEnPeriodo()` (specs/005) y alcance únicamente de `monto_renta`

**Decision**: `ServicioCalculoProrrateoContrato::calcular(Contrato $contrato, Carbon $periodo): ?array` retorna `null` si el contrato estuvo activo el mes completo (su `fecha_inicio` es `<=` el primer día del mes Y su `fecha_fin` es `>=` el último día del mes), o un arreglo `['dias_activos' => int, 'dias_totales' => int, 'monto_renta_sugerido' => float]` en caso contrario, calculado como `max(fecha_inicio, inicio_de_mes)` hasta `min(fecha_fin, fin_de_mes)` inclusive. `ReciboController@create` (de `specs/005`) invoca este Service tras resolver el contrato activo vía `Locacion::contratoActivoEnPeriodo()` (mismo helper), y si el resultado no es `null`, precarga `monto_renta` con `monto_renta_sugerido` en vez del `Contrato.monto_renta` completo; los costos fijos (agua/luz/seguridad/pasadizo) se precargan siempre con su valor de referencia completo, sin prorratear (A-004).

**Rationale**: Reutilizar `contratoActivoEnPeriodo()` (introducido en `specs/005/research.md` §2) evita duplicar la lógica de resolución de "qué contrato aplica a este periodo", que ya es un requisito compartido entre generar el recibo (FR-008 de 005) y sugerir el prorrateo (FR-006/007 de esta especificación). Limitar el prorrateo a `monto_renta` es una decisión explícita de la propia especificación (A-004: "su naturaleza (consumo o servicio fijo mensual) no necesariamente escala de forma lineal con los días"), por lo que el Service no toca los demás campos del formulario de recibo.

**Alternatives considered**:
- Prorratear también los costos fijos de forma automática: rechazado explícitamente por la Asunción A-004 de la especificación.
- Persistir el prorrateo directamente en `Contrato` en vez de calcularlo en cada generación de recibo: rechazado, el prorrateo es específico de cada periodo/recibo (un contrato prorratea su mes de inicio una sola vez, en su mes de fin otra), no es un atributo del contrato en sí (A-003: "el contrato en sí no se modifica por este cálculo").

## 5. Envío de correo de alerta: reutilización del patrón síncrono de `specs/004`

**Decision**: `ServicioAlertaFechaLimitePago` envía el `Mailable AlertaFechaLimitePago` de forma síncrona (sin cola), igual que `ServicioNotificacionVencimientoContrato` de `specs/004`, ejecutado por el comando `pagos:alertar-fecha-limite` programado diariamente (`Schedule::command(...)->daily()`) en `routes/console.php`, junto al ya existente `contratos:verificar-vencimientos`.

**Rationale**: Consistencia directa con la decisión ya tomada y justificada en `specs/004-condiciones-contrato-recibo/research.md` §3 (ausencia de workers de cola persistentes en shared hosting); no hay ninguna razón específica de esta feature para desviarse de ese patrón ya establecido.

**Alternatives considered**: Ninguna nueva — se reutiliza la decisión de 004.

## 6. Ausencia de roles/permisos

**Decision**: Consistente con `specs/001-007`, solo `middleware(['auth'])`.

**Rationale**: Ver `specs/004/research.md` §5.

**Alternatives considered**: Ninguna nueva.

## 7. Framework de pruebas

**Decision**: Pest, consistente con `specs/001-007`.

**Rationale**: Ya adoptado por el proyecto.

**Alternatives considered**: Ninguna — decisión ya tomada a nivel de proyecto.
