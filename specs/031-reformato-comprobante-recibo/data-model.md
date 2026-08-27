# Data Model: Reformato de Jerarquía Visual del Comprobante de Recibo

Esta feature no crea entidades nuevas ni modifica el esquema de `recibos`/`recibo_conceptos`/`locaciones`/
`inquilinos` — reorganiza cómo el comprobante presenta datos ya existentes, y extiende una entidad de
configuración ya existente con un atributo nuevo.

## `ConfiguracionGeneral` (extendida)

Entidad clave-valor ya existente (`app/Models/ConfiguracionGeneral.php`, patrón de specs/018). Se le agrega
un atributo nuevo a su interfaz pública de columnas virtuales:

| Atributo | Tipo | Nullable | Valor por defecto | Descripción |
|---|---|---|---|---|
| `nombre_propietario` | `string` | Sí | `null` | Nombre del propietario/administrador que recibe el pago, mostrado en el bloque de partes del comprobante ("Recibido por"). Editable desde Configuración General. |

Los 4 atributos ya existentes (`correo_notificaciones_vencimiento`, `tarifa_luz_por_unidad`,
`dias_anticipacion_alerta_pago`, `alerta_pago_mes_enviada_en`) no cambian.

**Regla de estado**: si `nombre_propietario` es `null` o una cadena vacía, el comprobante omite la fila
"Recibido por" del bloque de partes (spec.md FR-005a) — no se sustituye por ningún valor por defecto
visible.

## Entidades de solo lectura que consume el comprobante (sin cambios de esquema)

Estas entidades ya existen; se listan para dejar explícito qué campo de cada una alimenta cada bloque del
comprobante reestructurado.

### Recibo

| Campo usado | Bloque del comprobante |
|---|---|
| `id` | Metadatos — "N.° de recibo" |
| `fecha_emision` | Metadatos — "Fecha de emisión" |
| `periodo` | Metadatos — "Período" |
| `estado` | Metadatos — "Estado" (y la marca "Anulado" superpuesta cuando corresponde) |
| `monto_renta` | Detalle de conceptos — línea "Alquiler" (solo si no es `null`) |
| `total()` | Total — único monto del bloque de total |

### Concepto de recibo (`recibo_conceptos` vía `$recibo->conceptos`)

| Campo usado | Bloque del comprobante |
|---|---|
| `conceptoGastoFijo->nombre` | Detalle de conceptos — nombre de línea (o "Concepto eliminado", ya manejado desde specs/026) |
| `monto` | Detalle de conceptos — monto de línea, alineado a la derecha |

### Locación

| Campo usado | Bloque del comprobante |
|---|---|
| `nombre` | Partes — a qué locación corresponde el pago |

### Inquilino (vía `$recibo->contrato->inquilinoPrincipal()`)

| Campo usado | Bloque del comprobante |
|---|---|
| `nombreCompleto()` | Partes — "Recibí de" |
