# Implementation Plan: Emisión Masiva de Recibos por Periodo

**Branch**: `023-emision-masiva-recibos` | **Date**: 2026-08-25 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/023-emision-masiva-recibos/spec.md`

## Summary

Nueva pantalla "Registro Masivo de Recibos", estructuralmente análoga a "Registro Masivo de Lecturas"
(specs/015): un árbol de locaciones para un periodo seleccionable, donde cada locación con contrato activo
muestra sus conceptos todavía no cubiertos (renta prorrateada, luz por consumo, agua/pasadizo/seguridad del
contrato) y un botón que abre un modal Bootstrap con esos conceptos disponibles, montos editables y
checkboxes. Confirmar el modal genera un recibo de inmediato (vía htmx, sin recargar la página) con
exactamente los conceptos marcados, y la fila se actualiza sola. Esto habilita tanto la generación en bloque
de varias locaciones (repitiendo la acción, Historia 2) como el cobro fraccionado de una misma locación en
más de un recibo sin repetir conceptos (reabriendo el modal, Historia 3). El cambio de fondo es reemplazar
la regla de negocio actual "un solo recibo por locación y periodo" (`ReciboDuplicadoPeriodoException`) por
una regla a nivel de concepto, compartida entre este flujo nuevo y el flujo individual ya existente
(`ReciboController`/`ServicioGeneracionReciboPeriodo`).

## Technical Context

**Language/Version**: PHP 8.2+ (binario de Herd en esta máquina: `C:\Users\joel5\.config\herd\bin\php.bat`)

**Primary Dependencies**: Laravel 11.x (Eloquent, Form Requests, `DB::transaction`), htmx (`hx-get`/`hx-post`/
`hx-swap`, sin Alpine.js — Principio VI de la constitución), Bootstrap 5.3 (`Modal` nativo vía
`bootstrap.Modal`), Bootstrap Icons

**Storage**: PostgreSQL 15+ — tabla `recibos` existente; requiere **una migración** para quitar la constraint
`UNIQUE(locacion_id, periodo)` agregada en specs/004 (research.md Decisión 2, corregida tras encontrar la
constraint real al ejecutar los primeros tests de esta feature — la investigación inicial la había pasado
por alto)

**Testing**: Pest — Feature tests de controlador (flujo masivo nuevo y flujo individual ya existente, para
confirmar que ambos respetan la misma regla de no-superposición) + Unit tests del servicio de generación

**Target Platform**: Web (navegador), rutas autenticadas del panel administrativo existente

**Project Type**: Aplicación web Laravel monolítica existente — esta feature agrega un controlador, una
vista con sus parciales, y modifica un servicio ya existente; no se agrega ninguna capa ni carpeta nueva

**Performance Goals**: mismo orden de magnitud que "Registro Masivo de Lecturas" (decenas de locaciones por
página) — sin metas de rendimiento nuevas

**Constraints**: el modal NUNCA debe ofrecer un concepto ya cubierto (FR-006); la confirmación DEBE
re-validar contra el estado real de la base de datos en el momento de confirmar, no contra el estado leído
cuando el modal se abrió (condición de carrera, FR-008) — se resuelve con `DB::transaction()` +
`lockForUpdate()` sobre los recibos existentes de esa locación y periodo antes de insertar, ya que la regla
de no-superposición es sobre un conjunto de columnas booleanas, no una restricción de unicidad simple que la
propia base de datos pueda arbitrar con una constraint

**Scale/Scope**: 1 controlador nuevo, 1 servicio existente modificado (regla de negocio compartida con el
flujo individual), 1 excepción nueva reemplazando a `ReciboDuplicadoPeriodoException`, 3-4 vistas/parciales
nuevas, 1 migración (quitar `UNIQUE(locacion_id, periodo)` de `recibos`)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno**: cumple — Eloquent/Form Requests, sin SQL crudo; el `lockForUpdate()` de
  Constraints usa el mecanismo de bloqueo de Eloquent (`->lockForUpdate()`), no SQL directo.
- **II. Nomenclatura en Español**: cumple — controlador, rutas, servicio y vistas se nombran en español
  siguiendo el mismo patrón ya usado por `RegistroMasivoLecturasController`/`registro-masivo`.
- **III. Diseño Moderno e Intuitivo**: cumple — el modal es exactamente el tipo de componente interactivo
  moderno que este principio fomenta explícitamente ("dropdowns, popovers, tooltips... donde mejoren la
  eficiencia y densidad de la interfaz"); estados de foco/hover ya los resuelve el `Modal` nativo de
  Bootstrap.
- **IV. Pruebas Automatizadas Exhaustivas**: cumple — a diferencia de specs/022 (comando/seeder sin test),
  esta feature SÍ es lógica de dominio expuesta por controlador/servicio y SÍ requiere cobertura Pest
  completa (happy path, validación, autorización, condición de carrera) tanto del flujo nuevo como del
  flujo individual ya existente que ahora comparte la misma regla.
- **V. Integridad de Datos y Seguridad Transaccional**: cumple — cada generación de recibo (nueva y
  existente) queda envuelta en `DB::transaction()` con `lockForUpdate()` sobre los recibos existentes de esa
  locación/periodo antes de insertar, precisamente para blindar la regla de no-superposición bajo
  concurrencia (FR-008).
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: aplica en forma completa — se crean/modifican
  vistas Blade (`resources/views/recibos/registro-masivo/**`), así que esta feature DEBE pasar por una
  revisión con el skill `impeccable` (`/impeccable polish` o `audit`) antes de darse por completa, y
  documentar el resultado en `DESIGN.md` si corresponde.

**Resultado**: PASS, sin desviaciones que justificar.

## Project Structure

### Documentation (this feature)

```text
specs/023-emision-masiva-recibos/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
├── checklists/
│   └── requirements.md
└── tasks.md              # Phase 2 output (/speckit-tasks command)
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── RegistroMasivoRecibosController.php       # nuevo — index, modal (GET), store (POST)
│   │   └── ReciboController.php                      # modificado — create()/store() usan la nueva regla
│   │                                                   # a nivel de concepto (Assumption A-003) en vez de
│   │                                                   # "existe algún recibo" ($reciboExistente)
│   └── Requests/
│       └── SolicitudGuardarReciboRegistroMasivo.php  # nuevo — conceptos + montos del modal
├── Exceptions/
│   └── ConceptosReciboYaCubiertosException.php       # nuevo — reemplaza a ReciboDuplicadoPeriodoException
└── Services/
    └── ServicioGeneracionReciboPeriodo.php           # modificado — regla de no-superposición por concepto,
                                                        # compartida por ReciboController y el nuevo controlador

resources/
└── views/
    └── recibos/
        └── registro-masivo/
        │   ├── index.blade.php                        # árbol de locaciones + selector de periodo
        │   └── partials/
        │       ├── fila-registro-masivo-recibos.blade.php   # fila recursiva (análoga a fila-registro-masivo de lecturas)
        │       └── modal-recibo.blade.php                    # contenido del modal (conceptos disponibles)
        └── locaciones/recibos/create.blade.php        # modificado — oculta conceptos ya cubiertos por
                                                          # otro recibo del mismo periodo, en vez de solo
                                                          # bloquear si "ya existe un recibo"

routes/web.php   # nuevo grupo de rutas recibos.registroMasivo.*
```

**Structure Decision**: aplicación Laravel monolítica existente (Option 1, adaptada) — misma estructura ya
usada por `RegistroMasivoLecturasController`/`lecturas/registro-masivo`, replicada para recibos. El único
archivo de dominio ya existente que cambia de comportamiento (no de esquema) es
`ServicioGeneracionReciboPeriodo`, para que la regla de no-superposición de conceptos sea una sola fuente de
verdad compartida por el flujo individual (`ReciboController`) y el flujo masivo nuevo (Assumption A-003 de
spec.md).

## Complexity Tracking

*Sin violaciones que requieran justificación — Constitution Check en PASS.*
