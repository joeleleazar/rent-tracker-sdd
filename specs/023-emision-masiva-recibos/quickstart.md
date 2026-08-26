# Quickstart: Emisión Masiva de Recibos por Periodo

Escenarios de validación manual, a correr tras implementar. Usar el binario de PHP de Herd en esta máquina:
`C:\Users\joel5\.config\herd\bin\php.bat`.

## Escenario 1 — Generar el primer recibo de una locación (Historia 1 + 2)

1. Con una locación con contrato activo y ninguna lectura/recibo del periodo, abrir
   `/recibos/registro-masivo?periodo=2026-08`.
2. Verificar que la fila de esa locación muestra un botón "Generar Recibo" (o equivalente) y ningún
   concepto marcado como cubierto.
3. Abrir el modal de esa fila: verificar que los 5 conceptos aparecen disponibles, cada uno con su monto
   sugerido (renta según contrato o prorrateo, luz según lectura del periodo o S/ 0.00 si no hay, agua/
   pasadizo/seguridad según el contrato).
4. Marcar todos los conceptos, confirmar. Verificar: el recibo se crea de inmediato, el modal se cierra, la
   fila se actualiza sola mostrando el periodo completo, sin recargar la página.

## Escenario 2 — Varias locaciones en la misma visita, sin recargar (Historia 2)

1. En la misma pantalla del Escenario 1, repetir la generación con una segunda locación distinta.
2. Verificar que ambos recibos quedaron creados de forma independiente y que en ningún momento se recargó
   la página completa (network tab: solo requests `hx-get`/`hx-post` parciales).

## Escenario 3 — Cobro fraccionado sin repetir conceptos (Historia 3)

1. Generar un recibo para una locación marcando únicamente "renta".
2. Reabrir el modal de esa misma locación (sin recargar la página). Verificar: "renta" ya no aparece como
   opción; "luz", "agua", "pasadizo" y "seguridad" sí.
3. Marcar los 4 conceptos restantes y confirmar. Verificar: se crea un segundo recibo independiente, con
   exactamente esos 4 conceptos, sin duplicar "renta". La fila ahora muestra el periodo completo.
4. Abrir `recibos.show` de ambos recibos y confirmar que cada uno tiene exactamente los conceptos y montos
   esperados (ningún concepto en ambos).

## Escenario 4 — Condición de carrera (FR-008)

1. Con una locación con "renta" todavía disponible, abrir el modal en dos pestañas distintas del mismo
   navegador (simula dos confirmaciones casi simultáneas).
2. En la pestaña A, marcar "renta" y confirmar — debe generar el recibo con éxito.
3. Sin recargar la pestaña B (que todavía muestra "renta" como disponible, dato ya obsoleto), marcar
   "renta" ahí también y confirmar.
4. Verificar: la pestaña B recibe un error 422 indicando que "renta" ya fue cubierta (por el recibo de la
   pestaña A), y NO se crea un segundo recibo con "renta" duplicada.

## Escenario 5 — El flujo individual respeta la misma regla (Assumption A-003)

1. Con una locación que ya tiene, desde el Escenario 3, un recibo cubriendo "renta" y otro cubriendo el
   resto, abrir el flujo individual (`locaciones.recibos.create`) para esa misma locación y periodo.
2. Verificar que el sistema indica que el periodo ya está completamente cubierto (ya no ofrece generar un
   tercer recibo con conceptos repetidos) — mismo criterio que la pantalla de registro masivo.
