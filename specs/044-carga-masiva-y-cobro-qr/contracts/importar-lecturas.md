# Contrato — Importar lecturas (vista previa + confirmar)

## 1) Previsualizar

`POST /lecturas/registro-masivo/importar/previsualizar` →
`name: lecturas.registroMasivo.importar.previsualizar`

Cuerpo (multipart): `archivo` (`.xlsx`/`.csv`, `max:5120` KB), `periodo` (`Y-m-d`).
Disparado por htmx desde el `<input type="file">` (`hx-post`, `hx-target` = contenedor de vista previa).

### Respuesta `200` (parcial Blade `vista-previa-importacion`)

- Una `<table>` con una fila por fila del archivo:
  - `<input type="hidden" name="filas[i][local_id]">`
  - `<input name="filas[i][lectura_actual]">` (editable)
  - celda de "Lectura anterior" (referencia), "Consumo" y "Total sugerido" recalculadas por JS
  - `badge` `bg-success` "Válida" / `bg-danger` "Con error" + lista de `motivos` (persistente)
- Un `<button>` "Confirmar importación" (deshabilitado si 0 filas válidas).
- Contador "N válidas · M con error".

### Rechazo `422` (archivo no aceptable, FR-010)

- Falta una columna esperada (`periodo`, `local_id`, `Lectura Actual`), o el archivo trae columnas de la
  plantilla de **recibos**, o la columna `periodo` del archivo no coincide con el `periodo` seleccionado
  en la pantalla, o no parsea.
- Respuesta: parcial con `<x-mensaje-alerta tipo="error">` explicando el motivo; sin tabla.

## 2) Confirmar

`POST /lecturas/registro-masivo/importar/confirmar` →
`name: lecturas.registroMasivo.importar.confirmar`
Form Request: `SolicitudConfirmarImportacionLecturas`.

Cuerpo: `periodo` (`Y-m-d`), `filas[]` = `{ local_id, lectura_actual, total? }` (los inputs de la
tabla; **no** el archivo).

### Comportamiento

1. Revalida cada fila server-side (misma lógica que la previsualización).
2. En **una** `DB::transaction`: por cada fila válida
   `LecturaMedidor::updateOrCreate(['locacion_id'=>local_id,'periodo'=>periodo], [...])` con
   `lectura_anterior` derivada, `total` = valor explícito o `round(consumo * tarifaGlobal, 2)`.
3. Filas inválidas: se omiten (no abortan).

### Respuesta

`302` a `lecturas.registroMasivo.index?periodo=YYYY-MM` con `session('mensaje')` efímero:
`"Importación: N creadas, M actualizadas, K omitidas."`
Si **todas** las filas eran inválidas → `302` de vuelta con `withErrors` + `withInput` (nada se guarda).

## Idempotencia

Confirmar el mismo `filas[]` dos veces deja `lecturas_medidor` idéntica (segundo `updateOrCreate` no
cambia valores) — verificado por test.
