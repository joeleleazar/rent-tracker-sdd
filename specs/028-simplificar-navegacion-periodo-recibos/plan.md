# Implementation Plan: Simplificar Navegación de Periodo en Registro Masivo de Recibos

**Branch**: `028-simplificar-navegacion-periodo-recibos` | **Date**: 2026-08-26 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/028-simplificar-navegacion-periodo-recibos/spec.md`

## Summary

Aplica a `/recibos/registro-masivo` la misma simplificación de specs/027: retira el botón "Ir" del selector de periodo, dejando la navegación limitada a las flechas y al autoenvío del campo de fecha vía htmx.

## Technical Context

**Language/Version**: PHP 8.3+ (Laravel), Blade + htmx

**Primary Dependencies**: htmx, Pest

**Storage**: N/A

**Testing**: Pest — `tests/Feature/RegistroMasivoRecibosControllerTest.php`

**Target Platform**: Web (misma aplicación)

**Project Type**: Web application (single Laravel app)

**Constraints**: Cero cambio de comportamiento en flechas y autoenvío del campo de fecha (FR-002/FR-003)

**Scale/Scope**: 1 vista Blade (`recibos/registro-masivo/index.blade.php`), 1 archivo de test

## Constitution Check

- **Principio I-V**: Sin cambios de stack, nombres, datos ni transacciones. PASA.
- **Principio III**: Misma simplificación de interfaz ya aprobada en specs/027. PASA.
- **Principio IV**: Se agrega una prueba negativa antes de implementar (TDD). PASA.
- **Principio VI**: Modifica una vista Blade — requiere revisión `impeccable` de cierre antes de completar la tarea. PASA, condicionado a esa revisión dentro de este feature.

Sin violaciones.

## Project Structure

```text
resources/views/recibos/registro-masivo/index.blade.php   # FR-001 — quitar botón "Ir", ajustar comentario
tests/Feature/RegistroMasivoRecibosControllerTest.php       # FR-004 — agregar prueba de ausencia
```

**Structure Decision**: Cambio acotado a 1 vista + 1 archivo de test, réplica directa de specs/027.

## Complexity Tracking

*Sin violaciones que justificar.*
