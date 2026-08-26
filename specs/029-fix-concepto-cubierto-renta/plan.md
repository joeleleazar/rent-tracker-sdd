# Implementation Plan: Corregir Cobertura de Conceptos y Edición de Renta en Recibos

**Branch**: `029-fix-concepto-cubierto-renta` | **Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/029-fix-concepto-cubierto-renta/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Dos correcciones puntuales, ya diagnosticadas por inspección de código: (1) `ReciboController::edit()`
nunca reincorpora "Renta" a la lista de conceptos ofrecidos en el formulario de edición cuando el propio
recibo ya la incluye — porque la unión "disponibles + ya incluidos por este recibo" solo mira
`$recibo->conceptos` (la relación `recibo_conceptos`), y Renta nunca vive ahí (se guarda aparte, como
`monto_renta` directo en el recibo); (2) refuerzo de prueba explícita de que ningún concepto se muestra
como cubierto en `/recibos/registro-masivo` sin un recibo vigente real que lo incluya — invariante que
`specs/026` ya implementó (`Recibo::vigente()`), verificado en vivo como correcto contra los datos actuales,
pero sin una prueba dedicada al caso puntual reportado por el usuario.

## Technical Context

**Language/Version**: PHP 8.3, Laravel 13.x (mismo stack que el resto del proyecto, sin cambios)

**Primary Dependencies**: Ninguna nueva — reutiliza `ServicioGeneracionReciboPeriodo` y
`ReciboController` ya existentes (specs/005/019/024/026)

**Storage**: PostgreSQL 15+ — sin cambios de esquema; no se agrega, quita ni modifica ninguna tabla ni
columna

**Testing**: Pest (PHPUnit) — Feature tests sobre los controladores ya existentes

**Target Platform**: Aplicación web Laravel (sin cambios)

**Project Type**: Aplicación web monolítica existente (sin cambios de estructura)

**Performance Goals**: N/A — corrección puntual sin impacto de escala

**Constraints**: No debe alterar ningún comportamiento ya probado de specs/005 (edición de recibo),
specs/019 (montos editables), specs/024 (catálogo dinámico) ni specs/026 (exclusión de recibos anulados)
salvo lo descrito aquí. Independiente de specs/028 (en curso en paralelo, quita el botón "Ir" del selector
de periodo de esta misma pantalla) — no toca los mismos archivos salvo el archivo de tests compartido
(`tests/Feature/RegistroMasivoRecibosControllerTest.php`), donde las nuevas pruebas se agregan sin tocar
las ya existentes de specs/028.

**Scale/Scope**: Dos archivos de producción (`app/Http/Controllers/ReciboController.php` y, si el
Escenario 2 revela algún caso no cubierto en vivo, `app/Services/ServicioGeneracionReciboPeriodo.php`),
más pruebas.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno**: Cumple — cambio acotado dentro de Eloquent/controladores ya
  idiomáticos, sin nueva infraestructura.
- **II. Nomenclatura en Español**: Cumple — no se agregan símbolos nuevos; los ya existentes
  (`conceptosDisponibles`, `esRenta()`, etc.) ya siguen la convención.
- **III. Diseño Moderno e Intuitivo**: Cumple — el formulario de edición ya sabe renderizar Renta
  correctamente (usa `esRenta()` igual que el resto); esta corrección solo hace que el controlador se la
  entregue, sin cambios visuales nuevos.
- **IV. Pruebas Automatizadas Exhaustivas**: Aplica — cada FR de spec.md requiere su propio test Feature,
  detallado en tasks.md.
- **V. Integridad de Datos y Seguridad Transaccional**: Cumple — reutiliza
  `ServicioGeneracionReciboPeriodo::actualizar()`, que ya corre dentro de `DB::transaction()`; sin cambios
  a esa garantía.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: Cumple — no se modifica ninguna vista Blade (el
  fix es puramente de datos entregados al controlador; `edit.blade.php` ya renderiza Renta correctamente
  cuando está presente en la colección). Si el Escenario 2 llegara a requerir un cambio de vista, pasaría
  por la revisión `impeccable` exigida por este principio antes de darse por completo.

Sin violaciones — no se requiere la sección de Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/029-fix-concepto-cubierto-renta/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output — las 2 causas raíz, ya diagnosticadas
├── quickstart.md        # Phase 1 output — escenarios de validación manual
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

No se generan `data-model.md` ni `contracts/`: no hay entidades nuevas ni rutas/interfaces nuevas — ambas
correcciones operan sobre datos y endpoints ya existentes y ya documentados en specs/005/019/024/026.

### Source Code (repository root)

Aplicación Laravel monolítica ya existente — sin cambios de estructura. Archivos relevantes:

```text
app/
├── Http/Controllers/
│   └── ReciboController.php            # edit(): reincorporar Renta a $conceptosDisponibles
└── Services/
    └── ServicioGeneracionReciboPeriodo.php  # sin cambio esperado (US2 ya corregida por specs/026);
                                              # se revisa si el Escenario 2 revela algún caso residual

resources/views/locaciones/recibos/
└── edit.blade.php                      # sin cambio esperado — ya renderiza Renta correctamente
                                         # cuando está presente en la colección

tests/Feature/
├── ReciboControllerTest.php            # + tests de US1 (editar Renta)
└── RegistroMasivoRecibosControllerTest.php  # + tests de US2 (badges), sin tocar los ya
                                              # agregados por specs/028 en el mismo archivo
```

**Structure Decision**: Se mantiene la estructura Laravel estándar ya usada por todo el proyecto — esta
feature es una corrección acotada dentro de un controlador ya existente, sin ninguna carpeta ni patrón
arquitectónico nuevo.

## Complexity Tracking

Sin violaciones a la constitución — tabla no aplica (ver Constitution Check arriba).
