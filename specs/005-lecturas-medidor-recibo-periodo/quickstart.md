# Quickstart: Lecturas de Medidor de Luz y Recibo por Periodo

**Feature**: `005-lecturas-medidor-recibo-periodo` | **Date**: 2026-08-20

Guía de validación end-to-end. Ver `data-model.md` y `contracts/rutas-lecturas-medidor-recibo-periodo.md` para el detalle técnico, y `tasks.md` para las tareas de construcción.

## Prerrequisitos

- Migraciones de `specs/001-004` ya ejecutadas (incluye `configuracion_general`, `recibos`).
- Migraciones de esta feature ejecutadas (`lecturas_medidor`, `recibos` alterada, `configuracion_general` alterada).
- `tarifa_luz_por_unidad` configurada en `/configuracion` con un valor mayor a cero.
- Usuario autenticado.

## Escenario 1 — Registro de lectura mensual (US1)

1. Ir a `/locaciones/{locacion}/lecturas/crear` e ingresar lectura "1250" para el periodo "Agosto 2026".
2. **Resultado esperado**: se guarda la lectura y se muestra el consumo calculado como la diferencia con el periodo anterior (o "sin dato anterior" si es el primero).
3. Intentar registrar una segunda lectura para "Agosto 2026" en la misma locación.
4. **Resultado esperado**: el sistema ofrece editar la lectura existente en vez de crear un duplicado.

## Escenario 2 — Generación de recibo con conceptos configurables (US2)

1. Con lectura de "Agosto 2026" registrada (consumo 150) y un contrato activo con renta "S/ 1500.00", ir a `/locaciones/{locacion}/recibos/crear?periodo=2026-08`.
2. **Resultado esperado**: se muestran los 5 conceptos con casillas de inclusión, montos precargados (renta del contrato, luz = 150 × tarifa vigente).
3. Desmarcar "seguridad" y editar el monto de "luz" antes de confirmar.
4. **Resultado esperado**: el recibo se emite sin el concepto de seguridad y con el monto de luz editado; el contrato no se modifica.
5. Intentar generar un segundo recibo para la misma locación y el mismo periodo.
6. **Resultado esperado**: el sistema advierte que ya existe un recibo para ese periodo y ofrece editarlo.

## Escenario 3 — Historial de consumo y recibos (US3)

1. Con lecturas de "Junio 2026", "Julio 2026" y "Agosto 2026" registradas, ir a `/locaciones/{locacion}/lecturas` (o `/locaciones/{locacion}/recibos` para el historial de recibos).
2. **Resultado esperado**: los tres periodos se listan en orden cronológico con lectura, consumo calculado y enlace al recibo (si existe), tipografía ≥18px y alto contraste.

## Escenario 4 — Lectura menor a la anterior (Edge Case)

1. Con lectura anterior de "1250", registrar una nueva lectura de "1100" para el siguiente periodo.
2. **Resultado esperado**: el sistema advierte en alto contraste que el consumo sería negativo y exige confirmación explícita antes de guardar.

## Escenario 5 — Recibo sin contrato activo (Edge Case)

1. Intentar generar un recibo para una locación sin contrato activo vigente en el periodo solicitado.
2. **Resultado esperado**: el sistema bloquea la generación del recibo con un mensaje explícito, pero permite registrar la lectura del medidor de ese mismo periodo sin problema.

## Escenario 6 — Edición de lectura ya usada en recibo emitido (Edge Case)

1. Editar la lectura de un periodo cuyo recibo ya fue emitido.
2. **Resultado esperado**: el sistema advierte que el recibo emitido no se actualiza automáticamente.

## Validación automatizada (referencia)

```bash
php artisan test --filter=LecturaMedidor
php artisan test --filter=ServicioGeneracionReciboPeriodo
```

**Cobertura esperada** (Principio IV): modelo `LecturaMedidor` (cálculo de consumo, unicidad por periodo), `ServicioCalculoConsumoMedidor` (consumo negativo, "sin dato anterior"), `ServicioGeneracionReciboPeriodo` (bloqueo sin contrato activo, no-duplicación, conceptos seleccionables), `LecturaMedidorController`/`ReciboController` (happy path, validación 422).
