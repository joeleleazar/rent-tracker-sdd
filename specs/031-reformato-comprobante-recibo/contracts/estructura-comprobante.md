# Contrato: estructura de bloques del comprobante

Este documento fija el orden y los nombres de clase CSS de los 6 bloques que
`resources/views/locaciones/recibos/comprobante.blade.php` DEBE producir, para que tanto los tests de
Feature como una futura revisión de diseño puedan anclarse a una estructura estable en vez de al texto
exacto de cada dato.

## Orden y clases (dentro de `#comprobante-recibo`)

| # | Bloque | Elemento / clase | Contenido |
|---|---|---|---|
| 1 | Encabezado | `.bloque-encabezado` | `<img class="logo-comprobante">` + título "Recibo de Pago" |
| 2 | Metadatos | `.bloque-metadatos` | Filas `.fila-dato`: N.° de recibo, Fecha de emisión, Período, Estado |
| 3 | Partes | `.bloque-partes` | "Recibí de" (inquilino) + Locación; "Recibido por" (solo si `nombre_propietario` no es vacío) |
| 4 | Conceptos | `.bloque-conceptos` | Filas `.fila-concepto`: Alquiler (si aplica) + un `.fila-concepto` por cada `recibo_concepto` |
| 5 | Total | `.bloque-total` | Único monto con fondo `#1e40af` (research.md Decisión 3) |
| 6 | Cierre | `.bloque-cierre` | Frase de agradecimiento |

Cada bloque, salvo el primero, va precedido de un `<hr class="separador-bloque">` (research.md Decisión 1).

## Reglas que los tests de Feature pueden verificar

- Los 6 bloques aparecen en este orden exacto en el HTML (`assertSeeInOrder` sobre un texto identificador
  de cada uno, ej. "Recibo de Pago", "N.° de recibo", "Recibí de", el nombre del primer concepto, "Total",
  la frase de cierre).
- La fila "Recibido por" solo aparece cuando `ConfiguracionGeneral::actual()->nombre_propietario` no es
  `null` ni cadena vacía (spec.md FR-005a).
- Cada concepto cobrado (alquiler + cada `recibo_concepto`) aparece como una línea `.fila-concepto`
  independiente — nunca agregado a otra.
- La marca "Anulado" (ya existente) sigue presente cuando `$recibo->estado === 'anulado'`, y su posicionamiento
  (`position: absolute; inset: 0`, centrada sobre todo `#comprobante-recibo`) no cambia — sigue sin
  superponerse con `.bloque-total` en la esquina donde este vive dentro del flujo vertical normal del
  documento.
