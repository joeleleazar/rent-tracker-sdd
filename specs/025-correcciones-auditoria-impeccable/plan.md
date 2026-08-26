# Implementation Plan: Correcciones de Auditoría Impeccable

**Branch**: `025-correcciones-auditoria-impeccable` | **Date**: 2026-08-26 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/025-correcciones-auditoria-impeccable/spec.md`

## Summary

Corrige un bug de validación P0 (el formulario de editar locación no puede guardarse para ninguna locación sin `tipo` asignado — 8 de 8 en la base de demo) sin afectar la exigencia de `tipo` al crear; consolida el color/dimensiones del sidebar de `app-bootstrap.blade.php` en `bootstrap.scss` usando el token `$dark` ya existente, eliminando el `<style>` embebido duplicado; y cierra formalmente la revisión de diseño pendiente (Principio VI) sobre las 3 vistas que la motivaron.

## Technical Context

**Language/Version**: PHP 8.3+ (Laravel 11.x, ver `composer.json` para la versión exacta instalada)

**Primary Dependencies**: Laravel Framework, Eloquent Form Requests, Pest (suite existente en `tests/Feature/LocacionControllerTest.php`)

**Storage**: PostgreSQL — sin cambios de esquema; `locaciones.tipo` ya es nullable (ver `2026_08_23_020000_add_tipo_to_locaciones_table.php`)

**Testing**: Pest, suite existente de `LocacionControllerTest.php` como referencia de comportamiento a preservar

**Target Platform**: Aplicación web Laravel monolítica (sin cambios de plataforma)

**Project Type**: Web application (single Laravel app)

**Performance Goals**: N/A — corrección de validación y consolidación de CSS, sin impacto de rendimiento esperado

**Constraints**: Cero cambio de comportamiento en la creación de una locación nueva (FR-003); cero cambio visual perceptible del sidebar (FR-006)

**Scale/Scope**: 1 Form Request, 1 vista de layout, 1 hoja de estilos, actualización de `DESIGN.md`/sidecar vía `/impeccable document` o similar al cierre

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Principio I (Stack)**: Sin cambios de stack ni SQL directo fuera del ORM; la corrección es una regla de validación condicional en un Form Request ya existente. PASA.
- **Principio II (Español)**: Ningún nombre nuevo (se ajusta una regla dentro de una clase ya nombrada en español). PASA.
- **Principio III (Diseño Moderno)**: Sin cambios de patrones de interacción; el formulario de edición se ve y se comporta igual, solo deja de bloquear un caso que no debía bloquear. PASA.
- **Principio IV (Pruebas Exhaustivas)**: Se agregan casos de prueba nuevos para el escenario de edición sin tipo (locación con tipo null que guarda sin tipo; locación con tipo que no puede vaciarlo; creación que sigue exigiendo tipo) antes de tocar la regla de validación (TDD). PASA.
- **Principio V (Integridad de Datos)**: Sin cambios a transacciones ni tipos numéricos. PASA.
- **Principio VI (Bootstrap 5 / htmx / impeccable)**: Esta feature existe precisamente para cerrar una revisión `impeccable` pendiente sobre `app-bootstrap.blade.php`, `error-modal-recibo.blade.php` y `estado-recibo-locacion.blade.php`, y para corregir el hallazgo P2 (theming) que la misma auditoría encontró. La corrección de `app-bootstrap.blade.php` (eliminar el `<style>` embebido) SÍ modifica una vista Blade, por lo que — antes de cerrar la tarea — DEBE pasar de nuevo por `/impeccable polish` o `audit` para confirmar que la consolidación no introdujo un defecto nuevo, y su resultado se documenta en `DESIGN.md`. PASA, condicionado a esa revisión final dentro de este mismo feature (no es circular: la revisión inicial ya identificó los defectos; esta es la revisión de cierre que confirma la corrección).

Sin violaciones. No aplica Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/025-correcciones-auditoria-impeccable/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
app/Http/Requests/SolicitudGuardarLocacion.php   # FR-001/FR-002/FR-003 — regla condicional de "tipo"
resources/views/components/layouts/app-bootstrap.blade.php   # FR-004 — quitar <style> embebido
resources/css/bootstrap.scss                                  # FR-004/FR-005 — regla base de .sidebar-principal con $dark
DESIGN.md                                                      # FR-007 — cierre de revisión de las 3 vistas

tests/
└── Feature/
    └── LocacionControllerTest.php   # casos nuevos: editar sin tipo (locación sin tipo previo),
                                       # no permitir vaciar tipo ya asignado, crear sigue exigiendo tipo
```

**Structure Decision**: Cambios acotados a 3 archivos de código + 1 archivo de documentación de diseño, dentro de la misma aplicación Laravel única ya usada por el resto del proyecto — sin estructura nueva.

## Complexity Tracking

*Sin violaciones que justificar.*
