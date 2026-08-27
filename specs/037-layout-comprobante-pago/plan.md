# Implementation Plan: Más Espacio para Firmar y Aprovechamiento Horizontal en el Comprobante de Pago

**Branch**: `037-layout-comprobante-pago` | **Date**: 2026-08-27 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/037-layout-comprobante-pago/spec.md`

## Summary

Rediseñar el layout de `pagos/comprobante.blade.php` (specs/035, ajustado en specs/036): ensanchar el
documento y dividirlo en dos columnas Bootstrap (`row`/`col-md-7`/`col-md-5`) — la columna principal
conserva todo el contenido ya existente con un bloque de firma con mucho más espacio en blanco, y la nueva
columna lateral muestra la lista completa de los pagos del recibo, marcando cuál de ellos es este
comprobante. En pantallas angostas, el grid de Bootstrap ya apila las columnas automáticamente. Sin cambios
de datos ni de cálculo (FR-006) — `$recibo->pagos` ya llega cargado al controlador desde specs/035.

## Technical Context

**Language/Version**: PHP 8.2+, Laravel 11.x

**Primary Dependencies**: Blade, Bootstrap 5.3 (grid `row`/`col-md-*`, ya usado en el resto del proyecto)

**Storage**: PostgreSQL — sin cambios (solo se itera `$recibo->pagos`, ya cargado por
`PagoReciboController::comprobante()` desde specs/035)

**Testing**: Pest (Feature tests), binario de PHP de Herd

**Target Platform**: Aplicación web Laravel, dominio de desarrollo `rent-tracker-sdd.test`

**Project Type**: Web (Laravel monolito con Blade)

**Performance Goals**: N/A — sin consultas nuevas.

**Constraints**: El resto del contenido del comprobante (monto de este pago, avance histórico de specs/036,
metadatos, partes) no cambia de significado ni de cálculo (FR-006) — solo su disposición visual.

**Scale/Scope**: 1 vista modificada (`pagos/comprobante.blade.php`). Sin controladores, rutas, modelos ni
migraciones nuevas — `recibo.pagos` ya viaja cargado.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I-V**: N/A / Cumple — sin cambios de datos, backend ni transacciones.
- **III. Diseño Moderno e Intuitivo**: Cumple — el pago identificado dentro de la lista se distingue por
  color **y** por una etiqueta de texto explícita ("Este pago"), no solo por color, consistente con el
  principio de no depender únicamente del color para comunicar estado.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: Cumple — usa el grid nativo `row`/`col-md-*` (ya
  usado en todo el proyecto) y el componente `badge` (`bg-primary`) para marcar "Este pago", el mismo
  patrón semántico ya usado para otros estados. Pasa por revisión con el skill `impeccable` antes de
  completarse (Principio VI, sin la excepción de specs/031 — esta vista ya usa Bootstrap real desde
  specs/035).

Sin violaciones. No se requiere `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/037-layout-comprobante-pago/
├── plan.md              # Este archivo
├── research.md          # Fase 0
├── quickstart.md         # Fase 1 (validación manual)
└── tasks.md              # Fase 2 (/speckit-tasks)
```

Sin `data-model.md` ni `contracts/`: no hay entidades, columnas ni rutas nuevas — se reordena contenido ya
disponible en la misma vista.

### Source Code (repository root)

```text
resources/views/pagos/comprobante.blade.php          # rediseño de layout (2 columnas, firma ampliada, lista de pagos)
tests/Feature/ComprobantePagoControllerTest.php        # + tests de la lista de pagos y el marcado del pago actual
```

**Structure Decision**: Cambio de layout puro sobre 1 vista ya existente. Sin controladores, rutas, modelos
ni migraciones nuevas.

## Complexity Tracking

*(vacío — sin violaciones que justificar)*
