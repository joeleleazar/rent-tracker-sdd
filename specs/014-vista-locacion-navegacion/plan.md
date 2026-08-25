# Implementation Plan: Navegación a Contratos y Recibos desde las Vistas de Locación

**Branch**: `014-vista-locacion-navegacion` | **Date**: 2026-08-24 (revisado tras `/speckit-clarify`) | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/014-vista-locacion-navegacion/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

**Revisión de alcance (2026-08-24)**: una sesión de `/speckit-clarify` reveló que el pedido original del usuario apuntaba a la tabla jerárquica de `/locaciones` (donde se listan todas las áreas — `resources/views/locaciones/partials/fila-arbol-locacion.blade.php`), no a la vista de detalle individual (`locaciones/show.blade.php`) que se había implementado en una primera interpretación. Esta revisión del plan cubre el trabajo que falta (User Story 1, ahora P1/MVP) sin descartar lo ya construido (User Story 2, ahora P3, se conserva por decisión explícita del usuario en la sesión de clarificación).

**Trabajo ya completado** (US2 — detalle de locación): `locaciones/show.blade.php` ya tiene los botones "Ver Contratos" y "Ver Recibos" condicionados a `es_alquilable`, con su prueba Feature correspondiente. No requiere cambios.

**Trabajo pendiente** (US1 — fila de la tabla jerárquica, P1): la fila de cada locación en `fila-arbol-locacion.blade.php` solo tiene hoy "+" (crear hija) y "Editar" en su columna Acciones. Hay que agregar, únicamente para locaciones alquilables, un menú desplegable Bootstrap ("Acciones", ícono `bi-three-dots-vertical`) que agrupe "Editar", "Ver Contratos" y "Ver Recibos" — decisión de FR-009/FR-010 tomada en la clarificación para no desbordar la columna Acciones (ya de ancho `auto`) con 4 botones sueltos. El botón "+" permanece fuera del menú, siempre visible. No se requieren rutas, controladores, migraciones ni modelos nuevos: reutiliza exactamente las mismas rutas ya existentes (`contratos.index`, `locaciones.recibos.index`) que ya usa la vista de detalle.

## Technical Context

**Language/Version**: PHP 8.3 (constitución exige 8.2+), Laravel 13.17

**Primary Dependencies**: Laravel Blade Components, Bootstrap 5.3 (Sass) + Bootstrap Icons, htmx (`hx-boost`) para navegación/escritura asíncrona (Principio VI de la constitución). El componente `Dropdown` de Bootstrap (incluido en el bundle JS ya importado en `resources/js/bootstrap.js`, junto con Popper) se usa por primera vez en el proyecto en esta feature — antes solo se habían usado `Modal` y `Collapse` del mismo bundle.

**Storage**: PostgreSQL (sin cambios de esquema — no se agregan ni modifican columnas/tablas)

**Testing**: Pest 4 (`pestphp/pest`) sobre PHPUnit 12, siguiendo el patrón de `tests/Feature/LocacionControllerTest.php`

**Target Platform**: Aplicación web Laravel servida vía navegador (sin app móvil nativa)

**Project Type**: Aplicación web monolítica Laravel (single project — no hay frontend/backend separados)

**Performance Goals**: N/A — cambio de vista sin impacto de rendimiento medible (sin nuevas queries; el menú es puro HTML/CSS/JS declarativo vía atributos `data-bs-*`)

**Constraints**: Debe respetar el Principio VI (Bootstrap 5, iconografía consistente `bi-*`, componente `Dropdown` explícitamente permitido para densidad) y el Principio III (contraste WCAG AA, foco/hover visibles y operables por teclado en el nuevo menú) de la constitución del proyecto. El menú desplegable NO debe quedar recortado por el contenedor `.tabla-arbol-locaciones` (que tiene `overflow-x: auto`, ver research.md Decisión 4) ni producir scroll horizontal de página (FR-011).

