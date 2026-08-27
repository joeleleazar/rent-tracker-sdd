# Contrato: pantalla de Seguimiento de Pagos

## Ruta

| Método | Ruta | Nombre | Acción |
|---|---|---|---|
| GET | `/pagos/seguimiento` | `pagos.seguimiento.index` | Árbol de locaciones con el avance de pago del período |

Acepta el mismo parámetro de query `?periodo=YYYY-MM` que ya usa `recibos.registroMasivo.index`, con el
mismo valor por defecto (mes actual) cuando se omite.

## Forma de los datos (análoga a `RegistroMasivoRecibosController::datosDelPeriodo()`)

- `raices`: el árbol completo de locaciones — idéntico a `ServicioConstruccionArbolLocaciones::construir()`,
  sin ningún filtro (research.md Decisión 6).
- `periodo`: el período elegido (`Illuminate\Support\Carbon`, inicio de mes).
- Por cada locación alquilable, agregado sobre sus recibos **vigentes** (`estado != 'anulado'`) del
  período (research.md Decisión 7):
  - `montoPagadoPorLocacion[$id]`: suma de `montoPagado()` de esos recibos.
  - `montoTotalPorLocacion[$id]`: suma de `total()` de esos recibos.
  - `cantidadRecibosPorLocacion[$id]`: cuántos recibos vigentes tiene esa locación en el período.
  - `estadoAgregadoPorLocacion[$id]`: uno de `sin_recibos` (la locación no tiene ningún recibo vigente ese
    período — distinto de tener recibos sin pagos, spec.md Acceptance Scenario US2.3), `sin_pagos`
    (`montoPagadoPorLocacion[$id] == 0`), `parcial` (`0 < pagado < total`), `pagado` (`pagado >= total`).

## Presentación por fila (una fila por locación, igual que hoy en emisión de recibos)

| Columna | Contenido |
|---|---|
| Nombre / Locación | Igual que hoy — árbol expandible/colapsable, mismo componente `fila-arbol` |
| Estado de pago | Badge según `estadoAgregadoPorLocacion`: sin badge si `sin_recibos`; "Sin pagos" (secundario) si `sin_pagos`; "Parcial" (advertencia) con `S/ pagado / S/ total` si `parcial`; "Pagado" (éxito) si `pagado` |
| Avance | `S/ {montoPagadoPorLocacion} / S/ {montoTotalPorLocacion}` — omitido si `sin_recibos` |
| Acción | "Ver Pagos", presente solo si `cantidadRecibosPorLocacion > 0`; reutiliza la misma desambiguación de `recibosDelPeriodo()` (research.md Decisión 7) — redirige a `recibos.show` si hay un único recibo vigente, o a la lista de recibos del período si hay más de uno |

Una locación no alquilable (una galería o un piso, sin contrato posible) muestra sus columnas vacías, igual
que ya hace `estado-recibo-locacion.blade.php` para el árbol de emisión de recibos.
