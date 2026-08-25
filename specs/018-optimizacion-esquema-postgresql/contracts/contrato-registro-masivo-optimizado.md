# Contrato de Comportamiento: `RegistroMasivoLecturasController::store()` optimizado

**Feature**: `018-optimizacion-esquema-postgresql` | **Date**: 2026-08-25

El fix de N+1 (research.md R4) DEBE preservar exactamente el comportamiento observable actual de `POST lecturas.registroMasivo.store`:

## Contrato 1 — Resultado por fila idéntico

- Una fila con `lectura_actual` vacía o ausente se sigue omitiendo en silencio (no cuenta como guardada ni como error) — igual que hoy.
- Una fila con `lectura_actual` no numérica o negativa sigue produciendo el mismo mensaje de error (`"La lectura debe ser un número mayor o igual a 0."`) bajo la misma clave `lecturas.{locacionId}.lectura_actual`.
- Una fila cuya locación no existe o no es alquilable se sigue omitiendo en silencio — igual que hoy.
- Una fila que ya tiene una lectura registrada para ese periodo sigue produciendo el mismo error que lanza hoy `LecturaMedidorDuplicadaException`.
- Una fila con consumo negativo sin `confirmar_consumo_negativo` sigue produciendo el mismo error que lanza hoy `ConsumoNegativoSinConfirmarException`.
- Una fila válida sigue creando exactamente el mismo registro `LecturaMedidor` (mismos valores de `lectura_anterior`, `consumo_calculado`, `fecha_registro`) que produce hoy.

## Contrato 2 — Éxito parcial preservado (FR-002)

- Un lote con una mezcla de filas válidas e inválidas sigue guardando todas las válidas y reportando solo las inválidas en `$errores` — el fix NO envuelve el lote completo en una única transacción todo-o-nada.
- El mensaje final (`"Se registraron {N} lecturas correctamente."`) sigue contando solo las filas efectivamente guardadas.
- Si `$errores` no está vacío, la respuesta sigue siendo `back()->withInput()->withErrors($errores)`, igual que hoy (ninguna lectura válida del mismo lote se pierde ni se revierte).

## Contrato 3 — Reducción de consultas (FR-001, SC-001)

- El número de consultas a la base de datos para procesar un lote de N locaciones ya NO crece de forma lineal con N: las consultas de lookup (locación, lectura existente del periodo, lectura anterior) se reducen a un número constante (3) independientemente de N, y solo el `INSERT` de cada fila válida sigue siendo una consulta por fila.
