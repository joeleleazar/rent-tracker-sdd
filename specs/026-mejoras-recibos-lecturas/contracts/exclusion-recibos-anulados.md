# Contrato: Exclusión de Recibos Anulados al Calcular Cobertura y Uso

Sin rutas nuevas. Cambia el resultado de rutas y vistas ya existentes.

## `Recibo::scopeVigente()`

Nuevo scope reutilizable: `Recibo::vigente()` equivale a `where('estado', '!=', 'anulado')`.

## `ServicioGeneracionReciboPeriodo`

- `conceptosDisponibles(Locacion, Carbon)`: la consulta de recibos de esa locación/periodo agrega
  `->vigente()`. Un concepto cuyo único recibo cubridor está anulado vuelve a aparecer como disponible.
- `reciboQueCubre(Locacion, Carbon, ?excluirReciboId)`: mismo filtro — un concepto ya no apunta a un
  recibo anulado como su "cubridor".
- `validarSinSuperposicion(...)`: mismo filtro en la consulta con `lockForUpdate()` — generar (o editar)
  un recibo que cubra un concepto antes cubierto por un recibo ahora anulado deja de lanzar
  `ConceptosReciboYaCubiertosException`.
- `conceptosDisponiblesDesde()`/`reciboQueCubreDesde()` (variantes puras usadas por
  `RegistroMasivoRecibosController::datosDelPeriodo()` para evitar N+1): sus llamadores deben pasarles ya
  filtrada la colección de recibos (`->vigente()` aplicado en la consulta previa), no dentro de estas
  funciones puras.

## `recibos/registro-masivo/index.blade.php` y sus parciales

Efecto observable: los badges de conceptos de una locación (`estado-recibo-locacion.blade.php`) dejan de
marcar como cubierto un concepto cuyo único recibo cubridor está anulado, y el botón "Generar Recibo"
vuelve a ofrecer esos conceptos.

## `ConceptoGastoFijoController`

- `index()`: `withCount(['reciboConceptos as recibos_en_uso'])` cambia a un conteo que excluye
  `recibo_conceptos` cuyo `recibo` padre está anulado (`whereHas('recibo', fn ($q) => $q->vigente())`).
- `destroy()`: el chequeo `$enUso = ... + $conceptosGastoFijo->reciboConceptos()->count()` usa el mismo
  conteo filtrado. Un concepto cuyas únicas referencias en `recibo_conceptos` pertenecen a recibos
  anulados ya no cuenta como "en uso" y puede eliminarse (si tampoco tiene valores de contrato
  configurados).

## Fuera de alcance

- El conteo/total de recibos por locación en `recibos/registro-masivo/index.blade.php`
  (`cantidadRecibosPorLocacion`/`totalFacturadoPorLocacion`, specs/024) ya excluye anulados — sin cambios
  aquí, solo se verifica que sigue así (regresión).
- El comprobante y el detalle de un recibo anulado siguen mostrando su marca de "Anulado" sin cambios; no
  se ocultan ni se eliminan sus filas de `recibo_conceptos`.
