# Implementation Plan: Migración de la Interfaz a Bootstrap 5

**Branch**: `010-migracion-interfaz-bootstrap` | **Date**: 2026-08-21 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/010-migracion-interfaz-bootstrap/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Reemplazar el sistema de presentación actual (Tailwind CSS v4 + Alpine.js + componentes Blade custom Senior-First) por Bootstrap 5.3 en las ~30 vistas Blade de las 9 features ya implementadas (001-009), en tres tandas de prioridad (P1: 001-004, P2: 005/007/009, P3: 006+gráfico/008), sin tocar rutas, controladores, modelos, servicios ni Form Requests. Enfoque técnico: instalar Bootstrap 5.3 vía npm (SCSS + JS bundle, con Popper incluido), personalizar sus variables SCSS para hornear los requisitos Senior-First (tipografía base 18px, radios, colores de alto contraste) directamente en el framework en vez de sobreescribir clase por clase, mantener un layout Blade paralelo (Tailwind vs Bootstrap) durante la migración incremental, y retirar Tailwind/Alpine del proyecto solo al completar las 3 historias de usuario.

## Technical Context

**Language/Version**: PHP 8.3+ (sin cambios; esta feature es puramente de presentación)

**Primary Dependencies**: Bootstrap 5.3.3 (SCSS + bundle JS con Popper), Bootstrap Icons 1.11+, Chart.js 4.x (solo para el gráfico de consumo de FR-005), `sass` (dart-sass, dev dependency de Vite para compilar el SCSS de Bootstrap con variables personalizadas). Blade se mantiene como motor de vistas; Laravel 13.x, Eloquent, Pest sin cambios.

**Storage**: Sin cambios — PostgreSQL, mismas tablas y migraciones de 001-009. Esta feature no crea, altera ni elimina ninguna tabla.

**Testing**: Pest (sin cambios de framework); la suite Feature existente (`assertSee`, `assertSessionHasErrors`, `assertOk`, etc.) se reejecuta contra cada vista migrada como criterio de no-regresión (FR-004, SC-002), sin modificar las aserciones de negocio.

**Target Platform**: Servidor Linux de shared hosting, sin cambios respecto a `specs/002-gestion-contratos/research.md` §2 (el build de Bootstrap/Sass ocurre en tiempo de desarrollo/despliegue vía Vite, igual que Tailwind hoy; no se ejecuta Sass en producción).

**Project Type**: Aplicación web monolítica (single project), sin cambios de estructura.

**Performance Goals**: El bundle CSS/JS final (tras retirar Tailwind al completar la migración) no debe superar significativamente el tamaño actual combinado de Tailwind+Alpine (~65KB CSS / ~53KB JS comprimidos, ver build de `specs/001`); se acepta un pico temporal mayor mientras ambos frameworks coexisten durante la migración incremental.

**Constraints**: Sin cambiar ninguna ruta (`routes/web.php`), controlador, Form Request, modelo, servicio, migración o test de negocio ya existente; los IDs/nombres de rutas (`route('locaciones.index')`, etc.) permanecen idénticos. El paquete `bootstrap` requiere Popper (incluido en su bundle JS) pero no requiere jQuery (Bootstrap 5 lo eliminó).

**Scale/Scope**: ~30 vistas Blade + 4 componentes Blade custom (`mensaje-alerta`, `modal`, `ruta-jerarquia-locacion`, y los botones `primary/secondary/danger-button`) a migrar o reemplazar; ningún cambio de volumen de datos respecto a 001-009.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|---|---|
| I. Stack Tecnológico Moderno (PHP/Laravel/PostgreSQL) | ✅ Sin cambios en PHP/Laravel/PostgreSQL/Eloquent; la Constitución permite explícitamente "Blade Templates o componentes desacoplados con CSS semántico accesible" sin fijar un framework CSS específico, por lo que Bootstrap 5 es una opción tan válida como Tailwind dentro de esa restricción |
| II. Nomenclatura en Español | ✅ Los componentes Blade custom nuevos o modificados (ej. wrappers de tarjetas/badges reutilizables) se nombran en español; las clases utilitarias de Bootstrap (`btn`, `card`, `modal`, `badge`) se usan tal cual las define el framework en inglés, igual criterio ya aplicado a los sufijos técnicos de Laravel (`Controller`, `Request`) |
| III. Accesibilidad Senior-First | ✅ Objetivo explícito y no negociable de esta feature (FR-002): tipografía ≥18px, contraste WCAG AA/AAA, botones ≥48x48px, navegación plana, confirmación explícita — todo horneado en las variables SCSS de Bootstrap (ver `research.md` §1) en vez de overrides dispersos |
| IV. Pruebas Automatizadas Exhaustivas | ✅ No se agregan pruebas de negocio nuevas (no hay lógica de negocio nueva), pero la suite completa existente (191 pruebas) se ejecuta como gate de no-regresión después de migrar cada bloque de prioridad (P1/P2/P3), conforme a FR-004/SC-002 |
| V. Integridad de Datos y Seguridad Transaccional | ✅ Sin cambios: ningún flujo transaccional, de validación ni de persistencia se modifica; esta feature es exclusivamente de presentación |

**Resultado**: PASS. No se identifican violaciones que requieran justificación en `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/010-migracion-interfaz-bootstrap/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── inventario-vistas-migradas.md
└── tasks.md
```

### Source Code (repository root)

```text
resources/
├── css/
│   ├── app.css                        # Tailwind (existente); se retira al completar P3
│   └── bootstrap.scss                 # Nuevo: import de Bootstrap + variables Senior-First personalizadas
├── js/
│   ├── app.js                         # Se le agrega el import del bundle JS de Bootstrap (modal, collapse)
│   └── historial-consumo-medidor.js   # Nuevo: inicialización de Chart.js para FR-005 (006)
└── views/
    ├── layouts/
    │   ├── app.blade.php              # Layout Tailwind actual; convive durante la migración
    │   └── app-bootstrap.blade.php    # Nuevo layout Bootstrap; las vistas migradas lo adoptan una a una
    ├── components/                    # Se reemplazan progresivamente: mensaje-alerta, modal, botones,
    │                                  # ruta-jerarquia-locacion (ver contracts/inventario-vistas-migradas.md)
    ├── locaciones/                    # P1 — vistas de 001
    ├── contratos/                     # P1 — vistas de 002/003/004 (incluye partials de garantía/representantes/costos)
    ├── locaciones/lecturas/           # P2 — vistas de 005
    ├── locaciones/recibos/            # P2 — vistas de 005/007
    ├── configuracion/                 # P2/P3 — vista de 004/008
    └── (historial de medidor)         # P3 — vista nueva o migrada de 006, con gráfico de consumo

vite.config.js                         # Se agrega 'resources/css/bootstrap.scss' como entry adicional
package.json                          # Se agregan bootstrap, bootstrap-icons, chart.js, sass (devDependency)
```

**Structure Decision**: Se mantiene la estructura de vistas existente por feature (`locaciones/`, `contratos/`, etc.); no se reorganiza el árbol de `resources/views/`. Solo cambian los componentes/clases usados dentro de cada archivo y se introduce un layout Bootstrap paralelo (`app-bootstrap.blade.php`) para permitir la migración incremental sin romper las vistas aún no migradas, tal como exige el Edge Case de convivencia temporal.

## Complexity Tracking

*No violations identified — table intentionally left empty.*
