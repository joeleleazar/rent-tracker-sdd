# Implementation Plan: Simplificar Navegación de Periodo en Registro Masivo de Lecturas

**Branch**: `027-simplificar-navegacion-periodo` | **Date**: 2026-08-26 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/027-simplificar-navegacion-periodo/spec.md`

## Summary

Retira el botón "Ir" del selector de periodo en `/lecturas/registro-masivo`, dejando la navegación limitada a las flechas anterior/siguiente y al autoenvío del campo de fecha vía htmx — una simplificación de interfaz decidida explícitamente por el usuario tras evaluar el trade-off frente al fallback de degradación elegante sin JavaScript que ese botón cubría.

## Technical Context

**Language/Version**: PHP 8.3+ (Laravel), Blade + htmx

**Primary Dependencies**: htmx (ya usado en la vista), Pest (suite existente)

**Storage**: N/A — sin cambios de datos

**Testing**: Pest — ajustar `tests/Feature/RegistroMasivoLecturasControllerTest.php`

**Target Platform**: Web (misma aplicación existente)

**Project Type**: Web application (single Laravel app)

**Performance Goals**: N/A

**Constraints**: Cero cambio de comportamiento en flechas y autoenvío del campo de fecha (FR-002/FR-003); el envío del periodo debe seguir aislado de tarifa/exportar (FR-004)

**Scale/Scope**: 1 vista Blade, 1 archivo de test

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Principio I (Stack)**: Sin cambios de stack. PASA.
- **Principio II (Español)**: Sin nombres nuevos. PASA.
- **Principio III (Diseño Moderno)**: Simplifica la interfaz eliminando un control redundante en el uso normal (con JS activo) — coherente con priorizar fluidez (Principio III ya prioriza fluidez sobre pasos adicionales para interacciones no destructivas). PASA.
- **Principio IV (Pruebas Exhaustivas)**: Se ajusta la prueba existente que exigía el botón "Ir" para reflejar el nuevo comportamiento (TDD: primero la prueba nueva, luego el cambio de vista). PASA.
- **Principio V (Integridad de Datos)**: Sin cambios a transacciones ni datos. PASA.
- **Principio VI (Bootstrap 5 / htmx / impeccable)**: Se modifica una vista Blade (`lecturas/registro-masivo/index.blade.php`) — DEBE pasar por revisión `impeccable` (`polish` o `audit`) antes de cerrar la tarea, documentando en `DESIGN.md` si corresponde. PASA, condicionado a esa revisión dentro de este mismo feature.

Sin violaciones. No aplica Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/027-simplificar-navegacion-periodo/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command) — N/A, se omite (sin entidades)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
resources/views/lecturas/registro-masivo/index.blade.php   # FR-001/FR-004 — quitar botón "Ir", ajustar comentario
tests/Feature/RegistroMasivoLecturasControllerTest.php      # FR-005 — reemplazar la prueba del botón "Ir"
```

**Structure Decision**: Cambio acotado a 1 vista + 1 archivo de test, dentro de la misma aplicación Laravel única.

## Complexity Tracking

*Sin violaciones que justificar.*
