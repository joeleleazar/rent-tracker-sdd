# Implementation Plan: Mejoras al Flujo de Recibos y Lecturas

**Branch**: `026-mejoras-recibos-lecturas` | **Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/026-mejoras-recibos-lecturas/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Cinco correcciones/ampliaciones acotadas sobre el flujo de recibos y lecturas ya existente: (1) los
recibos anulados dejan de contar como cobertura de sus conceptos, tanto en la UI del registro masivo como
en la validación de superposición y en el conteo de "en uso" para eliminar un concepto del catálogo; (2)
la generación de un recibo desde el registro masivo deja de ser un modal y reutiliza la página individual
ya existente (`locaciones.recibos.create`), sumándole un borrador guardable/recuperable (mismo patrón que
`BorradorLecturaMedidor` de specs/015, con los conceptos en una columna `jsonb`); (3) cada fila del
registro masivo gana una acción "Ver Recibos" acotada al periodo visible, que redirige directo si hay uno
solo o lista si hay varios; (4) la barra de herramientas del registro masivo de lecturas se reordena en
una sola fila; (5) se cierra el único vacío real de la eliminación de conceptos de gasto fijo (que ya
existe), el mismo ajuste del punto (1).

## Technical Context

**Language/Version**: PHP 8.3 (`composer.json` `"php": "^8.3"`), Laravel 13.x

**Primary Dependencies**: Laravel 13 (Eloquent, Form Requests, Blade), htmx (`hx-boost`, interactividad
asíncrona — Principio VI de la constitución), Bootstrap 5.3 (compilado desde Sass) + Bootstrap Icons,
maatwebsite/excel y barryvdh/laravel-dompdf (exportación ya existente, sin cambios)

**Storage**: PostgreSQL 15+ — nueva tabla `borradores_recibo` (con columna `jsonb`); sin cambios de
esquema en `recibos`, `recibo_conceptos` ni `conceptos_gasto_fijo`

**Testing**: Pest (PHPUnit) — Feature tests por controlador/servicio, igual que el resto del proyecto

**Target Platform**: Aplicación web servida por Laravel (Herd en desarrollo), navegador de escritorio como
uso principal (responsive sin scroll horizontal en breakpoints Bootstrap estándar)

**Project Type**: Aplicación web monolítica (Laravel + Blade), sin frontend separado ni API pública

**Performance Goals**: Sin metas de rendimiento nuevas — se mantiene la disciplina anti-N+1 ya establecida
(specs/018/023/024) al agregar la acción "Ver Recibos" y el borrador a las pantallas de listado existentes

**Constraints**: Ninguna corrección de esta feature debe alterar el comportamiento ya probado de
specs/015 (borrador de lecturas), specs/018 (esquema/anti-N+1), specs/023 (múltiples recibos por
locación/periodo) ni specs/024 (catálogo dinámico, periodo ágil, totales por locación) salvo los cambios
explícitamente descritos aquí

**Scale/Scope**: Mismo alcance de datos que el resto del sistema (decenas de locaciones, cientos de
recibos) — sin cambios de escala

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno**: Cumple — todo el trabajo usa Eloquent/Migraciones/Form Requests
  idiomáticos de Laravel sobre PostgreSQL; la columna `jsonb` de `borradores_recibo` es un tipo específico
  de PostgreSQL usado deliberadamente (research.md Decisión 4), no un bypass del ORM.
- **II. Nomenclatura en Español**: Cumple — `BorradorRecibo`, `borradores_recibo`, `scopeVigente()`,
  `recibosDelPeriodo`, etc., siguen la convención ya establecida en el resto del código.
- **III. Diseño Moderno e Intuitivo**: Cumple — la página de generación de recibo reemplaza un modal por
  una página completa (más clara, no menos), y la eliminación de un concepto sigue pasando por el modal de
  confirmación de dos botones ya exigido (sin cambios ahí). El reacomodo de la barra de lecturas usa
  `flex-wrap` para no romper el responsive.
- **IV. Pruebas Automatizadas Exhaustivas**: Aplica — cada FR de spec.md requiere su propio test Feature
  (scope `vigente()`, borrador guardar/recuperar/descartar, redirección 1-vs-varios de "Ver Recibos",
  eliminación de concepto con uso solo en recibos anulados). Se detalla en tasks.md.
