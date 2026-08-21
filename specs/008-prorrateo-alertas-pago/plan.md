# Implementation Plan: Fecha Límite de Pago Mensual, Alertas y Prorrateo por Días Activos

**Branch**: `008-prorrateo-alertas-pago` | **Date**: 2026-08-20 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/008-prorrateo-alertas-pago/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Establecer el último sábado de cada mes como fecha límite de pago general, con una alerta configurable por anticipación (en días) enviada al Administrador sin duplicarse dentro del mismo mes; y calcular automáticamente, al generar el recibo de un periodo, la cantidad de días que un contrato estuvo activo dentro de ese mes cuando su `fecha_inicio`/`fecha_fin` no coincide con el primer/último día del mes, sugiriendo un monto de renta prorrateado editable. Enfoque técnico: se extiende `ConfiguracionGeneral` (de `specs/004`) con la anticipación configurable y la marca de envío mensual; se extiende `Recibo` (de `specs/004`/`005`) con la trazabilidad de días activos/totales usados; un `ServicioCalculoProrrateoContrato` reutiliza el helper `Locacion::contratoActivoEnPeriodo()` (de `specs/005`) para integrarse en `ReciboController@create`; y un segundo comando artisan programado (además de `contratos:verificar-vencimientos` de `specs/004`) revisa diariamente si corresponde enviar la alerta de fecha límite del mes.

## Technical Context

**Language/Version**: PHP 8.3+ (`composer.json`)

**Primary Dependencies**: Laravel 13.x (misma nota de discrepancia que `specs/001` §1), Eloquent ORM, Blade, `Illuminate\Support\Carbon` (cálculo del último sábado del mes), Mailable, Scheduler nativo de Laravel, Pest 4

**Storage**: PostgreSQL; se altera `configuracion_general` (de `specs/004`, extendida en `005`) y `recibos` (de `specs/004`, extendida en `005`/`007`)

**Testing**: Pest, `RefreshDatabase`; unit tests para `ServicioCalculoFechaLimitePago` (último sábado, casos borde de mes terminado en sábado), `ServicioCalculoProrrateoContrato` (días activos, prorrateo, mes completo), `ServicioAlertaFechaLimitePago` (no-duplicación mensual, `Mail::fake()`); feature tests para el comando artisan y para la precarga prorrateada en `ReciboController@create`

**Target Platform**: Servidor Linux de shared hosting, mismo cron único de `schedule:run` ya usado por `specs/004`

**Project Type**: Aplicación web monolítica (single project)

**Performance Goals**: Sugerencia de días activos y monto prorrateado visible en <2s al iniciar la generación del recibo (SC-002)

**Constraints**: La fecha límite de pago es única y general para todos los contratos (A-002, sin diferenciación por locación/contrato); el prorrateo aplica únicamente a `monto_renta`, no a los costos fijos (A-004); los cálculos de días/prorrateo son sugerencias editables, no modifican `Contrato` (A-003)

**Scale/Scope**: Mismo volumen que `Contrato`/`Recibo` de specs previas

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|---|---|
| I. Stack Tecnológico Moderno (PHP/Laravel/PostgreSQL) | ✅ Migraciones Eloquent, `Carbon` para cálculo de fechas (sin SQL crudo de fechas), Services desacoplados (`ServicioCalculoFechaLimitePago`, `ServicioCalculoProrrateoContrato`, `ServicioAlertaFechaLimitePago`), comando artisan + scheduler nativo. ⚠️ Misma nota de discrepancia de versión de Laravel que `specs/001` (preexistente) |
| II. Nomenclatura en Español | ✅ Columnas `dias_anticipacion_alerta_pago`/`alerta_pago_mes_enviada_en`/`dias_activos_periodo`/`dias_totales_periodo`, `ServicioCalculoFechaLimitePago`, `ServicioCalculoProrrateoContrato`, `ServicioAlertaFechaLimitePago`, comando `pagos:alertar-fecha-limite` |
| III. Accesibilidad Senior-First | ✅ Indicador "X días de Y activos" y monto prorrateado sugerido con tipografía ≥18px y alto contraste al generar el recibo; configuración de anticipación con etiquetas explícitas |
| IV. Pruebas Automatizadas Exhaustivas | ✅ Pest cubre `ServicioCalculoFechaLimitePago` (último sábado, mes que termina en sábado), `ServicioCalculoProrrateoContrato` (inicio a mitad de mes, fin a mitad de mes, mes completo, inicio y fin en el mismo mes), `ServicioAlertaFechaLimitePago` (no-duplicación mensual, anticipación mayor a los días del mes, `Mail::fake()`), `ReciboController@create` (sugerencia prorrateada visible) |
| V. Integridad de Datos y Seguridad Transaccional | ✅ `DB::transaction` al marcar `alerta_pago_mes_enviada_en` junto con el envío del correo (mismo patrón que `specs/004` §3); `decimal:2` en el monto prorrateado sugerido; `dias_activos_periodo`/`dias_totales_periodo` son enteros simples sin riesgo de precisión |

