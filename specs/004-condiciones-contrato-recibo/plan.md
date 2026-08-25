# Implementation Plan: Condiciones del Contrato y Costos de Referencia para Recibos

**Branch**: `004-condiciones-contrato-recibo` | **Date**: 2026-08-20 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/004-condiciones-contrato-recibo/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Agregar a `Contrato` los cuatro costos fijos de referencia (agua, luz, pasadizo, seguridad) y tres marcas de tiempo de hitos de notificación de vencimiento (30/15/7 días); introducir la entidad `ConfiguracionGeneral` (fila única, correo administrativo de notificaciones); introducir la entidad `Recibo` (comprobante mensual con montos editables e independientes de los valores de referencia del contrato); y un comando Artisan programado que revisa diariamente los contratos activos y envía correos de vencimiento por correo sin duplicar hitos. Enfoque técnico: dos migraciones de alteración/creación + un comando de consola (`routes/console.php`, scheduler nativo de Laravel 13) + un Service de notificación + un `ReciboController` que precarga desde `Contrato` sin acoplar ambos modelos.

## Technical Context

**Language/Version**: PHP 8.3+ (`composer.json`)

**Primary Dependencies**: Laravel 13.x (instalado; misma nota de discrepancia con la Constitución ya documentada en `specs/001-jerarquia-locaciones/research.md` §1), Eloquent ORM, Blade, Mailable (`Illuminate\Mail`), Scheduler nativo de Laravel (`routes/console.php`, sin `app/Console/Kernel.php` en Laravel 11+/13), Pest 4

**Storage**: PostgreSQL; se altera `contratos` (nuevas columnas de costos fijos y de hitos de notificación) y se crean `configuracion_general` y `recibos`

**Testing**: Pest, `RefreshDatabase`; feature tests para `ReciboController` y `ConfiguracionGeneralController`, unit tests para el modelo `Contrato` (nuevos scopes/helpers), `Recibo`, `ConfiguracionGeneral` y `ServicioNotificacionVencimientoContrato`; `Mail::fake()` para verificar el envío sin tocar un servidor SMTP real (Principio IV)

**Target Platform**: Servidor Linux de shared hosting, consistente con `specs/002-gestion-contratos/research.md` §2 (cron único `* * * * * php artisan schedule:run`, sin colas persistentes obligatorias — el envío de correo puede ser síncrono dado el volumen esperado, ver `research.md` §3)

**Project Type**: Aplicación web monolítica (single project)

**Performance Goals**: Precarga de montos al iniciar la generación de un recibo en <2s (SC-002); verificación diaria de vencimientos completa en un tiempo razonable para el volumen esperado (cientos de contratos, consistente con `specs/002`)

**Constraints**: Los tres hitos de notificación (30/15/7 días) no deben duplicarse por contrato; los recibos ya emitidos MUST permanecer inmutables ante ediciones posteriores del contrato (SC-003); todos los montos son `DECIMAL`/`decimal:2` (Principio V)

**Scale/Scope**: Mismo orden de magnitud que `Contrato`/`Locacion` (cientos a pocos miles de registros), consistente con specs previas

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|---|---|
| I. Stack Tecnológico Moderno (PHP/Laravel/PostgreSQL) | ✅ Migraciones Eloquent, `NUMERIC`/`decimal:2` para todos los montos, Form Requests, Service desacoplado (`ServicioNotificacionVencimientoContrato`), comando Artisan + scheduler nativo, sin SQL crudo. ⚠️ Misma nota de discrepancia de versión de Laravel que `specs/001` (preexistente) |
| II. Nomenclatura en Español | ✅ `ConfiguracionGeneral`, `Recibo`, columnas `costo_agua`/`costo_luz`/`costo_pasadizo`/`costo_seguridad`/`notificado_30_dias_en`/etc., `ReciboController`, `ConfiguracionGeneralController`, `ServicioNotificacionVencimientoContrato`, `SolicitudGuardarRecibo`, `SolicitudActualizarConfiguracionGeneral`, comando `contratos:verificar-vencimientos` |
| III. Diseño Moderno e Intuitivo | ✅ Formulario de recibo precargado con campos editables, botones "Guardar Costos del Contrato"/"Emitir Recibo" claros, pantalla de configuración general accesible solo a usuarios autenticados (ver `research.md` §5 sobre roles) |
| IV. Pruebas Automatizadas Exhaustivas | ✅ Pest cubre modelo `Contrato` (nuevos costos, reinicio de hitos), `Recibo` (precarga, independencia de valores históricos), `ConfiguracionGeneral` (singleton), `ServicioNotificacionVencimientoContrato` (hitos, no-duplicación, `Mail::fake()`), `ReciboController`/`ConfiguracionGeneralController` (happy path, validación, códigos HTTP) |
| V. Integridad de Datos y Seguridad Transaccional | ✅ `DB::transaction` en creación/edición de recibo y en actualización de costos del contrato; `decimal:2` en todos los montos; marcas de hitos actualizadas dentro de la misma transacción que el envío de correo para evitar reenvíos por fallos parciales (ver `research.md` §3) |

