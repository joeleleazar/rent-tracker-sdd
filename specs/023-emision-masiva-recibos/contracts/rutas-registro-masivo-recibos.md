# Contrato de Rutas: Registro Masivo de Recibos

Todas dentro del grupo `Route::middleware('auth')` ya existente en `routes/web.php`, siguiendo el mismo
patrón de nombres que `lecturas.registroMasivo.*` (specs/015).

## `GET recibos.registroMasivo.index`

`/recibos/registro-masivo?periodo=YYYY-MM`

- Sin `periodo`: usa el mes actual (mismo criterio que `lecturas.registroMasivo.index`).
- Devuelve el árbol completo de locaciones (`ServicioConstruccionArbolLocaciones::construir()`, ya
  reutilizado), y para cada locación alquilable: si tiene contrato activo en ese periodo, y el conjunto de
  conceptos disponibles (data-model.md) — no monta ningún monto sugerido en esta respuesta (eso lo resuelve
  el modal bajo demanda, research.md Decisión 4).
- Cada fila con al menos un concepto disponible expone un botón/acción con
  `hx-get="{{ route('recibos.registroMasivo.modal', $locacion) }}?periodo=..."` apuntando al contenedor de
  modal compartido.

## `GET recibos.registroMasivo.modal`

`/recibos/registro-masivo/{locacion}/modal?periodo=YYYY-MM`

- Devuelve la parcial `modal-recibo.blade.php` con: el contrato activo (si no hay, 404 o mensaje — no
  debería ser alcanzable si FR-003/FR-005 ya ocultan la acción, pero se valida igual del lado del servidor),
  los conceptos disponibles de esa locación y periodo (data-model.md) con su monto sugerido
  (`ServicioCalculoProrrateoContrato` para renta, `ServicioGeneracionReciboPeriodo::calcularMontoLuzSugerido()`
  para luz, `contratoActivo->costo_*` para agua/pasadizo/seguridad), cada uno con checkbox y campo de monto
  editable.
- Si no quedan conceptos disponibles (periodo ya completo para esa locación), devuelve una parcial de
  "periodo completo" en vez del formulario (defensivo — FR-009 ya debería impedir llegar aquí desde la UI).

## `POST recibos.registroMasivo.store`

`/recibos/registro-masivo/{locacion}`

- Body: `periodo`, `fecha_emision` (opcional, default hoy — Assumption A-004), y por cada concepto marcado:
  `incluye_{concepto}=1` + `monto_{concepto}`.
- Rechaza (422) si no viene ningún concepto marcado (FR-012).
- Dentro de `DB::transaction()` con `lockForUpdate()` (research.md Decisión 3): si algún concepto marcado ya
  está cubierto por otro recibo de esa locación y periodo (leído en este mismo instante, no el que vio el
  modal al abrirse), responde 422 con el detalle de qué concepto(s) ya estaban cubiertos y por cuál recibo
  (`ConceptosReciboYaCubiertosException`) — sin crear nada.
- Si todo es válido, crea el recibo (mismo criterio que `ReciboController::store`/
  `ServicioGeneracionReciboPeriodo::generar()`, pero con la nueva regla de no-superposición) y responde con
  la parcial `fila-registro-masivo-recibos.blade.php` de esa locación ya actualizada — sin redirect
  (research.md Decisión 5).

## Sin cambios en las rutas ya existentes

`locaciones.recibos.create/store`, `recibos.show/edit/update/estado.update/comprobante` — el flujo
individual sigue existiendo tal cual (mismas rutas), solo cambia la regla de negocio que aplica internamente
`ServicioGeneracionReciboPeriodo` (Assumption A-003 de spec.md).
