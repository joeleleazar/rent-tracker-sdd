# Contrato: Borrador de Generación de Recibo

Análogo al borrador de registro masivo de lecturas (specs/015), aplicado a la página individual de
generación de recibo (`locaciones.recibos.create`/`store`).

## `POST locaciones.recibos.borrador`

`/locaciones/{locacion}/recibos/borrador`

Body: `periodo` (`Y-m-d`), `incluye_alquiler` (bool), `monto_renta`, `fecha_emision`,
`conceptos[{concepto_gasto_fijo_id}][incluido]`/`[monto]` — mismos nombres de campo que ya usa el
formulario real (`recibo-conceptos-dinamico.md`, specs/024), para que un único `hx-include` sirva tanto al
autoguardado como al envío final.

Hace `upsert` de una fila en `borradores_recibo` para (`usuario_id` = usuario autenticado, `periodo`,
`locacion_id` = `$locacion->id`). No valida montos ni conceptos más allá de tipos básicos (numérico si
está presente) — un borrador puede quedar deliberadamente incompleto.

Responde con un mensaje breve de confirmación (texto plano, ej. "Borrador guardado a las 14:35."), igual
que `lecturas.registroMasivo.borrador`.

Disparado por dos triggers en la página de generación:
- Automático: `hx-trigger="every 120s"` (mismo intervalo que lecturas), mientras la página está abierta.
- Manual: botón "Guardar Borrador" (`hx-trigger="click"` sobre el mismo endpoint), que además da
  retroalimentación inmediata y visible del guardado (FR-009).

## `GET locaciones.recibos.create` — carga de borrador existente

Antes de renderizar, busca `BorradorRecibo` para (usuario autenticado, locación, periodo resuelto). Si
existe, sus valores (`incluye_alquiler`, `monto_renta`, `fecha_emision`, `conceptos`) prellenan el
formulario en lugar de (o además de, para los campos que el borrador no cubre) los montos sugeridos por
defecto — un valor explícito del borrador siempre gana sobre el sugerido automático, porque representa una
decisión ya tomada por el usuario.

## `POST locaciones.recibos.store` — descarte del borrador

Al confirmar exitosamente (sin excepciones de `ServicioGeneracionReciboPeriodo::generar()`), se elimina el
`BorradorRecibo` de (usuario autenticado, locación, periodo) si existe. Si la confirmación falla (por
ejemplo, `ConceptosReciboYaCubiertosException` porque otro recibo cubrió el concepto mientras tanto — Edge
Case de spec.md), el borrador NO se elimina y el usuario permanece en la página con el error visible,
pudiendo corregir y reintentar sin haber perdido su borrador.

## Fuera de alcance

- Ningún otro flujo (edición de un recibo ya emitido, `recibos.edit`/`recibos.update`) usa este borrador —
  aplica solo a la generación de un recibo nuevo.
- El borrador no se comparte entre usuarios ni se sincroniza en tiempo real entre pestañas del mismo
  usuario; el último guardado (manual o automático) gana.
