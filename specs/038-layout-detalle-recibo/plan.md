# Implementation Plan: Distribución en Dos Columnas del Detalle de Recibo

**Branch**: `038-layout-detalle-recibo` | **Date**: 2026-08-27 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/038-layout-detalle-recibo/spec.md`

## Summary

Reestructurar `locaciones/recibos/show.blade.php` en un `row` de Bootstrap de dos columnas: `col-lg-7` con
la tarjeta de resumen del recibo (contenido y acciones ya existentes, sin cambios), `col-lg-5` con la
tarjeta de Pagos (condicional, specs/032/034/035) seguida de la tarjeta de Estado del Recibo. Se apila
automáticamente por debajo de `lg` (992px) — mismo mecanismo responsive ya usado en todo el proyecto. Sin
cambios de datos, rutas ni comportamiento (FR-005).

## Technical Context

**Language/Version**: PHP 8.2+, Laravel 11.x

**Primary Dependencies**: Blade, Bootstrap 5.3 (grid `row`/`col-lg-*`, ya usado en todo el proyecto)

**Storage**: PostgreSQL — sin cambios.

**Testing**: Pest (Feature tests), binario de PHP de Herd

**Target Platform**: Aplicación web Laravel, dominio de desarrollo `rent-tracker-sdd.test`

**Project Type**: Web (Laravel monolito con Blade)

**Performance Goals**: N/A — sin consultas nuevas, mismas variables ya pasadas por `ReciboController::show()`.

**Constraints**: Ningún dato, cálculo ni acción cambia de comportamiento (FR-005) — solo la disposición
visual del contenido ya existente.

**Scale/Scope**: 1 vista modificada (`locaciones/recibos/show.blade.php`). Sin controladores, rutas, modelos
ni migraciones nuevas.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I-V**: N/A / Cumple — sin cambios de datos, backend ni transacciones.
- **III. Diseño Moderno e Intuitivo**: Cumple — se conserva toda la jerarquía y los textos ya existentes;
  el único cambio es la disposición espacial.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: Cumple — usa el grid nativo `row`/`col-lg-*`, sin
  introducir ningún componente nuevo. Se descarta deliberadamente reproducir los elementos decorativos de la
  imagen de referencia que violarían este principio (etiquetas en mayúsculas, chips con ícono, menú de solo
  ícono) — ver spec.md, sección Assumptions. Pasa por revisión con el skill `impeccable` antes de
  completarse.

Sin violaciones. No se requiere `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/038-layout-detalle-recibo/
├── plan.md              # Este archivo
├── research.md          # Fase 0
├── quickstart.md         # Fase 1 (validación manual)
└── tasks.md              # Fase 2 (/speckit-tasks)
```

Sin `data-model.md` ni `contracts/`: no hay entidades, columnas ni rutas nuevas — se reordena contenido ya
disponible en la misma vista.

### Source Code (repository root)

```text
resources/views/locaciones/recibos/show.blade.php     # rediseño de layout (2 columnas)
tests/Feature/ReciboControllerTest.php                  # + test de estructura de columnas (si aplica)
```

**Structure Decision**: Cambio de layout puro sobre 1 vista ya existente. Sin controladores, rutas, modelos
ni migraciones nuevas.

## Complexity Tracking

*(vacío — sin violaciones que justificar)*
