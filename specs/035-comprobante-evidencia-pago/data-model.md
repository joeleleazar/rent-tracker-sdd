# Data Model: Comprobante de Pago Firmado y Evidencia de Pago

## Pago (extendida — specs/032)

Se agregan 3 columnas nullable, sin crear ninguna tabla nueva (research.md Decisión 2).

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| `evidencia_ruta` | string | Sí | Ruta del archivo en el disco `local` (ej. `pagos/42/comprobante-firmado.jpg`) |
| `evidencia_nombre_archivo` | string | Sí | Nombre original del archivo subido, para mostrarlo/descargarlo con su nombre reconocible |
| `evidencia_tipo` | enum(`pdf`,`imagen`) | Sí | Tipo del archivo de evidencia, para elegir cómo previsualizarlo/servirlo |

**Método derivado nuevo**:

| Método | Retorna | Descripción |
|---|---|---|
| `tieneEvidencia()` | `bool` | `evidencia_ruta !== null` — si el pago ya tiene un archivo de evidencia subido |

**Regla de reemplazo** (research.md Decisión 3): al subir una evidencia nueva para un pago que ya tenía una,
el archivo físico anterior se borra del disco y las 3 columnas se sobrescriben con los datos del archivo
nuevo — nunca coexisten dos archivos de evidencia para el mismo pago.

## Comprobante de pago (documento derivado, no persistido)

No es una entidad de base de datos — se genera en el momento a partir de datos ya existentes (research.md
Decisión 4). Datos que muestra, todos ya calculables desde `Pago`/`Recibo` (specs/032):

| Dato mostrado | Origen |
|---|---|
| N.° de recibo | `$pago->recibo->id` |
| N.° de pago | `$pago->id` |
| Fecha del pago | `$pago->fecha_pago` |
| Locación | `$pago->recibo->locacion->nombre` |
| Inquilino ("Recibí de") | `$pago->recibo->contrato->inquilinoPrincipal()?->nombreCompleto()` |
| Propietario ("Recibido por") | `ConfiguracionGeneral::actual()->nombre_propietario` (specs/031) |
| Monto de este pago | `$pago->monto` |
| Total del recibo | `$pago->recibo->total()` |
| Pagado hasta ahora (acumulado) | `$pago->recibo->montoPagado()` — calculado al momento de generar el documento |
| Saldo pendiente | `$pago->recibo->saldoPendiente()` — calculado al momento de generar el documento |

## Relaciones y consistencia

- `Pago` sigue perteneciendo a `Recibo` (1 a N, ya establecido en specs/032) — esta feature no cambia esa
  relación.
- La evidencia es 1 a 1 con `Pago` (columnas directas, no una relación separada).
- El comprobante de pago siempre refleja el estado más reciente del recibo al momento de generarse — no
  hay ninguna garantía de que coincida con una versión impresa anteriormente si el pago se editó después
  (spec.md, Edge Cases).
