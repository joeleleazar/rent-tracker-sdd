# Data Model: Correcciones de Auditoría Impeccable

**Feature**: `025-correcciones-auditoria-impeccable` | **Date**: 2026-08-26

Este feature no cambia el esquema de base de datos ni agrega entidades. `locaciones.tipo` ya es una columna nullable (migración `2026_08_23_020000_add_tipo_to_locaciones_table.php`); el único cambio es en la capa de validación de `SolicitudGuardarLocacion`, no en el modelo `Locacion` ni en la tabla.

## `Locación` — regla de validación de `tipo` (sin cambio de forma)

| Contexto | Valor previo de `tipo` | Regla aplicada a `tipo` |
|---|---|---|
| Crear (`POST /locaciones`) | N/A (no existe la locación aún) | `required` + `Rule::in(TIPOS)` — sin cambio |
| Editar (`PUT /locaciones/{locacion}`) | `null` | `nullable` + `Rule::in(TIPOS)` — nuevo |
| Editar (`PUT /locaciones/{locacion}`) | valor válido existente | `required` + `Rule::in(TIPOS)` — sin cambio |

## `.sidebar-principal` — origen del estilo (sin cambio de apariencia)

| Regla | Antes | Después |
|---|---|---|
| `background-color` | `<style>` embebido en `app-bootstrap.blade.php`, hex literal `#111827` | `bootstrap.scss`, token `$dark` |
| `width`/`min-height` (responsive) | `<style>` embebido en `app-bootstrap.blade.php` | `bootstrap.scss`, mismo breakpoint `768px` |