- **V. Integridad de Datos y Seguridad Transaccional**: Cumple — `generar()`/`actualizar()` de
  `ServicioGeneracionReciboPeriodo` ya corren dentro de `DB::transaction()`; el descarte del borrador tras
  confirmar se hace dentro de esa misma transacción o inmediatamente después de que se confirma el éxito
  (no antes), para no perder el borrador si la emisión falla.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: Cumple — la página de generación de recibo ya usa
  `card`/`input-group`/`btn`; el nuevo botón "Guardar Borrador" y el estado de autoguardado siguen esos
  mismos componentes. La revisión con el skill `impeccable` es obligatoria antes de dar por completas las
  vistas modificadas/nuevas (`locaciones/recibos/create.blade.php`, `recibos/registro-masivo/*.blade.php`,
  `lecturas/registro-masivo/index.blade.php`, `recibos/registro-masivo/recibos-del-periodo.blade.php`).

Sin violaciones — no se requiere la sección de Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/026-mejoras-recibos-lecturas/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md         # Phase 1 output (/speckit-plan command)
├── quickstart.md         # Phase 1 output (/speckit-plan command)
├── contracts/            # Phase 1 output (/speckit-plan command)
│   ├── exclusion-recibos-anulados.md
│   ├── generacion-recibo-pagina.md
│   ├── borrador-recibo.md
│   └── ver-recibos-del-periodo.md
└── tasks.md              # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

Aplicación Laravel monolítica ya existente (Opción "Single project", sin frontend/backend separados).
Archivos relevantes para esta feature:

```text
app/
├── Models/
│   ├── Recibo.php                          # + scopeVigente()
│   └── BorradorRecibo.php                  # nuevo
├── Http/
│   ├── Controllers/
│   │   ├── ReciboController.php            # create()/store(): borrador + carga/descarte
│   │   ├── RegistroMasivoRecibosController.php  # index(): quita modal()/store(); + recibosDelPeriodo()
│   │   └── ConceptoGastoFijoController.php # index()/destroy(): conteo "en uso" excluye anulados
│   └── Requests/
│       └── SolicitudGuardarReciboRegistroMasivo.php   # eliminado (sin llamador)
├── Services/
│   └── ServicioGeneracionReciboPeriodo.php # conceptosDisponibles/reciboQueCubre/validarSinSuperposicion
database/
└── migrations/
    └── ..._create_borradores_recibo_table.php  # nueva

resources/views/
├── locaciones/recibos/
│   └── create.blade.php                    # + borrador (botón, autoguardado, prellenado)
├── recibos/registro-masivo/
│   ├── index.blade.php                     # quita contenedor de modal; + "Ver Recibos" por fila
│   ├── recibos-del-periodo.blade.php       # nueva (lista cuando hay 2+ recibos)
│   └── partials/
│       ├── estado-recibo-locacion.blade.php     # "Generar Recibo" → enlace normal; + "Ver Recibos"
│       ├── fila-registro-masivo-recibos.blade.php
│       ├── modal-recibo.blade.php          # eliminado (sin llamador)
│       └── error-modal-recibo.blade.php    # eliminado (sin llamador)
└── lecturas/registro-masivo/
    └── index.blade.php                     # barra de herramientas en una sola fila

routes/web.php   # + locaciones.recibos.borrador, + recibos.registroMasivo.recibosDelPeriodo
                 # - recibos.registroMasivo.modal, - recibos.registroMasivo.store

tests/Feature/
├── ServicioGeneracionReciboPeriodoTest.php
├── ReciboControllerTest.php
├── RegistroMasivoRecibosControllerTest.php
├── ConceptoGastoFijoControllerTest.php
└── RegistroMasivoLecturasControllerTest.php   # sin cambios de lógica, solo si algún test toca el HTML
```

**Structure Decision**: Se mantiene la estructura Laravel estándar ya usada por todo el proyecto
(`app/Models`, `app/Http/Controllers`, `app/Http/Requests`, `app/Services`, `resources/views`,
`database/migrations`, `tests/Feature`) — ninguna carpeta ni patrón arquitectónico nuevo se introduce; esta
feature es una serie de cambios acotados dentro de controladores, un servicio y vistas ya existentes, más
un modelo/tabla transitorios (`BorradorRecibo`) que sigue el precedente ya establecido por
`BorradorLecturaMedidor`.

## Complexity Tracking

Sin violaciones a la constitución — tabla no aplica (ver Constitution Check arriba).
