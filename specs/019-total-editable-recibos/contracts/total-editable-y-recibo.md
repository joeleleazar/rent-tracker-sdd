# Contrato de Interfaz: Total Editable y Consumo por Recibo

**Feature**: `019-total-editable-recibos` | **Date**: 2026-08-25

No hay rutas nuevas — esta feature actúa sobre `POST lecturas.registroMasivo.store`,
`POST lecturas.registroMasivo.borrador` (ya documentadas en
`specs/015-registro-masivo-lecturas/contracts/rutas-registro-masivo-lecturas.md`) y sobre el
método interno `ServicioGeneracionReciboPeriodo::calcularMontoLuzSugerido()`, consumido por
`ReciboController` (specs/005, sin ruta nueva). Este documento fija el contrato que cada uno DEBE
cumplir.

## Contrato 1 — Cálculo de consumo con lectura anterior por defecto (FR-001)

- En `RegistroMasivoLecturasController::store()`, para cada fila del lote: si no existe una
  `LecturaMedidor` de esa locación con `periodo` estrictamente anterior al periodo del lote, la
  lectura anterior usada por `ServicioCalculoConsumoMedidor::calcularConsumo()` es `0.0`, no
  `null`.
- Este contrato es exclusivo del registro masivo. `LecturaMedidorController` (registro individual)
  y `ServicioCalculoConsumoMedidor::sugerirLecturaAnterior()` conservan su comportamiento actual
  (devuelven `null` cuando no hay lectura anterior) — ningún llamador fuera del registro masivo
  cambia de comportamiento.

## Contrato 2 — Campo `lecturas[{locacion_id}][total]` en el formulario de registro masivo

- Para una locación sin lectura guardada del periodo (fila "pendiente"), la celda "Total" de
  `fila-registro-masivo.blade.php` DEBE ser un `<input type="number">` con
  `name="lecturas[{locacion_id}][total]"`, prellenado por JS con el total sugerido
  (consumo × tarifa vigente) y editable por el usuario antes de guardar.
- Para una locación con lectura ya guardada del periodo (fila "completada"), la celda "Total" DEBE
  seguir siendo un elemento de solo lectura (sin `name`, no se envía en ningún formulario), mostrando
  el valor de `lectura->total` ya persistido — nunca un valor recalculado, y nunca editable desde
  ese estado (Q2: la edición de total después de guardado queda fuera de alcance).
- `POST lecturas.registroMasivo.store` DEBE persistir, para cada fila válida:
  - El valor numérico recibido en `lecturas[{locacion_id}][total]`, si es numérico.
  - Si no es numérico o está ausente: `round(consumo × tarifa_vigente, 2)` como fallback (research.md
    Decisión 2) — nunca rechaza la fila por esta causa.
- `POST lecturas.registroMasivo.borrador` DEBE incluir `total` en el `upsert()` del borrador
  (mismo criterio de filtrado que `lectura_actual`: solo si es numérico), y `GET
  lecturas.registroMasivo.index` DEBE restaurar ese valor en el input de total al reabrir la
  pantalla para el mismo periodo (research.md Decisión 4).

## Contrato 3 — `ServicioGeneracionReciboPeriodo::calcularMontoLuzSugerido()`

- **Antes**: `consumo_calculado × tarifa_vigente_al_momento_de_generar_el_recibo`.
- **Después**: `lectura->total` (el valor ya persistido en `LecturaMedidor` al momento en que se
  registró/editó esa lectura), o `0.0` si no existe ninguna `LecturaMedidor` para esa
  locación/periodo — el mismo fallback que ya existía.
- Este método sigue siendo el único punto del sistema que calcula un "monto de luz sugerido"; el
  monto final del recibo (`monto_luz`, en `ReciboController`/`ServicioGeneracionReciboPeriodo::
  generar()`/`actualizar()`) sigue siendo editable por el usuario en el formulario de recibo, sin
  cambios — este contrato solo cambia cuál es el valor que prellena ese formulario.
