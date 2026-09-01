# Contrato — Descargar plantilla de lecturas

## Endpoint

`GET /lecturas/registro-masivo/plantilla?periodo=YYYY-MM` → `name: lecturas.registroMasivo.plantilla`

Middleware: `['auth','cuenta.activa']`. `hx-boost="false"` en el enlace (descarga binaria).

## Respuesta

`200` con archivo `lecturas-plantilla-YYYY-MM.xlsx` (`Content-Type` de hoja de cálculo).

### Encabezados de columna (fila 1)

| `periodo` | `local_id` | `Locación` | `Lectura Periodo Anterior` | `Lectura Actual` |
|-----------|------------|------------|----------------------------|------------------|

### Filas (una por locación alquilable, en orden del árbol de locaciones)

- `periodo`: `YYYY-MM` del periodo para el que se generó la plantilla (columna técnica; permite que
  `previsualizar` rechace un archivo generado para otro periodo — FR-010). El usuario no debe editarla.
- `local_id`: id numérico de la `Locacion` (clave de emparejamiento; el usuario no debe editarla).
- `Locación`: ruta jerárquica legible (`Padre > Hijo`), solo referencia.
- `Lectura Periodo Anterior`: `lectura_actual` de la última lectura real anterior al periodo, o vacío.
- `Lectura Actual`: valor de la `LecturaMedidor` del periodo si ya existe; vacío si no.

La tarifa por kWh **no** aparece en el archivo (FR-015).

## Errores

- Sin `periodo` → se usa el mes actual (mismo criterio que `index`).
- `periodo` inválido → `Carbon::parse` lanza; se captura y se responde `302` de vuelta con aviso.
