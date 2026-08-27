# Implementation Plan: Barra de Progreso de Pagos

**Branch**: `034-barra-progreso-pagos` | **Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/034-barra-progreso-pagos/spec.md`

## Summary

Agregar un refuerzo visual (barra de progreso Bootstrap, `progress`/`progress-bar`) junto al detalle
numérico de avance de pago ya existente, en los dos lugares donde ya se muestra en texto: la fila de cada
locación en "Registro de Pagos" (specs/033) y la sección de Pagos del detalle de un recibo (specs/032). Se
implementa como un único componente Blade reutilizable que calcula su propio porcentaje y color a partir de
`montoPagado`/`montoTotal` — los mismos dos números que el texto ya muestra — para que nunca pueda
desincronizarse de él (FR-004/SC-003 por construcción, no por sincronización manual).

## Technical Context

**Language/Version**: PHP 8.2+, Laravel 11.x

**Primary Dependencies**: Blade (componente anónimo nuevo), Bootstrap 5.3 (componente `progress` nativo,
primer uso en el proyecto), Pest

**Storage**: PostgreSQL — sin cambios de esquema (no se agregan entidades, columnas ni cálculos nuevos; solo
representación visual de `Recibo::montoPagado()`/`total()`, ya existentes desde specs/032)

**Testing**: Pest (Feature tests), binario de PHP de Herd

**Target Platform**: Aplicación web Laravel, dominio de desarrollo `rent-tracker-sdd.test`

**Project Type**: Web (Laravel monolito con Blade)

**Performance Goals**: N/A — no agrega consultas; reutiliza valores ya calculados en cada vista.

**Constraints**: La barra nunca debe mostrar un avance distinto al que ya muestra el texto adyacente
(FR-004, FR-006).

**Scale/Scope**: 1 componente Blade nuevo (`x-barra-progreso-pago`), usado en 2 vistas ya existentes; sin
controladores, rutas, modelos ni migraciones nuevas.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno**: Cumple — no se toca la capa de datos.
- **II. Nomenclatura en Español**: Cumple — el componente se llama `barra-progreso-pago` (`<x-barra-progreso-pago>`), sus props (`montoPagado`, `montoTotal`) en español/camelCase consistente con el resto del proyecto.
- **III. Diseño Moderno e Intuitivo**: Cumple — refuerzo visual adicional a un dato que ya existe en texto, sin quitar ni oscurecer ese texto (FR-006).
- **IV. Pruebas Automatizadas Exhaustivas**: Cumple — se agregan tests de Feature para las 3 franjas de avance (vacío/parcial/completo) en ambos lugares, y para la ausencia de barra cuando no hay recibo vigente.
- **V. Integridad de Datos**: N/A — sin escritura de datos nueva.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: Cumple — usa el componente nativo `progress`/`progress-bar` de Bootstrap 5 (primer uso en el proyecto, pero un primitivo estándar de la librería, no un componente casero), con `role="progressbar"` y los atributos `aria-valuenow/aria-valuemin/aria-valuemax` que exige. El color de la barra reutiliza exactamente los mismos 3 colores semánticos que ya usan los badges de estado (`bg-secondary`/`bg-warning`/`bg-success`), cumpliendo la regla de "mismo color para el mismo concepto de estado" (FR-007). Pasa por revisión con el skill `impeccable` antes de completarse.

Sin violaciones. No se requiere `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/034-barra-progreso-pagos/
├── plan.md              # Este archivo
├── research.md          # Fase 0
├── quickstart.md         # Fase 1 (validación manual)
└── tasks.md              # Fase 2 (/speckit-tasks)
```

Sin `data-model.md` ni `contracts/`: no hay entidades, columnas ni rutas nuevas.

### Source Code (repository root)

```text
resources/views/components/barra-progreso-pago.blade.php                         # NUEVO
resources/views/pagos/seguimiento/partials/estado-pago-locacion.blade.php          # + <x-barra-progreso-pago>
resources/views/locaciones/recibos/show.blade.php                                  # + <x-barra-progreso-pago>
resources/css/bootstrap.scss                                                       # + estilos mínimos si el layout de grid lo requiere
tests/Feature/SeguimientoPagosControllerTest.php                                   # + tests US1
tests/Feature/ReciboControllerTest.php (o el test de show ya existente)            # + tests US2
```

**Structure Decision**: Un componente Blade nuevo, reutilizado en 2 vistas ya existentes. Sin
controladores, rutas, modelos ni migraciones nuevas — toda la lógica de datos que el componente necesita
(`montoPagado`, `montoTotal`) ya llega a ambas vistas como variables/props desde specs/032 y specs/033.

## Complexity Tracking

*(vacío — sin violaciones que justificar)*
