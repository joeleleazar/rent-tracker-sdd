# Contrato — Descargar plantilla de recibos

## Endpoint

`GET /recibos/registro-masivo/plantilla?periodo=YYYY-MM` → `name: recibos.registroMasivo.plantilla`

Middleware `['auth','cuenta.activa']`; enlace con `hx-boost="false"`.

## Respuesta

`200` con `recibos-plantilla-YYYY-MM.xlsx`.

### Encabezados (fila 1) — columnas dinámicas

```
periodo | local_id | Locación | Contrato | Renta (S/) | Luz (S/) | <Concepto A> (S/) | <Concepto B> (S/) | … | Total (S/)
```

- `periodo`: `YYYY-MM` de la plantilla (columna técnica; `previsualizar` rechaza un archivo de otro
  periodo — FR-010). No editable por el usuario.

- `Renta` y `Luz`: columnas fijas (conceptos protegidos `esRenta()` / `esLuz()`).
- Una columna por cada `ConceptoGastoFijo::activos()->ordenados()` **no protegido**, encabezada por su
  `nombre`.
- `Total (S/)`: última columna.

### Filas (una por locación con contrato activo en el periodo)

- `local_id`, `Locación` (ruta jerárquica), `Contrato` (nº + inquilino principal) → referencia.
- Si existe **un** recibo vigente de la locación/periodo: `Renta` = `monto_renta`, `Luz` = monto del
  concepto luz del recibo (o `lectura_medidor->total`), cada `<Concepto>` = `monto` de su
  `recibo_concepto`, `Total` = `recibo->total()`.
- Si no existe recibo: valores derivados por la lógica vigente
  (`ServicioGeneracionReciboPeriodo::calcularMontoLuzSugerido()` para luz, `ValorConceptoContrato` para
  cada concepto, prorrateo de renta), o vacío si no hay base.
- Si existen **varios** recibos vigentes: la fila se incluye con un marcador en `Contrato`
  ("varios recibos — editar individualmente"); al importar se marcará inválida.

## Errores

Igual que la plantilla de lecturas.
