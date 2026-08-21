# Implementation Plan: Registro de Garantía Entregada por Contrato

**Branch**: `009-garantia-contrato` | **Date**: 2026-08-20 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/009-garantia-contrato/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Agregar a `Contrato` (de `specs/002`) el registro de la garantía entregada por el inquilino (monto, fecha de entrega, medio de entrega) y de su resolución al finalizar el contrato (monto devuelto, monto retenido, motivo de retención obligatorio si hay retención, fecha de resolución), validando que la suma de devuelto + retenido coincida exactamente con el monto entregado, y exigiendo confirmación explícita antes de corregir una resolución ya registrada. Enfoque técnico: migración de alteración sobre `contratos` (mismo patrón 1-a-1 usado para los costos fijos de `specs/004`) y un `ServicioResolucionGarantiaContrato` que centraliza la validación de cuadre exacto y el motivo obligatorio dentro de `DB::transaction`.

## Technical Context

**Language/Version**: PHP 8.3+ (`composer.json`)

**Primary Dependencies**: Laravel 13.x (misma nota de discrepancia que `specs/001` §1), Eloquent ORM, Blade, Pest 4

**Storage**: PostgreSQL; se altera `contratos` (de `specs/002`, ya extendida por `specs/004`)

**Testing**: Pest, `RefreshDatabase`; unit tests para el modelo `Contrato` (garantía, cuadre de montos) y `ServicioResolucionGarantiaContrato`; feature tests para las acciones de garantía en `ContratoController`

**Target Platform**: Servidor Linux de shared hosting, consistente con specs previas

**Project Type**: Aplicación web monolítica (single project)

**Performance Goals**: Registro de garantía completado en <1 minuto adicional al registro del contrato (SC-001)

**Constraints**: La suma de `monto_devuelto_garantia` + `monto_retenido_garantia` MUST ser exactamente igual a `monto_garantia` (FR-008); `motivo_retencion_garantia` es obligatorio si `monto_retenido_garantia > 0` (FR-007); un contrato tiene como máximo un registro de garantía, sin cuotas ni historial de cambios (A-001, A-003)

**Scale/Scope**: Mismo volumen que `Contrato` (uno por contrato, opcional)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|---|---|
| I. Stack Tecnológico Moderno (PHP/Laravel/PostgreSQL) | ✅ Migración Eloquent, `NUMERIC`/`decimal:2` para todos los montos, `enum` para `medio_entrega_garantia`/`estado_garantia` (mismo patrón `varchar`+`CHECK` que `contratos.estado`), Service desacoplado, sin SQL crudo. ⚠️ Misma nota de discrepancia de versión de Laravel que `specs/001` (preexistente) |
| II. Nomenclatura en Español | ✅ Columnas `monto_garantia`/`fecha_entrega_garantia`/`medio_entrega_garantia`/`estado_garantia`/`monto_devuelto_garantia`/`monto_retenido_garantia`/`motivo_retencion_garantia`/`fecha_resolucion_garantia`, `ServicioResolucionGarantiaContrato`, `SolicitudRegistrarResolucionGarantia` |
| III. Accesibilidad Senior-First | ✅ Etiquetas explícitas "Monto de Garantía Entregada"/"Fecha de Entrega de Garantía"/"Registrar Resolución de Garantía", mensaje "Sin garantía registrada" en vez de campo vacío ambiguo, confirmación explícita de alta visibilidad antes de corregir una resolución ya registrada (FR-010) |
| IV. Pruebas Automatizadas Exhaustivas | ✅ Pest cubre modelo `Contrato` (garantía opcional, "sin garantía" con monto 0, cuadre exacto), `ServicioResolucionGarantiaContrato` (motivo obligatorio con retención, bloqueo si la suma no cuadra, confirmación de re-edición), `ContratoController` (happy path, 422 en discrepancia de montos, 422 sin motivo) |
| V. Integridad de Datos y Seguridad Transaccional | ✅ `DB::transaction` en el registro de garantía y de su resolución (junto con el resto de datos del contrato, FR-011); `decimal:2` en todos los montos; validación de cuadre exacto antes del commit, no después |

**Resultado**: PASS. No se identifican violaciones que requieran justificación en `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/009-garantia-contrato/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── rutas-garantia-contrato.md
└── tasks.md
```

### Source Code (repository root)

```text
app/
├── Models/
│   └── Contrato.php                          # Se agregan campos de garantía y su resolución, helper tieneGarantia()
├── Http/
│   ├── Controllers/
│   │   └── ContratoController.php             # Se agrega acción registrarResolucionGarantia()
│   └── Requests/
│       └── SolicitudRegistrarResolucionGarantia.php   # Nuevo
└── Services/
    └── ServicioResolucionGarantiaContrato.php  # Nuevo: valida cuadre exacto, motivo obligatorio, DB::transaction

database/
└── migrations/
    └── xxxx_add_garantia_to_contratos_table.php   # Nuevo (ALTER contratos)

resources/
└── views/
    └── contratos/
        ├── create.blade.php                   # Se agregan campos de garantía (monto, fecha, medio)
        ├── edit.blade.php                     # Ídem
        └── show.blade.php                     # Se agrega sección de garantía + formulario de resolución con confirmación de re-edición

routes/
└── web.php                                    # Se agrega ruta de registro/edición de resolución de garantía

tests/
├── Feature/
│   └── ContratoControllerTest.php             # Se extiende (de specs/002/004): garantía y su resolución
└── Unit/
    ├── ContratoTest.php                       # Se extiende: garantía opcional, "sin garantía" con monto 0
    └── ServicioResolucionGarantiaContratoTest.php   # Nuevo
```

**Structure Decision**: Aplicación Laravel monolítica única. Esta feature extiende `Contrato` (de `specs/002`, ya extendida por `specs/004` con costos fijos) con columnas de garantía 1-a-1, sin tablas ni entidades nuevas, siguiendo el mismo patrón ya usado en `specs/004/research.md` §1 para los costos fijos del contrato.

## Complexity Tracking

*No violations identified — table intentionally left empty.*
