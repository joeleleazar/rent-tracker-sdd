# Implementation Plan: Gestión de Contratos de Locación

**Branch**: `002-gestion-contratos` | **Date**: 2026-08-19 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/002-gestion-contratos/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Permitir registrar contratos de alquiler por locación (inquilino, fechas de vigencia, monto de renta, estado), impidiendo mediante validación transaccional que dos contratos de la misma locación se solapen en el tiempo, y adjuntar como respaldo un PDF único o hasta 10 fotos del contrato firmado notarialmente. Enfoque técnico: aplicación Laravel monolítica server-rendered (Blade), sin dependencias de infraestructura no disponibles en shared hosting (sin Redis, sin colas con workers persistentes, sin symlinks públicos para archivos), con PostgreSQL como base de datos relacional y validación de solapamiento aplicada en un Service dentro de `DB::transaction` con bloqueo de filas.

## Technical Context

**Language/Version**: PHP 8.3+ (última versión estable soportada por el shared hosting objetivo; ver `research.md` §1)

**Primary Dependencies**: Laravel 11.x+ (última LTS/estable disponible), Eloquent ORM, Blade + Alpine.js (interactividad ligera sin build de Node en producción), Pest (testing)

**Storage**: PostgreSQL 16+ (relacional, vía Eloquent/migraciones); archivos binarios (PDF/fotos del contrato) en el sistema de archivos local del servidor, disco `local` por defecto (`storage/app/private/contratos/`), fuera del árbol público; solo la ruta relativa se persiste en PostgreSQL

**Testing**: Pest (sobre PHPUnit), con `RefreshDatabase` contra PostgreSQL de pruebas; feature tests HTTP para controladores y unit tests para modelos/servicios (Principio IV)

**Target Platform**: Servidor Linux de shared hosting (cPanel/Plesk típico), sin acceso root, sin Docker en producción, cron único para `php artisan schedule:run`

**Project Type**: Aplicación web monolítica (single project) — Blade server-rendered, sin frontend SPA separado

**Performance Goals**: Consultas de validación de solapamiento y listados de historial de contratos deben responder en <300ms bajo carga típica de shared hosting (decenas de usuarios concurrentes, no miles)

**Constraints**: Sin Redis/Memcached, sin queue workers persistentes (usar driver `database` o `sync`), sin symlinks públicos de almacenamiento, límites de archivo: PDF ≤15MB, imágenes ≤5MB c/u (máx. 10), sin extensiones de PostgreSQL no garantizadas en shared hosting (ej. `btree_gist`) como dependencia dura

**Scale/Scope**: Consistente con la Asunción A-002 de `specs/001-jerarquia-locaciones` (hasta ~1,000 locaciones); volumen de contratos e inquilinos del mismo orden de magnitud (cientos a pocos miles de registros), típico de un cliente único gestionando una o pocas galerías comerciales

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|---|---|
| I. Stack Tecnológico Moderno (PHP/Laravel/PostgreSQL) | ✅ PHP 8.3+, Laravel 11.x+, PostgreSQL 16+; Eloquent ORM, migraciones, Form Requests, Services desacoplados para la lógica de solapamiento; sin SQL crudo sin sanitizar |
| II. Nomenclatura en Español | ✅ Modelos (`Contrato`, `DocumentoContrato`, `Inquilino`), tablas/columnas (`contratos`, `documentos_contrato`, `locacion_id`, `fecha_inicio`) y métodos de negocio en español; sufijos técnicos de Laravel (`Controller`, `Request`, `Migration`) se mantienen en inglés por convención del framework, igual que el ejemplo `RegistrarPagoRequest` de la propia Constitución |
| III. Diseño Moderno e Intuitivo | ✅ Formularios y listados con contraste WCAG AA, navegación clara, confirmación explícita antes de eliminar documentos (ver `contracts/rutas-contrato.md`) |
| IV. Pruebas Automatizadas Exhaustivas | ✅ Pest cubre modelo `Contrato` (relaciones, scope de solapamiento, casts), `ContratoController` y `DocumentoContratoController` (happy path, validación, códigos HTTP, autorización, persistencia) — ver `quickstart.md` |
| V. Integridad de Datos y Seguridad Transaccional | ✅ `DB::transaction` + `lockForUpdate()` para la validación de solapamiento y para la creación conjunta de contrato/documentos; `monto_renta` como `NUMERIC(12,2)`/`decimal:2`; CSRF activo (comportamiento por defecto de Laravel); archivos servidos por ruta autenticada, no públicos |

**Resultado**: PASS. No se identifican violaciones que requieran justificación en `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/002-gestion-contratos/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
│   └── rutas-contrato.md
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
app/
├── Models/
│   ├── Locacion.php               # De la especificación 001 (prerrequisito)
│   ├── Inquilino.php
│   ├── Contrato.php
│   └── DocumentoContrato.php
├── Http/
│   ├── Controllers/
│   │   ├── ContratoController.php
│   │   └── DocumentoContratoController.php
│   └── Requests/
│       ├── SolicitudGuardarContrato.php
│       └── SolicitudSubirDocumentoContrato.php
└── Services/
    └── ServicioValidacionSolapamientoContrato.php

database/
├── migrations/
│   ├── xxxx_xx_xx_create_inquilinos_table.php
│   ├── xxxx_xx_xx_create_contratos_table.php
│   └── xxxx_xx_xx_create_documentos_contrato_table.php
└── seeders/
    └── ContratoSeeder.php

resources/
├── views/
│   └── contratos/
│       ├── index.blade.php        # Historial cronológico (US3)
│       ├── create.blade.php
│       ├── edit.blade.php
│       └── show.blade.php         # Detalle + galería/preview de documentos
└── css/js (Vite, compilado en build, sin Node en producción)

routes/
└── web.php                        # Rutas de /contratos y /documentos, ver contracts/rutas-contrato.md

storage/
└── app/
    └── private/
        └── contratos/{contrato_id}/   # Archivos PDF/fotos, disco `local` (privado por defecto en Laravel 11+)

tests/
├── Feature/
│   ├── ContratoControllerTest.php
│   └── DocumentoContratoControllerTest.php
└── Unit/
    ├── ContratoTest.php
    └── ServicioValidacionSolapamientoContratoTest.php
```

**Structure Decision**: Aplicación Laravel monolítica única (estructura estándar `app/`, `database/`, `resources/`, `routes/`, `tests/`), sin separación backend/frontend ni subproyectos adicionales, conforme a la restricción explícita de "monolito para shared hosting". Esta feature depende del modelo `Locacion` de `specs/001-jerarquia-locaciones` (debe implementarse como prerrequisito o en paralelo) e introduce una entidad mínima `Inquilino` que deberá reconciliarse al planificar `specs/003-representantes-contrato` (ver `research.md` §6).

## Complexity Tracking

*No violations identified — table intentionally left empty.*
