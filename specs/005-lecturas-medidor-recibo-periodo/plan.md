# Implementation Plan: Lecturas de Medidor de Luz y Recibo por Periodo

**Branch**: `005-lecturas-medidor-recibo-periodo` | **Date**: 2026-08-20 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/005-lecturas-medidor-recibo-periodo/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Registrar por locación la lectura mensual de su medidor de luz (con cálculo automático de consumo respecto al periodo anterior) e introducir la posibilidad de generar el recibo del periodo con conceptos seleccionables (alquiler, luz calculada por consumo, agua, seguridad, pasadizo), bloqueando la generación si la locación no tiene contrato activo vigente en ese periodo y evitando duplicar recibos o lecturas del mismo periodo. Enfoque técnico: nueva entidad `LecturaMedidor` (1 fila por locación+periodo); se extiende `Recibo` (de `specs/004`) con `locacion_id` (denormalizado), `lectura_medidor_id` y los booleanos `incluye_*`; el punto de entrada de generación de recibo pasa de ser contrato-céntrico (004) a locación-céntrico, resolviendo internamente el contrato activo del periodo (ver `research.md` §1, reconciliación necesaria con las rutas de 004).

## Technical Context

**Language/Version**: PHP 8.3+ (`composer.json`)

**Primary Dependencies**: Laravel 13.x (misma nota de discrepancia que `specs/001` §1), Eloquent ORM, Blade, Pest 4

**Storage**: PostgreSQL; se crea `lecturas_medidor`, se altera `recibos` (de `specs/004`) y se altera `configuracion_general` (de `specs/004`) agregando `tarifa_luz_por_unidad`

**Testing**: Pest, `RefreshDatabase`; feature tests para `LecturaMedidorController` y el flujo locación-céntrico de `ReciboController`; unit tests para el modelo `LecturaMedidor` (cálculo de consumo) y el cálculo de monto de luz sugerido

**Target Platform**: Servidor Linux de shared hosting, consistente con specs previas

**Project Type**: Aplicación web monolítica (single project)

**Performance Goals**: Cálculo y visualización de consumo en <2s tras guardar (SC-001); monto sugerido de luz calculado y mostrado en <2s al iniciar la generación del recibo (SC-005)

**Constraints**: Una única lectura por locación y periodo (FR-003); un único recibo por locación y periodo (FR-009); no se permite generar recibo sin contrato activo vigente en el periodo, pero sí registrar la lectura de forma independiente (FR-008); los recibos ya emitidos no se recalculan si cambia la tarifa de luz posteriormente (A-006, mismo principio de inmutabilidad histórica que `specs/004`)

**Scale/Scope**: Una lectura y (opcionalmente) un recibo por locación y por mes; mismo orden de magnitud que `Locacion`/`Contrato`

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|---|---|
| I. Stack Tecnológico Moderno (PHP/Laravel/PostgreSQL) | ✅ Migraciones Eloquent, `NUMERIC`/`decimal:2` para lecturas y montos, Form Requests, Service desacoplado (`ServicioCalculoConsumoMedidor`, `ServicioGeneracionReciboPeriodo`), sin SQL crudo. ⚠️ Misma nota de discrepancia de versión de Laravel que `specs/001` (preexistente) |
| II. Nomenclatura en Español | ✅ `LecturaMedidor`, columnas `locacion_id`/`periodo`/`lectura`/`consumo_calculado`/`fecha_registro`, `LecturaMedidorController`, `ServicioCalculoConsumoMedidor`, `ServicioGeneracionReciboPeriodo`, `SolicitudGuardarLecturaMedidor` |
| III. Diseño Moderno e Intuitivo | ✅ Casillas claras para incluir/excluir conceptos, etiquetas explícitas "Guardar Lectura del Medidor"/"Emitir Recibo del Periodo", advertencia de alto contraste ante lectura menor a la anterior (Edge Case) |
| IV. Pruebas Automatizadas Exhaustivas | ✅ Pest cubre modelo `LecturaMedidor` (cálculo de consumo, "sin dato anterior", unicidad por periodo), `ServicioGeneracionReciboPeriodo` (bloqueo sin contrato activo, no-duplicación, conceptos seleccionables), `LecturaMedidorController`/`ReciboController` (happy path, validación, códigos HTTP) |
| V. Integridad de Datos y Seguridad Transaccional | ✅ `DB::transaction` en guardado de lectura y en generación/edición de recibo; `decimal:2` en `lectura`/`consumo_calculado`/montos; unicidad `(locacion_id, periodo)` reforzada con índice `UNIQUE` de base de datos, no solo validación de aplicación |

