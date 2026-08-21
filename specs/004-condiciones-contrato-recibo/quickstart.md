# Quickstart: Condiciones del Contrato y Costos de Referencia para Recibos

**Feature**: `004-condiciones-contrato-recibo` | **Date**: 2026-08-20

Guía de validación end-to-end. Ver `data-model.md` y `contracts/rutas-condiciones-contrato-recibo.md` para el detalle técnico, y `tasks.md` para las tareas de construcción.

## Prerrequisitos

- Migraciones de `specs/001-003` ya ejecutadas.
- Migraciones de esta feature ejecutadas (`contratos` alterada, `configuracion_general`, `recibos`).
- Fila `id = 1` de `configuracion_general` presente (seeder/migración de datos) con un correo administrativo válido.
- Usuario autenticado.

## Escenario 1 — Registro de costo de renta y costos fijos (US1)

1. Crear o editar un contrato ingresando renta "S/ 1500.00", agua "S/ 50.00", luz "S/ 80.00", pasadizo "S/ 30.00", seguridad "S/ 40.00".
2. **Resultado esperado**: el contrato se guarda con los 5 valores como campos individuales.
3. Repetir dejando "pasadizo" vacío.
4. **Resultado esperado**: el sistema acepta el registro asignando "S/ 0.00" a "pasadizo" sin bloquear el guardado.

## Escenario 2 — Notificación de vencimiento por correo (US2)

1. Configurar `correo_notificaciones_vencimiento` en `/configuracion`.
2. Registrar un contrato activo cuya `fecha_fin` esté a exactamente 30 días de la fecha actual.
3. Ejecutar `php artisan contratos:verificar-vencimientos`.
4. **Resultado esperado** (usar `Mail::fake()` en la prueba automatizada): se envía un correo a la dirección configurada con locación, inquilino y fecha de vencimiento; `notificado_30_dias_en` queda con marca de tiempo.
5. Ejecutar el comando de nuevo el mismo día.
6. **Resultado esperado**: no se reenvía el hito de 30 días (sin duplicados).
7. Avanzar la fecha simulada hasta el hito de 15 días y ejecutar el comando.
8. **Resultado esperado**: se envía una nueva notificación distinta a la de 30 días.

## Escenario 3 — Generación de recibo con montos editables (US3)

1. Sobre un contrato con renta "S/ 1500.00" y luz "S/ 80.00", ir a `/contratos/{contrato}/recibos/crear`.
2. **Resultado esperado**: el formulario se precarga con "S/ 1500.00" en renta y "S/ 80.00" en luz, ambos editables.
3. Editar renta a "S/ 1450.00" y confirmar la emisión.
4. **Resultado esperado**: el recibo se guarda con "S/ 1450.00"; el contrato conserva "S/ 1500.00" sin cambios.
5. Consultar `/contratos/{contrato}/recibos` (historial).
6. **Resultado esperado**: se muestran los montos efectivamente cobrados de cada recibo, no los valores de referencia del contrato, con tipografía ≥18px.

## Escenario 4 — Edición del contrato después de emitidos recibos (Edge Case)

1. Con al menos un recibo ya emitido, editar el costo de renta del contrato a un nuevo valor.
2. **Resultado esperado**: los recibos ya emitidos conservan sus montos originales sin alteración.

## Escenario 5 — Corrección de fecha de fin tras notificación enviada (Edge Case)

1. Con un contrato que ya tiene `notificado_30_dias_en` marcado, editar su `fecha_fin` (renovación).
2. **Resultado esperado**: los tres hitos (`notificado_30_dias_en`, `notificado_15_dias_en`, `notificado_7_dias_en`) se reinician a `null`.

## Escenario 6 — Contrato con fecha de fin ya dentro de varios hitos al crearse (Edge Case)

1. Crear un contrato con `fecha_fin` a solo 10 días de la fecha actual.
2. Ejecutar `php artisan contratos:verificar-vencimientos`.
3. **Resultado esperado**: se envían en la misma ejecución las notificaciones de los hitos de 30 y 15 días (ya alcanzados y no enviados), sin esperar a que se cumplan individualmente en el calendario.

## Validación automatizada (referencia)

```bash
php artisan test --filter=Recibo
php artisan test --filter=ConfiguracionGeneral
php artisan test --filter=VerificarVencimientosContratos
```

**Cobertura esperada** (Principio IV): modelo `Contrato` (nuevos costos, reinicio de hitos), `Recibo` (precarga, independencia post-emisión), `ConfiguracionGeneral` (singleton `actual()`), `ServicioNotificacionVencimientoContrato` (hitos escalonados, no-duplicación, `Mail::fake()`), `ReciboController`/`ConfiguracionGeneralController` (happy path, validación 422).