**Resultado**: PASS. No se identifican violaciones que requieran justificación en `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/008-prorrateo-alertas-pago/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── rutas-prorrateo-alertas-pago.md
└── tasks.md
```

### Source Code (repository root)

```text
app/
├── Console/
│   └── Commands/
│       └── AlertarFechaLimitePago.php         # Nuevo: comando artisan `pagos:alertar-fecha-limite`
├── Mail/
│   └── AlertaFechaLimitePago.php               # Nuevo: Mailable con la fecha límite calculada
├── Models/
│   ├── ConfiguracionGeneral.php                 # Se agregan dias_anticipacion_alerta_pago/alerta_pago_mes_enviada_en (de specs/004)
│   └── Recibo.php                               # Se agregan dias_activos_periodo/dias_totales_periodo (de specs/004-005-007)
├── Http/
│   ├── Controllers/
│   │   └── ReciboController.php                 # @create (de specs/005) invoca ServicioCalculoProrrateoContrato
│   └── Requests/
│       └── SolicitudActualizarConfiguracionGeneral.php   # Se extiende (de specs/004) con dias_anticipacion_alerta_pago
└── Services/
    ├── ServicioCalculoFechaLimitePago.php        # Nuevo: último sábado del mes
    ├── ServicioCalculoProrrateoContrato.php      # Nuevo: días activos + monto prorrateado sugerido
    └── ServicioAlertaFechaLimitePago.php         # Nuevo: no-duplicación mensual, envío síncrono

database/
└── migrations/
    ├── xxxx_add_alerta_pago_to_configuracion_general_table.php   # Nuevo (ALTER configuracion_general)
    └── xxxx_add_prorrateo_to_recibos_table.php                    # Nuevo (ALTER recibos)

resources/
└── views/
    ├── configuracion/
    │   └── edit.blade.php                        # Se extiende (de specs/004): campo de anticipación de alerta
    └── locaciones/
        └── recibos/
            └── create.blade.php                   # Se extiende (de specs/005): indicador "X de Y días activos" + monto prorrateado sugerido

routes/
└── console.php                                   # Se agrega `Schedule::command('pagos:alertar-fecha-limite')->daily()`

tests/
├── Feature/
│   ├── AlertarFechaLimitePagoTest.php             # Nuevo
│   └── ReciboControllerTest.php                  # Se extiende (de specs/004-005-007): sugerencia de prorrateo
└── Unit/
    ├── ServicioCalculoFechaLimitePagoTest.php     # Nuevo
    ├── ServicioCalculoProrrateoContratoTest.php   # Nuevo
    └── ServicioAlertaFechaLimitePagoTest.php       # Nuevo
```

**Structure Decision**: Aplicación Laravel monolítica única. Esta feature no introduce entidades nuevas: extiende `ConfiguracionGeneral` y `Recibo` (ambas ya creadas en `specs/004` y extendidas en `005`/`007`) y agrega tres Services de cálculo/notificación puros, reutilizando el patrón de comando artisan + scheduler ya establecido por `specs/004` para el envío de correos.

## Complexity Tracking

*No violations identified — table intentionally left empty.*
