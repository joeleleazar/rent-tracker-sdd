# Data Model — Carga Masiva por Plantilla y Cobro por QR

## Cambios de esquema

### `pagos` (migración aditiva)

| Columna      | Tipo          | Nulo | Notas                                                            |
|--------------|---------------|------|-----------------------------------------------------------------|
| `medio_pago` | `string(60)`  | Sí   | Medio informado en el cobro rápido (efectivo, transferencia…). Nullable; el resto del flujo de pagos no lo envía. |

Sin otras columnas, índices ni tablas nuevas. `lecturas_medidor`, `recibos`, `recibo_conceptos`,
`conceptos_gasto_fijo`, `contratos`, `locaciones` se usan tal como están.

## Entidades existentes involucradas (sin cambios de forma salvo lo indicado)

- **LecturaMedidor** (`lecturas_medidor`): única `(locacion_id, periodo)`. La carga masiva hace
  `updateOrCreate` por esa clave. Campos tocados: `lectura_anterior` (derivado), `lectura_actual`
  (de la plantilla), `total` (calculado con la tarifa global o el valor explícito de la fila),
  `fecha_registro`.
- **Recibo** (`recibos`): estado `pendiente | pagado | anulado`. La carga masiva crea vía
  `ServicioGeneracionReciboPeriodo::generar()` o actualiza vía `::actualizar()` el **único** recibo
  vigente de la locación/periodo. `total()` = `monto_renta + Σ conceptos`.
- **ReciboConcepto** (`recibo_conceptos`): filas `(recibo_id, concepto_gasto_fijo_id, monto)`.
  En una actualización por importación se borran y recrean (patrón de `::actualizar()`).
- **ConceptoGastoFijo** (`conceptos_gasto_fijo`): catálogo dinámico. `esRenta()`/`esLuz()` marcan los
  dos conceptos protegidos con fuente de valor especial. Las columnas de la plantilla de recibos se
  derivan de `ConceptoGastoFijo::activos()->ordenados()`.
- **Pago** (`pagos`): lo crea `ServicioGestionPagosRecibo::registrar()`. El cobro rápido añade
  `medio_pago` opcional y, si se sube archivo, delega la evidencia en `EvidenciaPagoController`.

## Artefactos transitorios (no persistidos)

### Fila de plantilla de lecturas

```
periodo (YYYY-MM, técnico) — se valida contra el periodo de pantalla al importar (FR-010)
local_id (int, técnico)   — clave de emparejamiento
Locación (string)         — solo referencia
Lectura Periodo Anterior  — solo referencia (número o vacío)
Lectura Actual            — editable (número)
```

### Fila de plantilla de recibos

```
periodo (YYYY-MM, técnico) — se valida contra el periodo de pantalla al importar (FR-010)
local_id (int, técnico)
Locación (string)         — referencia
Contrato (string)         — referencia (nº / inquilino principal)
Renta (S/)                — editable
Luz (S/)                  — editable
<Concepto N> (S/)         — una por ConceptoGastoFijo activo no protegido, editable
Total (S/)                — editable; vacío ⇒ se usa el sugerido
```

### Fila de vista previa (en memoria durante la petición)

| Campo            | Origen                                    |
|------------------|-------------------------------------------|
| `local_id`       | columna técnica de la plantilla           |
| `nombre`         | resuelto del catálogo de locaciones       |
| valores editados | inputs de la tabla htmx                    |
| `estado`         | `valida` \| `invalida`                     |
| `motivos[]`      | lista de textos de validación por fila     |
| `accion`         | `crear` \| `actualizar` \| `omitir`        |

### Enlace verificable de recibo

`URL::signedRoute('cobro.recibo', $recibo)` — URL absoluta firmada, sin expiración. Se materializa como
PNG (data-URI) en el QR del comprobante y se verifica con el middleware `signed` al abrirla.

## Reglas de validación (resumen; detalle en research.md Decisión 4)

- **Archivo aceptable** (FR-010): encabezados esperados presentes y columna `periodo` del archivo igual
  al periodo seleccionado en pantalla; si no ⇒ rechazo 422 sin vista previa.
- **Emparejamiento**: `local_id` numérico y correspondiente a `Locacion` alquilable/activa; si no ⇒
  fila inválida.
- **Lecturas**: `lectura_actual` numérica ≥ 0 y ≥ lectura anterior real; consumo negativo ⇒ inválida.
- **Recibos**: montos numéricos ≥ 0; locación con contrato activo en el periodo; total vacío ⇒ sugerido.
- **Idempotencia**: confirmar dos veces el mismo conjunto de valores no produce cambios ni duplicados.
- **Cobro rápido**: `monto` `required numeric gt:0` y ≤ `saldoPendiente()`; `fecha_pago` `required date
  before_or_equal:today`; `medio_pago` `nullable string max:60`; evidencia con las reglas ya vigentes de
  `EvidenciaPagoController` (imagen o PDF). Recibo anulado o con saldo 0 ⇒ no se registra.

## Estados y transiciones

Ninguna máquina de estados nueva. El registro de pago por el cobro rápido dispara la misma
`ServicioGestionPagosRecibo::recalcularEstado()` existente (`pendiente` ↔ `pagado`). La carga masiva de
recibos no cambia `estado` (crea en `pendiente` por el default del modelo; actualizar no lo toca).
