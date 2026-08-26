# Data Model: Emisión Masiva de Recibos por Periodo

Sin cambios de esquema (research.md Decisión 2). Esta feature reutiliza las entidades existentes y agrega
solo un concepto de dominio nuevo (derivado, no persistido): el conjunto de "conceptos disponibles" de una
locación y periodo.

## Recibo (existente, sin cambio de columnas)

| Atributo | Tipo | Relevante a esta feature |
|---|---|---|
| `contrato_id`, `locacion_id`, `lectura_medidor_id` | FK | sin cambio |
| `monto_renta`, `monto_agua`, `monto_luz`, `monto_pasadizo`, `monto_seguridad` | `decimal:2` | sin cambio — cada recibo generado desde esta pantalla sigue guardando el monto de cada concepto que incluye |
| `incluye_alquiler`, `incluye_agua`, `incluye_luz`, `incluye_pasadizo`, `incluye_seguridad` | `boolean` | **regla de negocio nueva**: el conjunto de estos 5 campos en `true`, agregado entre *todos* los recibos de una misma `(locacion_id, periodo)`, no puede tener conceptos repetidos |
| `periodo`, `fecha_emision`, `estado`, ... | — | sin cambio |

**Ya no aplica**: la regla implícita anterior de "como máximo un recibo por `(locacion_id, periodo)`" —
ahora pueden existir varios, mientras sus conceptos no se superpongan.

## Concepto disponible (concepto de dominio derivado, no persistido)

Para una locación y periodo dados, el conjunto de "conceptos disponibles" es:

```
CONCEPTOS = {incluye_alquiler, incluye_agua, incluye_luz, incluye_pasadizo, incluye_seguridad}
cubiertos(locacion, periodo) = unión de los conceptos en `true` de todos los recibos existentes de esa locación y periodo
disponibles(locacion, periodo) = CONCEPTOS − cubiertos(locacion, periodo)
```

Se calcula en el momento (no se guarda en ninguna tabla), tanto para pintar la fila/modal (lectura simple)
como para validar una confirmación (lectura con `lockForUpdate()` dentro de la transacción, research.md
Decisión 3).

## Contrato (existente, sin cambios)

Sigue siendo la fuente de `monto_renta` (antes de prorrateo), `costo_agua`, `costo_pasadizo`,
`costo_seguridad`, y de `fecha_inicio`/`fecha_fin` para el prorrateo de renta — sin cambios de esquema ni de
significado.

## LecturaMedidor (existente, sin cambios)

Sigue siendo la fuente del monto sugerido de "luz" vía `total` (specs/019) — sin cambios.
