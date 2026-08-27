# Contrato: campo `nombre_propietario` en Configuración General

## Validación (`SolicitudActualizarConfiguracionGeneral`)

| Regla | Valor |
|---|---|
| Campo | `nombre_propietario` |
| Tipo | `string` |
| Obligatorio | No (`nullable`) — a diferencia de los 3 campos ya existentes, que sí son `required` |
| Longitud máxima | `255` |
| Mensaje de error (`max`) | "El nombre del propietario no puede superar los 255 caracteres." |

**Por qué `nullable`**: instalaciones existentes de la aplicación no tienen este valor configurado — el
formulario debe poder guardarse (incluso dejando este campo vacío) sin forzar a completar un dato nuevo de
forma retroactiva. Ver data-model.md y spec.md FR-005a para el comportamiento del comprobante cuando el
valor es `null`.

## Persistencia (`ConfiguracionGeneral`)

- Clave en `configuracion_general.clave`: `nombre_propietario`.
- Valor por defecto (`valoresPorDefecto()`) cuando la clave todavía no existe en la tabla: `null`.
- Se guarda con el mismo mecanismo `save()`/`updateOrInsert` ya existente — ningún cambio de comportamiento
  de persistencia más allá de agregar la clave a `$fillable` y a `valoresPorDefecto()`.

## Lectura desde el comprobante

`ConfiguracionGeneral::actual()->nombre_propietario` — mismo patrón de lectura ya usado por otras vistas
que consultan la configuración general (ej. la tarifa de luz en el formulario de lecturas). El controlador
del comprobante (`ReciboController::comprobante()`) pasa este valor a la vista junto con el recibo.