**Scale/Scope**: Dos archivos de código modificados (`fila-arbol-locacion.blade.php`, `bootstrap.scss`) más su prueba Feature; no se tocan controladores, rutas, ni `locaciones/show.blade.php` (US2, ya completo).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno**: Cumple — no se introduce ningún componente fuera de PHP/Laravel/PostgreSQL; el menú usa únicamente HTML/atributos `data-bs-*` y el bundle JS de Bootstrap ya importado, sin JS custom.
- **II. Nomenclatura en Español**: Cumple — no se agrega código con identificadores; los textos visibles del menú ("Acciones", "Editar", "Ver Contratos", "Ver Recibos") siguen el español ya usado en el resto de la vista.
- **III. Diseño Moderno e Intuitivo**: Cumple — el Principio III fomenta explícitamente "menús desplegables (dropdowns)... donde mejoren la eficiencia y densidad de la interfaz"; este es exactamente ese caso (4 acciones por fila en una tabla ya angosta). El menú requiere estados de foco/hover visibles y operabilidad por teclado (nativos del componente `Dropdown` de Bootstrap, sin override).
- **IV. Pruebas Automatizadas Exhaustivas**: Aplica — se añade una prueba Feature (Pest) sobre `LocacionControllerTest` que verifique, para `locaciones.index`, la presencia del menú con sus 3 opciones en una fila alquilable y su ausencia (solo "Editar" sin menú, o menú sin las opciones de contratos/recibos) en una fila no alquilable.
- **V. Integridad de Datos y Seguridad Transaccional**: N/A — no hay escritura de datos ni transacciones nuevas (navegación de solo lectura).
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: Cumple — usa el componente `Dropdown` nativo de Bootstrap 5 (`data-bs-toggle="dropdown"`), nunca un menú casero; reutiliza los mismos íconos `bi-file-earmark-text`/`bi-receipt`/`bi-pencil-square` ya usados con ese significado en `locaciones/show.blade.php` y `contratos/show.blade.php`, manteniendo consistencia de iconografía. Al modificar vistas Blade, la tarea de implementación DEBE pasar por revisión con el skill `impeccable` antes de darse por completa, según exige este principio.

**Resultado**: PASS — no hay violaciones que requieran justificación en Complexity Tracking.

**Re-check post-diseño (Fase 1)**: `research.md` y `data-model.md` confirman que no se introduce
esquema, ruta ni endpoint nuevo — el menú desplegable reutiliza las mismas rutas ya
documentadas (`contratos.index`, `locaciones.recibos.index`) y solo agrega un componente visual
Bootstrap ya autorizado por la constitución. No se genera carpeta `contracts/` porque la feature
no expone ninguna interfaz nueva. El Constitution Check se mantiene en PASS sin cambios.

## Project Structure

### Documentation (this feature)

```text
specs/014-vista-locacion-navegacion/
├── plan.md              # Este archivo (salida de /speckit-plan, revisado tras /speckit-clarify)
├── research.md          # Salida de Fase 0 (/speckit-plan)
├── data-model.md        # Salida de Fase 1 (/speckit-plan)
├── quickstart.md        # Salida de Fase 1 (/speckit-plan)
└── tasks.md             # Salida de Fase 2 (/speckit-tasks — NO generado por /speckit-plan)
```

No se genera carpeta `contracts/`: la feature no expone ninguna interfaz nueva.

### Source Code (repository root)

```text
resources/views/locaciones/
├── show.blade.php                        # US2 — YA COMPLETO, sin cambios en esta revisión
└── partials/
    └── fila-arbol-locacion.blade.php     # US1 — reemplaza "+"/"Editar" sueltos por "+" +
                                           # menú desplegable "Acciones" (Editar, Ver Contratos,
                                           # Ver Recibos), solo para locaciones alquilables

resources/css/
└── bootstrap.scss                        # US1 — ajuste menor si el ancho de la columna
                                           # Acciones (`auto`) necesita más espacio para el
                                           # botón trigger del menú

routes/web.php                            # Sin cambios — contratos.index/create/edit y
                                           # locaciones.recibos.index ya existen

app/Http/Controllers/
├── ContratoController.php                # Sin cambios
└── ReciboController.php                  # Sin cambios

tests/Feature/
└── LocacionControllerTest.php            # US1 — nueva prueba sobre locaciones.index
                                           # (menú de acciones con/sin opciones según
                                           # es_alquilable); US2 ya cubierta, sin cambios
```

**Structure Decision**: Aplicación web monolítica Laravel de un solo proyecto (no aplica el
layout frontend/backend separado ni mobile+API). El trabajo pendiente se acota a la parcial de
fila del árbol, sus estilos y su prueba Feature; no se crean nuevos directorios, controladores
ni rutas.

## Complexity Tracking

*No aplica — el Constitution Check no registró violaciones que requieran justificación.*
