# Data Model: Fecha Límite de Pago Mensual, Alertas y Prorrateo por Días Activos

**Feature**: `008-prorrateo-alertas-pago` | **Date**: 2026-08-20

## Entidades

### ConfiguracionGeneral (extensión de `specs/004`, ya extendida por `specs/005`)

| Campo nuevo | Tipo | Reglas |
|---|---|---|
| `dias_anticipacion_alerta_pago` | Entero, obligatorio, por defecto `5` | Editable por el Administrador desde `/configuracion` (FR-002) |
| `alerta_pago_mes_enviada_en` | timestamp, nullable | Se asigna al enviar la alerta del mes en curso; se considera "no enviada este mes" si es `null` o de un mes anterior (ver `research.md` §3), sin un job de reinicio activo |

### Recibo (extensión de `specs/004`, ya extendida por `005`/`007`)

| Campo nuevo | Tipo | Reglas |
|---|---|---|
| `dias_activos_periodo` | Entero, nullable | `null` si el contrato estuvo activo el mes completo; en caso contrario, la cantidad de días activos usada para sugerir el prorrateo (FR-005) |
| `dias_totales_periodo` | Entero, nullable | `null` en el mismo caso que arriba; cantidad real de días del mes calendario usada en el cálculo (FR-008) |

**Nota**: estos dos campos son de **trazabilidad** del cálculo sugerido al momento de emitir el recibo, no se recalculan ni se usan para validar nada después de guardado (consistente con la inmutabilidad histórica de `Recibo` ya establecida en `specs/004`).

### Contrato (de `specs/002`, sin cambios de esquema)

No se agregan columnas; el prorrateo se calcula en tiempo de generación de recibo a partir de `fecha_inicio`/`fecha_fin`/`monto_renta` ya existentes (A-003).

## Relaciones

Sin cambios respecto a `specs/007-estado-envio-recibo/data-model.md`; esta feature solo agrega columnas de configuración y de trazabilidad de prorrateo, sin nuevas tablas ni relaciones.

## Notas de migración

- `configuracion_general`: migración de alteración que agrega `dias_anticipacion_alerta_pago` (`integer` default `5`) y `alerta_pago_mes_enviada_en` (`timestamp` nullable).
- `recibos`: migración de alteración que agrega `dias_activos_periodo` y `dias_totales_periodo` (`integer` nullable); los recibos ya emitidos antes de esta migración quedan con ambos campos en `null` (equivalente a "mes completo", que es la interpretación correcta ya que 004/005 no tenían noción de prorrateo).
