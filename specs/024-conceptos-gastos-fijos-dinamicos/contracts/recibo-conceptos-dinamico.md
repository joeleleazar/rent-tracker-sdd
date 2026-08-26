# Contrato: Payload Dinámico de Conceptos al Emitir un Recibo

Aplica a las 3 superficies que generan/editan un recibo: `locaciones.recibos.store`, `recibos.update`
(flujo individual, specs/005/019) y `recibos.registroMasivo.store` (flujo masivo, specs/023).

## Payload

- `periodo`, `fecha_emision`: sin cambio.
- **Renta**: sigue siendo `incluye_alquiler` (checkbox) + `monto_renta` (campo) — es la única excepción que
  conserva su forma fija, porque "Renta" nunca es un concepto dinámico más (research.md Decisión 2).
- **Resto de conceptos**: `conceptos[{concepto_gasto_fijo_id}][monto]` — un campo por cada concepto
  disponible (research.md Decisión 6); la presencia de la clave `{id}` en `conceptos[]` significa "incluido",
  su ausencia significa "no incluido". "Luz" sigue siendo uno de estos conceptos dinámicos (no un campo
  fijo), pero su monto sugerido en el formulario sigue viniendo de la lectura del periodo, nunca de
  `contrato_valores_concepto` (FR-006).

## Comportamiento server-side

`ServicioGeneracionReciboPeriodo::generar()`/`actualizar()`:
- Si `incluye_alquiler`, crea/actualiza `recibos.monto_renta` (con prorrateo, sin cambios de specs/008/019).
- Por cada `concepto_id` presente en `conceptos[]`, hace `upsert` de una fila en `recibo_conceptos` —
  aplicando la misma validación de no-superposición ya existente (specs/023 FR-007/FR-008), ahora contra
  filas de `recibo_conceptos` en vez de columnas booleanas.
- `ConceptosReciboYaCubiertosException` sigue existiendo, pero su lista de conceptos superpuestos ahora
  referencia `ConceptoGastoFijo` (id + nombre actual) en vez de una clave fija tipo `incluye_agua`.

## Migración de las vistas ya existentes

`locaciones/recibos/create.blade.php`, `edit.blade.php`, `show.blade.php`, `comprobante.blade.php`, y las 3
parciales de `recibos/registro-masivo/` pasan de listar 4 conceptos fijos (Agua/Luz/Pasadizo/Seguridad) a
iterar `conceptosDisponibles`/`conceptos()` del recibo, mismo patrón que `modal-recibo.blade.php` ya usa
desde specs/023 (que, al construirse sobre un array fijo de 5 claves, solo necesita generalizarse a una
consulta al catálogo).
