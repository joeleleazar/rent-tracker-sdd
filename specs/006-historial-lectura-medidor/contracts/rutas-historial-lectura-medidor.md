# Contrato de Interfaz: Rutas web de Historial de Lectura de Medidor

**Feature**: `006-historial-lectura-medidor` | **Date**: 2026-08-20

Esta especificación no agrega rutas nuevas: reutiliza y refina el comportamiento de las rutas de `LecturaMedidorController` ya definidas en `specs/005-lecturas-medidor-recibo-periodo/contracts/rutas-lecturas-medidor-recibo-periodo.md`. Se documentan aquí únicamente los cambios de contrato sobre esas mismas rutas.

## Cambios sobre rutas existentes (de `specs/005`)

| Método | Ruta | Controlador@acción | Cambio introducido por esta feature |
|---|---|---|---|
| GET | `/locaciones/{locacion}/lecturas/crear` | `LecturaMedidorController@create` | El formulario ahora precarga `lectura_anterior` con el valor sugerido por `ServicioCalculoConsumoMedidor::sugerirLecturaAnterior()` (editable), o muestra "Sin lectura previa registrada" si no existe periodo anterior (FR-002) |
| POST | `/locaciones/{locacion}/lecturas` | `LecturaMedidorController@store` | El payload ahora incluye `lectura_anterior` explícito (antes derivado implícitamente); `consumo_calculado` se calcula como `lectura_actual - lectura_anterior` del mismo registro |
| GET | `/locaciones/{locacion}/lecturas` | `LecturaMedidorController@index` | Cada fila del historial ahora muestra `lectura_anterior`, `lectura_actual` y, si aplica, un indicador de advertencia cuando `discrepanciaConSiguiente()` es `true` (FR-007) |

## Form Requests (validación de entrada)

- `SolicitudGuardarLecturaMedidor` (de `specs/005`, se extiende): agrega `lectura_anterior` (`numeric`, `nullable`, sin restricción de coincidencia con ningún otro registro), renombra el campo antes llamado `lectura` a `lectura_actual` (`numeric`, `required`, `min:0`).

## Errores y mensajes

- El indicador de discrepancia en el historial (FR-007) MUST ser visible con alto contraste, pero MUST NOT bloquear ninguna acción de guardado ni edición.
- El campo `lectura_anterior` vacío por ausencia de periodo previo MUST mostrar el texto explícito "Sin lectura previa registrada" en vez de un campo en blanco ambiguo (US1, Acceptance Scenario 2).
