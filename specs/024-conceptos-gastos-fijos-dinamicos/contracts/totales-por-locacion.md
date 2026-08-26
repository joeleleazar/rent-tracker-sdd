# Contrato: Total Facturado y Cantidad de Recibos por Locación

Cambia la respuesta de `GET recibos.registroMasivo.index` (specs/023) — sin ruta nueva.

## Datos agregados por locación (dentro del periodo visible)

- `cantidadRecibos`: cantidad de recibos de esa locación y periodo con `estado != 'anulado'`.
- `totalFacturado`: suma de `total()` de esos mismos recibos (renta + todos sus `recibo_conceptos`).

## Presentación

En la fila de cada locación alquilable, junto a la columna "Conceptos" (o en una columna nueva, a definir en
`tasks.md`): un texto o badge con la cantidad ("0 recibos" / "1 recibo" / "N recibos") y el monto total
(`S/ 0.00` si `cantidadRecibos` es 0), usando la clase `.cifra` ya establecida en DESIGN.md para toda cifra
monetaria mostrada en columna.

Una locación sin contrato activo en el periodo igual puede tener recibos previos de un contrato ya vencido
—`cantidadRecibos`/`totalFacturado` se calculan independientemente de si hay contrato activo en el periodo
visible, porque reflejan lo ya facturado, no lo facturable.
