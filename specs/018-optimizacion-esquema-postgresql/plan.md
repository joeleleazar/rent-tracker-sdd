# Implementation Plan: Optimización de Esquema y Consultas PostgreSQL

**Branch**: `018-optimizacion-esquema-postgresql` | **Date**: 2026-08-25 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/018-optimizacion-esquema-postgresql/spec.md`

## Summary

Endurecer el esquema PostgreSQL de `rent-tracker` (24 migraciones ya aplicadas) siguiendo el skill `postgres` y el Principio I de la constitución, sin alterar el comportamiento observable de la aplicación: agregar índices de soporte a 6 columnas de llave foránea sin cobertura, eliminar el patrón N+1 del registro masivo de lecturas mediante batch-fetch, resolver el escaneo secuencial de la búsqueda de inquilinos con un índice `pg_trgm`, migrar todas las columnas de fecha/hora a `timestamptz` interpretando los valores existentes como UTC (según `config('app.timezone')`), y reestructurar `configuracion_general` a una tabla clave-valor (`clave`, `valor jsonb`) que preserva la interfaz pública actual del modelo `ConfiguracionGeneral`, agregando además un `CHECK` de integridad para que `periodo` sea siempre el día 1 del mes en las tres tablas que lo usan.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 13.17)

**Primary Dependencies**: Laravel Framework 13.17, Eloquent ORM, Pest 4.7 (pestphp/pest-plugin-laravel)

**Storage**: PostgreSQL 15+ (driver `pgsql`, sin `timezone` explícito en `config/database.php` → depende del `timezone` de sesión por defecto del servidor; ver research.md R1)

**Testing**: Pest (PHPUnit 12.5 por debajo), suite existente en `tests/Feature/*` y `tests/Unit/*`

**Target Platform**: Aplicación web Laravel monolítica (servidor Linux/Windows con PHP-FPM o `php artisan serve`)

**Project Type**: Web application (single Laravel app, sin frontend separado — Blade + htmx)

**Performance Goals**: SC-001 (lote de 50 locaciones ≤120% del tiempo de un lote de 5), SC-002 (búsqueda de inquilinos con tiempo estable entre 100 y 5,000 registros)

**Constraints**: Cero cambios de comportamiento observable salvo los documentados en spec.md (FR-009); cero pérdida/corrupción de datos existentes al migrar (FR-010, SC-005); ninguna prueba automatizada existente debe cambiar su aserción de resultado esperado (SC-004) salvo ajustes de fixtures exigidos por el cambio de zona horaria.

**Scale/Scope**: 6 tablas de dominio con índices nuevos, 1 tabla rediseñada (`configuracion_general`), 1 controlador con fix de N+1 (`RegistroMasivoLecturasController::store()`), 1 controlador con índice de búsqueda nuevo (`InquilinoController::buscar()`), ~24 migraciones existentes cuyas columnas de fecha/hora migran a `timestamptz` vía una migración nueva (no se editan las históricas).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Principio I (Stack, PostgreSQL)**: Este feature es precisamente la aplicación explícita de "el esquema DEBE aprovechar las capacidades relacionales e integridad de PostgreSQL (claves foráneas, índices, restricciones CHECK... marcas temporales con zona horaria)". Todo el trabajo se hace vía migraciones Eloquent/Schema Builder; el único SQL crudo necesario (CHECK constraints, `CREATE EXTENSION pg_trgm`, `ALTER COLUMN ... TYPE timestamptz`) no tiene equivalente en el Schema Builder de Laravel y se ejecuta vía `DB::statement()` dentro de migraciones, consistente con la excepción ya prevista en el propio Principio I ("queda prohibido el bypass del ORM sin justificación técnica"). PASA.
- **Principio II (Español)**: La tabla rediseñada mantiene su nombre `configuracion_general`; las columnas nuevas (`clave`, `valor`) y los métodos nuevos del modelo (si los hay) se nombran en español. Ningún nombre de tabla/columna/índice nuevo en inglés salvo términos técnicos ya aceptados en el proyecto (`id`, `created_at`/`updated_at`, convención Laravel). PASA.
- **Principio III (Diseño Moderno)**: Sin cambios de UI — el feature es puramente de esquema/consultas; la vista `configuracion.edit` y las de registro masivo/búsqueda de inquilinos no cambian su marcado. PASA (no aplica revisión `impeccable`, no se toca ninguna vista Blade).
- **Principio IV (Pruebas Exhaustivas)**: Cada punto (índices, N+1, búsqueda, timestamptz, `configuracion_general`, CHECK de `periodo`) requiere pruebas nuevas o ajustadas que verifiquen que el comportamiento observable no cambió (SC-003/SC-004) además de que la mejora estructural existe. PASA, condicionado a que `/speckit-tasks` incluya explícitamente esas pruebas antes de la implementación (TDD).
- **Principio V (Integridad de Datos)**: El feature refuerza este principio (índices, CHECK, tipos con zona horaria) en vez de arriesgarlo; los montos monetarios no cambian de tipo (siguen `DECIMAL`/`decimal:2`); toda migración que transforma datos existentes (timestamptz, configuracion_general, CHECK de periodo) DEBE ejecutarse dentro de una migración con verificación previa (FR-010) y, cuando mueva datos entre tablas/columnas, dentro de una transacción. PASA.
- **Principio VI (Bootstrap 5 / htmx / impeccable)**: No aplica — ninguna vista Blade se crea o modifica en este feature. PASA (no aplica).

Sin violaciones. Complexity Tracking documenta una única desviación deliberada del patrón Eloquent estándar (ver abajo), no una violación de la constitución.

## Project Structure

### Documentation (this feature)

```text
specs/018-optimizacion-esquema-postgresql/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
# Opción única: aplicación Laravel monolítica ya existente (sin frontend separado)
database/
└── migrations/
    ├── 2026_08_2X_..._add_indices_llaves_foraneas.php            # FR-004
    ├── 2026_08_2X_..._migrar_timestamps_a_timestamptz.php        # FR-005/FR-006
    ├── 2026_08_2X_..._rediseñar_configuracion_general_clave_valor.php  # FR-007
    ├── 2026_08_2X_..._check_periodo_dia_uno.php                  # FR-007b
    └── 2026_08_2X_..._extension_pg_trgm_busqueda_inquilinos.php  # FR-008

app/
├── Models/
│   └── ConfiguracionGeneral.php        # rediseño interno, misma interfaz pública
└── Http/Controllers/
    ├── RegistroMasivoLecturasController.php  # fix N+1 en store()
    └── InquilinoController.php               # sin cambios de código (el índice basta)

tests/
├── Feature/
│   ├── ConfiguracionGeneralControllerTest.php     # ajustado/ampliado
│   ├── RegistroMasivoLecturasControllerTest.php   # ampliado (conteo de queries + resultado)
│   └── InquilinoControllerTest.php                # ampliado (substring + volumen)
└── Unit/
    └── ConfiguracionGeneralTest.php               # nuevo: contrato del modelo rediseñado
```

**Structure Decision**: Aplicación Laravel única (sin separación backend/frontend); todo el trabajo vive en `database/migrations/`, `app/Models/ConfiguracionGeneral.php`, `app/Http/Controllers/RegistroMasivoLecturasController.php` y la suite de pruebas existente — no se crean módulos, paquetes ni servicios nuevos fuera de estas rutas.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No hay violaciones de la constitución. Se documenta aquí, por transparencia, una desviación deliberada del patrón Eloquent estándar (no un incumplimiento constitucional):

| Desviación | Por qué es necesaria | Alternativa más simple descartada porque |
|-----------|------------|-------------------------------------|
| `ConfiguracionGeneral` deja de mapear columnas reales 1:1 y en su lugar traduce sus atributos públicos (`tarifa_luz_por_unidad`, etc.) hacia/desde filas clave-valor internamente | Es la única forma de cumplir FR-007 (tabla clave-valor extensible) sin romper FR-009 (cero cambios en controladores/servicios/vistas que ya usan `$configuracion->tarifa_luz_por_unidad`, `->update([...])`) | Migrar todos los call sites a una API nueva (ej. `$config->obtener('clave')`) es más simple de implementar, pero viola explícitamente FR-009 y obliga a tocar 5 archivos adicionales (controladores/servicios) sin necesidad real |
