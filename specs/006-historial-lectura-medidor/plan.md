# Implementation Plan: Traslado Editable de Lectura Anterior e Historial de Medidor

**Branch**: `006-historial-lectura-medidor` | **Date**: 2026-08-20 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/006-historial-lectura-medidor/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Refinar el modelo de `LecturaMedidor` (introducido en `specs/005`) reemplazando el cálculo implícito de consumo (comparando contra el registro del periodo anterior en tiempo de consulta) por dos columnas explícitas y editables por registro: `lectura_anterior` (autocompletada desde la `lectura_actual` del periodo cronológicamente más reciente, pero editable) y `lectura_actual`. El consumo se calcula siempre a partir de ambos valores del mismo registro, y cada periodo queda desacoplado tras guardarse: editar la `lectura_anterior` de un nuevo periodo NO modifica retroactivamente el periodo previo del cual se trasladó el valor. Se agrega una advertencia de discrepancia (no bloqueante) cuando un periodo y el siguiente quedan desincronizados. Enfoque técnico: migración de alteración sobre `lecturas_medidor` (renombra `lectura` a `lectura_actual`, agrega `lectura_anterior`, migración de datos para poblarla desde el historial existente), y un `ServicioCalculoConsumoMedidor` (de `specs/005`) refactorizado para separar "sugerir lectura anterior" (autocompletado) de "calcular consumo" (resta directa de ambas columnas del mismo registro).

## Technical Context

**Language/Version**: PHP 8.3+ (`composer.json`)

**Primary Dependencies**: Laravel 13.x (misma nota de discrepancia que `specs/001` §1), Eloquent ORM, Blade, Pest 4

**Storage**: PostgreSQL; se altera `lecturas_medidor` (de `specs/005`): renombra `lectura` → `lectura_actual`, agrega `lectura_anterior` nullable

**Testing**: Pest, `RefreshDatabase`; unit tests para el modelo `LecturaMedidor` (autocompletado, edición desacoplada, discrepancia) y `ServicioCalculoConsumoMedidor` refactorizado; feature tests para `LecturaMedidorController` (de `specs/005`, ahora con `lectura_anterior` editable)

**Target Platform**: Servidor Linux de shared hosting, consistente con specs previas

**Project Type**: Aplicación web monolítica (single project)

**Performance Goals**: Precarga de `lectura_anterior` autocompletada en <2s al iniciar el registro (SC-001); confirmación de consumo recalculado tras edición en <30s de interacción humana (SC-002, no es una métrica de sistema)

**Constraints**: El historial completo de periodos MUST permanecer accesible e inalterado ante el registro de nuevos periodos (FR-005); la sincronización entre periodos es unidireccional solo al momento del traslado (autocompletar), nunca retroactiva (FR-006, A-003)

**Scale/Scope**: Mismo volumen que `LecturaMedidor` de `specs/005` (una fila por locación y periodo)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|---|---|
| I. Stack Tecnológico Moderno (PHP/Laravel/PostgreSQL) | ✅ Migración de alteración con `renameColumn`, `NUMERIC`/`decimal:2`, Service desacoplado, sin SQL crudo. ⚠️ Misma nota de discrepancia de versión de Laravel que `specs/001` (preexistente) |
| II. Nomenclatura en Español | ✅ Columnas `lectura_anterior`/`lectura_actual` en español; se mantiene `LecturaMedidor`, `LecturaMedidorController`, `ServicioCalculoConsumoMedidor` (de `specs/005`, refactorizado) |
| III. Accesibilidad Senior-First | ✅ Campo "lectura anterior" claramente editable con indicación "Sin lectura previa registrada" cuando no aplica; advertencia de discrepancia en alto contraste al consultar el historial (FR-007), sin bloquear el registro |
| IV. Pruebas Automatizadas Exhaustivas | ✅ Pest cubre `LecturaMedidor` (autocompletado, edición desacoplada tras guardar, discrepancia con periodo siguiente), `ServicioCalculoConsumoMedidor` (cálculo directo lectura_actual - lectura_anterior, sugerencia desde el periodo más reciente disponible incluso con huecos), `LecturaMedidorController` (formulario con `lectura_anterior` editable) |
| V. Integridad de Datos y Seguridad Transaccional | ✅ Migración de datos ejecutada dentro de una transacción única al alterar el esquema; `decimal:2` en `lectura_anterior`/`lectura_actual`/`consumo_calculado`; ningún guardado de un periodo modifica filas de otros periodos (Principio V, consistencia con el resto de recibos/contratos inmutables históricamente) |

**Resultado**: PASS. No se identifican violaciones que requieran justificación en `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/006-historial-lectura-medidor/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── rutas-historial-lectura-medidor.md
└── tasks.md
```

### Source Code (repository root)

```text
app/
├── Models/
│   └── LecturaMedidor.php                    # Se agregan lectura_anterior/lectura_actual (de specs/005, reemplaza lectura), helper discrepanciaConSiguiente()
└── Services/
    └── ServicioCalculoConsumoMedidor.php      # Se refactoriza (de specs/005): sugerirLecturaAnterior() + calcularConsumo() separados

database/
└── migrations/
    └── xxxx_add_lectura_anterior_to_lecturas_medidor_table.php   # Nuevo (ALTER: rename lectura→lectura_actual, agrega lectura_anterior, migración de datos)

resources/
└── views/
    └── locaciones/
        └── lecturas/
            ├── create.blade.php               # Se modifica (de specs/005): campo lectura_anterior autocompletado y editable
            └── index.blade.php                # Se modifica (de specs/005): columna de discrepancia visible

tests/
├── Feature/
│   └── LecturaMedidorControllerTest.php       # Se extiende (de specs/005)
└── Unit/
    ├── LecturaMedidorTest.php                 # Se extiende (de specs/005): autocompletado, desacoplamiento, discrepancia
    └── ServicioCalculoConsumoMedidorTest.php   # Se extiende (de specs/005): sugerencia con huecos de periodos
```

**Structure Decision**: Aplicación Laravel monolítica única. Esta feature no introduce entidades nuevas; refina el esquema y la lógica de `LecturaMedidor` y `ServicioCalculoConsumoMedidor` ya creados en `specs/005`, mediante una migración de alteración con migración de datos (ver `research.md` §1) y un refactor del Service existente, sin tocar `Recibo` ni `Contrato`.

## Complexity Tracking

*No violations identified — table intentionally left empty.*
