# Contrato de Interfaz: Rutas web de Configuración de Alerta de Pago y Prorrateo

**Feature**: `008-prorrateo-alertas-pago` | **Date**: 2026-08-20

Esta especificación no agrega rutas HTTP nuevas: extiende la pantalla de configuración general (`specs/004`) y el flujo de generación de recibo (`specs/005`) ya existentes.

## Cambios sobre rutas existentes

| Método | Ruta | Controlador@acción | Cambio introducido por esta feature |
|---|---|---|---|
| GET/PUT | `/configuracion` | `ConfiguracionGeneralController@edit`/`@update` (de `specs/004`) | Se agrega el campo `dias_anticipacion_alerta_pago` (entero, ≥1) al formulario y a la validación |
| GET | `/locaciones/{locacion}/recibos/crear` | `ReciboController@create` (de `specs/005`) | Si `ServicioCalculoProrrateoContrato::calcular()` retorna un resultado no nulo, la vista muestra "X días de Y activos" y precarga `monto_renta` con el monto prorrateado sugerido (editable); si retorna `null` (mes completo), no se muestra ninguna sugerencia y se precarga el `monto_renta` completo del contrato (comportamiento sin cambios de 004/005) |

## Form Requests (validación de entrada)

- `SolicitudActualizarConfiguracionGeneral` (extendida de `specs/004`): agrega `dias_anticipacion_alerta_pago` (`required`, `integer`, `min:1`).

## Comando de consola (sin ruta web)

`php artisan pagos:alertar-fecha-limite` — ejecutado diariamente por el scheduler (`routes/console.php`: `Schedule::command('pagos:alertar-fecha-limite')->daily()`), junto al ya existente `contratos:verificar-vencimientos` (de `specs/004`); no expone ruta HTTP.

## Errores y mensajes (Senior-First)

- El indicador de días activos y el monto prorrateado sugerido MUST mostrarse con tipografía ≥18px y alto contraste, distinguiéndose claramente de un recibo de mes completo (sin esa sección visible).
- El campo `dias_anticipacion_alerta_pago` MUST validar como entero positivo, con mensaje explícito si se ingresa un valor no numérico o menor a 1.
