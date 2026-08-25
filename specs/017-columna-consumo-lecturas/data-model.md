# Data Model: Columna de Consumo y Alineación del Ícono de Completado en Registro Masivo

**Feature**: `017-columna-consumo-lecturas` | **Date**: 2026-08-25

Esta feature **no agrega ni modifica columnas, tablas ni relaciones**. No hay entidad nueva: es
una capa de presentación adicional sobre un valor que ya existe y ya se persiste.

## Entidad reutilizada (sin cambios de esquema)

### LecturaMedidor

| Campo usado | Regla que esta feature expone en pantalla |
|---|---|
| `consumo_calculado` | Ya calculado y persistido por `ServicioCalculoConsumoMedidor::calcularConsumo()` (specs/006) al guardar una lectura. Esta feature lo muestra tal cual en la nueva columna "Consumo" para filas ya completadas — el mismo valor que ya viaja hoy a `data-consumo` en `campo-lectura-registro-masivo.blade.php` y a la exportación Excel/PDF (FR-002). No se recalcula ni se re-deriva: se lee y se muestra. |

Para filas todavía no guardadas (FR-003/FR-004/FR-005), el valor de "Consumo" no proviene de
`consumo_calculado` (no existe todavía) sino de una resta en memoria del lado del cliente entre el
valor tipeado en el campo y la lectura anterior ya resuelta por `datosDelPeriodo()` — sin acceso a
base de datos nuevo, igual que ya ocurre hoy para el cálculo en vivo de "Total".

## Relaciones

Sin cambios respecto a `specs/015-registro-masivo-lecturas/data-model.md` y
`specs/016-correccion-registro-masivo-lecturas/data-model.md`.

## Reglas de validación

Sin cambios: esta feature no valida ni persiste ningún dato nuevo, solo expone en pantalla un
valor ya calculado y ya validado en el momento del guardado original de la lectura.
