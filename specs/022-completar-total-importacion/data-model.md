# Data Model: Completar Total en Importación Histórica y Seeder

Esta feature no agrega, quita ni modifica entidades, atributos ni relaciones — reutiliza el modelo `LecturaMedidor`
y la columna `total` tal como quedaron definidos en specs/019-total-editable-recibos. Se documenta aquí solo
para referencia rápida de qué parte de ese modelo está en juego.

## LecturaMedidor (existente, sin cambios de esquema)

| Atributo | Tipo | Relevante a esta feature |
|---|---|---|
| `locacion_id` | FK a `locaciones` | sin cambio — ambos procesos ya lo asignaban correctamente |
| `periodo` | `date` | sin cambio |
| `lectura_anterior` | `decimal:2`, nullable | sin cambio — sigue pudiendo ser `null` (primera lectura de una unidad) |
| `lectura_actual` | `decimal:2` | sin cambio |
| `total` | `decimal:2`, **`NOT NULL`** (desde specs/019) | **campo que esta feature ahora completa** en los dos procesos que antes lo omitían |
| `fecha_registro` | `datetime` | sin cambio |

`consumo_calculado` no es una columna desde specs/021 (accessor derivado en el modelo) — no participa del
`INSERT`, así que no es parte del alcance de esta feature.

## Sin nuevas entidades ni relaciones

No hay contratos de API, eventos ni tablas nuevas. El único "contrato" relevante es el de comportamiento de
los dos procesos de escritura, documentado en `contracts/calculo-total.md`.
