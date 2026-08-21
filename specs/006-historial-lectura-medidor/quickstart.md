# Quickstart: Traslado Editable de Lectura Anterior e Historial de Medidor

**Feature**: `006-historial-lectura-medidor` | **Date**: 2026-08-20

Guía de validación end-to-end. Ver `data-model.md` y `contracts/rutas-historial-lectura-medidor.md` para el detalle técnico, y `tasks.md` para las tareas de construcción.

## Prerrequisitos

- Migraciones de `specs/001-005` ya ejecutadas.
- Migración de esta feature ejecutada (`lecturas_medidor` con `lectura_anterior`/`lectura_actual`).
- Usuario autenticado.

## Escenario 1 — Traslado automático de lectura actual a lectura anterior (US1)

1. Registrar lectura actual "1250" para "Local A" en el periodo "Julio 2026".
2. Iniciar el registro del periodo "Agosto 2026" para "Local A".
3. **Resultado esperado**: el campo "lectura anterior" aparece precargado con "1250".
4. Repetir el registro para una locación sin ningún periodo previo.
5. **Resultado esperado**: el campo "lectura anterior" aparece vacío con el texto "Sin lectura previa registrada", sin bloquear el ingreso de la lectura actual.

## Escenario 2 — Edición del valor trasladado antes de confirmar (US2)

1. Con "lectura anterior" precargada en "1250", editarla a "1245" e ingresar "lectura actual" "1400".
2. **Resultado esperado**: el consumo calculado se muestra como "155" (1400 - 1245); se guarda "1245" como la lectura anterior utilizada.
3. Consultar el registro histórico del periodo previo (Julio 2026).
4. **Resultado esperado**: su "lectura actual" permanece en "1250", sin cambios, aunque ahora difiera del "1245" usado como anterior en Agosto.

## Escenario 3 — Consulta del historial completo (US3)

1. Con lecturas registradas para "Mayo 2026" a "Agosto 2026" (4 periodos), consultar el historial de la locación.
2. **Resultado esperado**: se listan los 4 periodos en orden cronológico, cada uno con "lectura anterior", "lectura actual" y consumo calculado, con tipografía ≥18px y alto contraste.

## Escenario 4 — Corrección posterior de una lectura ya trasladada (Edge Case)

1. Editar la "lectura actual" de "Julio 2026" (ya trasladada como "lectura anterior" de "Agosto 2026") a un nuevo valor.
2. Consultar el historial.
3. **Resultado esperado**: "Agosto 2026" no se actualiza automáticamente; el historial muestra una advertencia visible indicando la discrepancia entre ambos periodos, sin bloquear ninguna acción.

## Escenario 5 — Registro de periodos fuera de orden (Edge Case)

1. Con "Junio 2026" registrado, registrar directamente "Agosto 2026" (saltando "Julio 2026") para la misma locación.
2. **Resultado esperado**: el sistema traslada como "lectura anterior" la "lectura actual" de "Junio 2026" (el periodo disponible más reciente), indicando de qué periodo proviene.

## Validación automatizada (referencia)

```bash
php artisan test --filter=LecturaMedidor
```

**Cobertura esperada** (Principio IV): modelo `LecturaMedidor` (autocompletado, edición desacoplada, discrepancia con el periodo siguiente), `ServicioCalculoConsumoMedidor` (sugerencia con y sin huecos de periodos, cálculo directo de consumo), `LecturaMedidorController` (formulario con `lectura_anterior` editable, historial con indicador de discrepancia).
