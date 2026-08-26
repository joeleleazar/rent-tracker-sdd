# Quickstart: Consumo Calculado en el Momento en vez de Almacenado

**Feature**: `021-derivar-consumo-calculado` | **Date**: 2026-08-25

Guía de validación end-to-end. Ver `data-model.md` y `contracts/accessor-consumo-calculado.md` para
el detalle técnico.

## Prerrequisitos

- Usuario autenticado.
- **Nota de entorno**: usar el binario de PHP de Herd
  (`C:\Users\joel5\.config\herd\bin\php.bat`) para `artisan`/`pest`.
- Una locación con dos lecturas consecutivas (para ver un consumo "normal") y otra locación con una
  única lectura sin ninguna anterior (para ver el caso de Q1:A).

## Escenario 1 — Consumo idéntico al de hoy, con lectura anterior conocida (US1, FR-001/FR-002)

1. Ver el historial individual de una locación con al menos dos lecturas consecutivas.
2. **Resultado esperado**: el consumo de la segunda lectura es exactamente lectura actual menos
   lectura anterior — el mismo valor que ya mostraba antes de esta feature.
3. Repetir la comparación en el formulario de generación de recibo y en el registro masivo para esa
   misma lectura.
4. **Resultado esperado**: los tres lugares muestran el mismo valor entre sí.

## Escenario 2 — Sin lectura anterior, criterio unificado (US1, FR-005, Q1:A)

1. Registrar la primera lectura de una locación nueva desde el flujo **individual** (no el
   registro masivo).
2. Ver esa lectura en el historial individual.
3. **Resultado esperado**: el consumo mostrado es igual a la lectura actual (0 como anterior) — ya
   no dice "sin dato anterior". Este es el cambio de comportamiento intencional confirmado en Q1.
4. Repetir el mismo registro desde el registro masivo.
5. **Resultado esperado**: mismo criterio, mismo resultado — sin diferencia entre ambos flujos.

## Escenario 3 — La importación histórica sigue funcionando sin escribir su propio consumo

1. Correr `ImportarLecturasMedidorHistoricas` (o el comando equivalente) sobre un lote de prueba.
2. **Resultado esperado**: la importación se completa sin errores; las lecturas importadas muestran
   su consumo calculado correctamente en el historial, sin que el comando haya escrito un valor de
   consumo propio.

## Regresión (specs/005/006/015-020, no debe romperse)

- `total` (specs/019) sigue siendo el único valor persistido que usa la generación de recibos —
  esta feature no lo toca.
- `discrepanciaConSiguiente()` (specs/006) sigue funcionando igual — nunca usó
  `consumo_calculado`.
- El registro masivo (specs/015-020: columna Consumo, total editable, exportaciones) sigue
  mostrando los mismos valores que antes, ahora derivados en vez de leídos de una columna.
