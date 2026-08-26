# Contrato de Interfaz: Lectura Anterior y Autoguardado (corrección)

**Feature**: `016-correccion-registro-masivo-lecturas` | **Date**: 2026-08-25

No hay rutas nuevas — esta corrección actúa sobre dos rutas ya documentadas en
`specs/015-registro-masivo-lecturas/contracts/rutas-registro-masivo-lecturas.md`
(`lecturas.registroMasivo.index` y `lecturas.registroMasivo.borrador`). Este documento fija el
contrato exacto que ambas DEBEN volver a cumplir, como referencia para las pruebas endurecidas de
`research.md` (Hallazgos H1/H2) y para la revisión de la corrección final.

## Contrato 1 — Columna "Lectura Periodo Anterior" en `GET lecturas.registroMasivo.index`

Para cada fila de locación alquilable renderizada por
`resources/views/lecturas/registro-masivo/partials/fila-registro-masivo.blade.php`:

- **Si existe** una `LecturaMedidor` de esa locación con `periodo` estrictamente anterior al
  periodo mostrado: el texto de la columna es exactamente `lectura_actual` de la fila con el
  `periodo` más reciente entre esas (FR-001, FR-003) — nunca el de otra `locacion_id`, nunca el de
  un periodo posterior al seleccionado.
- **Si no existe** ninguna: el texto es literalmente `"0"` — nunca una celda vacía sin ningún valor
  (FR-002). **Enmendado 2026-08-25**: el criterio original de este contrato exigía el texto
  `"Sin lectura previa registrada"`; se reemplazó por `"0"` para ser consistente con la convención
  "lectura anterior ausente = 0" adoptada después por specs/019 (Q1) y specs/021 (Q1:A) para todo
  cálculo de consumo del sistema — ver la misma enmienda en
  `specs/006-historial-lectura-medidor/spec.md`.
- El mismo dato se expone en `data-lectura-anterior` del `<div id="campo-lectura-{locacion_id}">`
  correspondiente (usado por `resources/js/registro-masivo-lecturas.js` para el total en vivo) —
  ambos lugares DEBEN coincidir para la misma locación.
- Al cambiar el query param `periodo`, ambos valores se recalculan en relación al nuevo periodo
  (FR-003), incluyendo cuando el cambio cruza de enero a diciembre del año anterior.

## Contrato 2 — Autoguardado periódico vía `POST lecturas.registroMasivo.borrador`

- El elemento `<div id="autoguardado-borrador">` de `index.blade.php` DEBE declarar
  `hx-trigger="every 120s"`, `hx-post` apuntando a `lecturas.registroMasivo.borrador`, e
  `hx-include="#formulario-registro-masivo"` — la ausencia o alteración de cualquiera de estos tres
  atributos rompe FR-004 sin que ningún test de servidor lo detecte por sí solo (ver research.md
  H2), de ahí que el contrato los liste explícitamente.
- Cada ciclo (cada ~120s mientras la pantalla permanece abierta) hace un `upsert()` sobre
  `(usuario_id, periodo, locacion_id)` con los valores no vacíos y numéricos presentes en el
  formulario en ese momento — un ciclo sin cambios nuevos no debe reducir ni vaciar un borrador ya
  existente (Edge Case de spec.md).
- Al reabrir `GET lecturas.registroMasivo.index` para el mismo `periodo` autenticado como el mismo
  usuario, cada campo `lecturas[{locacion_id}][lectura_actual]` se prellena con el valor del
  borrador correspondiente, si existe, sin acción adicional del usuario (FR-005).