**Resultado**: PASS. No se identifican violaciones que requieran justificación en `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/004-condiciones-contrato-recibo/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── rutas-condiciones-contrato-recibo.md
└── tasks.md
```

### Source Code (repository root)

```text
app/
├── Console/
│   └── Commands/
│       └── VerificarVencimientosContratos.php   # Nuevo: comando artisan `contratos:verificar-vencimientos`
├── Mail/
│   └── ContratoProximoAVencer.php               # Nuevo: Mailable con datos de locación/inquilino/fecha de fin
├── Models/
│   ├── Contrato.php                             # Se agregan costos fijos, hitos de notificación, relación recibos()
│   ├── ConfiguracionGeneral.php                 # Nuevo: modelo singleton (helper estático actual())
│   └── Recibo.php                               # Nuevo
├── Http/
│   ├── Controllers/
│   │   ├── ReciboController.php                 # Nuevo: precarga desde Contrato, creación con montos editables
│   │   └── ConfiguracionGeneralController.php   # Nuevo: edit/update del correo administrativo
│   └── Requests/
│       ├── SolicitudGuardarCostosContrato.php    # Nuevo: extiende validación de costos fijos (se integra a SolicitudGuardarContrato existente, ver research.md §1)
│       ├── SolicitudGuardarRecibo.php             # Nuevo
│       └── SolicitudActualizarConfiguracionGeneral.php   # Nuevo
└── Services/
    └── ServicioNotificacionVencimientoContrato.php   # Nuevo: determina hitos pendientes, envía correo, marca notificado_X_dias_en dentro de DB::transaction

database/
├── migrations/
│   ├── xxxx_add_costos_y_notificaciones_to_contratos_table.php   # Nuevo (ALTER contratos)
│   ├── xxxx_create_configuracion_general_table.php                # Nuevo
│   └── xxxx_create_recibos_table.php                              # Nuevo
└── factories/
    ├── ReciboFactory.php                        # Nuevo
    └── ConfiguracionGeneralFactory.php           # Nuevo

resources/
└── views/
    ├── configuracion/
    │   └── edit.blade.php                        # Nuevo: pantalla de configuración general
    └── contratos/
        └── recibos/
            ├── create.blade.php                   # Nuevo: formulario de emisión con montos precargados y editables
            ├── show.blade.php                     # Nuevo: detalle de un recibo emitido
            └── index.blade.php                    # Nuevo: historial de recibos de un contrato (US3)

routes/
├── web.php                                       # Se añaden rutas de recibos y configuración general
└── console.php                                   # Se agrega `Schedule::command('contratos:verificar-vencimientos')->daily()`

tests/
├── Feature/
│   ├── ReciboControllerTest.php                  # Nuevo
│   ├── ConfiguracionGeneralControllerTest.php     # Nuevo
│   └── VerificarVencimientosContratosTest.php     # Nuevo: prueba del comando artisan de punta a punta con Mail::fake()
└── Unit/
    ├── ReciboTest.php                             # Nuevo
    ├── ConfiguracionGeneralTest.php                # Nuevo
    └── ServicioNotificacionVencimientoContratoTest.php   # Nuevo
```

**Structure Decision**: Aplicación Laravel monolítica única. Esta feature extiende `Contrato` (de `specs/002`) con columnas nuevas (sin tabla adicional para los costos, dado que son propiedades 1-a-1 del contrato) e introduce dos entidades nuevas (`ConfiguracionGeneral`, `Recibo`) que sientan la base que las specs 005-008 extenderán incrementalmente (ver `research.md` §2 y §4 para las notas de extensibilidad explícitas).

## Complexity Tracking

*No violations identified — table intentionally left empty.*
