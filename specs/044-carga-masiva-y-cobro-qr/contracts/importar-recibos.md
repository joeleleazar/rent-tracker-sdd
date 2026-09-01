# Contrato — Importar recibos (vista previa + confirmar)

## 1) Previsualizar

`POST /recibos/registro-masivo/importar/previsualizar` →
`name: recibos.registroMasivo.importar.previsualizar`

Cuerpo (multipart): `archivo` (`.xlsx`/`.csv`, `max:5120` KB), `periodo` (`Y-m-d`).

### Respuesta `200` (parcial `vista-previa-importacion` de recibos)

- `<table>` con una fila por fila del archivo:
  - `hidden filas[i][local_id]`
  - `filas[i][renta]`, `filas[i][luz]`, `filas[i][conceptos][<concepto_id>]` (uno por concepto activo),
    `filas[i][total]` — todos `input-group` con prefijo `S/`
  - celda "Total sugerido" recalculada por JS = `renta + luz + Σ conceptos`; `filas[i][total]` sigue al
    sugerido hasta que el usuario lo edita (`data-editado`)
  - `badge` estado + `motivos` por fila
- Aviso de importación (nivel archivo) si alguna columna de concepto del archivo ya no existe en el
  catálogo ("La columna «X» se ignoró: ya no está en el catálogo de conceptos").
- Botón "Confirmar importación".

### Rechazo `422`

Falta `periodo`/`local_id`/`Total`, o el archivo es la plantilla de **lecturas**, o la columna
`periodo` del archivo no coincide con el periodo seleccionado, o no parsea.

## 2) Confirmar

`POST /recibos/registro-masivo/importar/confirmar` →
`name: recibos.registroMasivo.importar.confirmar`
Form Request: `SolicitudConfirmarImportacionRecibos`.

Cuerpo: `periodo`, `filas[]` = `{ local_id, renta, luz, conceptos: {id: monto}, total? }`.

### Comportamiento (en una `DB::transaction`)

Por cada fila válida:

- **0 recibos vigentes** en la locación/periodo → `ServicioGeneracionReciboPeriodo::generar()` con
  `incluye_alquiler = renta > 0`, `monto_renta = renta`, `conceptos` = `{concepto_id: monto}` (incluye
  luz mapeada a su `concepto_gasto_fijo_id`), `fecha_emision = hoy`.
- **1 recibo vigente** → `ServicioGeneracionReciboPeriodo::actualizar()` con los mismos datos (borra y
  recrea `recibo_conceptos`). El `total` explícito de la fila, si vino, se aplica ajustando… ver nota.
- **>1 recibo vigente** → fila inválida, motivo "varios recibos en el periodo".

**Nota sobre `total` explícito**: `Recibo::total()` es derivado (`monto_renta + Σ conceptos`), así que
el "total" no es una columna persistible por sí sola. Regla de la importación:

- `total` **vacío** → se usa `renta + luz + Σ conceptos` (los componentes tal cual).
- `total` **igual** a la suma de componentes → idem (sin ajuste).
- `total` **distinto** de la suma (el usuario lo editó a mano) → se persisten los componentes tal como
  vienen y, para que `Recibo::total()` coincida con lo que el usuario tecleó, se ajusta el `monto` del
  concepto **luz** en la diferencia (`luz_ajustada = luz + (total - suma)`), replicando la intención de
  specs/019 (el total editable del recibo se refleja en el componente de luz). Si no hay concepto luz en
  la fila, la diferencia se aplica sobre el concepto de mayor `orden` presente. La implementación DEBE
  verificar este mecanismo contra specs/019 antes de cerrarse; si specs/019 usa otro punto de ajuste, se
  adopta ese.

### Respuesta

`302` a `recibos.registroMasivo.index?periodo=YYYY-MM` con `session('mensaje')`:
`"Importación: N creados, M actualizados, K omitidos."`
Todas inválidas → `302` de vuelta con `withErrors` + `withInput`.

## Idempotencia

Reconfirmar el mismo `filas[]`: `actualizar()` reescribe `recibo_conceptos` con montos idénticos;
`Recibo::total()` no cambia; no se crean recibos nuevos.