**Resultado**: PASS. No se identifican violaciones que requieran justificación en `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/005-lecturas-medidor-recibo-periodo/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── rutas-lecturas-medidor-recibo-periodo.md
└── tasks.md
```

### Source Code (repository root)

```text
app/
├── Models/
│   ├── LecturaMedidor.php                    # Nuevo
│   ├── Locacion.php                          # Se agrega relación lecturasMedidor(): HasMany
│   ├── Contrato.php                          # Se agrega scope/helper activoEnPeriodo()
│   ├── Recibo.php                            # Se agregan locacion_id, lectura_medidor_id, incluye_* (de specs/004)
│   └── ConfiguracionGeneral.php              # Se agrega tarifa_luz_por_unidad (de specs/004)
├── Http/
│   ├── Controllers/
│   │   ├── LecturaMedidorController.php      # Nuevo
│   │   └── ReciboController.php              # Se reubica a locación-céntrico (de specs/004, ver research.md §1)
│   └── Requests/
│       ├── SolicitudGuardarLecturaMedidor.php   # Nuevo
│       └── SolicitudGuardarRecibo.php            # Se extiende (de specs/004) con conceptos incluye_*
└── Services/
    ├── ServicioCalculoConsumoMedidor.php      # Nuevo: cálculo de consumo, detección de lectura menor a la anterior
    └── ServicioGeneracionReciboPeriodo.php    # Nuevo: resuelve contrato activo del periodo, arma conceptos, bloquea duplicados

database/
├── migrations/
│   ├── xxxx_create_lecturas_medidor_table.php                     # Nuevo
│   ├── xxxx_add_periodo_conceptos_to_recibos_table.php            # Nuevo (ALTER recibos, de specs/004)
│   └── xxxx_add_tarifa_luz_to_configuracion_general_table.php      # Nuevo (ALTER configuracion_general, de specs/004)
└── factories/
    └── LecturaMedidorFactory.php              # Nuevo

resources/
└── views/
    └── locaciones/
        ├── lecturas/
        │   ├── create.blade.php               # Nuevo: formulario de lectura del periodo
        │   └── index.blade.php                # Nuevo: historial de lecturas y recibos por locación (US3)
        └── recibos/
            └── create.blade.php                # Nuevo: formulario de recibo con casillas de conceptos (reemplaza contratos/recibos/create.blade.php de 004, ver research.md §1)

routes/
└── web.php                                    # Rutas de lecturas y recibo por locación+periodo (ver research.md §1)

tests/
├── Feature/
│   ├── LecturaMedidorControllerTest.php       # Nuevo
│   └── ReciboControllerTest.php               # Se extiende (de specs/004) con flujo locación-céntrico
└── Unit/
    ├── LecturaMedidorTest.php                 # Nuevo
    ├── ServicioCalculoConsumoMedidorTest.php  # Nuevo
    └── ServicioGeneracionReciboPeriodoTest.php   # Nuevo
```

**Structure Decision**: Aplicación Laravel monolítica única. Esta feature introduce `LecturaMedidor` como entidad nueva anclada a `Locacion`, y reubica el punto de entrada de generación de `Recibo` (introducido en `specs/004`) de contrato-céntrico a locación-céntrico, porque el historial de una locación abarca múltiples contratos sucesivos en el tiempo (ver `research.md` §1 para la justificación completa de esta reconciliación).

## Complexity Tracking

*No violations identified — table intentionally left empty.*
