# Implementation Plan: Representantes de Contrato

**Branch**: `003-representantes-contrato` | **Date**: 2026-08-20 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/003-representantes-contrato/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Permitir asociar uno o varios representantes legales (apellidos, nombres, DNI, fecha de nacimiento) a cada contrato mediante una tabla pivote `contrato_representante`, exigiendo al menos un representante y exactamente uno marcado como "Principal" antes de guardar. Los representantes viven en un directorio global reutilizable (búsqueda por DNI/apellidos). Enfoque técnico: entidad `Representante` + pivote muchos-a-muchos gestionados por un `ServicioAsociacionRepresentantesContrato` dentro de `DB::transaction`, coexistiendo con el campo `Contrato.inquilino_id` ya existente (ver `research.md` §1 para la reconciliación).

## Technical Context

**Language/Version**: PHP 8.3+ (`composer.json`)

**Primary Dependencies**: Laravel 13.x (instalado; misma nota de discrepancia con la Constitución documentada en `specs/001-jerarquia-locaciones/research.md` §1), Eloquent ORM (relación `belongsToMany` con pivote), Blade, Pest 4

**Storage**: PostgreSQL; nuevas tablas `representantes` (directorio global) y `contrato_representante` (pivote con columna extra `es_principal`)

**Testing**: Pest, `RefreshDatabase`; feature tests para `RepresentanteController`/asociación a contrato y unit tests para el modelo `Representante` y el servicio de asociación (Principio IV)

**Target Platform**: Servidor Linux de shared hosting, consistente con `specs/002-gestion-contratos/research.md` §2

**Project Type**: Aplicación web monolítica (single project)

**Performance Goals**: Búsqueda de representantes por DNI/apellidos en el directorio global debe responder en <300ms bajo carga típica de shared hosting (consistente con `specs/002`)

**Constraints**: DNI único en el directorio global (Key Entities); representante MUST ser mayor de edad (Asunción A-001); exactamente un representante "Principal" por contrato cuando hay múltiples

**Scale/Scope**: Volumen de representantes del mismo orden que contratos/inquilinos (cientos a pocos miles), consistente con `specs/002-gestion-contratos/research.md`

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|---|---|
| I. Stack Tecnológico Moderno (PHP/Laravel/PostgreSQL) | ✅ Eloquent `belongsToMany` con tabla pivote nativa de Laravel/PostgreSQL, migraciones, Form Requests, Service desacoplado; sin SQL crudo. ⚠️ Misma nota de discrepancia de versión de Laravel que `specs/001` (preexistente, no introducida aquí) |
| II. Nomenclatura en Español | ✅ Modelo `Representante`, tabla `representantes`, tabla pivote `contrato_representante`, columnas (`apellidos`, `nombres`, `dni`, `fecha_nacimiento`, `es_principal`) en español; `RepresentanteController`, `SolicitudGuardarRepresentante`, `ServicioAsociacionRepresentantesContrato` |
| III. Accesibilidad Senior-First | ✅ Botones "Agregar Otro Representante"/"Quitar Representante" ≥48x48px, modal de confirmación explícita antes de quitar (US2), campos de búsqueda grandes y legibles (FR-005, FR-007) |
| IV. Pruebas Automatizadas Exhaustivas | ✅ Pest cubre modelo `Representante` (mayoría de edad, unicidad de DNI), `ServicioAsociacionRepresentantesContrato` (mínimo uno, exactamente un principal, bloqueo de remoción del último), `RepresentanteController`/integración con `ContratoController` |
| V. Integridad de Datos y Seguridad Transaccional | ✅ `DB::transaction` al guardar contrato+representantes; validación de "al menos uno" y "exactamente un principal" ejecutada antes del commit; DNI con restricción `UNIQUE` a nivel de base de datos |

**Resultado**: PASS. No se identifican violaciones que requieran justificación en `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/003-representantes-contrato/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── rutas-representante.md
└── tasks.md
```

### Source Code (repository root)

```text
app/
├── Models/
│   ├── Representante.php                  # Nuevo
│   └── Contrato.php                       # Se agrega relación belongsToMany representantes (sin tocar inquilino_id)
├── Http/
│   ├── Controllers/
│   │   └── RepresentanteController.php    # Nuevo: búsqueda/creación en el directorio global + asociación a un contrato
│   └── Requests/
│       └── SolicitudGuardarRepresentante.php   # Nuevo
└── Services/
    └── ServicioAsociacionRepresentantesContrato.php   # Nuevo: valida mínimo uno, exactamente un principal, bloqueo de remoción del último

database/
├── migrations/
│   ├── xxxx_create_representantes_table.php        # Nuevo (dni UNIQUE)
│   └── xxxx_create_contrato_representante_table.php   # Nuevo (pivote, es_principal)
└── factories/
    └── RepresentanteFactory.php            # Nuevo

resources/
└── views/
    └── contratos/
        └── partials/
            └── representantes-contrato.blade.php   # Nuevo: buscador + listado + alta/baja de representantes, embebido en create/edit/show de Contrato

routes/
└── web.php                                # Se añaden rutas de búsqueda de representantes y asociación/desasociación por contrato

tests/
├── Feature/
│   └── RepresentanteControllerTest.php     # Nuevo
└── Unit/
    ├── RepresentanteTest.php               # Nuevo
    └── ServicioAsociacionRepresentantesContratoTest.php   # Nuevo
```

**Structure Decision**: Aplicación Laravel monolítica única, sin subproyectos adicionales. Esta feature extiende `Contrato` (de `specs/002`) con una relación muchos-a-muchos adicional hacia `Representante`, sin modificar ni eliminar `inquilino_id` (ver `research.md` §1). La UI de representantes se embebe como parcial reutilizable dentro de las vistas ya existentes de `contratos/` en vez de crear una pantalla de "gestión de contrato" separada.

## Complexity Tracking

*No violations identified — table intentionally left empty.*
