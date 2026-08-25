# Implementation Plan: Jerarquía de Locaciones Alquilables

**Branch**: `001-jerarquia-locaciones` | **Date**: 2026-08-20 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-jerarquia-locaciones/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Permitir registrar locaciones (galerías, pisos, locales) con una jerarquía padre-hijo reflexiva, mostrando siempre el contexto jerárquico completo (truncado a 3 niveles) de cualquier locación marcada como alquilable, e impidiendo ciclos y eliminaciones que dejen locaciones huérfanas. Enfoque técnico: extender el modelo `Locacion` ya existente (creado como prerrequisito mínimo de `specs/002-gestion-contratos`) con validación de ciclos y bloqueo de borrado en un Service dentro de `DB::transaction`, más un `LocacionController` y vistas Blade con un componente de breadcrumb accesible.

## Technical Context

**Language/Version**: PHP 8.3+ (`composer.json` fija `"php": "^8.3"`)

**Primary Dependencies**: Laravel 13.x (versión instalada según `composer.json: "laravel/framework": "^13.17"`; ver nota de discrepancia en `research.md` §1), Eloquent ORM, Blade, Pest 4 (testing)

**Storage**: PostgreSQL 15+/16+ (relacional, vía Eloquent/migraciones); la tabla `locaciones` ya existe (migración `2026_08_20_031146_create_locaciones_table.php`) con clave foránea reflexiva `locacion_padre_id`

**Testing**: Pest (sobre PHPUnit), `RefreshDatabase` contra PostgreSQL de pruebas; feature tests HTTP para `LocacionController` y unit tests para el modelo `Locacion` y el servicio de validación de ciclos (Principio IV)

**Target Platform**: Servidor Linux de shared hosting (cPanel/Plesk típico), consistente con `specs/002-gestion-contratos/research.md` §2 (sin Redis, sin queue workers persistentes, sin Docker en producción)

**Project Type**: Aplicación web monolítica (single project) — Blade server-rendered, sin frontend SPA separado

**Performance Goals**: Renderizar la jerarquía completa de una locación en <200ms bajo 100 consultas simultáneas (SC-003)

**Constraints**: Profundidad de jerarquía ilimitada en base de datos, pero la UI trunca la visualización a los últimos 3 niveles (FR-004); sin extensiones de PostgreSQL no garantizadas en shared hosting como dependencia dura para la detección de ciclos (se resuelve en la capa de aplicación, no con CTEs recursivas nativas de PostgreSQL que dependan de extensiones adicionales)

**Scale/Scope**: Hasta ~1,000 locaciones por cliente (Asunción A-002 de esta especificación, ya referenciada por `specs/002-gestion-contratos`)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|---|---|
| I. Stack Tecnológico Moderno (PHP/Laravel/PostgreSQL) | ✅ PHP 8.3+, PostgreSQL 15+/16+, Eloquent ORM, migraciones, Form Requests, Service desacoplado (`ServicioValidacionJerarquiaLocacion`) para la lógica de ciclos; sin SQL crudo sin sanitizar. ⚠️ Nota: la Constitución fija "Laravel 11.x" como restricción explícita, pero el proyecto instalado usa Laravel 13.x — ver observación en `research.md` §1 (discrepancia preexistente, no introducida por este plan) |
| II. Nomenclatura en Español | ✅ Modelo `Locacion` (ya existente, español), tabla `locaciones`, columnas (`nombre`, `tamano`, `ubicacion_fisica`, `descripcion`, `locacion_padre_id`, `es_alquilable`) en español; nuevo `LocacionController`, `SolicitudGuardarLocacion`, `ServicioValidacionJerarquiaLocacion` en español con sufijos técnicos de Laravel en inglés por convención (igual criterio que `specs/002`) |
| III. Diseño Moderno e Intuitivo | ✅ Componente de breadcrumb (`resources/views/components/ruta-jerarquia-locacion.blade.php`) con contraste WCAG AA; confirmación explícita antes de bloquear/impedir eliminación de locaciones con hijos (FR-007) |
| IV. Pruebas Automatizadas Exhaustivas | ✅ Pest cubre modelo `Locacion` (relación reflexiva, scope de alquilables, detección de ciclos), `LocacionController` (happy path, validación, códigos HTTP, bloqueo de eliminación) — ver `quickstart.md` |
| V. Integridad de Datos y Seguridad Transaccional | ✅ `DB::transaction` para creación/edición y para la verificación de ciclos antes de guardar; `tamano` ya es `NUMERIC(10,2)`/`decimal:2` en la migración existente; bloqueo de eliminación con sub-locaciones asociadas a nivel de aplicación y de restricción de clave foránea (`nullOnDelete` ya definido, se reforzará con verificación previa en el Service) |

**Resultado**: PASS. La única observación (discrepancia de versión de Laravel respecto a la Constitución) es preexistente al proyecto completo y no constituye una violación introducida por esta feature; se documenta para que el usuario decida si actualiza la Constitución o fija la versión del framework.

## Project Structure

### Documentation (this feature)

```text
specs/001-jerarquia-locaciones/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md         # Phase 1 output (/speckit-plan command)
├── contracts/            # Phase 1 output (/speckit-plan command)
│   └── rutas-locacion.md
└── tasks.md              # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
app/
├── Models/
│   └── Locacion.php                       # Ya existe (prerrequisito de specs/002); se le añade el scope alquilables y helper de ancestros
├── Http/
│   ├── Controllers/
│   │   └── LocacionController.php         # Nuevo
│   └── Requests/
│       └── SolicitudGuardarLocacion.php   # Nuevo
└── Services/
    └── ServicioValidacionJerarquiaLocacion.php   # Nuevo: detección de ciclos + bloqueo de eliminación con hijos

database/
├── migrations/
│   └── 2026_08_20_031146_create_locaciones_table.php   # Ya existe, sin cambios de esquema previstos
└── factories/
    └── LocacionFactory.php                # Ya existe

resources/
└── views/
    ├── components/
    │   └── ruta-jerarquia-locacion.blade.php   # Nuevo: breadcrumb accesible truncado a 3 niveles (FR-004)
    └── locaciones/
        ├── index.blade.php                # Nuevo: listado filtrable por es_alquilable = true (FR-005)
        ├── create.blade.php               # Nuevo
        ├── edit.blade.php                 # Nuevo
        └── show.blade.php                 # Nuevo: detalle + breadcrumb completo

routes/
└── web.php                                # Se añaden rutas /locaciones, ver contracts/rutas-locacion.md

tests/
├── Feature/
│   └── LocacionControllerTest.php         # Nuevo
└── Unit/
    ├── LocacionTest.php                   # Nuevo
    └── ServicioValidacionJerarquiaLocacionTest.php   # Nuevo
```

**Structure Decision**: Aplicación Laravel monolítica única, sin subproyectos adicionales, consistente con la estructura ya adoptada por `specs/002-gestion-contratos`. Esta feature completa el modelo `Locacion` (creado como prerrequisito mínimo de 002) con la lógica de negocio, controlador, vistas y rutas que la especificación 001 exige; no se requieren cambios de esquema en la migración existente.

## Complexity Tracking

*No violations identified — table intentionally left empty.*
