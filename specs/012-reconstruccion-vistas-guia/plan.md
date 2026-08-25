# Implementation Plan: Reconstrucción de Vistas según la Guía de Referencia Bootstrap

**Branch**: `012-reconstruccion-vistas-guia` | **Date**: 2026-08-21 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/012-reconstruccion-vistas-guia/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Reconstruir 6 componentes visuales específicos (dropzone de documentos, modal de solapamiento en dos bloques, timeline de historial de contratos, grid de costos con total calculado, tarjetas de representante en grid, selector de estado de recibo con 3 opciones simultáneas) para que coincidan con la estructura literal de `docs/referencias-diseno-bootstrap/`, respetando sin excepción las 3 reconciliaciones ya vinculantes del Principio VI (sidebar, htmx, paleta propia). Es un reemplazo de marcado Blade/Bootstrap sobre datos y endpoints ya existentes; solo un componente (el modal de solapamiento) requiere una ampliación mínima y aditiva de un controlador para exponer datos ya calculados que hoy no llegan a la vista (ver `research.md` §3).

## Technical Context

**Language/Version**: PHP 8.3+ (sin cambios)

**Primary Dependencies**: Sin nuevas dependencias — Bootstrap 5.3, Bootstrap Icons, htmx, Chart.js, sass ya presentes desde `specs/010`/`specs/011`. El timeline se construye con primitivas de Bootstrap (`border-start`, `badge`, utilidades de espaciado), no con un componente o librería nueva.

**Storage**: Sin cambios — PostgreSQL, mismas tablas de 001-011. Esta feature no crea, altera ni elimina ninguna tabla.

**Testing**: Pest (sin cambios). La suite completa existente (193 pruebas) se ejecuta como gate de no-regresión tras cada historia de usuario. La única ampliación de controlador (ver §3) no cambia ningún código de estado HTTP, clave de sesión ni comportamiento de validación ya cubierto por pruebas existentes — solo agrega un dato adicional a la respuesta ya existente.

**Target Platform**: Servidor Linux de shared hosting, sin cambios.

**Project Type**: Aplicación web monolítica (single project), sin cambios de estructura.

**Performance Goals**: Sin metas nuevas; el recálculo del total de costos de referencia (FR-004) ocurre client-side sin peticiones adicionales al servidor.

**Constraints**: Ninguna ruta, modelo, servicio, migración, regla de validación o test de negocio existente cambia de comportamiento (FR-010); las 3 reconciliaciones del Principio VI (sidebar, htmx, paleta propia) son no negociables (FR-009).

**Scale/Scope**: 6 componentes puntuales sobre un subconjunto de las ~35 vistas ya migradas (no una reconstrucción completa de cada vista, solo de los componentes explícitamente listados en la especificación); un método de controlador ampliado de forma aditiva.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|---|---|
| I. Stack Tecnológico Moderno | ✅ Sin cambios en PHP/Laravel/PostgreSQL |
| II. Nomenclatura en Español | ✅ Cualquier variable/función JS nueva (recálculo de total, timeline) en español |
| III. Diseño Moderno e Intuitivo | ✅ Objetivo explícito (FR-011): contraste y confirmaciones explícitas se preservan en cada componente reconstruido |
| IV. Pruebas Automatizadas Exhaustivas | ✅ La suite completa (193 pruebas) actúa como gate de no-regresión; no se prevé lógica de negocio nueva que requiera pruebas adicionales, salvo verificar que la ampliación aditiva de §3 no rompe ninguna aserción existente |
| V. Integridad de Datos y Seguridad Transaccional | ✅ Sin cambios: ninguna transacción, validación ni persistencia se altera |
| VI. Sistema de Componentes Visuales (Bootstrap 5) | ✅ Es precisamente el principio que esta feature ejecuta con mayor literalidad; las 3 reconciliaciones ya fijadas en este principio (sidebar, htmx, paleta) se preservan sin excepción (FR-009) |

**Resultado**: PASS. La única observación (ampliación aditiva de `ContratoController`, ver `research.md` §3) se documenta explícitamente como la única excepción necesaria a "no tocar controladores", justificada porque el componente literal exigido por la especificación (FR-002) no es alcanzable con los datos que la vista ya recibe hoy.

## Project Structure

### Documentation (this feature)

```text
specs/012-reconstruccion-vistas-guia/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── mapeo-componentes-guia.md
└── tasks.md
```

### Source Code (repository root)

```text
resources/
├── views/
│   ├── contratos/
│   │   ├── partials/
│   │   │   ├── galeria-documentos.blade.php       # US1: se agrega el dropzone visual
│   │   │   ├── costos-fijos-contrato.blade.php    # US1: grid 2 columnas + total calculado
│   │   │   └── representantes-contrato.blade.php  # US2: grid de tarjetas min-width consistente
│   │   ├── create.blade.php / edit.blade.php       # US1: modal de solapamiento en dos bloques
│   │   └── index.blade.php                         # US1: timeline de historial
│   └── locaciones/recibos/
│       ├── show.blade.php                          # US2: selector de estado btn-group de 3 opciones
│       └── comprobante.blade.php                   # US3: reglas de impresión @media print
└── js/
    └── costos-fijos-contrato.js                    # Nuevo: recálculo en vivo del total de referencia

app/Http/Controllers/
└── ContratoController.php   # Ampliación aditiva puntual: exponer el contrato en conflicto además
                              #  del mensaje ya existente (ver research.md §3), sin alterar ninguna
                              #  ruta, validación ni respuesta ya cubierta por tests

app/Exceptions/
└── ContratoSolapadoException.php   # Sin cambios de comportamiento; ya expone $contratoEnConflicto
                                      #  como propiedad pública, solo faltaba pasarlo a la vista
```

**Structure Decision**: No se reorganiza el árbol de vistas. Se tocan únicamente los 6-7 archivos donde vive cada componente listado en la especificación, más un archivo JS nuevo pequeño; el resto de las ~35 vistas ya migradas en specs/010/011 no se modifican (FR-010, "no es una reconstrucción completa de cada vista").

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Ampliación aditiva de `ContratoController@store`/`@update` (catch de `ContratoSolapadoException`) | El modal de dos bloques (FR-002) necesita los datos estructurados del contrato en conflicto (fechas, inquilino, monto), no solo el mensaje de texto ya generado; `ContratoSolapadoException` ya expone `$contratoEnConflicto` como propiedad pública, solo faltaba pasarlo a la vista | Reconstruir esos datos parseando el string del mensaje en la vista: rechazado, frágil y mezcla lógica de presentación con parsing de texto; construir el mismo dato de nuevo en la vista consultando la base de datos: rechazado, duplicaría lógica de negocio (qué contrato conflictúa) fuera del Service que ya la calcula, violando separación de responsabilidades |
