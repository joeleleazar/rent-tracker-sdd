# Contrato de Interfaz: `ConfiguracionGeneral` tras el rediseño clave-valor

**Feature**: `018-optimizacion-esquema-postgresql` | **Date**: 2026-08-25

El almacenamiento subyacente cambia de una fila ancha a filas clave-valor (data-model.md), pero la interfaz pública del modelo — usada hoy por `ConfiguracionGeneralController`, `RegistroMasivoLecturasController`, `ServicioGeneracionReciboPeriodo`, `ServicioAlertaFechaLimitePago` y `ServicioNotificacionVencimientoContrato` — DEBE seguir cumpliendo exactamente lo siguiente, sin que ninguno de esos archivos cambie una sola línea:

## Contrato 1 — Lectura

- `ConfiguracionGeneral::actual()` DEBE seguir devolviendo una instancia de `ConfiguracionGeneral` (no un array, no una colección).
- `ConfiguracionGeneral::actual()->tarifa_luz_por_unidad` DEBE seguir devolviendo un valor con cast `decimal:4` (string con 4 decimales), igual que hoy.
- `ConfiguracionGeneral::actual()->dias_anticipacion_alerta_pago` DEBE seguir devolviendo un `int`.
- `ConfiguracionGeneral::actual()->alerta_pago_mes_enviada_en` DEBE seguir devolviendo una instancia `Carbon` o `null`.
- `ConfiguracionGeneral::actual()->correo_notificaciones_vencimiento` DEBE seguir devolviendo un `string`.
- Si nunca se ha guardado ninguna configuración (base de datos recién migrada), `actual()` DEBE devolver los mismos valores por defecto que hoy: `tarifa_luz_por_unidad = 0`, `dias_anticipacion_alerta_pago = 5`, `alerta_pago_mes_enviada_en = null`, `correo_notificaciones_vencimiento = config('mail.from.address')`.

## Contrato 2 — Escritura

- `ConfiguracionGeneral::actual()->update(['tarifa_luz_por_unidad' => $x])` DEBE persistir el nuevo valor de forma que una llamada posterior a `ConfiguracionGeneral::actual()->tarifa_luz_por_unidad` devuelva `$x` — igual que hoy, sin importar que internamente ya no sea un `UPDATE` de una sola fila.
- `ConfiguracionGeneral::actual()->update([...])` con varios atributos a la vez (como hace `ConfiguracionGeneralController::update()` con `$solicitud->validated()`) DEBE actualizar todos los atributos indicados y dejar los no indicados sin cambios.
- Actualizar un atributo NO DEBE alterar el valor de los otros tres.

## Contrato 2b — `fresh()` (detectado durante implementación)

- `ConfiguracionGeneral::actual()->fresh()` DEBE seguir devolviendo el estado vigente de la configuración (equivalente a llamar `actual()` de nuevo), ya que `RegistroMasivoLecturasControllerTest` depende de este método tras `actualizarTarifa()`. La instancia virtual no tiene una fila única identificable por `id`, así que este método se sobrescribe explícitamente en vez de heredar el comportamiento por defecto de Eloquent (que intentaría resolver por clave primaria y devolvería `null`).

## Contrato 3 — Extensibilidad (FR-007, SC-006)

- Agregar una configuración nueva al sistema (una `clave` que hoy no existe) DEBE lograrse insertando una fila en la tabla, sin requerir escribir ni ejecutar una migración de esquema (`Schema::table(...)->...`).

## Fuera de alcance de este contrato

- No se exige que `ConfiguracionGeneral` siga siendo un modelo Eloquent "estándar" internamente (ver plan.md, Complexity Tracking) — solo que su interfaz pública (los 4 nombres de atributo y los métodos `actual()`/`update()`) no cambie.
