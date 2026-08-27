# Implementation Plan: Menú de Registro de Pagos en la Jerarquía de Locales

**Branch**: `033-menu-registro-pagos` | **Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/033-menu-registro-pagos/spec.md`

## Summary

Hacer alcanzable desde el menú principal la pantalla de avance de pagos ya entregada por specs/032
(`pagos.seguimiento.index`), renombrándola visualmente a "Registro de Pagos" (título de página + nuevo
ítem de menú), y agregar en cada fila con saldo pendiente una acción "Registrar Pago" que lleve directo a
la pantalla de ingreso del pago — reutilizando, sin código nuevo, la ruta
`recibos.registroMasivo.recibosDelPeriodo` (specs/026), que ya redirige directo al recibo cuando hay uno
solo y ya ofrece un selector cuando hay varios.

## Technical Context

**Language/Version**: PHP 8.2+, Laravel 11.x

**Primary Dependencies**: Blade, Bootstrap 5.3 (Sass), htmx (`hx-boost`), Pest

**Storage**: PostgreSQL — sin cambios de esquema (no se agregan entidades ni columnas)

**Testing**: Pest (Feature tests), binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`)

**Target Platform**: Aplicación web Laravel, dominio de desarrollo `rent-tracker-sdd.test`

**Project Type**: Web (Laravel monolito con Blade)

**Performance Goals**: N/A — no se agregan consultas nuevas; reutiliza datos ya calculados por
`SeguimientoPagosController::datosDelPeriodo()`.

**Constraints**: Ninguna funcionalidad de specs/032 (avance de pago por locación) puede regresar.

**Scale/Scope**: 1 ítem de menú nuevo, 1 cambio de título de página, 1 acción condicional nueva en un
parcial ya existente. Sin controladores, rutas, modelos ni migraciones nuevas.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno**: Cumple — no se toca la capa de datos; se reutiliza el árbol de
  locaciones (`ServicioConstruccionArbolLocaciones`) y los cálculos de avance ya persistidos como métodos de
  `Recibo` (specs/032).
- **II. Nomenclatura en Español**: Cumple — el único texto nuevo es la etiqueta de menú y el título de
  página ("Registro de Pagos"), ambos en español. Ver Decisión 1 de `research.md` sobre por qué los
  identificadores internos (`SeguimientoPagosController`, ruta `pagos.seguimiento.index`) no se renombran.
- **III. Diseño Moderno e Intuitivo**: Cumple — el nuevo ítem de menú sigue exactamente el mismo patrón
  visual (`nav-link` + ícono + etiqueta + estado activo) que los 5 ítems ya existentes en
  `app-bootstrap.blade.php`, sin introducir un patrón nuevo.
- **IV. Pruebas Automatizadas Exhaustivas**: Cumple — se agregan tests de Feature para la presencia del
  ítem de menú, su estado activo, el nuevo título de página, y la aparición/ausencia condicional de
  "Registrar Pago" según haya o no saldo pendiente.
- **V. Integridad de Datos**: N/A — no hay escritura de datos nueva en esta feature (solo navegación).
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: Cumple — el nuevo botón "Registrar Pago" reutiliza
  exactamente la clase `btn btn-outline-primary btn-sm` + ícono ya usada por "Generar Recibo" en
  `estado-recibo-locacion.blade.php`, y el ícono `bi-cash-coin` ya usado en esta misma pantalla (estado
  vacío) para el mismo concepto de "pago/monto", consistente con la regla de iconografía del principio.
  Pasa por revisión con el skill `impeccable` antes de completarse (ver `tasks.md`, fase Polish).

Sin violaciones. No se requiere `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/033-menu-registro-pagos/
├── plan.md              # Este archivo
├── research.md          # Fase 0
├── quickstart.md         # Fase 1 (validación manual)
└── tasks.md              # Fase 2 (/speckit-tasks — no generado por /speckit-plan)
```

No se generan `data-model.md` ni `contracts/`: la feature no introduce entidades, columnas ni rutas nuevas
(ver research.md Decisión 1).

### Source Code (repository root)

```text
resources/views/components/layouts/app-bootstrap.blade.php   # + 1 ítem <li> de menú
resources/views/pagos/seguimiento/index.blade.php             # título "Seguimiento de Pagos" → "Registro de Pagos"
resources/views/pagos/seguimiento/partials/estado-pago-locacion.blade.php  # + acción "Registrar Pago"
tests/Feature/SeguimientoPagosControllerTest.php               # + tests de FR-001/003/004/005
```

**Structure Decision**: Modificación quirúrgica de 3 vistas Blade ya existentes (specs/010, specs/032) y su
test de Feature asociado. No se crean controladores, rutas, modelos, migraciones ni vistas nuevas — toda la
lógica de datos que la nueva acción necesita (`estadoAgregado`, `cantidadRecibos`, `periodo`) ya llega a
`estado-pago-locacion.blade.php` como props desde specs/032.

## Complexity Tracking

*(vacío — sin violaciones que justificar)*
