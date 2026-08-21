# Contrato de Interfaz: Rutas web de Costos de Contrato, Recibos y Configuración General

**Feature**: `004-condiciones-contrato-recibo` | **Date**: 2026-08-20

Aplicación monolítica Laravel con vistas Blade server-rendered, consistente con `specs/001-003`. Rutas protegidas por `middleware(['auth'])` (ver `research.md` §5 sobre la ausencia de roles). Todas las rutas mutantes exigen CSRF.

## Costos fijos del contrato

Los campos `costo_agua`/`costo_luz`/`costo_pasadizo`/`costo_seguridad` se editan como parte del formulario ya existente de `ContratoController@store`/`@update` (`specs/002`); no se agregan rutas nuevas para su registro inicial. Se agrega una acción secundaria para edición rápida de solo costos:

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| PATCH | `/contratos/{contrato}/costos` | `ContratoController@actualizarCostos` | Edición rápida de los 4 costos fijos desde el detalle del contrato, sin tocar fechas/monto_renta/estado | 302 en éxito; 422 si algún costo no es numérico ≥0 |

## Recibos

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| GET | `/contratos/{contrato}/recibos` | `ReciboController@index` | Historial de recibos del contrato, montos efectivamente cobrados (US3) | 200 |
| GET | `/contratos/{contrato}/recibos/crear` | `ReciboController@create` | Formulario de emisión, precargado con `monto_renta`/`costo_*` del contrato (FR-005) | 200, campos editables |
| POST | `/contratos/{contrato}/recibos` | `ReciboController@store` | Emite el recibo con los montos efectivamente confirmados (editados o no) | 302 redirect a `show`; 422 si algún monto no es numérico ≥0 |
| GET | `/recibos/{recibo}` | `ReciboController@show` | Detalle del recibo emitido | 200 |

**Precarga (FR-005/FR-006)**: `ReciboController@create` MUST pasar a la vista los valores actuales de `Contrato.monto_renta`/`costo_agua`/`costo_luz`/`costo_pasadizo`/`costo_seguridad` como valores por defecto de los campos del formulario, todos editables antes de confirmar.

**Independencia post-emisión (FR-007)**: `ReciboController@store` MUST persistir los valores recibidos del formulario (editados o no) en las columnas propias de `Recibo`, sin volver a leer `Contrato` después de guardado; una edición posterior de los costos del contrato no debe alterar los recibos ya emitidos.

## Configuración General

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| GET | `/configuracion` | `ConfiguracionGeneralController@edit` | Formulario de configuración general (correo administrativo) | 200 |
| PUT | `/configuracion` | `ConfiguracionGeneralController@update` | Actualiza `correo_notificaciones_vencimiento` | 302 en éxito; 422 si el correo no es válido |

## Form Requests (validación de entrada)

- `SolicitudGuardarCostosContrato` (`ContratoController@actualizarCostos`): valida `costo_agua`/`costo_luz`/`costo_pasadizo`/`costo_seguridad` como `numeric`, `min:0`, con valor por defecto `0` si se omiten.
- `SolicitudGuardarRecibo` (`ReciboController@store`): valida `monto_renta` (`numeric`, `required`, `min:0`), `monto_agua`/`monto_luz`/`monto_pasadizo`/`monto_seguridad` (`numeric`, `min:0`, por defecto 0), `periodo` (fecha válida, normalizada al día 1 del mes), `fecha_emision` (fecha, por defecto hoy).
- `SolicitudActualizarConfiguracionGeneral` (`ConfiguracionGeneralController@update`): valida `correo_notificaciones_vencimiento` (`required`, `email`).

## Comando de consola (sin ruta web)

`php artisan contratos:verificar-vencimientos` — ejecutado diariamente por el scheduler (`routes/console.php`: `Schedule::command('contratos:verificar-vencimientos')->daily()`); no expone ruta HTTP, ver `data-model.md`/`research.md` §3-4 para su lógica.

## Errores y mensajes (Senior-First)

- Todo error de validación se muestra junto al campo y en un resumen superior persistente, tipografía ≥18px, contraste WCAG AA/AAA (Principio III).
- El botón de emisión de recibo MUST tener la etiqueta explícita "Emitir Recibo" (≥48x48px); el de edición de costos, "Guardar Costos del Contrato".
