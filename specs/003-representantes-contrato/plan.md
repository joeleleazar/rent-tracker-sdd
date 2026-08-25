# Implementation Plan: Inquilinos de Contrato (Inquilino Principal)

**Branch**: `003-representantes-contrato` | **Date**: 2026-08-20 | **Revisado**: 2026-08-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/003-representantes-contrato/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Corrección de diseño: el "representante" del contrato **es** el inquilino, no una entidad separada. Se unifica `Representante` dentro de `Inquilino` (extendiéndolo con `apellidos`, `nombres`, `dni`, `fecha_nacimiento`), se reemplaza la relación 1:1 `Contrato.inquilino_id` (de `specs/002`) por una relación muchos-a-muchos vía tabla pivote `contrato_inquilino` con columna `es_principal`, exigiendo al menos un inquilino y exactamente uno marcado como Principal antes de guardar. Los inquilinos viven en un directorio global reutilizable (búsqueda por DNI/apellidos, reutilizando la tabla `inquilinos` ya existente). Enfoque técnico: extender el modelo `Inquilino` + pivote muchos-a-muchos gestionado por un `ServicioAsociacionInquilinosContrato` dentro de `DB::transaction`, con migraciones aditivas de consolidación de datos que eliminan `representantes`, `contrato_representante` y `contratos.inquilino_id` (ver `research.md` §1).

## Technical Context

**Language/Version**: PHP 8.3+ (`composer.json`)

**Primary Dependencies**: Laravel 13.x (instalado; misma nota de discrepancia con la Constitución documentada en `specs/001-jerarquia-locaciones/research.md` §1), Eloquent ORM (relación `belongsToMany` con pivote), Blade, htmx (Principio VI, interactividad de escritura), Pest 4

**Storage**: PostgreSQL; se extiende la tabla existente `inquilinos` (nuevas columnas `apellidos`, `nombres`, `dni`, `fecha_nacimiento`) y se crea `contrato_inquilino` (pivote con columna extra `es_principal`); se eliminan `representantes`, `contrato_representante` y `contratos.inquilino_id` tras migrar sus datos

**Testing**: Pest, `RefreshDatabase`; feature tests para `InquilinoController`/asociación a contrato y unit tests para el modelo `Inquilino` extendido, el servicio de asociación y la migración de consolidación de datos (Principio IV)

**Target Platform**: Servidor Linux de shared hosting, consistente con `specs/002-gestion-contratos/research.md` §2

**Project Type**: Aplicación web monolítica (single project)

**Performance Goals**: Búsqueda de inquilinos por DNI/apellidos en el directorio global debe responder en <300ms bajo carga típica de shared hosting (consistente con `specs/002`)

**Constraints**: DNI único en el directorio global (Key Entities); inquilino MUST ser mayor de edad (Asunción A-001); exactamente un inquilino "Principal" por contrato; migraciones de consolidación deben ser aditivas y no destructivas hasta confirmar la copia de datos (Principio I, shared hosting sin downtime controlado)

