# Contrato: Valores de Referencia por Concepto en un Contrato

Reemplaza la ruta ya existente `PATCH contratos.costos.update` (specs/002/004) — mismo nombre de ruta,
payload nuevo.

## `PATCH contratos.costos.update`

Body: `valores[{concepto_gasto_fijo_id}]` — un valor numérico por cada concepto activo que no sea "Renta" ni
"Luz" (esos dos NUNCA se ofrecen en este formulario, FR-004/FR-006). Los conceptos inactivos tampoco se
ofrecen, pero un valor ya configurado para un concepto que después se desactiva NO se borra (Edge Case de
spec.md) — solo deja de mostrarse en este formulario.

Al confirmar: hace `upsert` de una fila en `contrato_valores_concepto` por cada concepto enviado con un
valor numérico válido (`min:0`). No afecta ningún recibo ya emitido (FR-007).

`GET contratos.show` / el formulario de costos fijos del contrato pasan a iterar
`ConceptoGastoFijo::activos()->excluyendoRentaYLuz()->ordenados()->get()` en vez de renderizar 4 campos
fijos.
