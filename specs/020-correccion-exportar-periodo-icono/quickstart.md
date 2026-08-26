# Quickstart: Corrección de Exportación, Cambio de Periodo e Ícono de Edición en Registro Masivo

**Feature**: `020-correccion-exportar-periodo-icono` | **Date**: 2026-08-25

Guía de validación end-to-end. Ver `data-model.md` y `contracts/marcado-corregido.md` para el
detalle técnico. Los tres escenarios de abajo ya se reprodujeron una vez durante la planificación
(`research.md`) — esta guía sirve para reconfirmar tras la implementación.

## Prerrequisitos

- Usuario autenticado.
- **Nota de entorno**: usar el binario de PHP de Herd
  (`C:\Users\joel5\.config\herd\bin\php.bat`) para `artisan`/`pest`.
- Al menos una locación alquilable con una lectura registrada en un periodo (ej. agosto 2026).

## Escenario 1 — Cambiar de periodo actualiza la pantalla (US1, FR-001)

1. Abrir `/lecturas/registro-masivo?periodo=2026-08`, confirmar que hay al menos una locación con
   lectura registrada.
2. Cambiar el selector de periodo al mes siguiente (ej. septiembre 2026) y hacer clic en "Cambiar
   Periodo".
3. **Resultado esperado**: la URL de la pestaña cambia a `?periodo=2026-09`, la pantalla se
   recarga, y la locación del paso 1 muestra su lectura de agosto como "Lectura Periodo Anterior"
   de septiembre — no sigue mostrando el estado de agosto como si fuera el periodo actual.

## Escenario 2 — Exportar a Excel y PDF descargan el archivo (US2, FR-002/FR-003)

1. En la misma pantalla, con datos visibles, hacer clic en "Exportar a Excel".
2. **Resultado esperado**: el navegador descarga un archivo `.xlsx`; la pantalla permanece en
   `/lecturas/registro-masivo` (no navega a la URL de exportación, no se congela).
3. Repetir con "Exportar a PDF".
4. **Resultado esperado**: el navegador descarga un archivo `.pdf`, mismo comportamiento de
   permanencia en pantalla.

## Escenario 3 — Ícono informativo separado del botón de editar, sin tooltip atascado (US3, FR-004/FR-005/FR-006)

1. Ver una fila con una lectura ya completada.
2. **Resultado esperado**: se ven dos controles distintos junto al valor — un ícono verde (sin
   comportamiento de clic) y un botón de editar (ícono de lápiz) con su propio tooltip al pasar el
   cursor.
3. Pasar el cursor sobre el ícono verde.
4. **Resultado esperado**: muestra un tooltip informativo ("Lectura completada") sin iniciar
   ninguna acción al hacer clic sobre él.
5. Hacer clic en el botón de editar.
6. **Resultado esperado**: la celda cambia a modo edición (campo + guardar/cancelar) y ningún
   tooltip queda visible en pantalla después del clic.

## Regresión (specs/015-019, no debe romperse)

- El contenido y formato de los archivos exportados no cambia (specs/015 FR-016).
- Guardar/cancelar la edición en línea sigue funcionando igual (specs/015 FR-005/FR-017).
- El cálculo de "lectura anterior" (specs/016) y el total editable/persistido (specs/019) no
  cambian — esta corrección solo afecta el disparo del envío del formulario, no la consulta.
