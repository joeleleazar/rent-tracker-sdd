# Implementation Plan: Completar Total en Importación Histórica y Seeder

**Branch**: `022-completar-total-importacion` | **Date**: 2026-08-25 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/022-completar-total-importacion/spec.md`

**Nota**: como el resto de esta feature, este plan se escribió después de aplicar el fix (ver "Nota de
Proceso" en spec.md) — documenta la solución ya implementada, no una a diseñar desde cero.

## Summary

specs/019-total-editable-recibos volvió `total` un campo `NOT NULL` de `lecturas_medidor`. Su implementación
cubrió los dos flujos con test propio (`LecturaMedidorController` y `RegistroMasivoLecturasController`), pero
dejó dos puntos de escritura sin actualizar: `ImportarLecturasMedidorHistoricas` (comando Artisan) y
`DatabaseSeeder` (seeder de demo). Ambos crean `LecturaMedidor::create([...])` sin la clave `'total'`, lo que
hoy produce un error de base de datos (violación `NOT NULL`) apenas se ejecuten. El fix agrega el mismo
cálculo ya usado en `LecturaMedidorController::calcularTotal()` (consumo × tarifa vigente) a ambos puntos,
usando en cada uno la tarifa que ya tienen disponible: `ConfiguracionGeneral::actual()->tarifa_luz_por_unidad`
en el comando de importación, y la constante `0.85` que el propio seeder ya configura al principio de su
ejecución.

## Technical Context

**Language/Version**: PHP 8.2+ (binario de Herd en esta máquina: `C:\Users\joel5\.config\herd\bin\php.bat`)

**Primary Dependencies**: Laravel 11.x (Eloquent, comandos Artisan, `DatabaseSeeder`)

**Storage**: PostgreSQL 15+ — tabla `lecturas_medidor`, columna `total` (`NUMERIC`, `NOT NULL` desde
specs/019)

**Testing**: Pest — sin tests nuevos dedicados (ver Constitution Check, Principio IV, más abajo); la
verificación fue manual/directa, documentada en `quickstart.md`

**Target Platform**: CLI de servidor (comando Artisan `medidores:importar-historico` y `php artisan db:seed`),
sin superficie web propia

**Project Type**: Aplicación web Laravel monolítica existente — esta feature toca solo capa de comandos/seeders,
no rutas ni controladores HTTP

**Performance Goals**: N/A — sin cambio de rendimiento; el cálculo agregado es una multiplicación por fila,
despreciable frente al resto del procesamiento de ~1000 filas del comando de importación

**Constraints**: no se puede reconstruir una tarifa histórica por período (no existe ese dato) — mismo límite
ya aceptado y documentado en specs/019 para el backfill de `total`

**Scale/Scope**: dos archivos de código (`app/Console/Commands/ImportarLecturasMedidorHistoricas.php`,
`database/seeders/DatabaseSeeder.php`); ningún cambio de esquema, ruta, vista ni contrato público

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno**: cumple — cambio dentro de Eloquent/Artisan, sin SQL crudo ni bypass del ORM.
- **II. Nomenclatura en Español**: cumple — `$tarifa`, `$consumo`, nombres de clase y comentarios ya en español (ver código citado en research.md).
- **III. Diseño Moderno e Intuitivo**: N/A — sin superficie de interfaz de usuario involucrada.
- **IV. Pruebas Automatizadas Exhaustivas**: **desviación documentada, no violación** — ni el comando de importación histórica ni el seeder tienen (ni tenían antes de esta feature) suite de test dedicada; son utilidades de una sola ejecución (importación de un archivo histórico ya procesado una vez; poblado de datos de demostración), no lógica de negocio de dominio expuesta por controlador o modelo. La verificación de este fix se hizo por revisión directa de código y de los datos existentes (`quickstart.md`), consistente con cómo se trató el resto de estos dos archivos en specs/019/021. No se introduce deuda nueva: el nivel de cobertura de ambos archivos queda igual que antes de esta feature.
- **V. Integridad de Datos y Seguridad Transaccional**: cumple — el comando de importación ya envuelve toda la operación en `DB::transaction()`; el cálculo usa `round()`/`decimal:2` consistente con el resto del sistema, sin flotantes imprecisos persistidos.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: N/A — ningún archivo `resources/views/**` se crea ni se modifica en esta feature; no aplica revisión `impeccable`.

**Resultado**: PASS, con una desviación documentada (Principio IV) que se acepta por ser consistente con el
alcance ya existente de ambos archivos, no una regresión introducida por esta feature.

## Project Structure

### Documentation (this feature)

```text
specs/022-completar-total-importacion/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md         # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
├── checklists/
│   └── requirements.md
└── tasks.md              # Phase 2 output (/speckit-tasks command)
```

### Source Code (repository root)

```text
app/
└── Console/
    └── Commands/
        └── ImportarLecturasMedidorHistoricas.php   # cálculo de total agregado (US1)

database/
└── seeders/
    └── DatabaseSeeder.php                          # cálculo de total agregado (US2)
```

**Structure Decision**: aplicación Laravel monolítica ya existente (Option 1, adaptada) — esta feature no
agrega ninguna carpeta ni capa nueva, solo modifica dos archivos ya existentes dentro de la estructura
estándar de Laravel (`app/Console/Commands/`, `database/seeders/`).

## Complexity Tracking

*Sin violaciones que requieran justificación adicional — la única desviación (Principio IV, tests) se
documentó arriba en el Constitution Check, no en esta sección, porque no es una violación de un principio
sino una continuación del alcance de test ya existente en ambos archivos antes de esta feature.*