**Scale/Scope**: Volumen de inquilinos del mismo orden que contratos (cientos a pocos miles), consistente con `specs/002-gestion-contratos/research.md`

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|---|---|
| I. Stack Tecnológico Moderno (PHP/Laravel/PostgreSQL) | ✅ Eloquent `belongsToMany` con tabla pivote nativa, migraciones aditivas (nunca se editan migraciones ya aplicadas), Form Requests, Service desacoplado; sin SQL crudo. ⚠️ Misma nota de discrepancia de versión de Laravel que `specs/001` (preexistente, no introducida aquí) |
| II. Nomenclatura en Español | ✅ Modelo `Inquilino` (extendido), tabla `inquilinos`, tabla pivote `contrato_inquilino`, columnas (`apellidos`, `nombres`, `dni`, `fecha_nacimiento`, `es_principal`) en español; `InquilinoController`, `SolicitudGuardarInquilino`, `ServicioAsociacionInquilinosContrato` |
| III. Diseño Moderno e Intuitivo | ✅ Botones "Agregar Otro Inquilino"/"Quitar Inquilino" claros, modal de confirmación explícita antes de quitar (US2), campos de búsqueda legibles (FR-005, FR-007); contraste WCAG AA (4.5:1) como piso, sin imponer mínimos de 18px/48px ya retirados por la enmienda constitucional 2.0.0 |
| IV. Pruebas Automatizadas Exhaustivas | ✅ Pest cubre modelo `Inquilino` (mayoría de edad, unicidad de DNI), `ServicioAsociacionInquilinosContrato` (mínimo uno, exactamente un principal, bloqueo de remoción del último y del Principal sin reemplazo), `InquilinoController`/integración con `ContratoController`, y la migración de consolidación de datos |
| V. Integridad de Datos y Seguridad Transaccional | ✅ `DB::transaction` al guardar contrato+inquilinos; validación de "al menos uno" y "exactamente un principal" ejecutada antes del commit; DNI con restricción `UNIQUE` a nivel de base de datos; migración de consolidación de datos ejecutada de forma atómica y verificable antes de eliminar las tablas/columnas antiguas |
| VI. Sistema de Componentes Visuales (Bootstrap 5) | ✅ Reutiliza los mismos componentes (`card`, `Modal`, `btn`, iconografía `bi-*`) ya usados por la parcial `representantes-contrato.blade.php`, renombrada a `inquilinos-contrato.blade.php` |

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
│   └── rutas-inquilino.md
└── tasks.md
```

### Source Code (repository root)

```text
app/
├── Models/
│   ├── Inquilino.php                       # Extendido: agrega apellidos/nombres/dni/fecha_nacimiento; belongsTo → belongsToMany(Contrato)
│   ├── Representante.php                   # Eliminado (consolidado en Inquilino)
│   └── Contrato.php                        # Se quita 'inquilino_id' de $fillable y el belongsTo(Inquilino); se agrega belongsToMany(Inquilino) + inquilinoPrincipal()
├── Http/
│   ├── Controllers/
│   │   ├── InquilinoController.php         # Nuevo (renombra RepresentanteController): búsqueda/creación en el directorio global + asociación a un contrato
│   │   └── ContratoController.php          # Ajusta agregarInquilino/quitarInquilino (renombra agregarRepresentante/quitarRepresentante) y la referencia a $contrato->inquilino->nombre (ahora vía inquilinoPrincipal())
│   └── Requests/
│       ├── SolicitudGuardarInquilino.php   # Nuevo (renombra SolicitudGuardarRepresentante)
│       └── SolicitudGuardarContrato.php    # Se quita la regla 'inquilino_id' (required|integer|exists:inquilinos,id)
├── Services/
│   └── ServicioAsociacionInquilinosContrato.php   # Nuevo (renombra ServicioAsociacionRepresentantesContrato): valida mínimo uno, exactamente un principal, bloqueo de remoción del último y del Principal sin reemplazo (FR-009)
└── Exceptions/
    ├── ContratoSinInquilinosException.php          # Renombra ContratoSinRepresentantesException
    ├── InquilinoPrincipalInvalidoException.php     # Renombra RepresentantePrincipalInvalidoException
    └── UltimoInquilinoException.php                # Renombra UltimoRepresentanteException

database/
├── migrations/
│   ├── xxxx_add_datos_personales_to_inquilinos_table.php   # Nuevo: agrega apellidos/nombres/dni (UNIQUE)/fecha_nacimiento
│   ├── xxxx_create_contrato_inquilino_table.php             # Nuevo (pivote, es_principal)
│   ├── xxxx_migrar_representantes_a_inquilinos.php          # Nuevo: script de migración de datos (ver research.md §1)
│   └── xxxx_drop_representantes_y_contrato_inquilino_id.php # Nuevo: elimina representantes, contrato_representante, contratos.inquilino_id (tras verificar la copia)
└── factories/
    └── InquilinoFactory.php                # Actualizado: genera apellidos/nombres/dni/fecha_nacimiento (reemplaza RepresentanteFactory)

resources/
└── views/
    └── contratos/
        └── partials/
            └── inquilinos-contrato.blade.php   # Renombra representantes-contrato.blade.php: buscador + listado + alta/baja de inquilinos, embebido en create/edit/show de Contrato

resources/js/
└── inquilinos-contrato.js                  # Renombra representantes-contrato.js si contiene lógica específica de la parcial

routes/
└── web.php                                # Renombra rutas de /representantes → /inquilinos y /contratos/{contrato}/representantes → /contratos/{contrato}/inquilinos

tests/
├── Feature/
│   └── InquilinoControllerTest.php         # Renombra RepresentanteControllerTest
└── Unit/
    ├── InquilinoTest.php                   # Extiende el test existente del modelo Inquilino con mayoría de edad/unicidad de DNI
    └── ServicioAsociacionInquilinosContratoTest.php   # Renombra ServicioAsociacionRepresentantesContratoTest
```

**Structure Decision**: Aplicación Laravel monolítica única, sin subproyectos adicionales. Esta corrección **extiende** `Inquilino` (de `specs/002`) con los datos personales y la relación muchos-a-muchos que la versión anterior de esta feature había modelado erróneamente como una entidad `Representante` separada; no se crea una pantalla de "gestión de contrato" distinta — la UI de inquilinos se embebe como parcial reutilizable dentro de las vistas ya existentes de `contratos/`, igual que en el diseño anterior, solo renombrada.

## Complexity Tracking

*No violations identified — table intentionally left empty.*
