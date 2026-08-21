# Quickstart: Fecha Límite de Pago Mensual, Alertas y Prorrateo por Días Activos

**Feature**: `008-prorrateo-alertas-pago` | **Date**: 2026-08-20

Guía de validación end-to-end. Ver `data-model.md` y `contracts/rutas-prorrateo-alertas-pago.md` para el detalle técnico, y `tasks.md` para las tareas de construcción.

## Prerrequisitos

- Migraciones de `specs/001-007` ya ejecutadas.
- Migraciones de esta feature ejecutadas (`configuracion_general` y `recibos` alteradas).
- `correo_notificaciones_vencimiento` configurado en `/configuracion` (reutilizado como destinatario de esta alerta, ver spec A-001).
- Usuario autenticado.

## Escenario 1 — Alerta configurable de fecha límite de pago (US1)

1. Configurar `dias_anticipacion_alerta_pago = 5` en `/configuracion`.
2. Ubicarse (fecha simulada en la prueba) a exactamente 5 días del último sábado del mes en curso.
3. Ejecutar `php artisan pagos:alertar-fecha-limite`.
4. **Resultado esperado** (`Mail::fake()`): se envía una alerta al Administrador indicando la fecha límite próxima; `alerta_pago_mes_enviada_en` queda marcada.
5. Cambiar la anticipación a 10 días y ejecutar el comando en la siguiente verificación.
6. **Resultado esperado**: la alerta se genera respetando el nuevo valor (10 días).
7. Ejecutar el comando de nuevo dentro del mismo mes tras ya haberse enviado la alerta.
8. **Resultado esperado**: no se reenvía una alerta duplicada para el mismo mes.

## Escenario 2 — Prorrateo al iniciar un contrato a mitad de mes (US2)

1. Registrar un contrato con `fecha_inicio` "15 de agosto de 2026" y `monto_renta` "S/ 1550.00" (agosto tiene 31 días).
2. Iniciar la generación del recibo del periodo "Agosto 2026" para ese contrato.
3. **Resultado esperado**: se muestra "17 días de 31" activos y se precarga `monto_renta` con "S/ 850.00" (1550 ÷ 31 × 17), editable.
4. Repetir con un contrato cuya `fecha_inicio` sea el primer día del mes.
5. **Resultado esperado**: no se muestra ninguna sugerencia de prorrateo; se precarga el `monto_renta` completo del contrato.

## Escenario 3 — Prorrateo al finalizar un contrato a mitad de mes (US3)

1. Registrar un contrato con `fecha_fin` "10 de agosto de 2026" y `monto_renta` "S/ 1550.00".
2. Iniciar la generación del recibo del periodo "Agosto 2026" para ese contrato.
3. **Resultado esperado**: se muestra "10 días de 31" activos y se precarga `monto_renta` con "S/ 500.00" (1550 ÷ 31 × 10), editable.

## Escenario 4 — Contrato que inicia y finaliza en el mismo mes (Edge Case)

1. Registrar un contrato con `fecha_inicio` y `fecha_fin` ambas dentro de "Agosto 2026".
2. Generar el recibo de ese periodo.
3. **Resultado esperado**: los días activos se calculan como la diferencia entre ambas fechas (inclusive), mostrados como sugerencia única.

## Escenario 5 — Mes que termina en sábado (Edge Case)

1. Simular un mes cuyo último día calendario sea sábado.
2. Ejecutar `ServicioCalculoFechaLimitePago::calcular()` para ese mes.
3. **Resultado esperado**: la fecha límite calculada es ese mismo día (no el sábado anterior).

## Escenario 6 — Anticipación mayor a los días restantes del mes (Edge Case)

1. Configurar `dias_anticipacion_alerta_pago` con un valor mayor a los días disponibles en el mes en curso.
2. Ejecutar `php artisan pagos:alertar-fecha-limite` en la primera verificación disponible del mes.
3. **Resultado esperado**: la alerta se envía igualmente en esa primera ejecución, indicando que la fecha límite ya está próxima.

## Escenario 7 — Contrato vigente todo el mes (Edge Case)

1. Con un contrato activo desde antes del inicio del mes y vigente después de su fin, generar el recibo de ese periodo.
2. **Resultado esperado**: no se muestra ninguna sugerencia de prorrateo.

## Validación automatizada (referencia)

```bash
php artisan test --filter=ServicioCalculoFechaLimitePago
php artisan test --filter=ServicioCalculoProrrateoContrato
php artisan test --filter=AlertarFechaLimitePago
```

**Cobertura esperada** (Principio IV): `ServicioCalculoFechaLimitePago` (último sábado, mes que termina en sábado), `ServicioCalculoProrrateoContrato` (inicio/fin a mitad de mes, mes completo, inicio y fin en el mismo mes), `ServicioAlertaFechaLimitePago` (no-duplicación mensual, anticipación mayor a los días del mes, `Mail::fake()`), `ReciboController@create` (sugerencia de prorrateo visible cuando aplica).
